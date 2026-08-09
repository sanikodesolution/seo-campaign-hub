<?php
/**
 * Full WordPress site backup (DB + wp-content) → Google Drive.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FullSiteBackupService
 */
class FullSiteBackupService {

	public const OPTION_JOB = 'seo_campaign_hub_full_backup_job';

	private const TABLES_PER_TICK = 3;

	private const FILES_PER_TICK = 80;

	private const UPLOAD_CHUNK = 1048576; // 1MB (multiple of 256KB).

	private GoogleDriveService $drive;

	/**
	 * @param GoogleDriveService $drive Drive client.
	 */
	public function __construct( GoogleDriveService $drive ) {
		$this->drive = $drive;
	}

	/**
	 * Start a new full-site backup job.
	 *
	 * @param string $destination drive|download.
	 * @return array{success:bool,message:string,job?:array<string,mixed>}
	 */
	public function start_job( string $destination = 'drive' ): array {
		$destination = ( 'download' === $destination ) ? 'download' : 'drive';

		if ( 'drive' === $destination && ! $this->drive->is_connected() ) {
			return [ 'success' => false, 'message' => __( 'Connect Google Drive first.', 'seo-campaign-hub' ) ];
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			return [ 'success' => false, 'message' => __( 'PHP ZipArchive is required for full site backups.', 'seo-campaign-hub' ) ];
		}

		$existing = $this->get_job();
		if ( is_array( $existing ) && ( $existing['status'] ?? '' ) === 'running' ) {
			return [
				'success' => true,
				'message' => __( 'Backup already in progress.', 'seo-campaign-hub' ),
				'job'     => $this->public_job( $existing ),
			];
		}

		// Clear previous download zip if any.
		if ( is_array( $existing ) && ! empty( $existing['zip_path'] ) && is_string( $existing['zip_path'] ) && file_exists( $existing['zip_path'] ) ) {
			if ( ( $existing['destination'] ?? '' ) === 'download' || ( $existing['status'] ?? '' ) !== 'running' ) {
				wp_delete_file( $existing['zip_path'] );
			}
		}

		$base = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/temp/';
		if ( ! file_exists( $base ) ) {
			wp_mkdir_p( $base );
		}
		$this->ensure_temp_htaccess( $base );

		$stamp    = gmdate( 'Y-m-d-His' );
		$work_dir = $base . 'full-' . $stamp . '-' . wp_generate_password( 6, false ) . '/';
		if ( ! wp_mkdir_p( $work_dir ) ) {
			return [ 'success' => false, 'message' => __( 'Could not create temporary backup folder.', 'seo-campaign-hub' ) ];
		}

		global $wpdb;
		$like   = $wpdb->esc_like( $wpdb->prefix ) . '%';
		$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
		if ( ! is_array( $tables ) || $tables === [] ) {
			return [ 'success' => false, 'message' => __( 'No database tables found to back up.', 'seo-campaign-hub' ) ];
		}

		$job = [
			'id'             => wp_generate_password( 12, false ),
			'destination'    => $destination,
			'status'         => 'running',
			'phase'          => 'dump',
			'created_at'     => time(),
			'work_dir'       => $work_dir,
			'sql_path'       => $work_dir . 'database.sql',
			'files_manifest' => $work_dir . 'files.json',
			'zip_path'       => $base . 'seo-campaign-hub-full-' . $stamp . '.zip',
			'zip_name'       => 'seo-campaign-hub-full-' . $stamp . '.zip',
			'tables'         => array_values( $tables ),
			'table_index'    => 0,
			'file_index'     => 0,
			'file_total'     => 0,
			'zip_opened'     => 0,
			'upload_url'     => '',
			'upload_offset'  => 0,
			'upload_size'    => 0,
			'download_token' => '',
			'download_expires' => 0,
			'message'        => __( 'Starting database dump…', 'seo-campaign-hub' ),
			'percent'        => 1,
			'error'          => '',
		];

		// Seed SQL header.
		$header  = "-- SEO Campaign Hub full site backup\n";
		$header .= '-- Generated: ' . gmdate( 'c' ) . "\n";
		$header .= '-- Site: ' . home_url( '/' ) . "\n\n";
		$header .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $job['sql_path'], $header );

		$this->save_job( $job );

		return [
			'success' => true,
			'message' => $job['message'],
			'job'     => $this->public_job( $job ),
		];
	}

	/**
	 * Advance the job by one tick.
	 *
	 * @return array{success:bool,done:bool,message:string,percent:int,job?:array<string,mixed>}
	 */
	public function tick(): array {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$job = $this->get_job();
		if ( ! is_array( $job ) || ( $job['status'] ?? '' ) !== 'running' ) {
			if ( is_array( $job ) && ( $job['status'] ?? '' ) === 'done' ) {
				return [
					'success' => true,
					'done'    => true,
					'message' => (string) ( $job['message'] ?? __( 'Backup complete.', 'seo-campaign-hub' ) ),
					'percent' => 100,
					'job'     => $this->public_job( $job ),
				];
			}
			return [
				'success' => false,
				'done'    => true,
				'message' => is_array( $job ) ? (string) ( $job['error'] ?: $job['message'] ?? __( 'No backup job.', 'seo-campaign-hub' ) ) : __( 'No backup job.', 'seo-campaign-hub' ),
				'percent' => 0,
			];
		}

		try {
			switch ( (string) $job['phase'] ) {
				case 'dump':
					$job = $this->phase_dump( $job );
					break;
				case 'collect':
					$job = $this->phase_collect( $job );
					break;
				case 'zip':
					$job = $this->phase_zip( $job );
					break;
				case 'upload':
					$job = $this->phase_upload( $job );
					break;
				case 'finish_download':
					$job = $this->phase_finish_download( $job );
					break;
				case 'finish':
					$job = $this->phase_finish( $job );
					break;
				default:
					throw new \RuntimeException( __( 'Unknown backup phase.', 'seo-campaign-hub' ) );
			}
		} catch ( \Throwable $e ) {
			$job['status']  = 'error';
			$job['error']   = $e->getMessage();
			$job['message'] = $e->getMessage();
			$this->save_job( $job );
			$this->cleanup_work_dir( (string) ( $job['work_dir'] ?? '' ) );
			return [
				'success' => false,
				'done'    => true,
				'message' => $e->getMessage(),
				'percent' => (int) ( $job['percent'] ?? 0 ),
				'job'     => $this->public_job( $job ),
			];
		}

		$this->save_job( $job );

		$done = ( $job['status'] ?? '' ) !== 'running';
		$public = $this->public_job( $job );
		$out = [
			'success' => ( $job['status'] ?? '' ) !== 'error',
			'done'    => $done,
			'message' => (string) ( $job['message'] ?? '' ),
			'percent' => (int) ( $job['percent'] ?? 0 ),
			'job'     => $public,
		];
		if ( ! empty( $public['download_url'] ) ) {
			$out['download_url'] = $public['download_url'];
		}
		return $out;
	}

	/**
	 * Run full backup synchronously (cron).
	 *
	 * @return array{success:bool,message:string}
	 */
	public function run_blocking(): array {
		$start = $this->start_job();
		if ( empty( $start['success'] ) ) {
			$this->record_status( false, (string) $start['message'] );
			return [ 'success' => false, 'message' => (string) $start['message'] ];
		}

		$guard = 0;
		while ( $guard < 5000 ) {
			$guard++;
			$result = $this->tick();
			if ( ! empty( $result['done'] ) ) {
				$this->record_status( ! empty( $result['success'] ), (string) $result['message'] );
				return [
					'success' => ! empty( $result['success'] ),
					'message' => (string) $result['message'],
				];
			}
		}

		$msg = __( 'Full site backup timed out.', 'seo-campaign-hub' );
		$this->record_status( false, $msg );
		return [ 'success' => false, 'message' => $msg ];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_job(): ?array {
		$job = get_option( self::OPTION_JOB, null );
		return is_array( $job ) ? $job : null;
	}

	/**
	 * @param array<string, mixed> $job Job.
	 * @return void
	 */
	private function save_job( array $job ): void {
		update_option( self::OPTION_JOB, $job, false );
	}

	/**
	 * Strip sensitive paths from job for AJAX.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private function public_job( array $job ): array {
		$out = [
			'id'          => (string) ( $job['id'] ?? '' ),
			'status'      => (string) ( $job['status'] ?? '' ),
			'phase'       => (string) ( $job['phase'] ?? '' ),
			'destination' => (string) ( $job['destination'] ?? 'drive' ),
			'message'     => (string) ( $job['message'] ?? '' ),
			'percent'     => (int) ( $job['percent'] ?? 0 ),
			'error'       => (string) ( $job['error'] ?? '' ),
		];

		if ( ( $job['status'] ?? '' ) === 'done' && ( $job['destination'] ?? '' ) === 'download' && ! empty( $job['download_token'] ) ) {
			$out['download_url'] = wp_nonce_url(
				admin_url( 'admin-post.php?action=sch_full_site_backup_download&token=' . rawurlencode( (string) $job['download_token'] ) ),
				'sch_full_site_backup_download'
			);
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private function phase_dump( array $job ): array {
		global $wpdb;

		$tables = $job['tables'];
		$index  = (int) $job['table_index'];
		$total  = count( $tables );
		$end    = min( $total, $index + self::TABLES_PER_TICK );

		for ( $i = $index; $i < $end; $i++ ) {
			$table = (string) $tables[ $i ];
			$sql   = $this->dump_table( $table );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $job['sql_path'], $sql, FILE_APPEND );
		}

		$job['table_index'] = $end;
		$job['percent']     = 5 + (int) floor( ( $end / max( 1, $total ) ) * 25 );
		$job['message']     = sprintf(
			/* translators: 1: current table count, 2: total tables */
			__( 'Dumping database… %1$d / %2$d tables', 'seo-campaign-hub' ),
			$end,
			$total
		);

		if ( $end >= $total ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $job['sql_path'], "\nSET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND );
			$job['phase']   = 'collect';
			$job['message'] = __( 'Collecting files…', 'seo-campaign-hub' );
			$job['percent'] = 32;
		}

		return $job;
	}

	/**
	 * Dump one table to SQL.
	 *
	 * @param string $table Table name.
	 * @return string
	 */
	private function dump_table( string $table ): string {
		global $wpdb;

		// Table names cannot be prepared as values; whitelist against SHOW TABLES list already.
		$create = $wpdb->get_row( 'SHOW CREATE TABLE `' . str_replace( '`', '``', $table ) . '`', ARRAY_N );
		$out    = "\n\nDROP TABLE IF EXISTS `" . str_replace( '`', '``', $table ) . "`;\n";
		if ( is_array( $create ) && ! empty( $create[1] ) ) {
			$out .= $create[1] . ";\n\n";
		}

		$rows = $wpdb->get_results( 'SELECT * FROM `' . str_replace( '`', '``', $table ) . '`', ARRAY_A );
		if ( ! is_array( $rows ) || $rows === [] ) {
			return $out;
		}

		foreach ( $rows as $row ) {
			$vals = [];
			foreach ( $row as $value ) {
				if ( null === $value ) {
					$vals[] = 'NULL';
				} else {
					$vals[] = "'" . $wpdb->_real_escape( (string) $value ) . "'";
				}
			}
			$out .= 'INSERT INTO `' . str_replace( '`', '``', $table ) . '` VALUES (' . implode( ',', $vals ) . ");\n";
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private function phase_collect( array $job ): array {
		$root  = WP_CONTENT_DIR;
		$files = [];
		$skip  = [
			'/cache/',
			'/upgrade/',
			'/updraft/',
			'/ai1wm-backups/',
			'/backups-dup-lite/',
			'/wpvividbackups/',
			'/wpvivid_uploaded_backup/',
			'/seo-campaign-hub/uploads/temp/',
			'/debug.log',
		];

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			/** @var \SplFileInfo $file */
			if ( ! $file->isFile() ) {
				continue;
			}
			$path = $file->getPathname();
			$norm = str_replace( '\\', '/', $path );
			$skip_it = false;
			foreach ( $skip as $needle ) {
				if ( false !== stripos( $norm, $needle ) ) {
					$skip_it = true;
					break;
				}
			}
			if ( $skip_it ) {
				continue;
			}
			if ( false !== strpos( $norm, '/uploads/temp/full-' ) ) {
				continue;
			}
			$rel = 'wp-content/' . ltrim( str_replace( '\\', '/', substr( $path, strlen( $root ) ) ), '/' );
			$files[] = [
				'abs' => $path,
				'rel' => $rel,
			];
		}

		$manifest = (string) $job['files_manifest'];
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $manifest, wp_json_encode( $files ) );

		$job['file_index'] = 0;
		$job['file_total'] = count( $files );
		$job['phase']      = 'zip';
		$job['message']    = sprintf(
			/* translators: %d: file count */
			__( 'Zipping %d files…', 'seo-campaign-hub' ),
			count( $files )
		);
		$job['percent'] = 35;
		return $job;
	}

	/**
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private function phase_zip( array $job ): array {
		$manifest = (string) $job['files_manifest'];
		$raw      = is_readable( $manifest ) ? file_get_contents( $manifest ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$files    = is_string( $raw ) ? json_decode( $raw, true ) : [];
		if ( ! is_array( $files ) ) {
			$files = [];
		}

		$zip = new \ZipArchive();
		$flags = ! empty( $job['zip_opened'] ) ? 0 : ( \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
		if ( true !== $zip->open( (string) $job['zip_path'], $flags ) ) {
			throw new \RuntimeException( __( 'Could not create ZIP archive.', 'seo-campaign-hub' ) );
		}

		if ( empty( $job['zip_opened'] ) ) {
			$zip->addFile( (string) $job['sql_path'], 'database.sql' );
			$job['zip_opened'] = 1;
		}

		$index = (int) $job['file_index'];
		$total = (int) ( $job['file_total'] ?: count( $files ) );
		$end   = min( $total, $index + self::FILES_PER_TICK );

		for ( $i = $index; $i < $end; $i++ ) {
			$item = $files[ $i ] ?? null;
			if ( ! is_array( $item ) || empty( $item['abs'] ) || empty( $item['rel'] ) || ! is_readable( $item['abs'] ) ) {
				continue;
			}
			$zip->addFile( (string) $item['abs'], (string) $item['rel'] );
		}

		$zip->close();

		$job['file_index'] = $end;
		$job['percent']    = 35 + (int) floor( ( $end / max( 1, $total ) ) * 40 );
		$job['message']    = sprintf(
			/* translators: 1: current files, 2: total files */
			__( 'Zipping files… %1$d / %2$d', 'seo-campaign-hub' ),
			$end,
			$total
		);

		if ( $end >= $total ) {
			if ( ( $job['destination'] ?? 'drive' ) === 'download' ) {
				$job['phase']   = 'finish_download';
				$job['message'] = __( 'Preparing download…', 'seo-campaign-hub' );
				$job['percent'] = 95;
			} else {
				$job['phase']         = 'upload';
				$job['upload_offset'] = 0;
				$job['upload_size']   = (int) filesize( (string) $job['zip_path'] );
				$job['message']       = __( 'Uploading to Google Drive…', 'seo-campaign-hub' );
				$job['percent']       = 78;
			}
		}

		return $job;
	}

	/**
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private function phase_upload( array $job ): array {
		$folder = $this->drive->get_full_backup_folder_id();
		if ( ! $folder ) {
			throw new \RuntimeException( __( 'Could not create full backup folder on Google Drive.', 'seo-campaign-hub' ) );
		}

		$path  = (string) $job['zip_path'];
		$total = (int) $job['upload_size'];
		if ( $total <= 0 || ! is_readable( $path ) ) {
			throw new \RuntimeException( __( 'ZIP file missing for upload.', 'seo-campaign-hub' ) );
		}

		if ( empty( $job['upload_url'] ) ) {
			$start = $this->drive->start_resumable_upload(
				(string) $job['zip_name'],
				$folder,
				'application/zip',
				$total
			);
			if ( empty( $start['success'] ) || empty( $start['session_url'] ) ) {
				throw new \RuntimeException( (string) ( $start['message'] ?? __( 'Upload failed.', 'seo-campaign-hub' ) ) );
			}
			$job['upload_url'] = (string) $start['session_url'];
		}

		$offset = (int) $job['upload_offset'];
		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			throw new \RuntimeException( __( 'Could not open ZIP for upload.', 'seo-campaign-hub' ) );
		}
		if ( $offset > 0 ) {
			fseek( $handle, $offset );
		}
		$chunk = fread( $handle, self::UPLOAD_CHUNK ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( false === $chunk || $chunk === '' ) {
			throw new \RuntimeException( __( 'Could not read ZIP chunk.', 'seo-campaign-hub' ) );
		}

		$result = $this->drive->upload_resumable_chunk( (string) $job['upload_url'], $chunk, $offset, $total );
		if ( empty( $result['success'] ) ) {
			throw new \RuntimeException( (string) ( $result['message'] ?? __( 'Upload failed.', 'seo-campaign-hub' ) ) );
		}

		if ( ! empty( $result['done'] ) ) {
			$job['phase']   = 'finish';
			$job['message'] = __( 'Upload complete. Finishing…', 'seo-campaign-hub' );
			$job['percent'] = 95;
			return $job;
		}

		$job['upload_offset'] = (int) ( $result['next_offset'] ?? ( $offset + strlen( $chunk ) ) );
		$job['percent']       = 78 + (int) floor( ( $job['upload_offset'] / max( 1, $total ) ) * 17 );
		$job['message']       = sprintf(
			/* translators: 1: uploaded MB, 2: total MB */
			__( 'Uploading… %1$s / %2$s MB', 'seo-campaign-hub' ),
			number_format_i18n( $job['upload_offset'] / 1048576, 1 ),
			number_format_i18n( $total / 1048576, 1 )
		);

		return $job;
	}

	/**
	 * Finish local download backup (keep ZIP for download).
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private function phase_finish_download( array $job ): array {
		$this->cleanup_work_dir( (string) ( $job['work_dir'] ?? '' ) );

		if ( empty( $job['zip_path'] ) || ! file_exists( (string) $job['zip_path'] ) ) {
			throw new \RuntimeException( __( 'ZIP file missing for download.', 'seo-campaign-hub' ) );
		}

		$token = wp_generate_password( 32, false );
		$job['download_token']   = $token;
		$job['download_expires'] = time() + HOUR_IN_SECONDS;
		$job['status']           = 'done';
		$job['phase']            = 'done';
		$job['percent']          = 100;
		$job['message']          = __( 'Backup ready. Starting download…', 'seo-campaign-hub' );
		$this->record_status( true, __( 'Full site backup ZIP ready to download.', 'seo-campaign-hub' ) );

		return $job;
	}

	/**
	 * Stream ZIP to the browser for an admin with a valid token.
	 *
	 * @param string $token Download token.
	 * @return void
	 */
	public function serve_download( string $token ): void {
		$job = $this->get_job();
		if ( ! is_array( $job ) || ( $job['destination'] ?? '' ) !== 'download' ) {
			wp_die( esc_html__( 'No downloadable backup found.', 'seo-campaign-hub' ), 404 );
		}
		if ( empty( $job['download_token'] ) || ! hash_equals( (string) $job['download_token'], $token ) ) {
			wp_die( esc_html__( 'Invalid download token.', 'seo-campaign-hub' ), 403 );
		}
		if ( time() > (int) ( $job['download_expires'] ?? 0 ) ) {
			wp_die( esc_html__( 'Download link expired. Run backup again.', 'seo-campaign-hub' ), 410 );
		}

		$path = (string) ( $job['zip_path'] ?? '' );
		$name = (string) ( $job['zip_name'] ?? 'seo-campaign-hub-full.zip' );
		if ( $path === '' || ! is_readable( $path ) ) {
			wp_die( esc_html__( 'Backup file missing.', 'seo-campaign-hub' ), 404 );
		}

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $name ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );

		// One-time style cleanup after send.
		wp_delete_file( $path );
		$job['download_token'] = '';
		$job['zip_path']       = '';
		$job['message']        = __( 'Backup downloaded.', 'seo-campaign-hub' );
		$this->save_job( $job );
		exit;
	}

	/**
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private function phase_finish( array $job ): array {
		$folder = $this->drive->get_full_backup_folder_id();
		$settings = $this->drive->get_settings();
		$keep = (int) ( $settings['retention'] ?? 5 );
		if ( $folder && $keep > 0 ) {
			$this->drive->apply_retention( $folder, $keep );
		}

		$this->cleanup_work_dir( (string) ( $job['work_dir'] ?? '' ) );
		if ( ! empty( $job['zip_path'] ) && file_exists( (string) $job['zip_path'] ) ) {
			wp_delete_file( (string) $job['zip_path'] );
		}

		$msg = __( 'Full site backup uploaded to Google Drive.', 'seo-campaign-hub' );
		$job['status']  = 'done';
		$job['phase']   = 'done';
		$job['message'] = $msg;
		$job['percent'] = 100;
		$this->record_status( true, $msg );

		return $job;
	}

	/**
	 * @param string $dir Work directory.
	 * @return void
	 */
	private function cleanup_work_dir( string $dir ): void {
		if ( $dir === '' || ! is_dir( $dir ) ) {
			return;
		}
		$files = glob( trailingslashit( $dir ) . '*' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		@rmdir( $dir );
	}

	/**
	 * @param string $dir Temp base.
	 * @return void
	 */
	private function ensure_temp_htaccess( string $dir ): void {
		$path = trailingslashit( $dir ) . '.htaccess';
		if ( file_exists( $path ) ) {
			return;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $path, "Deny from all\n" );
	}

	/**
	 * @param bool   $success Success.
	 * @param string $message Message.
	 * @return void
	 */
	private function record_status( bool $success, string $message ): void {
		$this->drive->update_settings(
			[
				'last_backup_time'    => current_time( 'mysql' ),
				'last_backup_success' => $success ? '1' : '0',
				'last_backup_message' => $message,
			]
		);
	}
}

<?php
/**
 * Cloud backup orchestration (plugin JSON → Google Drive).
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CloudBackupService
 */
class CloudBackupService {

	private GoogleDriveService $drive;
	private ImportExportService $import_export;

	/**
	 * @param GoogleDriveService   $drive          Drive client.
	 * @param ImportExportService  $import_export  Export service.
	 */
	public function __construct( GoogleDriveService $drive, ImportExportService $import_export ) {
		$this->drive           = $drive;
		$this->import_export   = $import_export;
	}

	/**
	 * Register cron hook.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'seo_campaign_hub_cloud_backup_cron', [ $this, 'run_scheduled_backup' ] );
	}

	/**
	 * Run plugin backup and upload to Google Drive.
	 *
	 * @return array{success:bool,message:string}
	 */
	public function run_plugin_backup(): array {
		if ( ! $this->drive->is_connected() ) {
			$this->record_status( false, __( 'Connect Google Drive first.', 'seo-campaign-hub' ) );
			return [ 'success' => false, 'message' => __( 'Connect Google Drive first.', 'seo-campaign-hub' ) ];
		}

		$folder_id = $this->drive->get_plugin_backup_folder_id();
		if ( ! $folder_id ) {
			$this->record_status( false, __( 'Could not create backup folder on Google Drive.', 'seo-campaign-hub' ) );
			return [ 'success' => false, 'message' => __( 'Could not create backup folder on Google Drive.', 'seo-campaign-hub' ) ];
		}

		$json = $this->import_export->export_data( 'all', [] );
		if ( '' === $json ) {
			$this->record_status( false, __( 'Export produced empty data.', 'seo-campaign-hub' ) );
			return [ 'success' => false, 'message' => __( 'Export produced empty data.', 'seo-campaign-hub' ) ];
		}

		$dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/temp/';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$filename = 'seo-campaign-hub-plugin-backup-' . gmdate( 'Y-m-d-His' ) . '.json';
		$path     = $dir . $filename;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $path, $json ) ) {
			$this->record_status( false, __( 'Could not write temporary backup file.', 'seo-campaign-hub' ) );
			return [ 'success' => false, 'message' => __( 'Could not write temporary backup file.', 'seo-campaign-hub' ) ];
		}

		$upload = $this->drive->upload_file( $path, $filename, $folder_id );
		wp_delete_file( $path );

		if ( ! $upload['success'] ) {
			$this->record_status( false, $upload['message'] );
			return $upload;
		}

		$settings = $this->drive->get_settings();
		$keep     = (int) ( $settings['retention'] ?? 5 );
		if ( $keep > 0 ) {
			$this->drive->apply_retention( $folder_id, $keep );
		}

		$msg = __( 'Plugin backup uploaded to Google Drive.', 'seo-campaign-hub' );
		$this->record_status( true, $msg );

		return [ 'success' => true, 'message' => $msg ];
	}

	/**
	 * Cron entry point.
	 *
	 * @return void
	 */
	public function run_scheduled_backup(): void {
		$settings = $this->drive->get_settings();
		if ( empty( $settings['schedule_enabled'] ) || '1' !== (string) $settings['schedule_enabled'] ) {
			return;
		}
		$this->run_plugin_backup();
	}

	/**
	 * @param bool   $success Success flag.
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

	/**
	 * Last backup summary for admin UI.
	 *
	 * @return array<string, string>
	 */
	public function get_last_status(): array {
		$s = $this->drive->get_settings();
		return [
			'time'    => (string) ( $s['last_backup_time'] ?? '' ),
			'success' => (string) ( $s['last_backup_success'] ?? '' ),
			'message' => (string) ( $s['last_backup_message'] ?? '' ),
		];
	}
}

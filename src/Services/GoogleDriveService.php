<?php
/**
 * Google Drive API (OAuth + file upload/list/delete).
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GoogleDriveService
 */
class GoogleDriveService {

	private const OPTION_SETTINGS = 'seo_campaign_hub_cloud_backup';
	private const OPTION_TOKENS   = 'seo_campaign_hub_google_drive_tokens';
	private const OAUTH_SCOPE     = 'https://www.googleapis.com/auth/drive.file';

	/**
	 * Cloud backup settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {
		$settings = get_option( self::OPTION_SETTINGS, [] );
		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Update settings (merge).
	 *
	 * @param array<string, mixed> $data Settings patch.
	 * @return void
	 */
	public function update_settings( array $data ): void {
		$current = $this->get_settings();
		update_option( self::OPTION_SETTINGS, array_merge( $current, $data ) );
	}

	/**
	 * OAuth redirect URI.
	 *
	 * @return string
	 */
	public function get_redirect_uri(): string {
		return admin_url( 'admin.php?page=seo-campaign-hub-cloud-backup' );
	}

	/**
	 * Whether Google API credentials are configured.
	 *
	 * @return bool
	 */
	public function has_client_credentials(): bool {
		$s = $this->get_settings();
		return ! empty( $s['google_client_id'] ) && ! empty( $s['google_client_secret'] );
	}

	/**
	 * @return bool
	 */
	public function is_connected(): bool {
		$t = $this->get_tokens();
		return ! empty( $t['refresh_token'] ) || ! empty( $t['access_token'] );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_tokens(): array {
		$t = get_option( self::OPTION_TOKENS, [] );
		return is_array( $t ) ? $t : [];
	}

	/**
	 * @param array<string, mixed> $tokens Token payload.
	 * @return void
	 */
	public function save_tokens( array $tokens ): void {
		update_option( self::OPTION_TOKENS, $tokens );
	}

	/**
	 * Disconnect Google account.
	 *
	 * @return void
	 */
	public function disconnect(): void {
		delete_option( self::OPTION_TOKENS );
	}

	/**
	 * Build Google authorization URL.
	 *
	 * @return string
	 */
	public function get_auth_url(): string {
		$s       = $this->get_settings();
		$state   = wp_generate_password( 32, false );
		set_transient( 'sch_google_oauth_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS );

		$params = [
			'client_id'     => (string) $s['google_client_id'],
			'redirect_uri'  => $this->get_redirect_uri(),
			'response_type' => 'code',
			'scope'         => self::OAUTH_SCOPE,
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => $state,
		];

		return add_query_arg( $params, 'https://accounts.google.com/o/oauth2/v2/auth' );
	}

	/**
	 * Handle OAuth callback query params.
	 *
	 * @return array{success:bool,message:string}
	 */
	public function handle_oauth_callback(): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'seo-campaign-hub' ) ];
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

		if ( '' === $code ) {
			return [ 'success' => false, 'message' => __( 'Missing authorization code.', 'seo-campaign-hub' ) ];
		}

		$expected = get_transient( 'sch_google_oauth_state_' . get_current_user_id() );
		delete_transient( 'sch_google_oauth_state_' . get_current_user_id() );

		if ( ! $expected || $state !== $expected ) {
			return [ 'success' => false, 'message' => __( 'Invalid OAuth state. Try connecting again.', 'seo-campaign-hub' ) ];
		}

		if ( ! $this->has_client_credentials() ) {
			return [ 'success' => false, 'message' => __( 'Google Client ID and Secret are required.', 'seo-campaign-hub' ) ];
		}

		$s = $this->get_settings();
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			[
				'timeout' => 30,
				'body'    => [
					'code'          => $code,
					'client_id'     => (string) $s['google_client_id'],
					'client_secret' => (string) $s['google_client_secret'],
					'redirect_uri'  => $this->get_redirect_uri(),
					'grant_type'    => 'authorization_code',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return [ 'success' => false, 'message' => $response->get_error_message() ];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['access_token'] ) ) {
			$err = is_array( $body ) && isset( $body['error_description'] ) ? (string) $body['error_description'] : __( 'Token exchange failed.', 'seo-campaign-hub' );
			return [ 'success' => false, 'message' => $err ];
		}

		$tokens = $this->get_tokens();
		$tokens['access_token']  = (string) $body['access_token'];
		$tokens['expires_at']    = time() + (int) ( $body['expires_in'] ?? 3600 );
		if ( ! empty( $body['refresh_token'] ) ) {
			$tokens['refresh_token'] = (string) $body['refresh_token'];
		}

		$user_info = $this->api_request( 'GET', 'https://www.googleapis.com/drive/v3/about?fields=user', $tokens['access_token'] );
		if ( is_array( $user_info ) && isset( $user_info['user']['emailAddress'] ) ) {
			$tokens['email'] = (string) $user_info['user']['emailAddress'];
		}

		$this->save_tokens( $tokens );

		return [ 'success' => true, 'message' => __( 'Google Drive connected.', 'seo-campaign-hub' ) ];
	}

	/**
	 * @return string|null
	 */
	public function get_valid_access_token(): ?string {
		$tokens = $this->get_tokens();
		if ( empty( $tokens['access_token'] ) ) {
			return null;
		}

		$expires = (int) ( $tokens['expires_at'] ?? 0 );
		if ( $expires > time() + 60 ) {
			return (string) $tokens['access_token'];
		}

		if ( empty( $tokens['refresh_token'] ) ) {
			return null;
		}

		$s = $this->get_settings();
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			[
				'timeout' => 30,
				'body'    => [
					'client_id'     => (string) $s['google_client_id'],
					'client_secret' => (string) $s['google_client_secret'],
					'refresh_token' => (string) $tokens['refresh_token'],
					'grant_type'    => 'refresh_token',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['access_token'] ) ) {
			return null;
		}

		$tokens['access_token'] = (string) $body['access_token'];
		$tokens['expires_at']    = time() + (int) ( $body['expires_in'] ?? 3600 );
		$this->save_tokens( $tokens );

		return $tokens['access_token'];
	}

	/**
	 * Resolve folder IDs for plugin backups (parent / site / plugin).
	 *
	 * @return string|null Plugin folder ID.
	 */
	public function get_plugin_backup_folder_id(): ?string {
		$access = $this->get_valid_access_token();
		if ( ! $access ) {
			return null;
		}

		$settings = $this->get_settings();
		$parent_name = $this->sanitize_folder_name( (string) ( $settings['parent_folder'] ?? 'seo-campaign-hub-backups' ) );
		$site_name   = $this->sanitize_folder_name( (string) ( $settings['subfolder'] ?? $this->default_site_folder_name() ) );

		$tokens = $this->get_tokens();
		$cached = isset( $tokens['folder_ids'] ) && is_array( $tokens['folder_ids'] ) ? $tokens['folder_ids'] : [];

		$parent_id = $cached['parent'] ?? null;
		if ( $parent_id && ! $this->folder_exists( $access, $parent_id ) ) {
			$parent_id = null;
		}
		if ( ! $parent_id ) {
			$parent_id = $this->find_or_create_folder( $access, $parent_name, null );
		}

		$site_id = $cached['site'] ?? null;
		if ( $site_id && ! $this->folder_exists( $access, $site_id ) ) {
			$site_id = null;
		}
		if ( ! $parent_id ) {
			return null;
		}
		if ( ! $site_id ) {
			$site_id = $this->find_or_create_folder( $access, $site_name, $parent_id );
		}

		$plugin_id = $cached['plugin'] ?? null;
		if ( $plugin_id && ! $this->folder_exists( $access, $plugin_id ) ) {
			$plugin_id = null;
		}
		if ( ! $site_id ) {
			return null;
		}
		if ( ! $plugin_id ) {
			$plugin_id = $this->find_or_create_folder( $access, 'plugin', $site_id );
		}

		$tokens['folder_ids'] = [
			'parent' => $parent_id,
			'site'   => $site_id,
			'plugin' => $plugin_id,
		];
		$this->save_tokens( $tokens );

		return $plugin_id ?: null;
	}

	/**
	 * Upload a local file to a Drive folder.
	 *
	 * @param string $local_path   Absolute path.
	 * @param string $drive_name   File name on Drive.
	 * @param string $parent_id    Folder ID.
	 * @return array{success:bool,message:string,file_id?:string}
	 */
	public function upload_file( string $local_path, string $drive_name, string $parent_id ): array {
		$access = $this->get_valid_access_token();
		if ( ! $access ) {
			return [ 'success' => false, 'message' => __( 'Google Drive is not connected.', 'seo-campaign-hub' ) ];
		}

		if ( ! is_readable( $local_path ) ) {
			return [ 'success' => false, 'message' => __( 'Backup file is not readable.', 'seo-campaign-hub' ) ];
		}

		$content = file_get_contents( $local_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $content ) {
			return [ 'success' => false, 'message' => __( 'Could not read backup file.', 'seo-campaign-hub' ) ];
		}

		$metadata = wp_json_encode(
			[
				'name'    => $drive_name,
				'parents' => [ $parent_id ],
			]
		);

		$boundary = wp_generate_password( 24, false );
		$body     = "--{$boundary}\r\n";
		$body    .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
		$body    .= $metadata . "\r\n";
		$body    .= "--{$boundary}\r\n";
		$body    .= 'Content-Type: application/json; charset=UTF-8\r\n\r\n';
		$body    .= $content . "\r\n";
		$body    .= "--{$boundary}--";

		$response = wp_remote_post(
			'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart',
			[
				'timeout' => 120,
				'headers' => [
					'Authorization' => 'Bearer ' . $access,
					'Content-Type'  => 'multipart/related; boundary=' . $boundary,
				],
				'body'    => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [ 'success' => false, 'message' => $response->get_error_message() ];
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) || empty( $decoded['id'] ) ) {
			return [ 'success' => false, 'message' => __( 'Google Drive upload failed.', 'seo-campaign-hub' ) ];
		}

		return [
			'success' => true,
			'message' => __( 'Uploaded to Google Drive.', 'seo-campaign-hub' ),
			'file_id' => (string) $decoded['id'],
		];
	}

	/**
	 * List files in folder (plugin backups).
	 *
	 * @param string $folder_id Folder ID.
	 * @return array<int, array{id:string,name:string,createdTime:string}>
	 */
	public function list_files_in_folder( string $folder_id ): array {
		$access = $this->get_valid_access_token();
		if ( ! $access ) {
			return [];
		}

		$q = sprintf( "'%s' in parents and trashed = false", $folder_id );
		$url = add_query_arg(
			[
				'q'       => $q,
				'fields'  => 'files(id,name,createdTime)',
				'orderBy' => 'createdTime',
			],
			'https://www.googleapis.com/drive/v3/files'
		);

		$result = $this->api_request( 'GET', $url, $access );
		if ( ! is_array( $result ) || empty( $result['files'] ) ) {
			return [];
		}

		$out = [];
		foreach ( $result['files'] as $file ) {
			if ( ! is_array( $file ) || empty( $file['id'] ) ) {
				continue;
			}
			$out[] = [
				'id'          => (string) $file['id'],
				'name'        => (string) ( $file['name'] ?? '' ),
				'createdTime' => (string) ( $file['createdTime'] ?? '' ),
			];
		}

		return $out;
	}

	/**
	 * Delete a Drive file by ID.
	 *
	 * @param string $file_id File ID.
	 * @return bool
	 */
	public function delete_file( string $file_id ): bool {
		$access = $this->get_valid_access_token();
		if ( ! $access ) {
			return false;
		}

		$response = wp_remote_request(
			'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $file_id ),
			[
				'method'  => 'DELETE',
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'Bearer ' . $access,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code === 200 || $code === 204;
	}

	/**
	 * Apply retention — keep newest N files.
	 *
	 * @param string $folder_id Folder ID.
	 * @param int    $keep      Max files to keep.
	 * @return void
	 */
	public function apply_retention( string $folder_id, int $keep ): void {
		if ( $keep <= 0 ) {
			return;
		}

		$files = $this->list_files_in_folder( $folder_id );
		if ( count( $files ) <= $keep ) {
			return;
		}

		usort(
			$files,
			static function ( $a, $b ) {
				return strcmp( $a['createdTime'], $b['createdTime'] );
			}
		);

		$to_delete = count( $files ) - $keep;
		for ( $i = 0; $i < $to_delete; $i++ ) {
			$this->delete_file( $files[ $i ]['id'] );
		}
	}

	/**
	 * Default site subfolder from domain.
	 *
	 * @return string
	 */
	public function default_site_folder_name(): string {
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$host = is_string( $host ) ? $host : 'site';
		return $this->sanitize_folder_name( str_replace( '.', '_', $host ) );
	}

	/**
	 * @param string $name Folder name.
	 * @return string
	 */
	private function sanitize_folder_name( string $name ): string {
		$name = preg_replace( '/[^a-zA-Z0-9_-]/', '_', $name ) ?? 'backup';
		return substr( $name, 0, 100 );
	}

	/**
	 * @param string      $access     Access token.
	 * @param string      $name       Folder name.
	 * @param string|null $parent_id  Parent folder ID.
	 * @return string|null
	 */
	private function find_or_create_folder( string $access, string $name, ?string $parent_id ): ?string {
		$q = sprintf( "mimeType='application/vnd.google-apps.folder' and name='%s' and trashed=false", str_replace( "'", "\\'", $name ) );
		if ( $parent_id ) {
			$q .= sprintf( " and '%s' in parents", $parent_id );
		} else {
			$q .= " and 'root' in parents";
		}

		$url = add_query_arg(
			[
				'q'     => $q,
				'fields' => 'files(id)',
			],
			'https://www.googleapis.com/drive/v3/files'
		);

		$result = $this->api_request( 'GET', $url, $access );
		if ( is_array( $result ) && ! empty( $result['files'][0]['id'] ) ) {
			return (string) $result['files'][0]['id'];
		}

		$payload = [
			'name'     => $name,
			'mimeType' => 'application/vnd.google-apps.folder',
		];
		if ( $parent_id ) {
			$payload['parents'] = [ $parent_id ];
		}

		$created = $this->api_request(
			'POST',
			'https://www.googleapis.com/drive/v3/files',
			$access,
			wp_json_encode( $payload )
		);

		if ( is_array( $created ) && ! empty( $created['id'] ) ) {
			return (string) $created['id'];
		}

		return null;
	}

	/**
	 * @param string $access   Token.
	 * @param string $folder_id Folder.
	 * @return bool
	 */
	private function folder_exists( string $access, string $folder_id ): bool {
		$url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $folder_id ) . '?fields=id,trashed';
		$result = $this->api_request( 'GET', $url, $access );
		return is_array( $result ) && ! empty( $result['id'] ) && empty( $result['trashed'] );
	}

	/**
	 * @param string       $method  HTTP method.
	 * @param string       $url     URL.
	 * @param string       $access  Token.
	 * @param string|null  $body    JSON body.
	 * @return array<string, mixed>|null
	 */
	private function api_request( string $method, string $url, string $access, ?string $body = null ): ?array {
		$args = [
			'method'  => $method,
			'timeout' => 60,
			'headers' => [
				'Authorization' => 'Bearer ' . $access,
			],
		];

		if ( $body !== null ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body'] = $body;
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $decoded ) ? $decoded : null;
	}
}

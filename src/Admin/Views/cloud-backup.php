<?php
/**
 * Cloud Backup admin view (WPvivid-style).
 *
 * @var string                              $page_title Page title.
 * @var \SEO_Campaign_Hub\Services\GoogleDriveService|null $drive      Drive service.
 * @var \SEO_Campaign_Hub\Services\CloudBackupService|null $cloud_backup Cloud backup.
 * @var string                              $notice     Notice key.
 * @var string                              $message    Notice message.
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = $drive ? $drive->get_settings() : [];
$tokens   = $drive ? $drive->get_tokens() : [];
$status   = $cloud_backup ? $cloud_backup->get_last_status() : [ 'time' => '', 'success' => '', 'message' => '' ];

$redirect_uri = $drive ? $drive->get_redirect_uri() : '';
$connected    = $drive && $drive->is_connected();
$has_creds    = $drive && $drive->has_client_credentials();

$notices = [
	'saved'       => [ 'success', __( 'Settings saved.', 'seo-campaign-hub' ) ],
	'backup_ok'   => [ 'success', $message ?: __( 'Backup completed.', 'seo-campaign-hub' ) ],
	'backup_fail' => [ 'error', $message ?: __( 'Backup failed.', 'seo-campaign-hub' ) ],
	'disconnected'=> [ 'success', __( 'Google Drive disconnected.', 'seo-campaign-hub' ) ],
	'oauth_ok'    => [ 'success', $message ?: __( 'Google Drive connected.', 'seo-campaign-hub' ) ],
	'oauth_fail'  => [ 'error', $message ?: __( 'Google authorization failed.', 'seo-campaign-hub' ) ],
];
$notice_key = isset( $notice ) ? (string) $notice : '';
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Cloud Backup', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( $notice_key && isset( $notices[ $notice_key ] ) ) : ?>
		<div class="seo-campaign-hub-notice <?php echo esc_attr( $notices[ $notice_key ][0] ); ?>">
			<?php echo esc_html( $notices[ $notice_key ][1] ); ?>
		</div>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Phase 1: plugin data (campaigns, offers, short links, settings). Full WordPress site backup will be added in a later update.', 'seo-campaign-hub' ); ?>
	</p>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">

			<section class="sch-ie-panel" style="margin-bottom:24px">
				<h2><?php esc_html_e( 'Cloud Storage — Google Drive', 'seo-campaign-hub' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Keep both options: save your Client Secret (password), then use Sync to connect Google Drive in a browser popup. The parent Cloud Backup page stays open.', 'seo-campaign-hub' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_cloud_backup_settings">
					<?php wp_nonce_field( 'sch_cloud_backup_settings' ); ?>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="sch-google-client-id"><?php esc_html_e( 'Google Client ID', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="text" class="regular-text" id="sch-google-client-id" name="google_client_id"
									value="<?php echo esc_attr( (string) ( $settings['google_client_id'] ?? '' ) ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-google-client-secret"><?php esc_html_e( 'Google Client Secret', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="password" class="regular-text" id="sch-google-client-secret" name="google_client_secret"
									value="<?php echo esc_attr( (string) ( $settings['google_client_secret'] ?? '' ) ); ?>" autocomplete="off" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Authorized redirect URI', 'seo-campaign-hub' ); ?></th>
							<td>
								<code><?php echo esc_html( $redirect_uri ); ?></code>
								<p class="description"><?php esc_html_e( 'Add this exact URL in Google Cloud Console → OAuth client → Authorized redirect URIs.', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-parent-folder"><?php esc_html_e( 'Parent folder on Drive', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="text" class="regular-text" id="sch-parent-folder" name="parent_folder"
									value="<?php echo esc_attr( (string) ( $settings['parent_folder'] ?? 'seo-campaign-hub-backups' ) ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-subfolder"><?php esc_html_e( 'Site subfolder', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="text" class="regular-text" id="sch-subfolder" name="subfolder"
									value="<?php echo esc_attr( (string) ( $settings['subfolder'] ?? ( $drive ? $drive->default_site_folder_name() : '' ) ) ); ?>" />
								<p class="description"><?php esc_html_e( 'Unique per website when one Google account backs up multiple sites.', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-retention"><?php esc_html_e( 'Backup retention on Drive', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="number" class="small-text" id="sch-retention" name="retention" min="1" max="50"
									value="<?php echo esc_attr( (string) ( $settings['retention'] ?? '5' ) ); ?>" />
								<p class="description"><?php esc_html_e( 'How many plugin backups to keep; older files are deleted automatically.', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save cloud settings', 'seo-campaign-hub' ), 'secondary', 'submit', false ); ?>
				</form>

				<p style="margin-top:16px">
					<?php if ( $connected ) : ?>
						<strong><?php esc_html_e( 'Synced:', 'seo-campaign-hub' ); ?></strong>
						<?php echo esc_html( (string) ( $tokens['email'] ?? __( 'Google account', 'seo-campaign-hub' ) ) ); ?>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sch_google_disconnect' ), 'sch_google_disconnect' ) ); ?>">
							<?php esc_html_e( 'Disconnect', 'seo-campaign-hub' ); ?>
						</a>
					<?php elseif ( $has_creds ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( $drive->get_auth_url() ); ?>" data-sch-google-auth="1">
							<?php esc_html_e( 'Sync with Google Drive', 'seo-campaign-hub' ); ?>
						</a>
						<span class="description" style="margin-left:8px"><?php esc_html_e( 'Opens Google sign-in in a popup. Allow popups for this site if nothing appears.', 'seo-campaign-hub' ); ?></span>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'Save your Client ID and Client Secret (password), then click Sync with Google Drive.', 'seo-campaign-hub' ); ?></p>
					<?php endif; ?>
				</p>
			</section>

			<section class="sch-ie-panel" style="margin-bottom:24px">
				<h2><?php esc_html_e( 'Backup now', 'seo-campaign-hub' ); ?></h2>
				<p><?php esc_html_e( 'Creates a JSON backup of plugin data and uploads it to Google Drive.', 'seo-campaign-hub' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_cloud_backup_now">
					<?php wp_nonce_field( 'sch_cloud_backup_now' ); ?>
					<?php submit_button( __( 'Backup plugin data to Google Drive', 'seo-campaign-hub' ), 'primary', 'submit', false ); ?>
				</form>

				<?php if ( ! empty( $status['time'] ) ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: 1: datetime 2: status message */
							esc_html__( 'Last backup: %1$s — %2$s', 'seo-campaign-hub' ),
							esc_html( $status['time'] ),
							esc_html( $status['message'] )
						);
						?>
					</p>
				<?php endif; ?>
			</section>

			<section class="sch-ie-panel">
				<h2><?php esc_html_e( 'Backup schedule', 'seo-campaign-hub' ); ?></h2>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_cloud_backup_schedule">
					<?php wp_nonce_field( 'sch_cloud_backup_schedule' ); ?>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable schedule', 'seo-campaign-hub' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="schedule_enabled" value="1"
										<?php checked( (string) ( $settings['schedule_enabled'] ?? '0' ), '1' ); ?> />
									<?php esc_html_e( 'Run automatic plugin backups', 'seo-campaign-hub' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-schedule-frequency"><?php esc_html_e( 'Frequency', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<select id="sch-schedule-frequency" name="schedule_frequency">
									<option value="daily" <?php selected( (string) ( $settings['schedule_frequency'] ?? 'daily' ), 'daily' ); ?>><?php esc_html_e( 'Daily', 'seo-campaign-hub' ); ?></option>
									<option value="weekly" <?php selected( (string) ( $settings['schedule_frequency'] ?? '' ), 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'seo-campaign-hub' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Uses WordPress cron (runs when your site receives visits).', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save schedule', 'seo-campaign-hub' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>

		</div>
	</div>
</div>

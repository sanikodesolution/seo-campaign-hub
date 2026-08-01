<?php
/**
 * Web Push (OneSignal) admin view.
 *
 * @var string                                                    $page_title Page title.
 * @var \SEO_Campaign_Hub\Services\OneSignalWebPushService|null $push       Service.
 * @var string                                                    $notice     Notice key.
 * @var string                                                    $notice_msg Optional message.
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'seo_campaign_hub_options', [] );
if ( ! is_array( $options ) ) {
	$options = [];
}

$enabled     = ! empty( $options['enable_web_push'] ) && (string) $options['enable_web_push'] === '1';
$app_id      = isset( $options['onesignal_app_id'] ) ? (string) $options['onesignal_app_id'] : '';
$rest_key    = isset( $options['onesignal_rest_api_key'] ) ? (string) $options['onesignal_rest_api_key'] : '';
$auto        = ! array_key_exists( 'web_push_auto_notify', $options ) || (string) $options['web_push_auto_notify'] === '1';
$soft        = ! array_key_exists( 'web_push_soft_prompt', $options ) || (string) $options['web_push_soft_prompt'] === '1';
$delay       = isset( $options['web_push_soft_prompt_delay'] ) ? (int) $options['web_push_soft_prompt_delay'] : 8;
$delay       = max( 0, min( 120, $delay ) );
$notice_key  = isset( $notice ) ? (string) $notice : '';
$notice_text = isset( $notice_msg ) ? (string) $notice_msg : '';
$configured  = $push && $push->is_configured();
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Web Push', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( $notice_key === 'saved' ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'seo-campaign-hub' ); ?></p></div>
	<?php elseif ( $notice_key === 'sent' ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice_text !== '' ? $notice_text : __( 'Notification sent.', 'seo-campaign-hub' ) ); ?></p></div>
	<?php elseif ( $notice_key === 'error' ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $notice_text !== '' ? $notice_text : __( 'Something went wrong.', 'seo-campaign-hub' ) ); ?></p></div>
	<?php endif; ?>

	<div class="notice notice-info">
		<p>
			<strong><?php esc_html_e( 'Setup (OneSignal):', 'seo-campaign-hub' ); ?></strong>
			<?php esc_html_e( 'Create a free OneSignal app → Settings → Push & In-App → Web → Custom Code. Use your site HTTPS URL. Paste App ID and REST API Key below. The plugin serves /OneSignalSDKWorker.js automatically.', 'seo-campaign-hub' ); ?>
		</p>
		<p>
			<a href="https://onesignal.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open OneSignal', 'seo-campaign-hub' ); ?></a>
		</p>
	</div>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px;max-width:720px">
				<h2><?php esc_html_e( 'Settings', 'seo-campaign-hub' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_web_push_settings" />
					<?php wp_nonce_field( 'sch_web_push_settings' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable', 'seo-campaign-hub' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="enable_web_push" value="1" <?php checked( $enabled ); ?> />
									<?php esc_html_e( 'Enable OneSignal Web Push on this site', 'seo-campaign-hub' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-onesignal-app-id"><?php esc_html_e( 'OneSignal App ID', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="text" class="regular-text" id="sch-onesignal-app-id" name="onesignal_app_id" value="<?php echo esc_attr( $app_id ); ?>" autocomplete="off" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-onesignal-rest-key"><?php esc_html_e( 'REST API Key', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="password" class="regular-text" id="sch-onesignal-rest-key" name="onesignal_rest_api_key" value="<?php echo esc_attr( $rest_key ); ?>" autocomplete="new-password" />
								<p class="description"><?php esc_html_e( 'Server-only. Never exposed to visitors.', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Auto-notify on publish', 'seo-campaign-hub' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="web_push_auto_notify" value="1" <?php checked( $auto ); ?> />
									<?php esc_html_e( 'Send a push when a post is first published (can skip per post)', 'seo-campaign-hub' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Soft prompt', 'seo-campaign-hub' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="web_push_soft_prompt" value="1" <?php checked( $soft ); ?> />
									<?php esc_html_e( 'Show a “Get deal alerts” banner before the browser permission dialog', 'seo-campaign-hub' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-web-push-delay"><?php esc_html_e( 'Prompt delay (seconds)', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="number" class="small-text" id="sch-web-push-delay" name="web_push_soft_prompt_delay" min="0" max="120" value="<?php echo esc_attr( (string) $delay ); ?>" />
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save settings', 'seo-campaign-hub' ) ); ?>
				</form>
				<p class="description">
					<?php
					echo $configured
						? esc_html__( 'Credentials look complete.', 'seo-campaign-hub' )
						: esc_html__( 'Add App ID and REST API Key to send notifications.', 'seo-campaign-hub' );
					?>
				</p>
			</div>

			<div class="sch-panel" style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px;max-width:720px">
				<h2><?php esc_html_e( 'Send push now', 'seo-campaign-hub' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_web_push_send" />
					<?php wp_nonce_field( 'sch_web_push_send' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="sch-push-title"><?php esc_html_e( 'Title', 'seo-campaign-hub' ); ?></label></th>
							<td><input type="text" class="regular-text" id="sch-push-title" name="push_title" required /></td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-push-message"><?php esc_html_e( 'Message', 'seo-campaign-hub' ); ?></label></th>
							<td><textarea class="large-text" rows="3" id="sch-push-message" name="push_message" required></textarea></td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-push-url"><?php esc_html_e( 'Click URL', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="url" class="regular-text" id="sch-push-url" name="push_url" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
								<p class="description"><?php esc_html_e( 'Optional. Defaults to your homepage.', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
					</table>
					<?php
					submit_button(
						__( 'Send now', 'seo-campaign-hub' ),
						'primary',
						'submit',
						true,
						$configured ? [] : [ 'disabled' => 'disabled' ]
					);
					?>
				</form>
			</div>

		</div>
	</div>
</div>

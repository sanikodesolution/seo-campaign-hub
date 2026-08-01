<?php
/**
 * Social Share admin page.
 *
 * @var string                                               $page_title Page title.
 * @var \SEO_Campaign_Hub\Services\SocialShareService|null $share      Service.
 * @var string                                               $notice     Notice key.
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

$enabled = ! array_key_exists( 'enable_admin_social_share', $options ) || (string) $options['enable_admin_social_share'] === '1';
$all     = [
	'facebook'  => 'Facebook',
	'x'         => 'X (Twitter)',
	'linkedin'  => 'LinkedIn',
	'pinterest' => 'Pinterest',
	'whatsapp'  => 'WhatsApp',
	'blogger'   => 'Blogger',
	'telegram'  => 'Telegram',
	'quora'     => 'Quora',
	'reddit'    => 'Reddit',
	'email'     => 'Email',
	'copy'      => 'Copy link',
];
$selected = [];
if ( ! empty( $options['social_share_networks'] ) && is_array( $options['social_share_networks'] ) ) {
	$selected = array_map( 'strval', $options['social_share_networks'] );
} else {
	$selected = array_keys( $all );
}

$notice_key = isset( $notice ) ? (string) $notice : '';
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Social Share', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( $notice_key === 'saved' ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'seo-campaign-hub' ); ?></p></div>
	<?php endif; ?>

	<div class="notice notice-info">
		<p>
			<strong><?php esc_html_e( 'Where to find share icons:', 'seo-campaign-hub' ); ?></strong>
			<?php esc_html_e( 'Go to Posts → All Posts. Each published post has a Share column with network icons. No App ID required — links open in your browser.', 'seo-campaign-hub' ); ?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
				<?php esc_html_e( 'Open All Posts', 'seo-campaign-hub' ); ?>
			</a>
		</p>
	</div>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">
			<div class="sch-panel" style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px;max-width:720px">
				<h2><?php esc_html_e( 'Settings', 'seo-campaign-hub' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_social_share_settings" />
					<?php wp_nonce_field( 'sch_social_share_settings' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable', 'seo-campaign-hub' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="enable_admin_social_share" value="1" <?php checked( $enabled ); ?> />
									<?php esc_html_e( 'Show share icons on Posts → All Posts', 'seo-campaign-hub' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Networks', 'seo-campaign-hub' ); ?></th>
							<td>
								<fieldset>
									<?php foreach ( $all as $key => $label ) : ?>
										<label style="display:inline-block;min-width:140px;margin:0 12px 8px 0;">
											<input type="checkbox" name="social_share_networks[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $selected, true ) ); ?> />
											<?php echo esc_html( $label ); ?>
										</label>
									<?php endforeach; ?>
								</fieldset>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save settings', 'seo-campaign-hub' ) ); ?>
				</form>
			</div>
		</div>
	</div>
</div>

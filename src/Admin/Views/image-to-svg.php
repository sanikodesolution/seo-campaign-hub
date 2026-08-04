<?php
/**
 * Image → SVG admin page.
 *
 * @var string                                               $page_title Page title.
 * @var \SEO_Campaign_Hub\Services\ImageToSvgService|null $service    Service.
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

$enabled = ! array_key_exists( 'enable_image_to_svg', $options ) || (string) $options['enable_image_to_svg'] === '1';
$public_url = $service ? $service->get_public_url() : home_url( '/tools/image-to-svg/' );
$notice_key = isset( $notice ) ? (string) $notice : '';
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Image to SVG', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( $notice_key === 'saved' ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'seo-campaign-hub' ); ?></p></div>
	<?php endif; ?>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Public tool (no login)', 'seo-campaign-hub' ); ?></h2>
				<p>
					<?php esc_html_e( 'Visitors can convert PNG/JPG to SVG in the browser. Nothing is uploaded to the server.', 'seo-campaign-hub' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Public URL:', 'seo-campaign-hub' ); ?></strong>
					<?php if ( $enabled ) : ?>
						<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $public_url ); ?>
						</a>
					<?php else : ?>
						<code><?php echo esc_html( $public_url ); ?></code>
						— <?php esc_html_e( 'disabled below', 'seo-campaign-hub' ); ?>
					<?php endif; ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Shortcode:', 'seo-campaign-hub' ); ?></strong>
					<code>[sch_image_to_svg]</code>
				</p>
				<?php if ( $enabled ) : ?>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Open public tool', 'seo-campaign-hub' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>">
							<?php esc_html_e( 'Flush Permalinks', 'seo-campaign-hub' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Settings', 'seo-campaign-hub' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_image_to_svg_settings">
					<?php wp_nonce_field( 'sch_image_to_svg_settings' ); ?>
					<p>
						<label>
							<input type="checkbox" name="enable_image_to_svg" value="1" <?php checked( $enabled ); ?>>
							<?php esc_html_e( 'Enable public Image → SVG tool', 'seo-campaign-hub' ); ?>
						</label>
					</p>
					<?php submit_button( __( 'Save settings', 'seo-campaign-hub' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Ads on this tool', 'seo-campaign-hub' ); ?></h2>
				<p>
					<?php esc_html_e( 'Shared Above/Below ad HTML for both public tool pages is managed under Settings → Public Tools. Shortcode embeds stay ad-free.', 'seo-campaign-hub' ); ?>
				</p>
				<p>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ); ?>">
						<?php esc_html_e( 'Edit public tools ads', 'seo-campaign-hub' ); ?>
					</a>
				</p>
			</div>

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Convert now', 'seo-campaign-hub' ); ?></h2>
				<p><?php esc_html_e( 'Try the converter here in wp-admin (same tool visitors use).', 'seo-campaign-hub' ); ?></p>
				<?php
				$sch_embed = true;
				include SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Frontend/Views/image-to-svg-widget.php';
				?>
			</div>

		</div>
	</div>
</div>

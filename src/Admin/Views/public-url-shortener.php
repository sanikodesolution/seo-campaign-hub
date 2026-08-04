<?php
/**
 * Public URL Shortener admin page.
 *
 * @var string                                                      $page_title Page title.
 * @var \SEO_Campaign_Hub\Services\PublicShortenerService|null $service    Service.
 * @var string                                                      $notice     Notice key.
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

$enabled = ! array_key_exists( 'enable_public_url_shortener', $options ) || (string) $options['enable_public_url_shortener'] === '1';
$same_site = isset( $options['public_shortener_same_site_only'] ) && (string) $options['public_shortener_same_site_only'] === '1';
$rate_limit = isset( $options['public_shortener_rate_limit'] ) ? absint( $options['public_shortener_rate_limit'] ) : 10;
if ( $rate_limit < 1 ) {
	$rate_limit = 10;
}
$core_shortener_on = (bool) get_option( 'seo_campaign_hub_enable_shortener', true );
$public_url = $service ? $service->get_public_url() : home_url( '/tools/url-shortener/' );
$notice_key = isset( $notice ) ? (string) $notice : '';
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Public URL Shortener', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( $notice_key === 'saved' ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'seo-campaign-hub' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $core_shortener_on ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %s: settings link */
					esc_html__( 'The core URL Shortener is disabled. Enable it under %s before the public tool can create links.', 'seo-campaign-hub' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ) . '">' . esc_html__( 'Settings → URL Shortener', 'seo-campaign-hub' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Public tool (no login)', 'seo-campaign-hub' ); ?></h2>
				<p>
					<?php esc_html_e( 'Visitors paste a long URL and get a short link with an auto-generated slug. Links appear in URL Shortener with a Public badge.', 'seo-campaign-hub' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Public URL:', 'seo-campaign-hub' ); ?></strong>
					<?php if ( $enabled && $core_shortener_on ) : ?>
						<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $public_url ); ?>
						</a>
					<?php else : ?>
						<code><?php echo esc_html( $public_url ); ?></code>
						— <?php esc_html_e( 'disabled below or core shortener off', 'seo-campaign-hub' ); ?>
					<?php endif; ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Shortcode:', 'seo-campaign-hub' ); ?></strong>
					<code>[sch_url_shortener]</code>
				</p>
				<?php if ( $enabled && $core_shortener_on ) : ?>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Open public tool', 'seo-campaign-hub' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>">
							<?php esc_html_e( 'Flush Permalinks', 'seo-campaign-hub' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-shortener' ) ); ?>">
							<?php esc_html_e( 'Manage short links', 'seo-campaign-hub' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Settings', 'seo-campaign-hub' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_public_shortener_settings">
					<?php wp_nonce_field( 'sch_public_shortener_settings' ); ?>
					<p>
						<label>
							<input type="checkbox" name="enable_public_url_shortener" value="1" <?php checked( $enabled ); ?>>
							<?php esc_html_e( 'Enable public URL shortener tool', 'seo-campaign-hub' ); ?>
						</label>
					</p>
					<p>
						<label>
							<input type="checkbox" name="public_shortener_same_site_only" value="1" <?php checked( $same_site ); ?>>
							<?php esc_html_e( 'Same-site destinations only (destination host must match this site)', 'seo-campaign-hub' ); ?>
						</label>
					</p>
					<p>
						<label for="sch-public-shortener-rate">
							<?php esc_html_e( 'Rate limit (creates per IP per hour)', 'seo-campaign-hub' ); ?>
						</label><br>
						<input type="number" id="sch-public-shortener-rate" name="public_shortener_rate_limit" min="1" max="100" value="<?php echo esc_attr( (string) $rate_limit ); ?>" class="small-text">
					</p>
					<?php submit_button( __( 'Save settings', 'seo-campaign-hub' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<?php if ( $enabled && $core_shortener_on ) : ?>
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
				<h2><?php esc_html_e( 'Try it now', 'seo-campaign-hub' ); ?></h2>
				<p><?php esc_html_e( 'Same form visitors use (creates a real short link).', 'seo-campaign-hub' ); ?></p>
				<?php
				$sch_embed = true;
				include SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Frontend/Views/public-url-shortener-widget.php';
				?>
			</div>
			<?php endif; ?>

		</div>
	</div>
</div>

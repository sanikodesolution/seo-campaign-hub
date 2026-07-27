<?php
/**
 * Settings View
 *
 * @var string                                    $page_title Page title.
 * @var \SEO_Campaign_Hub\Admin\Settings|null     $settings   Settings manager.
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ads_url = home_url( '/ads.txt' );
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Settings', 'seo-campaign-hub' ) ); ?></h1>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">
			<form method="post" action="options.php">
				<?php
				settings_fields( 'seo_campaign_hub_settings' );
				do_settings_sections( 'seo_campaign_hub_settings' );
				submit_button( __( 'Save Settings', 'seo-campaign-hub' ) );
				?>
			</form>

			<hr />

			<p class="description">
				<?php
				printf(
					/* translators: %s: ads.txt URL */
					esc_html__( 'After enabling Ads.txt, verify it at: %s', 'seo-campaign-hub' ),
					'<a href="' . esc_url( $ads_url ) . '" target="_blank" rel="noopener noreferrer"><code>' . esc_html( $ads_url ) . '</code></a>'
				);
				?>
			</p>
		</div>
	</div>
</div>

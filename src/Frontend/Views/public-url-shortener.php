<?php
/**
 * Standalone public page: URL shortener tool.
 *
 * @package SEO_Campaign_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html__( 'URL Shortener', 'seo-campaign-hub' ); ?> — <?php bloginfo( 'name' ); ?></title>
	<meta name="robots" content="noindex,follow">
	<?php wp_head(); ?>
</head>
<body class="sch-public-shortener-page">
	<main class="sch-pus-shell">
		<header class="sch-pus-header">
			<p class="sch-pus-kicker"><?php bloginfo( 'name' ); ?></p>
			<h1><?php esc_html_e( 'URL Shortener', 'seo-campaign-hub' ); ?></h1>
			<p class="sch-pus-lead">
				<?php esc_html_e( 'Paste a long URL and get a short link. No login required.', 'seo-campaign-hub' ); ?>
			</p>
		</header>
		<?php
		$sch_embed = false;
		include SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Frontend/Views/public-url-shortener-widget.php';
		?>
		<p class="sch-pus-footnote">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; <?php esc_html_e( 'Back to site', 'seo-campaign-hub' ); ?></a>
		</p>
	</main>
	<?php wp_footer(); ?>
</body>
</html>

<?php
/**
 * Standalone public page: Image → SVG tool.
 *
 * @package SEO_Campaign_Hub
 * @var bool $sch_embed Optional when included from shortcode (unused here).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html__( 'Image to SVG Converter', 'seo-campaign-hub' ); ?> — <?php bloginfo( 'name' ); ?></title>
	<meta name="robots" content="noindex,follow">
	<?php wp_head(); ?>
</head>
<body class="sch-image-to-svg-page">
	<main class="sch-its-shell">
		<header class="sch-its-header">
			<p class="sch-its-kicker"><?php bloginfo( 'name' ); ?></p>
			<h1><?php esc_html_e( 'Image to SVG', 'seo-campaign-hub' ); ?></h1>
			<p class="sch-its-lead">
				<?php esc_html_e( 'Convert PNG or JPG to SVG in your browser. No login required — nothing is uploaded to the server.', 'seo-campaign-hub' ); ?>
			</p>
		</header>
		<?php
		$sch_embed = false;
		include SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Frontend/Views/image-to-svg-widget.php';
		?>
		<p class="sch-its-footnote">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; <?php esc_html_e( 'Back to site', 'seo-campaign-hub' ); ?></a>
		</p>
	</main>
	<?php wp_footer(); ?>
</body>
</html>

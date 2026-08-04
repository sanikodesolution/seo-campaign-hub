<?php
/**
 * Public URL shortener widget markup (page + shortcode).
 *
 * @package SEO_Campaign_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sch_embed = ! empty( $sch_embed );
?>
<div class="sch-pus-widget<?php echo $sch_embed ? ' sch-pus-widget--embed' : ''; ?>" data-sch-public-shortener>
	<form class="sch-pus-form" data-sch-pus-form novalidate>
		<label class="sch-pus-label" for="sch-pus-url-<?php echo $sch_embed ? 'embed' : 'page'; ?>">
			<?php esc_html_e( 'Long URL', 'seo-campaign-hub' ); ?>
		</label>
		<input
			type="url"
			id="sch-pus-url-<?php echo $sch_embed ? 'embed' : 'page'; ?>"
			class="sch-pus-input"
			name="url"
			data-sch-pus-url
			required
			autocomplete="url"
			placeholder="<?php echo esc_attr__( 'https://example.com/your-long-url', 'seo-campaign-hub' ); ?>"
		>

		<?php /* Honeypot — leave empty */ ?>
		<label class="sch-pus-hp" aria-hidden="true">
			<span><?php esc_html_e( 'Website', 'seo-campaign-hub' ); ?></span>
			<input type="text" name="honeypot" data-sch-pus-honeypot value="" tabindex="-1" autocomplete="off">
		</label>

		<button type="submit" class="sch-pus-submit" data-sch-pus-submit>
			<?php esc_html_e( 'Shorten URL', 'seo-campaign-hub' ); ?>
		</button>
	</form>

	<p class="sch-pus-status" data-sch-pus-status hidden></p>

	<div class="sch-pus-result" data-sch-pus-result hidden>
		<label class="sch-pus-label" for="sch-pus-short-<?php echo $sch_embed ? 'embed' : 'page'; ?>">
			<?php esc_html_e( 'Short URL', 'seo-campaign-hub' ); ?>
		</label>
		<div class="sch-pus-result-row">
			<input
				type="text"
				id="sch-pus-short-<?php echo $sch_embed ? 'embed' : 'page'; ?>"
				class="sch-pus-input"
				data-sch-pus-short
				readonly
			>
			<button type="button" class="sch-pus-copy" data-sch-pus-copy>
				<?php esc_html_e( 'Copy', 'seo-campaign-hub' ); ?>
			</button>
		</div>
	</div>
</div>

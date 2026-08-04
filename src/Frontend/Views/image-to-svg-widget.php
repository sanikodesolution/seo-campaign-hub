<?php
/**
 * Image → SVG converter widget markup (page + shortcode).
 *
 * @package SEO_Campaign_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sch_embed = ! empty( $sch_embed );
?>
<div class="sch-its-widget<?php echo $sch_embed ? ' sch-its-widget--embed' : ''; ?>" data-sch-image-to-svg>
	<div class="sch-its-modes" role="radiogroup" aria-label="<?php esc_attr_e( 'Conversion mode', 'seo-campaign-hub' ); ?>">
		<label class="sch-its-mode">
			<input type="radio" name="sch-its-mode" value="wrap" checked>
			<span><?php esc_html_e( 'Wrap in SVG', 'seo-campaign-hub' ); ?></span>
		</label>
		<label class="sch-its-mode">
			<input type="radio" name="sch-its-mode" value="vectorize">
			<span><?php esc_html_e( 'Vectorize', 'seo-campaign-hub' ); ?></span>
		</label>
	</div>

	<label class="sch-its-dropzone" data-sch-its-dropzone>
		<input type="file" accept="image/png,image/jpeg,image/jpg,image/webp" data-sch-its-file hidden>
		<span class="sch-its-dropzone-text" data-sch-its-drop-text>
			<?php esc_html_e( 'Drop PNG/JPG here or click to browse', 'seo-campaign-hub' ); ?>
		</span>
	</label>

	<p class="sch-its-status" data-sch-its-status hidden></p>
	<p class="sch-its-note" data-sch-its-note hidden></p>

	<div class="sch-its-preview" data-sch-its-preview hidden>
		<div class="sch-its-preview-frame" data-sch-its-preview-frame></div>
		<button type="button" class="sch-its-download" data-sch-its-download disabled>
			<?php esc_html_e( 'Download SVG', 'seo-campaign-hub' ); ?>
		</button>
	</div>
</div>

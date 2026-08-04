<?php
/**
 * Public Image → SVG converter (client-side; no login).
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ImageToSvgService
 */
class ImageToSvgService {

	public const QUERY_VAR = 'sch_image_to_svg';
	public const OPTION_KEY = 'enable_image_to_svg';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_rewrite' ], 20 );
		add_filter( 'query_vars', [ $this, 'register_query_var' ] );
		add_action( 'template_redirect', [ $this, 'maybe_render_tool_page' ], 1 );
		add_shortcode( 'sch_image_to_svg', [ $this, 'render_shortcode' ] );
		add_action( 'update_option_seo_campaign_hub_options', [ $this, 'maybe_flush_on_toggle' ], 10, 2 );

		// One-time rewrite flush after shipping this feature.
		if ( get_option( 'seo_campaign_hub_its_rewrite_ver' ) !== '1.1.7' ) {
			set_transient( 'seo_campaign_hub_flush_rewrite_rules', 1, MINUTE_IN_SECONDS * 5 );
			update_option( 'seo_campaign_hub_its_rewrite_ver', '1.1.7', false );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_options(): array {
		$options = get_option( 'seo_campaign_hub_options', [] );
		return is_array( $options ) ? $options : [];
	}

	public function is_enabled(): bool {
		$o = $this->get_options();
		if ( ! array_key_exists( self::OPTION_KEY, $o ) ) {
			return true;
		}
		return (string) $o[ self::OPTION_KEY ] === '1';
	}

	public function get_public_url(): string {
		return home_url( '/tools/image-to-svg/' );
	}

	/**
	 * Register rewrite rule.
	 */
	public function register_rewrite(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		add_rewrite_rule(
			'^tools/image-to-svg/?$',
			'index.php?' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	/**
	 * @param array<int, string> $vars Query vars.
	 * @return array<int, string>
	 */
	public function register_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Flush rewrites when the feature is toggled.
	 *
	 * @param mixed $old Old value.
	 * @param mixed $new New value.
	 */
	public function maybe_flush_on_toggle( $old, $new ): void {
		$old_arr = is_array( $old ) ? $old : [];
		$new_arr = is_array( $new ) ? $new : [];
		$old_on  = ! array_key_exists( self::OPTION_KEY, $old_arr ) || (string) $old_arr[ self::OPTION_KEY ] === '1';
		$new_on  = ! array_key_exists( self::OPTION_KEY, $new_arr ) || (string) $new_arr[ self::OPTION_KEY ] === '1';
		if ( $old_on !== $new_on ) {
			set_transient( 'seo_campaign_hub_flush_rewrite_rules', 1, MINUTE_IN_SECONDS * 5 );
		}
	}

	/**
	 * Serve the public tool page.
	 */
	public function maybe_render_tool_page(): void {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}
		if ( ! $this->is_enabled() ) {
			status_header( 404 );
			nocache_headers();
			return;
		}

		status_header( 200 );
		nocache_headers();
		$this->enqueue_assets();

		$view = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Frontend/Views/image-to-svg.php';
		if ( is_readable( $view ) ) {
			include $view;
		}
		exit;
	}

	/**
	 * Shortcode embed.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public function render_shortcode( $atts = [] ): string {
		if ( ! $this->is_enabled() ) {
			return '';
		}
		$this->enqueue_assets();
		ob_start();
		$sch_embed = true;
		include SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Frontend/Views/image-to-svg-widget.php';
		return (string) ob_get_clean();
	}

	/**
	 * Enqueue public assets.
	 */
	public function enqueue_assets(): void {
		$ver = SEO_CAMPAIGN_HUB_VERSION;
		$url = SEO_CAMPAIGN_HUB_PLUGIN_URL;

		wp_enqueue_style(
			'sch-image-to-svg',
			$url . 'assets/public/css/image-to-svg.css',
			[],
			$ver
		);
		wp_enqueue_script(
			'sch-image-to-svg',
			$url . 'assets/public/js/image-to-svg.js',
			[],
			$ver,
			true
		);
		wp_localize_script(
			'sch-image-to-svg',
			'schImageToSvg',
			[
				'maxBytes' => 5 * 1024 * 1024,
				'i18n'     => [
					'pick'       => __( 'Drop PNG/JPG here or click to browse', 'seo-campaign-hub' ),
					'wrap'       => __( 'Wrap in SVG', 'seo-campaign-hub' ),
					'vectorize'  => __( 'Vectorize', 'seo-campaign-hub' ),
					'download'   => __( 'Download SVG', 'seo-campaign-hub' ),
					'tooLarge'   => __( 'File is too large (max 5 MB).', 'seo-campaign-hub' ),
					'badType'    => __( 'Please choose a PNG, JPG, or WebP image.', 'seo-campaign-hub' ),
					'ready'      => __( 'Ready — download your SVG.', 'seo-campaign-hub' ),
					'working'    => __( 'Converting…', 'seo-campaign-hub' ),
					'vectorNote' => __( 'Vectorize works best on simple logos/icons. Photos should use Wrap in SVG.', 'seo-campaign-hub' ),
				],
			]
		);
	}
}

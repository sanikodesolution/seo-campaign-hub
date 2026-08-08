<?php
/**
 * Front-end code optimization: clean head, defer JS, minify HTML.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FrontEndOptimizerService
 */
class FrontEndOptimizerService {

	/**
	 * @return void
	 */
	public function init(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( $this->option_on( 'clean_wp_head', true ) ) {
			add_action( 'init', [ $this, 'clean_wp_head' ], 20 );
		}

		if ( $this->option_on( 'defer_scripts', true ) ) {
			add_filter( 'script_loader_tag', [ $this, 'defer_script_tag' ], 20, 2 );
		}

		if ( $this->option_on( 'lazy_load_images', true ) ) {
			add_filter( 'wp_lazy_loading_enabled', [ $this, 'enable_lazy_loading' ], 10, 2 );
		}

		if ( $this->option_on( 'minify_assets', true ) ) {
			add_action( 'template_redirect', [ $this, 'start_html_minify' ], 1 );
		}
	}

	/**
	 * Strip unused wp_head output.
	 *
	 * @return void
	 */
	public function clean_wp_head(): void {
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}

	/**
	 * Defer footer scripts that are safe to delay.
	 *
	 * @param string $tag    Script HTML.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function defer_script_tag( string $tag, string $handle ): string {
		if ( $this->should_skip_request() ) {
			return $tag;
		}

		if ( preg_match( '/\s(?:defer|async)[\s>]/i', $tag ) ) {
			return $tag;
		}

		$skip = [
			'jquery',
			'jquery-core',
			'jquery-migrate',
			'wp-hooks',
			'wp-i18n',
			'wp-a11y',
			'underscore',
			'backbone',
			'moxiejs',
			'plupload',
		];
		if ( in_array( $handle, $skip, true ) ) {
			return $tag;
		}

		if ( preg_match( '/^(elementor|e-gallery|swiper|webpack|wp-tinymce|editor|quicktags)/i', $handle ) ) {
			return $tag;
		}

		global $wp_scripts;
		if ( isset( $wp_scripts->registered[ $handle ] ) ) {
			$group = $wp_scripts->registered[ $handle ]->extra['group'] ?? 0;
			if ( (int) $group !== 1 ) {
				return $tag;
			}
		}

		return str_replace( ' src=', ' defer src=', $tag );
	}

	/**
	 * @param bool   $default Default.
	 * @param string $tag_name img|iframe.
	 * @return bool
	 */
	public function enable_lazy_loading( bool $default, string $tag_name ): bool {
		unset( $tag_name );
		if ( $this->should_skip_request() ) {
			return $default;
		}
		return true;
	}

	/**
	 * Buffer HTML for minify.
	 *
	 * @return void
	 */
	public function start_html_minify(): void {
		if ( $this->should_skip_request() || $this->should_skip_html_minify() ) {
			return;
		}
		ob_start( [ $this, 'minify_html' ] );
	}

	/**
	 * Collapse HTML whitespace; keep executable / preformatted blocks intact.
	 *
	 * @param string $html Buffered output.
	 * @return string
	 */
	public function minify_html( string $html ): string {
		if ( $html === '' || stripos( $html, '<html' ) === false ) {
			return $html;
		}

		$placeholders = [];
		$protected    = preg_replace_callback(
			'#<(pre|textarea|script|style|code)(\b[^>]*)>.*?</\1>#is',
			static function ( array $m ) use ( &$placeholders ): string {
				$key                    = '___SCH_MINIFY_' . count( $placeholders ) . '___';
				$placeholders[ $key ] = $m[0];
				return $key;
			},
			$html
		);

		if ( ! is_string( $protected ) ) {
			return $html;
		}

		$protected = preg_replace( '/<!--(?!\s*(?:\[if [^\]]+]|(?:<!|>)))(?:(?!-->).)*-->/s', '', $protected );
		if ( ! is_string( $protected ) ) {
			return $html;
		}

		$protected = preg_replace( '/>\s+</', '><', $protected );
		$protected = is_string( $protected ) ? preg_replace( '/[ \t]+/', ' ', $protected ) : $html;
		if ( ! is_string( $protected ) ) {
			return $html;
		}

		$protected = preg_replace( "/\n{2,}/", "\n", $protected );
		if ( ! is_string( $protected ) ) {
			return $html;
		}

		return strtr( $protected, $placeholders );
	}

	/**
	 * @param bool   $default Default when unset.
	 * @param string $key     Option key.
	 */
	private function option_on( string $key, bool $default = true ): bool {
		$options = get_option( 'seo_campaign_hub_options', [] );
		if ( ! is_array( $options ) || ! array_key_exists( $key, $options ) ) {
			return $default;
		}
		return (string) $options[ $key ] === '1';
	}

	/**
	 * @return bool
	 */
	private function should_skip_request(): bool {
		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return true;
		}
		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return true;
		}
		if ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) {
			return true;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function should_skip_html_minify(): bool {
		if ( defined( 'DONOTMINIFY' ) && DONOTMINIFY ) {
			return true;
		}
		if ( function_exists( 'is_sitemap' ) && is_sitemap() ) {
			return true;
		}
		return false;
	}
}

<?php
/**
 * Public URL shortener tool (no login).
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PublicShortenerService
 */
class PublicShortenerService {

	public const QUERY_VAR     = 'sch_public_shortener';
	public const OPTION_KEY    = 'enable_public_url_shortener';
	public const SAME_SITE_KEY = 'public_shortener_same_site_only';
	public const RATE_KEY      = 'public_shortener_rate_limit';
	public const REST_ROUTE    = '/public/shorten';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_rewrite' ], 20 );
		add_filter( 'query_vars', [ $this, 'register_query_var' ] );
		add_action( 'template_redirect', [ $this, 'maybe_render_tool_page' ], 1 );
		add_shortcode( 'sch_url_shortener', [ $this, 'render_shortcode' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
		add_action( 'update_option_seo_campaign_hub_options', [ $this, 'maybe_flush_on_toggle' ], 10, 2 );

		if ( get_option( 'seo_campaign_hub_pub_shortener_rewrite_ver' ) !== '1.1.8' ) {
			set_transient( 'seo_campaign_hub_flush_rewrite_rules', 1, MINUTE_IN_SECONDS * 5 );
			update_option( 'seo_campaign_hub_pub_shortener_rewrite_ver', '1.1.8', false );
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
		if ( ! get_option( 'seo_campaign_hub_enable_shortener', true ) ) {
			return false;
		}
		$o = $this->get_options();
		if ( ! array_key_exists( self::OPTION_KEY, $o ) ) {
			return true;
		}
		return (string) $o[ self::OPTION_KEY ] === '1';
	}

	public function is_same_site_only(): bool {
		$o = $this->get_options();
		return isset( $o[ self::SAME_SITE_KEY ] ) && (string) $o[ self::SAME_SITE_KEY ] === '1';
	}

	public function get_rate_limit(): int {
		$o     = $this->get_options();
		$limit = isset( $o[ self::RATE_KEY ] ) ? absint( $o[ self::RATE_KEY ] ) : 10;
		if ( $limit < 1 ) {
			$limit = 10;
		}
		return min( 100, $limit );
	}

	public function get_public_url(): string {
		return home_url( '/tools/url-shortener/' );
	}

	/**
	 * Register rewrite rule.
	 */
	public function register_rewrite(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		add_rewrite_rule(
			'^tools/url-shortener/?$',
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

		$view = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Frontend/Views/public-url-shortener.php';
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
		include SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Frontend/Views/public-url-shortener-widget.php';
		return (string) ob_get_clean();
	}

	/**
	 * Register public REST create endpoint.
	 */
	public function register_rest_route(): void {
		register_rest_route(
			'seo-campaign-hub/v1',
			self::REST_ROUTE,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_create_short_link' ],
				'permission_callback' => [ $this, 'rest_permission' ],
				'args'                => [
					'url'      => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					],
					'honeypot' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					],
				],
			]
		);
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function rest_permission() {
		if ( ! $this->is_enabled() ) {
			return new \WP_Error(
				'sch_public_shortener_disabled',
				__( 'Public URL shortener is disabled.', 'seo-campaign-hub' ),
				[ 'status' => 404 ]
			);
		}
		return true;
	}

	/**
	 * Create a short link from a public request.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_create_short_link( \WP_REST_Request $request ) {
		$honeypot = (string) $request->get_param( 'honeypot' );
		if ( $honeypot !== '' ) {
			return new \WP_Error(
				'sch_public_shortener_spam',
				__( 'Could not create short link.', 'seo-campaign-hub' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! $this->check_rate_limit() ) {
			return new \WP_Error(
				'sch_public_shortener_rate',
				__( 'Too many requests. Please try again later.', 'seo-campaign-hub' ),
				[ 'status' => 429 ]
			);
		}

		$url = (string) $request->get_param( 'url' );
		$validated = $this->validate_destination( $url );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$shortener = new ShortenerService();
		$short_url = $shortener->shorten_url(
			$validated,
			'',
			[
				'title'      => __( 'Public', 'seo-campaign-hub' ),
				'link_type'  => 'direct',
				'created_by' => 0,
			]
		);

		if ( ! $short_url || $short_url === $validated ) {
			return new \WP_Error(
				'sch_public_shortener_failed',
				__( 'Could not create the short link. Check that the URL shortener is enabled.', 'seo-campaign-hub' ),
				[ 'status' => 500 ]
			);
		}

		$this->bump_rate_limit();

		return rest_ensure_response(
			[
				'original_url' => $validated,
				'short_url'    => $short_url,
			]
		);
	}

	/**
	 * Validate destination URL for public creates.
	 *
	 * @param string $url Raw URL.
	 * @return string|\WP_Error Sanitized URL or error.
	 */
	public function validate_destination( string $url ) {
		$url = esc_url_raw( trim( $url ) );
		if ( $url === '' || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new \WP_Error(
				'sch_public_shortener_invalid',
				__( 'Please enter a valid URL.', 'seo-campaign-hub' ),
				[ 'status' => 400 ]
			);
		}

		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return new \WP_Error(
				'sch_public_shortener_scheme',
				__( 'Only http and https URLs are allowed.', 'seo-campaign-hub' ),
				[ 'status' => 400 ]
			);
		}

		if ( $this->is_same_site_only() ) {
			$dest_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
			if ( $dest_host === '' || $site_host === '' || $dest_host !== $site_host ) {
				return new \WP_Error(
					'sch_public_shortener_same_site',
					__( 'Destination URL must be on this website.', 'seo-campaign-hub' ),
					[ 'status' => 400 ]
				);
			}
		}

		return $url;
	}

	/**
	 * @return bool True if under the hourly limit.
	 */
	private function check_rate_limit(): bool {
		$key   = $this->rate_transient_key();
		$count = (int) get_transient( $key );
		return $count < $this->get_rate_limit();
	}

	private function bump_rate_limit(): void {
		$key   = $this->rate_transient_key();
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	private function rate_transient_key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
		return 'sch_pub_shorten_' . md5( $ip );
	}

	/**
	 * Enqueue public assets.
	 */
	public function enqueue_assets(): void {
		$ver = SEO_CAMPAIGN_HUB_VERSION;
		$url = SEO_CAMPAIGN_HUB_PLUGIN_URL;

		wp_enqueue_style(
			'sch-public-url-shortener',
			$url . 'assets/public/css/public-url-shortener.css',
			[],
			$ver
		);
		wp_enqueue_script(
			'sch-public-url-shortener',
			$url . 'assets/public/js/public-url-shortener.js',
			[],
			$ver,
			true
		);
		wp_localize_script(
			'sch-public-url-shortener',
			'schPublicShortener',
			[
				'restUrl'   => esc_url_raw( rest_url( 'seo-campaign-hub/v1' . self::REST_ROUTE ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'sameSite'  => $this->is_same_site_only(),
				'i18n'      => [
					'placeholder' => __( 'https://example.com/your-long-url', 'seo-campaign-hub' ),
					'submit'      => __( 'Shorten URL', 'seo-campaign-hub' ),
					'working'     => __( 'Creating…', 'seo-campaign-hub' ),
					'copy'        => __( 'Copy', 'seo-campaign-hub' ),
					'copied'      => __( 'Copied!', 'seo-campaign-hub' ),
					'empty'       => __( 'Please enter a URL.', 'seo-campaign-hub' ),
					'invalid'     => __( 'Please enter a valid http(s) URL.', 'seo-campaign-hub' ),
					'error'       => __( 'Could not create the short link.', 'seo-campaign-hub' ),
					'ready'       => __( 'Your short link is ready.', 'seo-campaign-hub' ),
				],
			]
		);
	}
}

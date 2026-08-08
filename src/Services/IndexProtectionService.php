<?php
/**
 * Block search indexing of WordPress core directory listings (SimplePie, etc.).
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IndexProtectionService
 */
class IndexProtectionService {

	public const OPTION_KEY = 'block_core_directory_listing';

	public const HTACCESS_MARKER = 'SEO Campaign Hub Index Protection';

	/**
	 * Library folders under wp-includes that must not be browsed or indexed.
	 *
	 * @return array<int, string>
	 */
	public static function blocked_wp_includes_dirs(): array {
		return [
			'SimplePie',
			'ID3',
			'IXR',
			'PHPMailer',
			'Requests',
			'Text',
			'sodium_compat',
			'rest-api',
		];
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'robots_txt', [ $this, 'filter_robots_txt' ], 20, 2 );
		add_action( 'admin_init', [ $this, 'maybe_sync_htaccess' ], 30 );
		add_action( 'update_option_seo_campaign_hub_options', [ $this, 'on_options_updated' ], 10, 2 );
	}

	/**
	 * Whether protection is enabled (default on).
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$options = get_option( 'seo_campaign_hub_options', [] );
		if ( ! is_array( $options ) || ! array_key_exists( self::OPTION_KEY, $options ) ) {
			return true;
		}
		return (string) $options[ self::OPTION_KEY ] === '1';
	}

	/**
	 * Append Disallow rules to virtual robots.txt.
	 *
	 * @param string $output Existing robots.txt body.
	 * @param bool   $public blog_public.
	 * @return string
	 */
	public function filter_robots_txt( string $output, bool $public ): string {
		if ( ! $public || ! $this->is_enabled() ) {
			return $output;
		}

		$lines   = [ '', '# SEO Campaign Hub — hide WP core library directories' ];
		$home    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$prefix  = is_string( $home ) ? untrailingslashit( $home ) : '';

		foreach ( self::blocked_wp_includes_dirs() as $dir ) {
			$lines[] = 'Disallow: ' . $prefix . '/wp-includes/' . $dir . '/';
		}

		return rtrim( $output ) . "\n" . implode( "\n", $lines ) . "\n";
	}

	/**
	 * Sync .htaccess after settings save.
	 *
	 * @param mixed $old_value Previous option.
	 * @param mixed $value     New option.
	 * @return void
	 */
	public function on_options_updated( $old_value, $value ): void {
		unset( $old_value, $value );
		$this->sync_htaccess();
	}

	/**
	 * Write or remove Apache rules when needed.
	 *
	 * @return void
	 */
	public function maybe_sync_htaccess(): void {
		$this->sync_htaccess();
	}

	/**
	 * Apply or remove marker block in site .htaccess.
	 *
	 * @return bool
	 */
	public function sync_htaccess(): bool {
		if ( $this->is_enabled() ) {
			return self::write_htaccess_rules();
		}
		return self::remove_htaccess_rules();
	}

	/**
	 * @return bool
	 */
	public static function write_htaccess_rules(): bool {
		$path = self::htaccess_path();
		if ( $path === '' || ! self::load_htaccess_api() ) {
			return false;
		}

		$rules = self::build_htaccess_rules();
		return (bool) insert_with_markers( $path, self::HTACCESS_MARKER, $rules );
	}

	/**
	 * @return bool
	 */
	public static function remove_htaccess_rules(): bool {
		$path = self::htaccess_path();
		if ( $path === '' || ! self::load_htaccess_api() ) {
			return false;
		}
		if ( ! file_exists( $path ) ) {
			return true;
		}
		return (bool) insert_with_markers( $path, self::HTACCESS_MARKER, [] );
	}

	/**
	 * @return array<int, string>
	 */
	public static function build_htaccess_rules(): array {
		$lines = [
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
		];
		foreach ( array_unique( self::blocked_wp_includes_dirs() ) as $dir ) {
			$lines[] = 'RewriteRule ^wp-includes/' . $dir . '/ - [F,L]';
		}
		$lines[] = '</IfModule>';
		return $lines;
	}

	/**
	 * @return string Absolute .htaccess path or empty.
	 */
	public static function htaccess_path(): string {
		if ( function_exists( 'get_home_path' ) ) {
			$home = get_home_path();
			if ( is_string( $home ) && $home !== '' ) {
				return $home . '.htaccess';
			}
		}

		if ( defined( 'ABSPATH' ) ) {
			return ABSPATH . '.htaccess';
		}

		return '';
	}

	/**
	 * @return bool
	 */
	private static function load_htaccess_api(): bool {
		if ( function_exists( 'insert_with_markers' ) ) {
			return true;
		}
		$misc = ABSPATH . 'wp-admin/includes/misc.php';
		if ( ! is_readable( $misc ) ) {
			return false;
		}
		require_once $misc;
		return function_exists( 'insert_with_markers' );
	}
}

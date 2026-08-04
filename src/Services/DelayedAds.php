<?php
/**
 * Delayed / lazy AdSense loading helpers.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DelayedAds
 */
class DelayedAds {

	public const ENABLE_KEY = 'enable_delay_adsense';
	public const DELAY_KEY  = 'adsense_load_delay';

	/**
	 * @return array<string, mixed>
	 */
	private static function get_options(): array {
		$options = get_option( 'seo_campaign_hub_options', [] );
		return is_array( $options ) ? $options : [];
	}

	public static function is_enabled(): bool {
		$o = self::get_options();
		if ( ! array_key_exists( self::ENABLE_KEY, $o ) ) {
			return true; // default on
		}
		return (string) $o[ self::ENABLE_KEY ] === '1';
	}

	/**
	 * Delay in seconds (1–10).
	 */
	public static function get_delay_seconds(): int {
		$o     = self::get_options();
		$delay = isset( $o[ self::DELAY_KEY ] ) ? absint( $o[ self::DELAY_KEY ] ) : 2;
		if ( $delay < 1 ) {
			$delay = 2;
		}
		return min( 10, $delay );
	}

	public static function get_delay_ms(): int {
		return self::get_delay_seconds() * 1000;
	}

	/**
	 * Whether a script blob looks like AdSense / Google ads.
	 */
	public static function looks_like_ads( string $html ): bool {
		$html = strtolower( $html );
		return (
			str_contains( $html, 'adsbygoogle' )
			|| str_contains( $html, 'googlesyndication' )
			|| str_contains( $html, 'pagead2' )
			|| str_contains( $html, 'doubleclick.net' )
			|| str_contains( $html, 'googletag' )
		);
	}

	/**
	 * Enqueue delayed-ads front script when needed.
	 *
	 * @param array<string, mixed> $extra Extra localize data.
	 */
	public static function enqueue_script( array $extra = [] ): void {
		static $done = false;
		if ( $done || ! self::is_enabled() ) {
			return;
		}
		$done = true;

		$ver = defined( 'SEO_CAMPAIGN_HUB_VERSION' ) ? SEO_CAMPAIGN_HUB_VERSION : '1.0.0';
		$url = defined( 'SEO_CAMPAIGN_HUB_PLUGIN_URL' ) ? SEO_CAMPAIGN_HUB_PLUGIN_URL : '';

		wp_enqueue_script(
			'sch-delayed-ads',
			$url . 'assets/public/js/delayed-ads.js',
			[],
			$ver,
			true
		);
		wp_localize_script(
			'sch-delayed-ads',
			'schDelayedAds',
			array_merge(
				[
					'delayMs'    => self::get_delay_ms(),
					'rootMargin' => '400px 0px',
				],
				$extra
			)
		);
	}
}

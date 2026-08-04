<?php
/**
 * Shared ad slots for public tool standalone pages.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PublicToolsAds
 */
class PublicToolsAds {

	public const ABOVE_KEY = 'tools_ad_above';
	public const BELOW_KEY = 'tools_ad_below';

	/**
	 * @return array<string, mixed>
	 */
	private static function get_options(): array {
		$options = get_option( 'seo_campaign_hub_options', [] );
		return is_array( $options ) ? $options : [];
	}

	/**
	 * @param string $key Option key.
	 */
	public static function get_slot_html( string $key ): string {
		$options = self::get_options();
		$html    = isset( $options[ $key ] ) ? trim( (string) $options[ $key ] ) : '';
		return $html;
	}

	/**
	 * Echo a slot wrapper when content exists.
	 *
	 * @param string $position above|below.
	 */
	public static function render( string $position ): void {
		$key = $position === 'below' ? self::BELOW_KEY : self::ABOVE_KEY;
		$html = self::get_slot_html( $key );
		if ( $html === '' ) {
			return;
		}

		$class = $position === 'below' ? 'sch-tools-ad sch-tools-ad--below' : 'sch-tools-ad sch-tools-ad--above';
		echo '<aside class="' . esc_attr( $class ) . '" aria-label="' . esc_attr__( 'Advertisement', 'seo-campaign-hub' ) . '">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-only settings code (AdSense / affiliate HTML).
		echo $html;
		echo '</aside>';
	}
}

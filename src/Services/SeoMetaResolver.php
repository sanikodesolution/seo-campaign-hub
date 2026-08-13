<?php
/**
 * Pure SEO title / description resolution.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SeoMetaResolver
 */
class SeoMetaResolver {

	public const META_TITLE = '_seo_campaign_hub_meta_title';

	public const META_DESCRIPTION = '_seo_campaign_hub_meta_description';

	public const TITLE_MAX = 200;

	public const DESCRIPTION_MAX = 500;

	public const TITLE_SOFT_LIMIT = 60;

	public const DESCRIPTION_SOFT_LIMIT = 160;

	/**
	 * Resolve title and description for a request context.
	 *
	 * @param array<string, mixed> $input Resolver input.
	 * @return array{title: string, description: string, title_is_custom: bool}
	 */
	public function resolve( array $input ): array {
		$context             = (string) ( $input['context'] ?? 'singular' );
		$custom_title        = $this->clean( $input['custom_title'] ?? '' );
		$custom_description  = $this->clean( $input['custom_description'] ?? '' );
		$homepage_title      = $this->clean( $input['homepage_title'] ?? '' );
		$homepage_description = $this->clean( $input['homepage_description'] ?? '' );
		$post_title          = $this->clean( $input['post_title'] ?? '' );
		$excerpt             = $this->clean( $input['excerpt'] ?? '' );
		$content             = $this->clean( $input['content'] ?? '' );
		$site_name           = $this->clean( $input['site_name'] ?? '' );
		$tagline             = $this->clean( $input['tagline'] ?? '' );

		if ( $context === 'front_posts' ) {
			return [
				'title'           => $homepage_title !== '' ? $homepage_title : $site_name,
				'description'     => $homepage_description !== '' ? $homepage_description : $tagline,
				'title_is_custom' => $homepage_title !== '',
			];
		}

		if ( $context === 'front_page' ) {
			$title = $custom_title !== '' ? $custom_title : ( $homepage_title !== '' ? $homepage_title : $post_title );
			$description = $custom_description !== ''
				? $custom_description
				: ( $homepage_description !== '' ? $homepage_description : $this->fallback_description( $excerpt, $content ) );

			return [
				'title'           => $title,
				'description'     => $description,
				'title_is_custom' => $custom_title !== '' || $homepage_title !== '',
			];
		}

		return [
			'title'           => $custom_title !== '' ? $custom_title : $post_title,
			'description'     => $custom_description !== '' ? $custom_description : $this->fallback_description( $excerpt, $content ),
			'title_is_custom' => $custom_title !== '',
		];
	}

	/**
	 * Cap a string to a maximum number of Unicode characters.
	 *
	 * @param string $value Raw value.
	 * @param int    $max   Max length.
	 * @return string
	 */
	public static function limit_length( string $value, int $max ): string {
		$value = trim( $value );
		if ( $max < 1 ) {
			return $value;
		}
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $value ) <= $max ) {
				return $value;
			}
			return mb_substr( $value, 0, $max );
		}
		if ( strlen( $value ) <= $max ) {
			return $value;
		}
		return substr( $value, 0, $max );
	}

	/**
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function clean( $value ): string {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}
		$text = trim( (string) $value );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return is_string( $text ) ? trim( $text ) : '';
	}

	/**
	 * @param string $excerpt Post excerpt.
	 * @param string $content Post content.
	 * @return string
	 */
	private function fallback_description( string $excerpt, string $content ): string {
		$excerpt_text = $this->strip_tags_text( $excerpt );
		if ( $excerpt_text !== '' ) {
			return $excerpt_text;
		}
		return $this->trim_description( $this->strip_tags_text( $content ) );
	}

	/**
	 * @param string $html HTML or text.
	 * @return string
	 */
	private function strip_tags_text( string $html ): string {
		$text = html_entity_decode( strip_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return $this->clean( $text );
	}

	/**
	 * @param string $text Plain text.
	 * @return string
	 */
	private function trim_description( string $text ): string {
		$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $words ) || $words === [] ) {
			return '';
		}
		$trimmed = false;
		if ( count( $words ) > 25 ) {
			$words   = array_slice( $words, 0, 25 );
			$trimmed = true;
		}
		$text = implode( ' ', $words );
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $text ) > self::DESCRIPTION_SOFT_LIMIT ) {
			$text    = rtrim( mb_substr( $text, 0, self::DESCRIPTION_SOFT_LIMIT - 3 ) );
			$trimmed = true;
		} elseif ( strlen( $text ) > self::DESCRIPTION_SOFT_LIMIT ) {
			$text    = rtrim( substr( $text, 0, self::DESCRIPTION_SOFT_LIMIT - 3 ) );
			$trimmed = true;
		}
		if ( $trimmed ) {
			$text .= '...';
		}
		return $text;
	}
}

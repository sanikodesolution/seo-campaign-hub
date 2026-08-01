<?php
/**
 * Browser-based social share URL builders (no OAuth).
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SocialShareService
 */
class SocialShareService {

	/**
	 * Network id => label + dashicon (empty dashicon = custom CSS icon).
	 *
	 * @return array<string, array{label:string,dashicon:string}>
	 */
	public function get_networks(): array {
		return [
			'facebook'  => [
				'label'    => __( 'Share on Facebook', 'seo-campaign-hub' ),
				'dashicon' => 'dashicons-facebook-alt',
			],
			'x'         => [
				'label'    => __( 'Share on X', 'seo-campaign-hub' ),
				'dashicon' => 'dashicons-twitter',
			],
			'linkedin'  => [
				'label'    => __( 'Share on LinkedIn', 'seo-campaign-hub' ),
				'dashicon' => 'dashicons-linkedin',
			],
			'pinterest' => [
				'label'    => __( 'Share on Pinterest', 'seo-campaign-hub' ),
				'dashicon' => '',
			],
			'whatsapp'  => [
				'label'    => __( 'Share on WhatsApp', 'seo-campaign-hub' ),
				'dashicon' => '',
			],
			'email'     => [
				'label'    => __( 'Share by Email', 'seo-campaign-hub' ),
				'dashicon' => 'dashicons-email',
			],
			'copy'      => [
				'label'    => __( 'Copy link', 'seo-campaign-hub' ),
				'dashicon' => 'dashicons-admin-page',
			],
		];
	}

	/**
	 * Whether admin post share actions are enabled.
	 */
	public function is_enabled(): bool {
		$options = get_option( 'seo_campaign_hub_options', [] );
		if ( ! is_array( $options ) ) {
			return true;
		}
		if ( ! array_key_exists( 'enable_admin_social_share', $options ) ) {
			return true;
		}
		return (string) $options['enable_admin_social_share'] === '1';
	}

	/**
	 * Enabled network keys (subset of get_networks keys).
	 *
	 * @return array<int, string>
	 */
	public function get_enabled_networks(): array {
		$all     = array_keys( $this->get_networks() );
		$options = get_option( 'seo_campaign_hub_options', [] );
		if ( ! is_array( $options ) || empty( $options['social_share_networks'] ) || ! is_array( $options['social_share_networks'] ) ) {
			return $all;
		}
		$enabled = array_values( array_intersect( $all, array_map( 'strval', $options['social_share_networks'] ) ) );
		return $enabled !== [] ? $enabled : $all;
	}

	/**
	 * Whether this post can show share actions.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function can_share_post( \WP_Post $post ): bool {
		if ( $post->post_type !== 'post' ) {
			return false;
		}
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return false;
		}
		if ( ! $this->is_enabled() ) {
			return false;
		}
		$status = get_post_status( $post );
		return in_array( $status, [ 'publish', 'future' ], true );
	}

	/**
	 * Build share URLs for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, string> network => url (copy uses permalink)
	 */
	public function get_share_urls( int $post_id ): array {
		$url   = get_permalink( $post_id );
		$title = get_the_title( $post_id );
		if ( ! is_string( $url ) || $url === '' ) {
			return [];
		}

		$encoded_url   = rawurlencode( $url );
		$encoded_title = rawurlencode( wp_strip_all_tags( $title ) );
		$image         = get_the_post_thumbnail_url( $post_id, 'full' );
		$encoded_image = is_string( $image ) && $image !== '' ? rawurlencode( $image ) : '';

		$urls = [
			'facebook'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url,
			'x'         => 'https://x.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title,
			'linkedin'  => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url,
			'pinterest' => 'https://www.pinterest.com/pin/create/button/?url=' . $encoded_url . '&description=' . $encoded_title . ( $encoded_image !== '' ? '&media=' . $encoded_image : '' ),
			'whatsapp'  => 'https://api.whatsapp.com/send?text=' . rawurlencode( wp_strip_all_tags( $title ) . ' ' . $url ),
			'email'     => 'mailto:?subject=' . $encoded_title . '&body=' . $encoded_url,
			'copy'      => $url,
		];

		$out = [];
		foreach ( $this->get_enabled_networks() as $network ) {
			if ( isset( $urls[ $network ] ) ) {
				$out[ $network ] = $urls[ $network ];
			}
		}
		return $out;
	}

	/**
	 * HTML snippets for post_row_actions (keyed for stable order).
	 *
	 * @param \WP_Post $post Post.
	 * @return array<string, string>
	 */
	public function get_row_action_links( \WP_Post $post ): array {
		if ( ! $this->can_share_post( $post ) ) {
			return [];
		}

		$networks = $this->get_networks();
		$urls     = $this->get_share_urls( (int) $post->ID );
		$actions  = [];

		foreach ( $urls as $network => $share_url ) {
			$meta  = $networks[ $network ] ?? null;
			if ( ! $meta ) {
				continue;
			}
			$label = $meta['label'];
			$icon  = $this->render_icon_html( $network, $meta['dashicon'] );

			if ( $network === 'copy' ) {
				$actions[ 'sch_share_' . $network ] = sprintf(
					'<a href="#" class="sch-share-action sch-share-copy" data-url="%s" aria-label="%s">%s<span class="screen-reader-text">%s</span></a>',
					esc_attr( $share_url ),
					esc_attr( $label ),
					$icon,
					esc_html( $label )
				);
				continue;
			}

			$actions[ 'sch_share_' . $network ] = sprintf(
				'<a class="sch-share-action sch-share-%s" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s<span class="screen-reader-text">%s</span></a>',
				esc_attr( $network ),
				esc_url( $share_url ),
				esc_attr( $label ),
				$icon,
				esc_html( $label )
			);
		}

		return $actions;
	}

	/**
	 * Icon markup.
	 *
	 * @param string $network  Network id.
	 * @param string $dashicon Dashicon class or empty.
	 */
	private function render_icon_html( string $network, string $dashicon ): string {
		if ( $dashicon !== '' ) {
			return '<span class="dashicons ' . esc_attr( $dashicon ) . '" aria-hidden="true"></span>';
		}
		$letter = $network === 'pinterest' ? 'P' : ( $network === 'whatsapp' ? 'W' : '?' );
		return '<span class="sch-share-letter sch-share-letter--' . esc_attr( $network ) . '" aria-hidden="true">' . esc_html( $letter ) . '</span>';
	}
}

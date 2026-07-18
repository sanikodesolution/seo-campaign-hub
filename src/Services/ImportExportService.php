<?php
/**
 * Import/Export Service
 *
 * CPT-aware JSON backup and restore for campaigns, offers, links, and settings.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ImportExportService
 */
class ImportExportService {

	/**
	 * Allowed export/import type keys.
	 *
	 * @var string[]
	 */
	private $types = [ 'all', 'campaigns', 'offers', 'links', 'analytics', 'settings' ];

	/**
	 * Settings option keys that may be imported.
	 *
	 * @var string[]
	 */
	private $settings_whitelist = [
		'seo_campaign_hub_enable_analytics',
		'seo_campaign_hub_ignore_bots',
		'seo_campaign_hub_anonymize_ip',
		'seo_campaign_hub_enable_shortener',
		'seo_campaign_hub_shortener_prefix',
		'seo_campaign_hub_shortener_slug_length',
		'seo_campaign_hub_default_redirect_type',
		'seo_campaign_hub_analytics_retention',
		'seo_campaign_hub_analytics_retention_days',
		'seo_campaign_hub_enable_qr_codes',
		'seo_campaign_hub_enable_schema',
		'seo_campaign_hub_options',
	];

	/**
	 * Export data as pretty JSON string.
	 *
	 * @param string               $type Export type.
	 * @param array<string, mixed> $args Extra args (e.g. analytics limit).
	 * @return string
	 */
	public function export_data( $type = 'all', $args = [] ) {
		$type = $this->normalize_type( $type );
		$data = [
			'format'      => 'seo-campaign-hub-backup',
			'version'     => defined( 'SEO_CAMPAIGN_HUB_VERSION' ) ? SEO_CAMPAIGN_HUB_VERSION : '1.0.0',
			'exported_at' => current_time( 'mysql' ),
			'site_url'    => home_url( '/' ),
		];

		switch ( $type ) {
			case 'campaigns':
				$data['campaigns'] = $this->export_campaign_posts();
				break;
			case 'offers':
				$data['offers'] = $this->export_offer_posts();
				break;
			case 'links':
				$data['links'] = $this->export_links();
				break;
			case 'analytics':
				$data['analytics'] = $this->export_analytics( $args );
				break;
			case 'settings':
				$data['settings'] = $this->export_settings();
				break;
			case 'all':
			default:
				$data['campaigns'] = $this->export_campaign_posts();
				$data['offers']    = $this->export_offer_posts();
				$data['links']     = $this->export_links();
				$data['settings']  = $this->export_settings();
				break;
		}

		return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Import JSON backup string.
	 *
	 * @param string               $json JSON payload.
	 * @param array<string, mixed> $args Import args: conflict=update|skip, include_settings=bool.
	 * @return array<string, mixed>
	 */
	public function import_data( $json, $args = [] ) {
		$data = json_decode( (string) $json, true );

		if ( ! is_array( $data ) ) {
			return [
				'success'  => false,
				'imported' => 0,
				'updated'  => 0,
				'skipped'  => 0,
				'failed'   => 0,
				'errors'   => [ __( 'Invalid JSON data format.', 'seo-campaign-hub' ) ],
				'message'  => __( 'Invalid JSON data format.', 'seo-campaign-hub' ),
			];
		}

		$args = wp_parse_args(
			$args,
			[
				'conflict'         => 'update',
				'include_settings' => false,
			]
		);

		$results = [
			'success'  => true,
			'imported' => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'errors'   => [],
		];

		if ( ! empty( $data['campaigns'] ) && is_array( $data['campaigns'] ) ) {
			$this->import_campaign_posts( $data['campaigns'], $args, $results );
		}

		if ( ! empty( $data['offers'] ) && is_array( $data['offers'] ) ) {
			$this->import_offer_posts( $data['offers'], $args, $results );
		}

		if ( ! empty( $data['links'] ) && is_array( $data['links'] ) ) {
			$this->import_links( $data['links'], $args, $results );
		}

		if ( ! empty( $args['include_settings'] ) && ! empty( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$this->import_settings( $data['settings'], $results );
		}

		$results['message'] = sprintf(
			/* translators: 1: imported, 2: updated, 3: skipped, 4: failed */
			__( 'Import finished. Imported: %1$d, Updated: %2$d, Skipped: %3$d, Failed: %4$d.', 'seo-campaign-hub' ),
			$results['imported'],
			$results['updated'],
			$results['skipped'],
			$results['failed']
		);

		return $results;
	}

	/**
	 * Export campaign CPT posts.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function export_campaign_posts() {
		$posts = get_posts(
			[
				'post_type'      => 'sch_campaign',
				'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future' ],
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);

		$out = [];
		foreach ( $posts as $post ) {
			$out[] = [
				'type'         => 'sch_campaign',
				'title'        => $post->post_title,
				'slug'         => $post->post_name,
				'content'      => $post->post_content,
				'excerpt'      => $post->post_excerpt,
				'status'       => $post->post_status,
				'categories'   => wp_get_post_terms( $post->ID, 'sch_campaign_category', [ 'fields' => 'names' ] ),
				'tags'         => wp_get_post_terms( $post->ID, 'sch_campaign_tag', [ 'fields' => 'names' ] ),
				'featured_url' => get_the_post_thumbnail_url( $post->ID, 'full' ) ?: '',
			];
		}

		return $out;
	}

	/**
	 * Export offer CPT posts.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function export_offer_posts() {
		$posts = get_posts(
			[
				'post_type'      => 'sch_offer',
				'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future' ],
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);

		$out = [];
		foreach ( $posts as $post ) {
			$out[] = [
				'type'         => 'sch_offer',
				'title'        => $post->post_title,
				'slug'         => $post->post_name,
				'content'      => $post->post_content,
				'excerpt'      => $post->post_excerpt,
				'status'       => $post->post_status,
				'categories'   => wp_get_post_terms( $post->ID, 'sch_offer_category', [ 'fields' => 'names' ] ),
				'featured_url' => get_the_post_thumbnail_url( $post->ID, 'full' ) ?: '',
			];
		}

		return $out;
	}

	/**
	 * Export short links from custom table.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function export_links() {
		global $wpdb;
		$table = $wpdb->prefix . 'sch_links';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return [];
		}

		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$out = [];
		foreach ( $rows as $row ) {
			$out[] = [
				'link_key'         => $row['link_key'] ?? '',
				'slug'             => $row['slug'] ?? '',
				'destination_url'  => $row['destination_url'] ?? '',
				'short_url'        => $row['short_url'] ?? '',
				'title'            => $row['title'] ?? '',
				'description'      => $row['description'] ?? '',
				'link_type'        => $row['link_type'] ?? 'direct',
				'redirect_type'    => $row['redirect_type'] ?? '301',
				'is_active'        => isset( $row['is_active'] ) ? (int) $row['is_active'] : 1,
				'is_public'        => isset( $row['is_public'] ) ? (int) $row['is_public'] : 1,
				'utm_source'       => $row['utm_source'] ?? '',
				'utm_medium'       => $row['utm_medium'] ?? '',
				'utm_campaign'     => $row['utm_campaign'] ?? '',
				'utm_term'         => $row['utm_term'] ?? '',
				'utm_content'      => $row['utm_content'] ?? '',
				'campaign_id'      => $row['campaign_id'] ?? null,
				'offer_id'         => $row['offer_id'] ?? null,
				'expires_at'       => $row['expires_at'] ?? null,
			];
		}

		return $out;
	}

	/**
	 * Export analytics rows (export-only).
	 *
	 * @param array<string, mixed> $args Args.
	 * @return array<int, array<string, mixed>>
	 */
	private function export_analytics( $args = [] ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sch_analytics';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return [];
		}

		$limit = isset( $args['limit'] ) ? max( 1, min( 5000, (int) $args['limit'] ) ) : 1000;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, event_name, campaign_id, offer_id, link_id, post_id, landing_page,
					device_type, utm_source, utm_medium, utm_campaign, time_on_page, scroll_depth,
					conversion_amount, created_at
				FROM {$table}
				ORDER BY created_at DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Export allowlisted settings.
	 *
	 * @return array<string, mixed>
	 */
	private function export_settings() {
		$settings = [];
		foreach ( $this->settings_whitelist as $option ) {
			$value = get_option( $option, null );
			if ( null !== $value ) {
				$settings[ $option ] = $value;
			}
		}
		return $settings;
	}

	/**
	 * Import campaign posts.
	 *
	 * @param array                $items   Items.
	 * @param array<string, mixed> $args    Args.
	 * @param array<string, mixed> $results Results ref.
	 * @return void
	 */
	private function import_campaign_posts( $items, $args, &$results ) {
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$this->upsert_post(
				'sch_campaign',
				$item,
				[
					'category_tax' => 'sch_campaign_category',
					'tag_tax'      => 'sch_campaign_tag',
				],
				$args,
				$results
			);
		}
	}

	/**
	 * Import offer posts.
	 *
	 * @param array                $items   Items.
	 * @param array<string, mixed> $args    Args.
	 * @param array<string, mixed> $results Results ref.
	 * @return void
	 */
	private function import_offer_posts( $items, $args, &$results ) {
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$this->upsert_post(
				'sch_offer',
				$item,
				[
					'category_tax' => 'sch_offer_category',
				],
				$args,
				$results
			);
		}
	}

	/**
	 * Create or update a CPT from backup item.
	 *
	 * @param string               $post_type Post type.
	 * @param array<string, mixed> $item      Item.
	 * @param array<string, mixed> $tax_map   Taxonomy map.
	 * @param array<string, mixed> $args      Import args.
	 * @param array<string, mixed> $results   Results ref.
	 * @return void
	 */
	private function upsert_post( $post_type, $item, $tax_map, $args, &$results ) {
		$title   = sanitize_text_field( (string) ( $item['title'] ?? $item['post_title'] ?? '' ) );
		$slug    = sanitize_title( (string) ( $item['slug'] ?? $item['post_name'] ?? $title ) );
		$content = wp_kses_post( (string) ( $item['content'] ?? $item['post_content'] ?? $item['description'] ?? '' ) );
		$excerpt = sanitize_textarea_field( (string) ( $item['excerpt'] ?? $item['post_excerpt'] ?? $item['short_description'] ?? '' ) );
		$status  = sanitize_key( (string) ( $item['status'] ?? $item['post_status'] ?? 'draft' ) );

		$allowed_status = [ 'publish', 'draft', 'pending', 'private', 'future' ];
		if ( ! in_array( $status, $allowed_status, true ) ) {
			$status = 'draft';
		}

		if ( '' === $title ) {
			$results['failed']++;
			$results['errors'][] = __( 'Skipped an item with empty title.', 'seo-campaign-hub' );
			return;
		}

		$existing = $slug ? get_page_by_path( $slug, OBJECT, $post_type ) : null;

		if ( $existing instanceof \WP_Post ) {
			if ( ( $args['conflict'] ?? 'update' ) === 'skip' ) {
				$results['skipped']++;
				return;
			}

			$post_id = wp_update_post(
				[
					'ID'           => $existing->ID,
					'post_title'   => $title,
					'post_content' => $content,
					'post_excerpt' => $excerpt,
					'post_status'  => $status,
					'post_name'    => $slug,
				],
				true
			);

			if ( is_wp_error( $post_id ) ) {
				$results['failed']++;
				$results['errors'][] = $post_id->get_error_message();
				return;
			}

			$this->assign_terms( $post_id, $item, $tax_map );
			$results['updated']++;
			return;
		}

		$post_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'post_excerpt' => $excerpt,
				'post_status'  => $status,
				'post_type'    => $post_type,
				'post_name'    => $slug,
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$results['failed']++;
			$results['errors'][] = $post_id->get_error_message();
			return;
		}

		$this->assign_terms( $post_id, $item, $tax_map );
		$results['imported']++;
	}

	/**
	 * Assign taxonomy terms from backup item.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $item    Item.
	 * @param array<string, mixed> $tax_map Tax map.
	 * @return void
	 */
	private function assign_terms( $post_id, $item, $tax_map ) {
		if ( ! empty( $tax_map['category_tax'] ) && ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
			$names = array_map( 'sanitize_text_field', $item['categories'] );
			wp_set_object_terms( $post_id, $names, $tax_map['category_tax'], false );
		}
		if ( ! empty( $tax_map['tag_tax'] ) && ! empty( $item['tags'] ) && is_array( $item['tags'] ) ) {
			$names = array_map( 'sanitize_text_field', $item['tags'] );
			wp_set_object_terms( $post_id, $names, $tax_map['tag_tax'], false );
		}
	}

	/**
	 * Import short links.
	 *
	 * @param array                $links   Links.
	 * @param array<string, mixed> $args    Args.
	 * @param array<string, mixed> $results Results ref.
	 * @return void
	 */
	private function import_links( $links, $args, &$results ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sch_links';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			$results['failed']++;
			$results['errors'][] = __( 'Short links table is missing. Reactivate the plugin.', 'seo-campaign-hub' );
			return;
		}

		$prefix = get_option( 'seo_campaign_hub_shortener_prefix', 'go' );

		foreach ( $links as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}

			$destination = esc_url_raw( (string) ( $link['destination_url'] ?? '' ) );
			$slug        = sanitize_title( (string) ( $link['slug'] ?? '' ) );

			if ( '' === $destination || '' === $slug ) {
				$results['failed']++;
				$results['errors'][] = __( 'Skipped a link with missing URL or slug.', 'seo-campaign-hub' );
				continue;
			}

			$link_key = sanitize_text_field( (string) ( $link['link_key'] ?? '' ) );
			if ( '' === $link_key ) {
				$link_key = 'link_' . uniqid();
			}

			$existing_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE slug = %s OR link_key = %s LIMIT 1",
					$slug,
					$link_key
				)
			);

			$row = [
				'link_key'        => $link_key,
				'slug'            => $slug,
				'destination_url' => $destination,
				'short_url'       => home_url( '/' . $prefix . '/' . $slug ),
				'title'           => sanitize_text_field( (string) ( $link['title'] ?? '' ) ),
				'description'     => sanitize_textarea_field( (string) ( $link['description'] ?? '' ) ),
				'link_type'       => sanitize_key( (string) ( $link['link_type'] ?? 'direct' ) ),
				'redirect_type'   => in_array( (string) ( $link['redirect_type'] ?? '301' ), [ '301', '302', '307' ], true )
					? (string) $link['redirect_type']
					: '301',
				'is_active'       => ! empty( $link['is_active'] ) ? 1 : 0,
				'is_public'       => isset( $link['is_public'] ) ? (int) (bool) $link['is_public'] : 1,
				'utm_source'      => sanitize_text_field( (string) ( $link['utm_source'] ?? '' ) ),
				'utm_medium'      => sanitize_text_field( (string) ( $link['utm_medium'] ?? '' ) ),
				'utm_campaign'    => sanitize_text_field( (string) ( $link['utm_campaign'] ?? '' ) ),
				'utm_term'        => sanitize_text_field( (string) ( $link['utm_term'] ?? '' ) ),
				'utm_content'     => sanitize_text_field( (string) ( $link['utm_content'] ?? '' ) ),
				'created_by'      => get_current_user_id() ?: 1,
			];

			if ( $existing_id ) {
				if ( ( $args['conflict'] ?? 'update' ) === 'skip' ) {
					$results['skipped']++;
					continue;
				}
				$updated = $wpdb->update( $table, $row, [ 'id' => (int) $existing_id ] );
				if ( false === $updated ) {
					$results['failed']++;
					$results['errors'][] = sprintf(
						/* translators: %s: slug */
						__( 'Failed to update link: %s', 'seo-campaign-hub' ),
						$slug
					);
				} else {
					$results['updated']++;
				}
				continue;
			}

			$inserted = $wpdb->insert( $table, $row );
			if ( $inserted ) {
				$results['imported']++;
			} else {
				$results['failed']++;
				$results['errors'][] = sprintf(
					/* translators: %s: slug */
					__( 'Failed to insert link: %s', 'seo-campaign-hub' ),
					$slug
				);
			}
		}
	}

	/**
	 * Import allowlisted settings.
	 *
	 * @param array                $settings Settings.
	 * @param array<string, mixed> $results  Results ref.
	 * @return void
	 */
	private function import_settings( $settings, &$results ) {
		$count = 0;
		foreach ( $settings as $key => $value ) {
			$key = (string) $key;
			if ( ! in_array( $key, $this->settings_whitelist, true ) ) {
				continue;
			}
			update_option( $key, $value );
			$count++;
		}
		if ( $count > 0 ) {
			$results['imported'] += $count;
		}
	}

	/**
	 * @param string $type Type.
	 * @return string
	 */
	private function normalize_type( $type ) {
		$type = sanitize_key( (string) $type );
		return in_array( $type, $this->types, true ) ? $type : 'all';
	}
}

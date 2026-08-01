<?php
/**
 * Image optimization: WebP alongside JPEG/PNG + optional front-end serving.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ImageOptimizationService
 */
class ImageOptimizationService {

	public const META_WEBP      = '_sch_webp_files';
	public const META_OPTIMIZED = '_sch_webp_optimized';
	public const OPTION_LAST    = 'seo_campaign_hub_image_opt_last';

	/**
	 * Register WordPress hooks.
	 */
	public function init(): void {
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'on_generate_metadata' ], 20, 2 );
		add_filter( 'wp_get_attachment_image_src', [ $this, 'filter_attachment_image_src' ], 20, 4 );
		add_filter( 'wp_calculate_image_srcset', [ $this, 'filter_image_srcset' ], 20, 5 );
		add_filter( 'the_content', [ $this, 'filter_content_images' ], 20 );
		add_filter( 'post_thumbnail_html', [ $this, 'filter_content_images' ], 20 );
	}

	/**
	 * @return array{engine:string,supports:bool}
	 */
	public function get_engine_status(): array {
		if ( class_exists( '\Imagick' ) ) {
			try {
				$formats = array_map( 'strtolower', \Imagick::queryFormats( 'WEBP' ) );
				if ( in_array( 'webp', $formats, true ) ) {
					return [ 'engine' => 'imagick', 'supports' => true ];
				}
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}
		if ( function_exists( 'imagewebp' ) && ( function_exists( 'imagecreatefromjpeg' ) || function_exists( 'imagecreatefrompng' ) ) ) {
			return [ 'engine' => 'gd', 'supports' => true ];
		}
		return [ 'engine' => 'none', 'supports' => false ];
	}

	public function supports_webp(): bool {
		return $this->get_engine_status()['supports'];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_options(): array {
		$options = get_option( 'seo_campaign_hub_options', [] );
		return is_array( $options ) ? $options : [];
	}

	public function is_auto_enabled(): bool {
		$o = $this->get_options();
		if ( ! array_key_exists( 'image_opt_auto_upload', $o ) ) {
			return true;
		}
		return (string) $o['image_opt_auto_upload'] === '1';
	}

	public function is_serve_enabled(): bool {
		$o = $this->get_options();
		if ( ! array_key_exists( 'image_opt_serve_webp', $o ) ) {
			return true;
		}
		return (string) $o['image_opt_serve_webp'] === '1';
	}

	public function get_quality(): int {
		$o = $this->get_options();
		$q = isset( $o['image_opt_quality'] ) ? (int) $o['image_opt_quality'] : 82;
		return max( 60, min( 90, $q ) );
	}

	/**
	 * Stats for admin UI.
	 *
	 * @return array{total:int,optimized:int,pending:int,last:array<string,string>}
	 */
	public function get_stats(): array {
		$total = (int) ( new \WP_Query(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => [ 'image/jpeg', 'image/png' ],
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => false,
			]
		) )->found_posts;

		$optimized = (int) ( new \WP_Query(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => [ 'image/jpeg', 'image/png' ],
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => false,
				'meta_query'     => [
					[
						'key'   => self::META_OPTIMIZED,
						'value' => '1',
					],
				],
			]
		) )->found_posts;

		$last = get_option( self::OPTION_LAST, [] );
		if ( ! is_array( $last ) ) {
			$last = [];
		}

		return [
			'total'     => $total,
			'optimized' => $optimized,
			'pending'   => max( 0, $total - $optimized ),
			'last'      => $last,
		];
	}

	/**
	 * Attachment IDs still needing WebP.
	 *
	 * @param int $limit Batch size.
	 * @return array<int, int>
	 */
	public function get_pending_ids( int $limit = 5 ): array {
		$q = new \WP_Query(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => [ 'image/jpeg', 'image/png' ],
				'fields'         => 'ids',
				'posts_per_page' => max( 1, $limit ),
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_query'     => [
					'relation' => 'AND',
					[
						'relation' => 'OR',
						[
							'key'     => self::META_OPTIMIZED,
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => self::META_OPTIMIZED,
							'value'   => '1',
							'compare' => '!=',
						],
					],
					[
						'relation' => 'OR',
						[
							'key'     => '_sch_webp_skip',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => '_sch_webp_skip',
							'value'   => '1',
							'compare' => '!=',
						],
					],
				],
			]
		);
		return array_map( 'intval', $q->posts );
	}

	/**
	 * After WP generates sizes, create WebP siblings when auto-optimize is on.
	 *
	 * @param array<string, mixed> $metadata      Metadata.
	 * @param int                  $attachment_id Attachment ID.
	 * @return array<string, mixed>
	 */
	public function on_generate_metadata( $metadata, $attachment_id ) {
		if ( ! $this->is_auto_enabled() || ! $this->supports_webp() ) {
			return $metadata;
		}
		$this->optimize_attachment( (int) $attachment_id, is_array( $metadata ) ? $metadata : [] );
		return $metadata;
	}

	/**
	 * Create WebP files for an attachment.
	 *
	 * @param int                  $attachment_id Attachment ID.
	 * @param array<string, mixed> $metadata      Optional metadata.
	 * @return array{success:bool,message:string,files:int}
	 */
	public function optimize_attachment( int $attachment_id, array $metadata = [] ): array {
		if ( ! $this->supports_webp() ) {
			return [ 'success' => false, 'message' => __( 'WebP not supported on this server.', 'seo-campaign-hub' ), 'files' => 0 ];
		}

		$mime = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime, [ 'image/jpeg', 'image/png' ], true ) ) {
			return [ 'success' => false, 'message' => __( 'Only JPEG and PNG are supported.', 'seo-campaign-hub' ), 'files' => 0 ];
		}

		if ( $metadata === [] ) {
			$metadata = wp_get_attachment_metadata( $attachment_id );
			if ( ! is_array( $metadata ) ) {
				$metadata = [];
			}
		}

		$file = get_attached_file( $attachment_id );
		if ( ! is_string( $file ) || ! is_readable( $file ) ) {
			return [ 'success' => false, 'message' => __( 'Original file not readable.', 'seo-campaign-hub' ), 'files' => 0 ];
		}

		$upload_dir = wp_upload_dir();
		$base_dir   = trailingslashit( $upload_dir['basedir'] );
		$rel_dir    = '';
		if ( ! empty( $metadata['file'] ) && is_string( $metadata['file'] ) ) {
			$rel_dir = trailingslashit( dirname( $metadata['file'] ) );
			if ( $rel_dir === './' ) {
				$rel_dir = '';
			}
		} else {
			$rel = ltrim( str_replace( $base_dir, '', $file ), '/\\' );
			$rel_dir = trailingslashit( dirname( $rel ) );
			if ( $rel_dir === './' ) {
				$rel_dir = '';
			}
		}

		$quality = $this->get_quality();
		$map     = [];
		$count   = 0;

		// Full size.
		$full_webp = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );
		if ( is_string( $full_webp ) && $this->create_webp_file( $file, $full_webp, $quality ) ) {
			$map['full'] = $rel_dir . wp_basename( $full_webp );
			++$count;
		}

		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size => $data ) {
				if ( empty( $data['file'] ) || ! is_string( $data['file'] ) ) {
					continue;
				}
				$src = $base_dir . $rel_dir . $data['file'];
				if ( ! is_readable( $src ) ) {
					continue;
				}
				$dest = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $src );
				if ( ! is_string( $dest ) ) {
					continue;
				}
				if ( $this->create_webp_file( $src, $dest, $quality ) ) {
					$map[ (string) $size ] = $rel_dir . wp_basename( $dest );
					++$count;
				}
			}
		}

		if ( $count > 0 ) {
			update_post_meta( $attachment_id, self::META_WEBP, $map );
			update_post_meta( $attachment_id, self::META_OPTIMIZED, '1' );
			return [
				'success' => true,
				'message' => sprintf(
					/* translators: %d: number of WebP files */
					__( 'Created %d WebP file(s).', 'seo-campaign-hub' ),
					$count
				),
				'files'   => $count,
			];
		}

		return [ 'success' => false, 'message' => __( 'Could not create WebP files.', 'seo-campaign-hub' ), 'files' => 0 ];
	}

	/**
	 * @param string $source  Source path.
	 * @param string $dest    Destination .webp path.
	 * @param int    $quality 60–90.
	 */
	public function create_webp_file( string $source, string $dest, int $quality ): bool {
		if ( is_readable( $dest ) ) {
			return true;
		}

		$engine = $this->get_engine_status()['engine'];

		if ( $engine === 'imagick' ) {
			try {
				$image = new \Imagick( $source );
				$image->setImageFormat( 'webp' );
				$image->setImageCompressionQuality( $quality );
				$ok = $image->writeImage( $dest );
				$image->clear();
				$image->destroy();
				return (bool) $ok && is_readable( $dest );
			} catch ( \Throwable $e ) {
				return false;
			}
		}

		if ( $engine !== 'gd' || ! function_exists( 'imagewebp' ) ) {
			return false;
		}

		$info = @getimagesize( $source ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! is_array( $info ) || empty( $info[2] ) ) {
			return false;
		}

		$type = (int) $info[2];
		$img  = null;
		if ( $type === IMAGETYPE_JPEG && function_exists( 'imagecreatefromjpeg' ) ) {
			$img = @imagecreatefromjpeg( $source ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		} elseif ( $type === IMAGETYPE_PNG && function_exists( 'imagecreatefrompng' ) ) {
			$img = @imagecreatefrompng( $source ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( $img ) {
				imagepalettetotruecolor( $img );
				imagealphablending( $img, true );
				imagesavealpha( $img, true );
			}
		}

		if ( ! $img ) {
			return false;
		}

		$ok = imagewebp( $img, $dest, $quality );
		imagedestroy( $img );
		return (bool) $ok && is_readable( $dest );
	}

	/**
	 * Whether the current request wants WebP.
	 */
	public function client_accepts_webp(): bool {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}
		if ( empty( $_SERVER['HTTP_ACCEPT'] ) || ! is_string( $_SERVER['HTTP_ACCEPT'] ) ) {
			return false;
		}
		return stripos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false;
	}

	/**
	 * Map original uploads URL to WebP URL if file exists.
	 */
	public function maybe_webp_url( string $url ): string {
		if ( ! $this->is_serve_enabled() || ! $this->client_accepts_webp() ) {
			return $url;
		}
		if ( ! preg_match( '/\.(jpe?g|png)(\?.*)?$/i', $url ) ) {
			return $url;
		}

		$webp_url = preg_replace( '/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $url );
		if ( ! is_string( $webp_url ) ) {
			return $url;
		}

		$upload = wp_upload_dir();
		if ( empty( $upload['baseurl'] ) || empty( $upload['basedir'] ) ) {
			return $url;
		}
		if ( strpos( $url, $upload['baseurl'] ) !== 0 ) {
			return $url;
		}

		$path = str_replace( $upload['baseurl'], $upload['basedir'], preg_replace( '/\?.*$/', '', $webp_url ) );
		if ( is_string( $path ) && is_readable( $path ) ) {
			return $webp_url;
		}
		return $url;
	}

	/**
	 * @param array<int, mixed>|false $image         Image data.
	 * @param int                     $attachment_id ID.
	 * @param string|int[]            $size          Size.
	 * @param bool                    $icon          Icon.
	 * @return array<int, mixed>|false
	 */
	public function filter_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		unset( $attachment_id, $size, $icon );
		if ( ! is_array( $image ) || empty( $image[0] ) || ! is_string( $image[0] ) ) {
			return $image;
		}
		$image[0] = $this->maybe_webp_url( $image[0] );
		return $image;
	}

	/**
	 * @param array<string, string> $sources Sources.
	 * @return array<string, string>
	 */
	public function filter_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		unset( $size_array, $image_src, $image_meta, $attachment_id );
		if ( ! is_array( $sources ) ) {
			return $sources;
		}
		foreach ( $sources as $width => $data ) {
			if ( ! empty( $data['url'] ) && is_string( $data['url'] ) ) {
				$sources[ $width ]['url'] = $this->maybe_webp_url( $data['url'] );
			}
		}
		return $sources;
	}

	/**
	 * Swap JPEG/PNG upload URLs in HTML when WebP exists.
	 *
	 * @param string $html HTML.
	 */
	public function filter_content_images( $html ): string {
		if ( ! is_string( $html ) || $html === '' || ! $this->is_serve_enabled() || ! $this->client_accepts_webp() ) {
			return is_string( $html ) ? $html : '';
		}

		$upload = wp_upload_dir();
		if ( empty( $upload['baseurl'] ) ) {
			return $html;
		}
		$base = preg_quote( $upload['baseurl'], '#' );

		return (string) preg_replace_callback(
			'#(' . $base . '/[^"\s>]+\.(?:jpe?g|png))#i',
			function ( $m ) {
				return $this->maybe_webp_url( $m[1] );
			},
			$html
		);
	}

	/**
	 * Persist last bulk run summary.
	 *
	 * @param array<string, string|int> $data Summary.
	 */
	public function save_last_run( array $data ): void {
		update_option(
			self::OPTION_LAST,
			[
				'time'      => gmdate( 'c' ),
				'processed' => (int) ( $data['processed'] ?? 0 ),
				'success'   => (int) ( $data['success'] ?? 0 ),
				'failed'    => (int) ( $data['failed'] ?? 0 ),
			],
			false
		);
	}
}

<?php
/**
 * Image Optimization admin view.
 *
 * @var string                                                    $page_title Page title.
 * @var \SEO_Campaign_Hub\Services\ImageOptimizationService|null $optimizer  Service.
 * @var string                                                    $notice     Notice key.
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$engine  = $optimizer ? $optimizer->get_engine_status() : [ 'engine' => 'none', 'supports' => false ];
$stats   = $optimizer ? $optimizer->get_stats() : [ 'total' => 0, 'optimized' => 0, 'pending' => 0, 'last' => [] ];
$options = get_option( 'seo_campaign_hub_options', [] );
if ( ! is_array( $options ) ) {
	$options = [];
}

$auto    = ! array_key_exists( 'image_opt_auto_upload', $options ) || (string) $options['image_opt_auto_upload'] === '1';
$serve   = ! array_key_exists( 'image_opt_serve_webp', $options ) || (string) $options['image_opt_serve_webp'] === '1';
$quality = isset( $options['image_opt_quality'] ) ? (int) $options['image_opt_quality'] : 82;
$quality = max( 60, min( 90, $quality ) );

$notice_key = isset( $notice ) ? (string) $notice : '';
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Image Optimization', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( $notice_key === 'saved' ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'seo-campaign-hub' ); ?></p></div>
	<?php endif; ?>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Status', 'seo-campaign-hub' ); ?></h2>
				<p>
					<?php if ( ! empty( $engine['supports'] ) ) : ?>
						<strong style="color:#155724"><?php esc_html_e( 'WebP supported', 'seo-campaign-hub' ); ?></strong>
						—
						<?php
						printf(
							/* translators: %s: engine name */
							esc_html__( 'Engine: %s', 'seo-campaign-hub' ),
							esc_html( (string) $engine['engine'] )
						);
						?>
					<?php else : ?>
						<strong style="color:#721c24"><?php esc_html_e( 'WebP not available', 'seo-campaign-hub' ); ?></strong>
						—
						<?php esc_html_e( 'Enable Imagick WebP or GD imagewebp on the server.', 'seo-campaign-hub' ); ?>
					<?php endif; ?>
				</p>
				<ul>
					<li><?php printf( esc_html__( 'JPEG/PNG attachments: %d', 'seo-campaign-hub' ), (int) $stats['total'] ); ?></li>
					<li><?php printf( esc_html__( 'Optimized: %d', 'seo-campaign-hub' ), (int) $stats['optimized'] ); ?></li>
					<li><?php printf( esc_html__( 'Pending: %d', 'seo-campaign-hub' ), (int) $stats['pending'] ); ?></li>
					<?php if ( ! empty( $stats['last']['time'] ) ) : ?>
						<li>
							<?php
							printf(
								/* translators: 1: datetime, 2: success count, 3: failed count */
								esc_html__( 'Last bulk run: %1$s (success %2$d, failed %3$d)', 'seo-campaign-hub' ),
								esc_html( (string) $stats['last']['time'] ),
								(int) ( $stats['last']['success'] ?? 0 ),
								(int) ( $stats['last']['failed'] ?? 0 )
							);
							?>
						</li>
					<?php endif; ?>
				</ul>
				<p class="description">
					<?php esc_html_e( 'Originals are kept. WebP files are created alongside JPEG/PNG and served to browsers that support WebP.', 'seo-campaign-hub' ); ?>
				</p>
			</div>

			<div class="sch-panel" style="margin-bottom:16px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Settings', 'seo-campaign-hub' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_image_opt_settings" />
					<?php wp_nonce_field( 'sch_image_opt_settings' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Auto-optimize on upload', 'seo-campaign-hub' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="image_opt_auto_upload" value="1" <?php checked( $auto ); ?> />
									<?php esc_html_e( 'Create WebP versions when new JPEG/PNG images are uploaded', 'seo-campaign-hub' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Serve WebP on front end', 'seo-campaign-hub' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="image_opt_serve_webp" value="1" <?php checked( $serve ); ?> />
									<?php esc_html_e( 'Replace image URLs with WebP when the browser supports it', 'seo-campaign-hub' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-image-opt-quality"><?php esc_html_e( 'WebP quality', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="number" id="sch-image-opt-quality" name="image_opt_quality" min="60" max="90" value="<?php echo esc_attr( (string) $quality ); ?>" class="small-text" />
								<p class="description"><?php esc_html_e( '60–90. Default 82.', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save settings', 'seo-campaign-hub' ) ); ?>
				</form>
			</div>

			<div class="sch-panel" style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
				<h2><?php esc_html_e( 'Bulk Optimize', 'seo-campaign-hub' ); ?></h2>
				<p><?php esc_html_e( 'Create WebP files for existing Media Library JPEG/PNG images that are not optimized yet.', 'seo-campaign-hub' ); ?></p>
				<p>
					<button type="button" class="button button-primary" id="sch-image-opt-bulk" <?php disabled( empty( $engine['supports'] ) || (int) $stats['pending'] === 0 ); ?>>
						<?php esc_html_e( 'Bulk Optimize', 'seo-campaign-hub' ); ?>
					</button>
				</p>
				<div id="sch-image-opt-progress" style="display:none;margin-top:12px">
					<progress id="sch-image-opt-bar" max="100" value="0" style="width:100%;max-width:420px"></progress>
					<p id="sch-image-opt-status" class="description"></p>
				</div>
			</div>

		</div>
	</div>
</div>

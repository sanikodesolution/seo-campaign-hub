<?php
/**
 * Import / Export View
 *
 * @var string               $page_title Page title.
 * @var string               $notice     Notice key.
 * @var string               $message    Optional message.
 * @var array<string, int>   $counts     Entity counts.
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notice  = isset( $notice ) ? (string) $notice : '';
$message = isset( $message ) ? (string) $message : '';
$counts  = ( isset( $counts ) && is_array( $counts ) ) ? $counts : [];

$notices = [
	'imported'      => [ 'success', $message ?: __( 'Import completed.', 'seo-campaign-hub' ) ],
	'import_failed' => [ 'error', $message ?: __( 'Import failed.', 'seo-campaign-hub' ) ],
	'missing_file'  => [ 'error', __( 'Please choose a JSON file to import.', 'seo-campaign-hub' ) ],
	'file_too_large'=> [ 'error', __( 'File is too large. Maximum size is 5 MB.', 'seo-campaign-hub' ) ],
	'invalid_type'  => [ 'error', __( 'Only .json files are allowed.', 'seo-campaign-hub' ) ],
	'empty_file'    => [ 'error', __( 'The uploaded file was empty or unreadable.', 'seo-campaign-hub' ) ],
];
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Import / Export', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
		<div class="seo-campaign-hub-notice <?php echo esc_attr( $notices[ $notice ][0] ); ?>">
			<?php echo esc_html( $notices[ $notice ][1] ); ?>
		</div>
	<?php endif; ?>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">

			<div class="seo-campaign-hub-dashboard-grid" style="margin-bottom:24px">
				<div class="seo-campaign-hub-stat-box">
					<div class="stat-number"><?php echo esc_html( number_format_i18n( (int) ( $counts['campaigns'] ?? 0 ) ) ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Campaigns', 'seo-campaign-hub' ); ?></div>
				</div>
				<div class="seo-campaign-hub-stat-box">
					<div class="stat-number"><?php echo esc_html( number_format_i18n( (int) ( $counts['offers'] ?? 0 ) ) ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Offers', 'seo-campaign-hub' ); ?></div>
				</div>
				<div class="seo-campaign-hub-stat-box">
					<div class="stat-number"><?php echo esc_html( number_format_i18n( (int) ( $counts['links'] ?? 0 ) ) ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Short links', 'seo-campaign-hub' ); ?></div>
				</div>
			</div>

			<div class="sch-ie-grid">
				<section class="sch-ie-panel">
					<h2><?php esc_html_e( 'Export', 'seo-campaign-hub' ); ?></h2>
					<p><?php esc_html_e( 'Download a JSON backup of your campaign content. Campaigns and offers are exported from WordPress posts; short links come from the plugin database.', 'seo-campaign-hub' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sch_export_data">
						<?php wp_nonce_field( 'sch_export_data' ); ?>

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="sch-export-type"><?php esc_html_e( 'What to export', 'seo-campaign-hub' ); ?></label>
								</th>
								<td>
									<select id="sch-export-type" name="export_type">
										<option value="all"><?php esc_html_e( 'Everything (campaigns, offers, links, settings)', 'seo-campaign-hub' ); ?></option>
										<option value="campaigns"><?php esc_html_e( 'Campaigns only', 'seo-campaign-hub' ); ?></option>
										<option value="offers"><?php esc_html_e( 'Offers only', 'seo-campaign-hub' ); ?></option>
										<option value="links"><?php esc_html_e( 'Short links only', 'seo-campaign-hub' ); ?></option>
										<option value="analytics"><?php esc_html_e( 'Analytics snapshot (export only)', 'seo-campaign-hub' ); ?></option>
										<option value="settings"><?php esc_html_e( 'Settings only', 'seo-campaign-hub' ); ?></option>
									</select>
								</td>
							</tr>
						</table>

						<?php submit_button( __( 'Download JSON', 'seo-campaign-hub' ), 'primary', 'submit', false ); ?>
					</form>
				</section>

				<section class="sch-ie-panel">
					<h2><?php esc_html_e( 'Import', 'seo-campaign-hub' ); ?></h2>
					<p><?php esc_html_e( 'Upload a previously exported JSON file. Matching items are found by slug (campaigns/offers) or slug/key (links).', 'seo-campaign-hub' ); ?></p>
					<p class="description"><?php esc_html_e( 'Always keep a full WordPress backup before large imports. Analytics JSON cannot be imported. Maximum file size: 5 MB.', 'seo-campaign-hub' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="sch_import_data">
						<?php wp_nonce_field( 'sch_import_data' ); ?>

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="sch-import-file"><?php esc_html_e( 'JSON file', 'seo-campaign-hub' ); ?></label>
								</th>
								<td>
									<input type="file" id="sch-import-file" name="import_file" accept=".json,application/json" required>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="sch-conflict"><?php esc_html_e( 'If item already exists', 'seo-campaign-hub' ); ?></label>
								</th>
								<td>
									<select id="sch-conflict" name="conflict">
										<option value="update"><?php esc_html_e( 'Update existing', 'seo-campaign-hub' ); ?></option>
										<option value="skip"><?php esc_html_e( 'Skip existing', 'seo-campaign-hub' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Settings', 'seo-campaign-hub' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="include_settings" value="1">
										<?php esc_html_e( 'Also import plugin settings from the file (if present)', 'seo-campaign-hub' ); ?>
									</label>
								</td>
							</tr>
						</table>

						<?php submit_button( __( 'Import JSON', 'seo-campaign-hub' ), 'secondary', 'submit', false ); ?>
					</form>
				</section>
			</div>

			<section style="margin-top:28px">
				<h2><?php esc_html_e( 'Tips', 'seo-campaign-hub' ); ?></h2>
				<ul>
					<li><?php esc_html_e( 'Use “Everything” for site migration or full backup.', 'seo-campaign-hub' ); ?></li>
					<li><?php esc_html_e( 'After importing short links, flush permalinks once if redirects return 404.', 'seo-campaign-hub' ); ?></li>
					<li><?php esc_html_e( 'Featured images are not transferred by URL in this version — re-attach them after import if needed.', 'seo-campaign-hub' ); ?></li>
				</ul>
			</section>
		</div>
	</div>
</div>

<style>
	.sch-ie-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:20px; }
	.sch-ie-panel { background:#f6f7f7; border:1px solid #dcdcde; border-radius:8px; padding:20px; }
	.sch-ie-panel h2 { margin-top:0; }
</style>

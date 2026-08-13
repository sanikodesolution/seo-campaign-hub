<?php
/**
 * Help & Support View
 *
 * @var string               $page_title Page title.
 * @var array<string, mixed> $status     Safe system diagnostics.
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status  = ( isset( $status ) && is_array( $status ) ) ? $status : [];
$prefix  = ! empty( $status['shortener_prefix'] ) ? (string) $status['shortener_prefix'] : 'go';
$tables  = is_array( $status['tables'] ?? null ) ? $status['tables'] : [];

$yes = __( 'OK', 'seo-campaign-hub' );
$no  = __( 'Needs attention', 'seo-campaign-hub' );

$shortcodes = [
	'[sch_campaign id="123"]',
	'[sch_campaign slug="summer-sale"]',
	'[sch_offer id="45"]',
	'[sch_offer slug="premium-hosting-deal"]',
	'[sch_offers campaign_id="123" limit="10" orderby="date" order="DESC"]',
	'[sch_short_link url="' . home_url( '/' . $prefix . '/deal' ) . '" target="_blank"]Shop the deal[/sch_short_link]',
	'[sch_qr_code id="10" size="200"]',
	'[sch_image_to_svg]',
	'[sch_url_shortener]',
];
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Help & Support', 'seo-campaign-hub' ) ); ?></h1>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content sch-help">

			<section class="sch-help-section">
				<h2><?php esc_html_e( 'Quick start (WP post campaigns)', 'seo-campaign-hub' ); ?></h2>
				<p><?php esc_html_e( 'Use normal WordPress posts/pages for SEO content. Use this plugin for short links, country/language routing, and analytics.', 'seo-campaign-hub' ); ?></p>
				<ol class="sch-help-checklist">
					<li>
						<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">
							<?php esc_html_e( 'Create SEO posts/pages', 'seo-campaign-hub' ); ?>
						</a>
						— <?php esc_html_e( 'one page per language if you need localized landings', 'seo-campaign-hub' ); ?>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>">
							<?php esc_html_e( 'Flush Permalinks', 'seo-campaign-hub' ); ?>
						</a>
						— <?php esc_html_e( 'Settings → Permalinks → Save Changes (once)', 'seo-campaign-hub' ); ?>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ); ?>">
							<?php esc_html_e( 'Confirm Localization settings', 'seo-campaign-hub' ); ?>
						</a>
						— <?php esc_html_e( 'smart redirects + enabled languages', 'seo-campaign-hub' ); ?>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-shortener' ) ); ?>">
							<?php esc_html_e( 'Create a Short Link', 'seo-campaign-hub' ); ?>
						</a>
						— <?php esc_html_e( 'default destination = your WP post URL; add language/country rules as needed', 'seo-campaign-hub' ); ?>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-analytics' ) ); ?>">
							<?php esc_html_e( 'Check Analytics', 'seo-campaign-hub' ); ?>
						</a>
						— <?php esc_html_e( 'clicks, country, and language', 'seo-campaign-hub' ); ?>
					</li>
				</ol>
			</section>

			<section class="sch-help-section">
				<h2><?php esc_html_e( 'Feature guides', 'seo-campaign-hub' ); ?></h2>
				<div class="sch-help-grid">
					<div>
						<h3><?php esc_html_e( 'SEO title & meta', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Set a homepage title and description under Settings → SEO. On posts, pages, campaigns, and offers, use the SEO metabox. Empty fields fall back to the WordPress title and excerpt.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ); ?>"><?php esc_html_e( 'Open SEO settings', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Campaigns', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Campaigns are landing pages published under /campaigns/. Add title, content, featured image, categories, and tags, then publish.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=sch_campaign' ) ); ?>"><?php esc_html_e( 'Manage Campaigns', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Offers', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Offers are destinations users click through to — affiliate links, product pages, or signup URLs. They appear under /offers/.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=sch_offer' ) ); ?>"><?php esc_html_e( 'Manage Offers', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'URL Shortener', 'seo-campaign-hub' ); ?></h3>
						<p>
							<?php
							printf(
								/* translators: %s: short URL pattern */
								esc_html__( 'Create tracked short links with optional UTM parameters. Default pattern: %s', 'seo-campaign-hub' ),
								'<code>' . esc_html( home_url( '/' . $prefix . '/{slug}' ) ) . '</code>'
							);
							?>
						</p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-shortener' ) ); ?>"><?php esc_html_e( 'Open URL Shortener', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Public URL Shortener', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Let visitors create short links without logging in at /tools/url-shortener/ or via [sch_url_shortener]. Guest links appear in URL Shortener with a Public badge.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-public-shortener' ) ); ?>"><?php esc_html_e( 'Open Public Shortener', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Public tools ads', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Paste shared Above/Below ad HTML under Settings → Public Tools. Appears on both /tools/ pages (not shortcodes). Use Header Scripts for AdSense loader code.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ); ?>"><?php esc_html_e( 'Open Public Tools settings', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'QR Codes', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Generate QR codes for campaigns, offers, or short links for print and offline channels.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-qr-codes' ) ); ?>"><?php esc_html_e( 'Open QR Codes', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Analytics', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Review page views, visitors, clicks, conversions, country/language breakdowns, and daily trends for the last 7, 30, or 90 days.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-analytics' ) ); ?>"><?php esc_html_e( 'Open Analytics', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Localization', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Enable smart language/country redirects under Settings → Localization. Add per-link rules on the URL Shortener so one /go/ slug can route visitors to different WP posts by browser language or IP country.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ); ?>"><?php esc_html_e( 'Open Localization settings', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Ads.txt', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Serve Google AdSense (or other network) ads.txt from your site root. Paste the authorized sellers lines in Settings → Ads.txt, enable the feature, then verify at /ads.txt.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ); ?>"><?php esc_html_e( 'Open Ads.txt settings', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'URL Replace', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Swap any URL across the public site with live rules (change anytime). Optional database replace updates posts, Elementor meta, and short links — dry run first, backup before Apply.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-url-replace' ) ); ?>"><?php esc_html_e( 'Open URL Replace', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Import / Export', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Download or upload JSON backups of campaigns, offers, short links, settings, or everything. Analytics snapshots export only (cannot be imported). Max import size: 5 MB.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-import-export' ) ); ?>"><?php esc_html_e( 'Open Import/Export', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Cloud Backup', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Save Client ID + Client Secret (password), then Sync with Google Drive in a browser popup. Back up plugin data (same Everything JSON as Import/Export), schedule daily or weekly runs, and keep the newest N backups on Drive.', 'seo-campaign-hub' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-cloud-backup' ) ); ?>"><?php esc_html_e( 'Open Cloud Backup', 'seo-campaign-hub' ); ?></a></p>
					</div>
					<div>
						<h3><?php esc_html_e( 'Elementor', 'seo-campaign-hub' ); ?></h3>
						<p><?php esc_html_e( 'Campaign and Offer posts support the block editor and REST API. With Elementor installed, edit campaign/offer content visually and place shortcodes via the Shortcode widget until native widgets are available.', 'seo-campaign-hub' ); ?></p>
						<?php if ( ! empty( $status['elementor_active'] ) ) : ?>
							<p><span class="sch-badge sch-badge-ok"><?php esc_html_e( 'Elementor detected', 'seo-campaign-hub' ); ?></span></p>
						<?php else : ?>
							<p><span class="sch-badge"><?php esc_html_e( 'Elementor not active', 'seo-campaign-hub' ); ?></span></p>
						<?php endif; ?>
					</div>
				</div>
			</section>

			<section class="sch-help-section">
				<h2><?php esc_html_e( 'Shortcodes', 'seo-campaign-hub' ); ?></h2>
				<p><?php esc_html_e( 'Embed plugin content in posts, pages, or Elementor Shortcode widgets:', 'seo-campaign-hub' ); ?></p>
				<ul class="sch-help-shortcodes">
					<?php foreach ( $shortcodes as $code ) : ?>
						<li>
							<code class="sch-copy-target"><?php echo esc_html( $code ); ?></code>
							<button type="button" class="button button-small sch-copy-btn" data-copy="<?php echo esc_attr( $code ); ?>">
								<?php esc_html_e( 'Copy', 'seo-campaign-hub' ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="sch-help-section">
				<h2><?php esc_html_e( 'Troubleshooting', 'seo-campaign-hub' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Problem', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'What to try', 'seo-campaign-hub' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Campaign / offer / short link URLs return 404', 'seo-campaign-hub' ); ?></td>
							<td>
								<?php
								printf(
									/* translators: %s: permalinks settings link */
									esc_html__( 'Go to %s and click Save Changes once to flush rewrite rules.', 'seo-campaign-hub' ),
									'<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Settings → Permalinks', 'seo-campaign-hub' ) . '</a>'
								);
								?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Short links not redirecting', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Confirm the URL Shortener is enabled, the link is Active, and permalinks have been flushed.', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Analytics show no data', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Confirm Analytics is enabled, visit a front-end page while logged out or in a private window, then refresh Analytics. Bot filtering may ignore some test traffic.', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Smart redirect goes to wrong page', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Confirm Localization is enabled, the link has language/country rules, match priority is correct, and a valid fallback Destination URL is set.', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( '/ads.txt wrong, empty, or 404', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Enable Ads.txt in Settings, paste valid content, and remove any conflicting physical ads.txt or other plugin that serves the same path.', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Google flags /wp-includes/SimplePie/ duplicates', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Those are Apache folder listings, not posts. Enable Settings → SEO → Block core directory listings, open wp-admin once, confirm the URL returns 403, then re-validate in Search Console. On Nginx turn autoindex off.', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Layout or slider breaks after optimize', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Turn off Defer JavaScript or Minify HTML under Settings → Advanced. Elementor preview is skipped automatically; jQuery and Elementor scripts are never deferred.', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Google Drive backup fails', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Enable the Drive API, match the redirect URI exactly, allow popups, click Sync with Google Drive, and check the Last backup message on Cloud Backup.', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Scheduled cloud backup never runs', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'WordPress cron needs site traffic. Save the schedule on Cloud Backup; on quiet sites use server cron or a cron plugin to hit wp-cron.php.', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Plugin pages look incomplete', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Make sure you are logged in as an administrator (manage_options capability).', 'seo-campaign-hub' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'PHP / WordPress version notice', 'seo-campaign-hub' ); ?></td>
							<td><?php esc_html_e( 'Upgrade to PHP 8.2+ and WordPress 6.0+.', 'seo-campaign-hub' ); ?></td>
						</tr>
					</tbody>
				</table>
			</section>

			<section class="sch-help-section">
				<h2><?php esc_html_e( 'System status', 'seo-campaign-hub' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<tbody>
						<tr>
							<th><?php esc_html_e( 'Plugin version', 'seo-campaign-hub' ); ?></th>
							<td><?php echo esc_html( (string) ( $status['plugin_version'] ?? '' ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'WordPress', 'seo-campaign-hub' ); ?></th>
							<td>
								<?php echo esc_html( (string) ( $status['wp_version'] ?? '' ) ); ?>
								<span class="sch-badge <?php echo ! empty( $status['wp_ok'] ) ? 'sch-badge-ok' : 'sch-badge-bad'; ?>">
									<?php echo esc_html( ! empty( $status['wp_ok'] ) ? $yes : $no ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'PHP', 'seo-campaign-hub' ); ?></th>
							<td>
								<?php echo esc_html( (string) ( $status['php_version'] ?? '' ) ); ?>
								<span class="sch-badge <?php echo ! empty( $status['php_ok'] ) ? 'sch-badge-ok' : 'sch-badge-bad'; ?>">
									<?php echo esc_html( ! empty( $status['php_ok'] ) ? $yes : $no ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Pretty permalinks', 'seo-campaign-hub' ); ?></th>
							<td>
								<span class="sch-badge <?php echo ! empty( $status['pretty_permalinks'] ) ? 'sch-badge-ok' : 'sch-badge-bad'; ?>">
									<?php echo esc_html( ! empty( $status['pretty_permalinks'] ) ? $yes : $no ); ?>
								</span>
								<?php if ( empty( $status['pretty_permalinks'] ) ) : ?>
									— <a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php esc_html_e( 'Enable permalinks', 'seo-campaign-hub' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'URL Shortener', 'seo-campaign-hub' ); ?></th>
							<td>
								<?php
								echo ! empty( $status['shortener_enabled'] )
									? esc_html__( 'Enabled', 'seo-campaign-hub' )
									: esc_html__( 'Disabled', 'seo-campaign-hub' );
								?>
								· <?php esc_html_e( 'Prefix:', 'seo-campaign-hub' ); ?>
								<code><?php echo esc_html( $prefix ); ?></code>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Analytics', 'seo-campaign-hub' ); ?></th>
							<td>
								<?php
								echo ! empty( $status['analytics_enabled'] )
									? esc_html__( 'Enabled', 'seo-campaign-hub' )
									: esc_html__( 'Disabled', 'seo-campaign-hub' );
								?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Elementor', 'seo-campaign-hub' ); ?></th>
							<td>
								<?php
								echo ! empty( $status['elementor_active'] )
									? esc_html__( 'Active', 'seo-campaign-hub' )
									: esc_html__( 'Not active', 'seo-campaign-hub' );
								?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Database tables', 'seo-campaign-hub' ); ?></th>
							<td>
								<span class="sch-badge <?php echo ! empty( $status['tables_ok'] ) ? 'sch-badge-ok' : 'sch-badge-bad'; ?>">
									<?php echo esc_html( ! empty( $status['tables_ok'] ) ? $yes : $no ); ?>
								</span>
								<?php if ( empty( $status['tables_ok'] ) ) : ?>
									<p class="description"><?php esc_html_e( 'If tables are missing, deactivate and reactivate the plugin once.', 'seo-campaign-hub' ); ?></p>
								<?php endif; ?>
								<ul class="sch-help-table-list">
									<?php foreach ( $tables as $key => $exists ) : ?>
										<li>
											<code>sch_<?php echo esc_html( (string) $key ); ?></code>
											—
											<?php echo $exists ? esc_html__( 'present', 'seo-campaign-hub' ) : esc_html__( 'missing', 'seo-campaign-hub' ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</td>
						</tr>
					</tbody>
				</table>
			</section>

			<section class="sch-help-section">
				<h2><?php esc_html_e( 'Quick links', 'seo-campaign-hub' ); ?></h2>
				<p class="sch-help-links">
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub' ) ); ?>"><?php esc_html_e( 'Dashboard', 'seo-campaign-hub' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'seo-campaign-hub' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-url-replace' ) ); ?>"><?php esc_html_e( 'URL Replace', 'seo-campaign-hub' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-import-export' ) ); ?>"><?php esc_html_e( 'Import/Export', 'seo-campaign-hub' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-cloud-backup' ) ); ?>"><?php esc_html_e( 'Cloud Backup', 'seo-campaign-hub' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php esc_html_e( 'Permalinks', 'seo-campaign-hub' ); ?></a>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sch_campaign' ) ); ?>"><?php esc_html_e( 'New Campaign', 'seo-campaign-hub' ); ?></a>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sch_offer' ) ); ?>"><?php esc_html_e( 'New Offer', 'seo-campaign-hub' ); ?></a>
				</p>
				<p class="description">
					<?php
					printf(
						/* translators: %s: support site URL */
						esc_html__( 'Documentation and support: %s', 'seo-campaign-hub' ),
						'<a href="https://seocampaignhub.com" target="_blank" rel="noopener noreferrer">seocampaignhub.com</a>'
					);
					?>
				</p>
			</section>

		</div>
	</div>
</div>

<style>
	.sch-help-section { margin: 0 0 28px; }
	.sch-help-section h2 { margin-top: 0; }
	.sch-help-checklist { line-height: 1.8; }
	.sch-help-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
	.sch-help-grid > div { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 6px; padding: 16px; }
	.sch-help-grid h3 { margin-top: 0; }
	.sch-help-shortcodes { list-style: none; margin: 0; padding: 0; }
	.sch-help-shortcodes li { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0 0 10px; }
	.sch-help-shortcodes code { background: #f0f0f1; padding: 6px 10px; border-radius: 4px; display: inline-block; max-width: 100%; overflow-x: auto; }
	.sch-help-table-list { margin: 8px 0 0; columns: 2; }
	.sch-help-links { display: flex; flex-wrap: wrap; gap: 8px; }
	.sch-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #f0f0f1; color: #50575e; }
	.sch-badge-ok { background: #d4edda; color: #155724; }
	.sch-badge-bad { background: #f8d7da; color: #721c24; }
</style>

<script>
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.sch-copy-btn');
		if (!btn) {
			return;
		}
		var text = btn.getAttribute('data-copy') || '';
		if (!navigator.clipboard || !text) {
			return;
		}
		navigator.clipboard.writeText(text).then(function () {
			var original = btn.textContent;
			btn.textContent = '<?php echo esc_js( __( 'Copied!', 'seo-campaign-hub' ) ); ?>';
			setTimeout(function () { btn.textContent = original; }, 1500);
		});
	});
</script>

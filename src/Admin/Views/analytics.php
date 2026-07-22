<?php
/**
 * Analytics Dashboard View
 *
 * @var string               $page_title Page title.
 * @var int                  $days       Selected period.
 * @var array<string, mixed> $summary    Dashboard summary from AnalyticsService.
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$days    = isset( $days ) ? (int) $days : 30;
$summary = ( isset( $summary ) && is_array( $summary ) ) ? $summary : [];

$enabled      = ! empty( $summary['enabled'] );
$table_exists = array_key_exists( 'table_exists', $summary ) ? (bool) $summary['table_exists'] : true;
$page_views   = (int) ( $summary['page_views'] ?? 0 );
$visitors     = (int) ( $summary['unique_visitors'] ?? 0 );
$sessions     = (int) ( $summary['sessions'] ?? 0 );
$clicks       = (int) ( $summary['clicks'] ?? 0 );
$conversions  = (int) ( $summary['conversions'] ?? 0 );
$conv_rate    = (float) ( $summary['conversion_rate'] ?? 0 );
$total_events = (int) ( $summary['total_events'] ?? 0 );
$daily        = is_array( $summary['daily_stats'] ?? null ) ? $summary['daily_stats'] : [];
$by_type      = is_array( $summary['events_by_type'] ?? null ) ? $summary['events_by_type'] : [];
$top_campaigns = is_array( $summary['top_campaigns'] ?? null ) ? $summary['top_campaigns'] : [];
$top_offers    = is_array( $summary['top_offers'] ?? null ) ? $summary['top_offers'] : [];
$top_links     = is_array( $summary['top_links'] ?? null ) ? $summary['top_links'] : [];
$top_countries = is_array( $summary['top_countries'] ?? null ) ? $summary['top_countries'] : [];
$top_languages = is_array( $summary['top_languages'] ?? null ) ? $summary['top_languages'] : [];

$max_daily = 1;
foreach ( $daily as $row ) {
	$max_daily = max( $max_daily, (int) ( $row['total_events'] ?? 0 ) );
}

$max_country = 1;
foreach ( $top_countries as $row ) {
	$max_country = max( $max_country, (int) ( $row['count'] ?? 0 ) );
}

$max_language = 1;
foreach ( $top_languages as $row ) {
	$max_language = max( $max_language, (int) ( $row['count'] ?? 0 ) );
}

/**
 * Build a flag emoji from a two-letter ISO country code.
 *
 * @param string $code ISO 3166-1 alpha-2 code.
 * @return string
 */
$sch_country_flag = static function ( $code ) {
	$code = strtoupper( trim( (string) $code ) );
	if ( strlen( $code ) !== 2 || ! ctype_alpha( $code ) ) {
		return '';
	}
	$flag = '';
	for ( $i = 0; $i < 2; $i++ ) {
		$flag .= mb_convert_encoding( '&#' . ( 0x1F1E6 + ( ord( $code[ $i ] ) - 65 ) ) . ';', 'UTF-8', 'HTML-ENTITIES' );
	}
	return $flag;
};

$base_url = admin_url( 'admin.php?page=seo-campaign-hub-analytics' );
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Analytics', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( ! $table_exists ) : ?>
		<div class="seo-campaign-hub-notice error">
			<?php esc_html_e( 'The analytics database table is missing. Deactivate and reactivate the plugin to create it, or contact support.', 'seo-campaign-hub' ); ?>
		</div>
	<?php elseif ( ! $enabled ) : ?>
		<div class="seo-campaign-hub-notice info">
			<?php
			printf(
				/* translators: %s: settings URL */
				esc_html__( 'Analytics collection is currently disabled. Existing data is still shown below. Enable tracking in %s.', 'seo-campaign-hub' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ) . '">' . esc_html__( 'Settings', 'seo-campaign-hub' ) . '</a>'
			);
			?>
		</div>
	<?php endif; ?>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">

			<div class="sch-analytics-toolbar">
				<span class="sch-analytics-period-label"><?php esc_html_e( 'Period:', 'seo-campaign-hub' ); ?></span>
				<?php foreach ( [ 7, 30, 90 ] as $preset ) : ?>
					<a class="button <?php echo $days === $preset ? 'button-primary' : ''; ?>"
					   href="<?php echo esc_url( add_query_arg( 'days', $preset, $base_url ) ); ?>">
						<?php
						printf(
							/* translators: %d: number of days */
							esc_html__( 'Last %d days', 'seo-campaign-hub' ),
							(int) $preset
						);
						?>
					</a>
				<?php endforeach; ?>
			</div>

			<?php if ( $total_events === 0 ) : ?>
				<div class="seo-campaign-hub-placeholder">
					<p><?php esc_html_e( 'No analytics events yet for this period.', 'seo-campaign-hub' ); ?></p>
					<p class="description">
						<?php esc_html_e( 'Visit a campaign or offer page on the front end, or share a short link, then refresh this page.', 'seo-campaign-hub' ); ?>
					</p>
				</div>
			<?php else : ?>

				<div class="seo-campaign-hub-dashboard-grid sch-analytics-metrics">
					<div class="seo-campaign-hub-stat-box">
						<div class="stat-number"><?php echo esc_html( number_format_i18n( $page_views ) ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Page views', 'seo-campaign-hub' ); ?></div>
					</div>
					<div class="seo-campaign-hub-stat-box">
						<div class="stat-number"><?php echo esc_html( number_format_i18n( $visitors ) ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Unique visitors', 'seo-campaign-hub' ); ?></div>
					</div>
					<div class="seo-campaign-hub-stat-box">
						<div class="stat-number"><?php echo esc_html( number_format_i18n( $sessions ) ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Sessions', 'seo-campaign-hub' ); ?></div>
					</div>
					<div class="seo-campaign-hub-stat-box">
						<div class="stat-number"><?php echo esc_html( number_format_i18n( $clicks ) ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Clicks', 'seo-campaign-hub' ); ?></div>
					</div>
					<div class="seo-campaign-hub-stat-box">
						<div class="stat-number"><?php echo esc_html( number_format_i18n( $conversions ) ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Conversions', 'seo-campaign-hub' ); ?></div>
					</div>
					<div class="seo-campaign-hub-stat-box">
						<div class="stat-number"><?php echo esc_html( number_format_i18n( $conv_rate, 2 ) ); ?>%</div>
						<div class="stat-label"><?php esc_html_e( 'Conversion rate', 'seo-campaign-hub' ); ?></div>
					</div>
				</div>

				<h2><?php esc_html_e( 'Daily trend', 'seo-campaign-hub' ); ?></h2>
				<table class="wp-list-table widefat fixed striped sch-analytics-trend">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Events', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Visitors', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Views', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Clicks', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Conversions', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Volume', 'seo-campaign-hub' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $daily as $row ) : ?>
							<?php
							$events = (int) ( $row['total_events'] ?? 0 );
							$pct    = $max_daily > 0 ? round( ( $events / $max_daily ) * 100 ) : 0;
							?>
							<tr>
								<td><?php echo esc_html( (string) ( $row['date'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $events ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) ( $row['unique_visitors'] ?? 0 ) ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) ( $row['page_views'] ?? 0 ) ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) ( $row['clicks'] ?? 0 ) ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) ( $row['conversions'] ?? 0 ) ) ); ?></td>
								<td>
									<div class="sch-bar" role="img" aria-label="<?php echo esc_attr( $events . ' events' ); ?>">
										<span style="width:<?php echo esc_attr( (string) $pct ); ?>%"></span>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div class="sch-analytics-columns">
					<div>
						<h2><?php esc_html_e( 'Events by type', 'seo-campaign-hub' ); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Type', 'seo-campaign-hub' ); ?></th>
									<th><?php esc_html_e( 'Count', 'seo-campaign-hub' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $by_type ) ) : ?>
									<tr><td colspan="2"><?php esc_html_e( 'No events.', 'seo-campaign-hub' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $by_type as $row ) : ?>
										<tr>
											<td><code><?php echo esc_html( (string) ( $row['event_type'] ?? '' ) ); ?></code></td>
											<td><?php echo esc_html( number_format_i18n( (int) ( $row['count'] ?? 0 ) ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					<div>
						<h2><?php esc_html_e( 'Top campaigns', 'seo-campaign-hub' ); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Campaign', 'seo-campaign-hub' ); ?></th>
									<th><?php esc_html_e( 'Events', 'seo-campaign-hub' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $top_campaigns ) ) : ?>
									<tr><td colspan="2"><?php esc_html_e( 'No campaign-attributed events yet.', 'seo-campaign-hub' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $top_campaigns as $row ) : ?>
										<tr>
											<td>
												<?php if ( ! empty( $row['edit_url'] ) ) : ?>
													<a href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></a>
												<?php else : ?>
													<?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( number_format_i18n( (int) ( $row['count'] ?? 0 ) ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="sch-analytics-columns">
					<div>
						<h2><?php esc_html_e( 'Top offers', 'seo-campaign-hub' ); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Offer', 'seo-campaign-hub' ); ?></th>
									<th><?php esc_html_e( 'Events', 'seo-campaign-hub' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $top_offers ) ) : ?>
									<tr><td colspan="2"><?php esc_html_e( 'No offer-attributed events yet.', 'seo-campaign-hub' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $top_offers as $row ) : ?>
										<tr>
											<td>
												<?php if ( ! empty( $row['edit_url'] ) ) : ?>
													<a href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></a>
												<?php else : ?>
													<?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( number_format_i18n( (int) ( $row['count'] ?? 0 ) ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					<div>
						<h2><?php esc_html_e( 'Top short links', 'seo-campaign-hub' ); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Link', 'seo-campaign-hub' ); ?></th>
									<th><?php esc_html_e( 'Events', 'seo-campaign-hub' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $top_links ) ) : ?>
									<tr><td colspan="2"><?php esc_html_e( 'No short-link events yet. Create and share a link from URL Shortener.', 'seo-campaign-hub' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $top_links as $row ) : ?>
										<tr>
											<td>
												<?php if ( ! empty( $row['edit_url'] ) ) : ?>
													<a href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></a>
												<?php else : ?>
													<?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( number_format_i18n( (int) ( $row['count'] ?? 0 ) ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<h2><?php esc_html_e( 'Traffic by country', 'seo-campaign-hub' ); ?></h2>
				<table class="wp-list-table widefat fixed striped sch-analytics-countries">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Country', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Events', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Share', 'seo-campaign-hub' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $top_countries ) ) : ?>
							<tr>
								<td colspan="3">
									<?php esc_html_e( 'No country data yet. Country is detected from visitor IP addresses on clicks and page views (local/private IPs are skipped).', 'seo-campaign-hub' ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $top_countries as $row ) : ?>
								<?php
								$code  = strtoupper( (string) ( $row['country'] ?? '' ) );
								$count = (int) ( $row['count'] ?? 0 );
								$pct   = $max_country > 0 ? round( ( $count / $max_country ) * 100 ) : 0;
								$flag  = $sch_country_flag( $code );
								?>
								<tr>
									<td>
										<?php if ( '' !== $flag ) : ?>
											<span class="sch-country-flag" aria-hidden="true"><?php echo esc_html( $flag ); ?></span>
										<?php endif; ?>
										<code><?php echo esc_html( '' !== $code ? $code : '—' ); ?></code>
									</td>
									<td><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
									<td>
										<div class="sch-bar" role="img" aria-label="<?php echo esc_attr( $count . ' events' ); ?>">
											<span style="width:<?php echo esc_attr( (string) $pct ); ?>%"></span>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Traffic by language', 'seo-campaign-hub' ); ?></h2>
				<table class="wp-list-table widefat fixed striped sch-analytics-countries">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Language', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Events', 'seo-campaign-hub' ); ?></th>
							<th><?php esc_html_e( 'Share', 'seo-campaign-hub' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $top_languages ) ) : ?>
							<tr>
								<td colspan="3">
									<?php esc_html_e( 'No language data yet. Language is detected from the visitor browser Accept-Language header on clicks and page views.', 'seo-campaign-hub' ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $top_languages as $row ) : ?>
								<?php
								$code  = strtolower( (string) ( $row['language'] ?? '' ) );
								$count = (int) ( $row['count'] ?? 0 );
								$pct   = $max_language > 0 ? round( ( $count / $max_language ) * 100 ) : 0;
								?>
								<tr>
									<td><code><?php echo esc_html( '' !== $code ? $code : '—' ); ?></code></td>
									<td><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
									<td>
										<div class="sch-bar" role="img" aria-label="<?php echo esc_attr( $count . ' events' ); ?>">
											<span style="width:<?php echo esc_attr( (string) $pct ); ?>%"></span>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

			<?php endif; ?>
		</div>
	</div>
</div>

<style>
	.sch-analytics-toolbar { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin:0 0 20px; }
	.sch-analytics-period-label { font-weight:600; margin-right:4px; }
	.sch-analytics-metrics { margin-bottom:24px; }
	.sch-analytics-columns { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:24px; margin:24px 0; }
	.sch-bar { background:#eef1f4; border-radius:4px; height:10px; overflow:hidden; }
	.sch-bar span { display:block; height:100%; background:#007cba; border-radius:4px; }
	.sch-analytics-trend td { vertical-align:middle; }
	.sch-analytics-countries td { vertical-align:middle; }
	.sch-country-flag { font-size:16px; margin-right:6px; line-height:1; }
</style>

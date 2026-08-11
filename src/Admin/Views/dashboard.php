<?php
/**
 * Dashboard View
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$campaign_count = wp_count_posts( 'sch_campaign' );
$offer_count    = wp_count_posts( 'sch_offer' );
$vs             = ( isset( $visitor_stats ) && is_array( $visitor_stats ) ) ? $visitor_stats : [];
$vs_enabled     = ! empty( $vs['enabled'] );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'SEO Campaign Hub Dashboard', 'seo-campaign-hub' ); ?></h1>
    <div class="seo-campaign-hub-admin">
        <div class="seo-campaign-hub-content">

            <!-- Site Visitors -->
            <h2 style="margin-top:0"><?php esc_html_e( 'Site Visitors', 'seo-campaign-hub' ); ?></h2>
            <?php if ( ! $vs_enabled ) : ?>
                <div class="notice notice-warning inline" style="margin:0 0 16px">
                    <p>
                        <?php
                        printf(
                            esc_html__( 'Visitor tracking is disabled. Enable it in %s to start collecting data.', 'seo-campaign-hub' ),
                            '<a href="' . esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ) . '">' . esc_html__( 'Settings', 'seo-campaign-hub' ) . '</a>'
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            <div class="seo-campaign-hub-dashboard-grid" style="margin-bottom:24px">
                <div class="seo-campaign-hub-stat-box">
                    <div class="stat-number"><?php echo esc_html( number_format_i18n( (int) ( $vs['today_visitors'] ?? 0 ) ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Today Visitors', 'seo-campaign-hub' ); ?></div>
                    <div class="stat-sub" style="color:#666;font-size:12px"><?php echo esc_html( number_format_i18n( (int) ( $vs['today_views'] ?? 0 ) ) ); ?> <?php esc_html_e( 'views', 'seo-campaign-hub' ); ?></div>
                </div>
                <div class="seo-campaign-hub-stat-box">
                    <div class="stat-number"><?php echo esc_html( number_format_i18n( (int) ( $vs['week_visitors'] ?? 0 ) ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Last 7 Days', 'seo-campaign-hub' ); ?></div>
                    <div class="stat-sub" style="color:#666;font-size:12px"><?php echo esc_html( number_format_i18n( (int) ( $vs['week_views'] ?? 0 ) ) ); ?> <?php esc_html_e( 'views', 'seo-campaign-hub' ); ?></div>
                </div>
                <div class="seo-campaign-hub-stat-box">
                    <div class="stat-number"><?php echo esc_html( number_format_i18n( (int) ( $vs['month_visitors'] ?? 0 ) ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Last 30 Days', 'seo-campaign-hub' ); ?></div>
                    <div class="stat-sub" style="color:#666;font-size:12px"><?php echo esc_html( number_format_i18n( (int) ( $vs['month_views'] ?? 0 ) ) ); ?> <?php esc_html_e( 'views', 'seo-campaign-hub' ); ?></div>
                </div>
                <div class="seo-campaign-hub-stat-box" style="background:#f0f6fc">
                    <div class="stat-number" style="color:#2271b1"><?php echo esc_html( number_format_i18n( (int) ( $vs['all_visitors'] ?? 0 ) ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Total Visitors (All Time)', 'seo-campaign-hub' ); ?></div>
                    <div class="stat-sub" style="color:#666;font-size:12px"><?php echo esc_html( number_format_i18n( (int) ( $vs['all_views'] ?? 0 ) ) ); ?> <?php esc_html_e( 'total views', 'seo-campaign-hub' ); ?></div>
                </div>
            </div>
            <p style="margin-bottom:24px">
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-analytics' ) ); ?>"><?php esc_html_e( 'View Full Analytics', 'seo-campaign-hub' ); ?></a>
            </p>

            <!-- Campaigns & Offers -->
            <h2><?php esc_html_e( 'Content', 'seo-campaign-hub' ); ?></h2>
            <div class="seo-campaign-hub-dashboard-grid">
                <div class="seo-campaign-hub-stat-box">
                    <div class="stat-number"><?php echo esc_html( (string) ( $campaign_count->publish ?? 0 ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Published Campaigns', 'seo-campaign-hub' ); ?></div>
                </div>
                <div class="seo-campaign-hub-stat-box">
                    <div class="stat-number"><?php echo esc_html( (string) ( $offer_count->publish ?? 0 ) ); ?></div>
                    <div class="stat-label"><?php esc_html_e( 'Published Offers', 'seo-campaign-hub' ); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
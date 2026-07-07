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
?>
<div class="wrap">
    <h1><?php esc_html_e( 'SEO Campaign Hub Dashboard', 'seo-campaign-hub' ); ?></h1>
    <div class="seo-campaign-hub-admin">
        <div class="seo-campaign-hub-content">
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
            <p>
                <?php esc_html_e( 'Core plugin bootstrap is active. More dashboard widgets can be added safely from here.', 'seo-campaign-hub' ); ?>
            </p>
        </div>
    </div>
</div>
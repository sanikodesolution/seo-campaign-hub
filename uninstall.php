<?php
/**
 * Plugin Uninstall
 *
 * Runs in isolation — the main plugin file is NOT loaded.
 * Do NOT use any plugin constants here; use __FILE__ / plugin_dir_path() instead.
 *
 * @package SEO_Campaign_Hub
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Main uninstall callback — registered via register_uninstall_hook().
 */
function seo_campaign_hub_uninstall() {

    global $wpdb;

    // ---- Delete options ----
    $options = [
        'seo_campaign_hub_version',
        'seo_campaign_hub_db_version',
        'seo_campaign_hub_options',
        'seo_campaign_hub_analytics_start_date',
        'seo_campaign_hub_analytics_enabled',
        'seo_campaign_hub_analytics_anonymize_ip',
        'seo_campaign_hub_analytics_ignore_bots',
        'seo_campaign_hub_analytics_ignore_users',
        'seo_campaign_hub_url_replacements',
        'seo_campaign_hub_text_replacements',
    ];

    foreach ( $options as $option ) {
        delete_option( $option );
    }

    // ---- Delete transients ----
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_seo_campaign_hub_%'
        )
    );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_timeout_seo_campaign_hub_%'
        )
    );

    // ---- Drop custom tables ----
    $tables = [
        $wpdb->prefix . 'sch_campaigns',
        $wpdb->prefix . 'sch_offers',
        $wpdb->prefix . 'sch_links',
        $wpdb->prefix . 'sch_analytics',
        $wpdb->prefix . 'sch_qr_codes',
        $wpdb->prefix . 'sch_redirects',
        $wpdb->prefix . 'sch_schemas',
        $wpdb->prefix . 'sch_internal_links',
        $wpdb->prefix . 'sch_campaign_offers',
        $wpdb->prefix . 'sch_ab_tests',
        $wpdb->prefix . 'sch_export_logs',
        $wpdb->prefix . 'sch_system_logs',
    ];

    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    // ---- Delete user meta ----
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            'seo_campaign_hub_%'
        )
    );

    // ---- Delete post meta ----
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            '_seo_campaign_hub_%'
        )
    );

    // ---- Remove uploaded files ----
    // Use plugin_dir_path( __FILE__ ) — plugin constants are NOT available here
    $upload_dir = plugin_dir_path( __FILE__ ) . 'uploads/';

    if ( file_exists( $upload_dir ) ) {
        seo_campaign_hub_delete_directory( $upload_dir );
    }

    // ---- Clear scheduled events ----
    wp_clear_scheduled_hook( 'seo_campaign_hub_analytics_cron' );
    wp_clear_scheduled_hook( 'seo_campaign_hub_cleanup_cron' );
    wp_clear_scheduled_hook( 'seo_campaign_hub_report_cron' );
    wp_clear_scheduled_hook( 'seo_campaign_hub_email_cron' );
    wp_clear_scheduled_hook( 'seo_campaign_hub_cloud_backup_cron' );

    $index_protection = plugin_dir_path( __FILE__ ) . 'src/Services/IndexProtectionService.php';
    if ( is_readable( $index_protection ) ) {
        require_once $index_protection;
        if ( class_exists( '\SEO_Campaign_Hub\Services\IndexProtectionService' ) ) {
            \SEO_Campaign_Hub\Services\IndexProtectionService::remove_htaccess_rules();
        }
    }
}

/**
 * Recursively delete a directory and its contents.
 *
 * Defined outside the main function so it can be called recursively.
 *
 * @param string $dir Absolute path to directory.
 */
function seo_campaign_hub_delete_directory( $dir ) {
    if ( ! file_exists( $dir ) ) {
        return;
    }

    $files = array_diff( scandir( $dir ), [ '.', '..' ] );

    foreach ( $files as $file ) {
        $path = $dir . '/' . $file;
        if ( is_dir( $path ) ) {
            seo_campaign_hub_delete_directory( $path );
        } else {
            wp_delete_file( $path );
        }
    }

    rmdir( $dir );
}

// ---- Run uninstall ----
seo_campaign_hub_uninstall();

<?php
/**
 * Plugin Uninstall
 *
 * @package SEO_Campaign_Hub
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('seo_campaign_hub_version');
delete_option('seo_campaign_hub_db_version');
delete_option('seo_campaign_hub_options');
delete_option('seo_campaign_hub_analytics_start_date');
delete_option('seo_campaign_hub_analytics_enabled');
delete_option('seo_campaign_hub_analytics_anonymize_ip');
delete_option('seo_campaign_hub_analytics_ignore_bots');
delete_option('seo_campaign_hub_analytics_ignore_users');

// Delete transients
global $wpdb;
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

// Drop custom tables
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
    $wpdb->prefix . 'sch_system_logs'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Delete user meta
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
        'seo_campaign_hub_%'
    )
);

// Delete post meta
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
        '_seo_campaign_hub_%'
    )
);

// Remove files
$upload_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/';
if (file_exists($upload_dir)) {
    // Recursive delete function
    function delete_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                delete_directory($path);
            } else {
                wp_delete_file($path);
            }
        }
        rmdir($dir);
    }
    delete_directory($upload_dir);
}

// Clear scheduled events
wp_clear_scheduled_hook('seo_campaign_hub_analytics_cron');
wp_clear_scheduled_hook('seo_campaign_hub_cleanup_cron');
wp_clear_scheduled_hook('seo_campaign_hub_report_cron');
wp_clear_scheduled_hook('seo_campaign_hub_email_cron');
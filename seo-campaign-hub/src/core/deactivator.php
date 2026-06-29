<?php
/**
 * Plugin Deactivator
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks
 */
class Deactivator {
    /**
     * Deactivate the plugin
     *
     * @param bool $network_wide Whether to deactivate network-wide
     * @return void
     */
    public static function deactivate($network_wide = false) {
        // Check user capabilities
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Check if WordPress multisite
        if ($network_wide && is_multisite()) {
            self::deactivate_multisite();
        } else {
            self::deactivate_single_site();
        }

        // Clear scheduled events
        self::clear_scheduled_events();

        /**
         * Fires after plugin deactivation
         *
         * @param bool $network_wide Whether deactivated network-wide
         */
        do_action('seo_campaign_hub_deactivated', $network_wide);
    }

    /**
     * Deactivate on single site
     *
     * @return void
     */
    private static function deactivate_single_site() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear cache
        self::clear_cache();

        // Remove temporary files
        self::clean_temp_files();
    }

    /**
     * Deactivate on multisite
     *
     * @return void
     */
    private static function deactivate_multisite() {
        global $wpdb;

        // Get all blogs in the network
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            self::deactivate_single_site();
            restore_current_blog();
        }
    }

    /**
     * Clear scheduled events
     *
     * @return void
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('seo_campaign_hub_analytics_cron');
        wp_clear_scheduled_hook('seo_campaign_hub_cleanup_cron');
        wp_clear_scheduled_hook('seo_campaign_hub_report_cron');
        wp_clear_scheduled_hook('seo_campaign_hub_email_cron');
    }

    /**
     * Clear cache
     *
     * @return void
     */
    private static function clear_cache() {
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

        // Clear cache directory
        $cache_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'cache/';
        if (file_exists($cache_dir)) {
            self::delete_directory_contents($cache_dir);
        }
    }

    /**
     * Clean temporary files
     *
     * @return void
     */
    private static function clean_temp_files() {
        $temp_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/temp/';
        if (file_exists($temp_dir)) {
            $files = glob($temp_dir . '*');
            $now = time();
            
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) > 86400)) {
                    wp_delete_file($file);
                }
            }
        }
    }

    /**
     * Delete directory contents recursively
     *
     * @param string $dir Directory path
     * @return void
     */
    private static function delete_directory_contents($dir) {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::delete_directory_contents($path);
                rmdir($path);
            } else {
                wp_delete_file($path);
            }
        }
    }
}
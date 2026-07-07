<?php
/**
 * Plugin Deactivator
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks.
 */
class Deactivator {

    /**
     * Deactivate the plugin.
     *
     * @param bool $network_wide Whether to deactivate network-wide.
     * @return void
     */
    public static function deactivate( bool $network_wide = false ): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        if ( $network_wide && is_multisite() ) {
            self::deactivate_multisite();
        } else {
            self::deactivate_single_site();
        }

        self::clear_scheduled_events();

        /**
         * Fires after plugin deactivation.
         *
         * @param bool $network_wide Whether deactivated network-wide.
         */
        do_action( 'seo_campaign_hub_deactivated', $network_wide );
    }

    /**
     * Deactivate on a single site.
     *
     * @return void
     */
    private static function deactivate_single_site(): void {
        // Remove rewrite rules added by this plugin
        flush_rewrite_rules();

        self::clear_cache();
        self::clean_temp_files();
    }

    /**
     * Deactivate on all sites in a multisite network.
     *
     * @return void
     */
    private static function deactivate_multisite(): void {
        global $wpdb;

        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( (int) $blog_id );
            self::deactivate_single_site();
            restore_current_blog();
        }
    }

    /**
     * Clear all plugin scheduled cron events.
     *
     * @return void
     */
    private static function clear_scheduled_events(): void {
        $hooks = [
            'seo_campaign_hub_analytics_cron',
            'seo_campaign_hub_cleanup_cron',
            'seo_campaign_hub_report_cron',
            'seo_campaign_hub_email_cron',
        ];

        foreach ( $hooks as $hook ) {
            wp_clear_scheduled_hook( $hook );
        }
    }

    /**
     * Clear plugin transients and cache directory.
     *
     * @return void
     */
    private static function clear_cache(): void {
        global $wpdb;

        // Delete transients from DB
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

        // Clear cache directory contents (keep the directory itself)
        $cache_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'cache/';
        if ( file_exists( $cache_dir ) ) {
            self::delete_directory_contents( $cache_dir );
        }
    }

    /**
     * Delete temp files older than 24 hours.
     *
     * @return void
     */
    private static function clean_temp_files(): void {
        $temp_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/temp/';

        if ( ! file_exists( $temp_dir ) ) {
            return;
        }

        $files = glob( $temp_dir . '*' );

        if ( empty( $files ) ) {
            return;
        }

        $now = time();

        foreach ( $files as $file ) {
            if ( is_file( $file ) && ( $now - filemtime( $file ) > DAY_IN_SECONDS ) ) {
                wp_delete_file( $file );
            }
        }
    }

    /**
     * Recursively delete all contents inside a directory.
     * The directory itself is preserved.
     *
     * @param string $dir Absolute path to directory.
     * @return void
     */
    private static function delete_directory_contents( string $dir ): void {
        if ( ! file_exists( $dir ) || ! is_dir( $dir ) ) {
            return;
        }

        $items = array_diff( scandir( $dir ), [ '.', '..' ] );

        foreach ( $items as $item ) {
            $path = $dir . '/' . $item;

            if ( is_dir( $path ) ) {
                self::delete_directory_contents( $path );
                rmdir( $path );
            } else {
                wp_delete_file( $path );
            }
        }
    }
}

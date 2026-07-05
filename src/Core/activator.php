<?php
/**
 * Plugin Activator
 *
 * @package SEO_Campaign_Hub\Core
 */

// Prevent direct access — MUST be before namespace
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

namespace SEO_Campaign_Hub\Core;

/**
 * Class Activator
 *
 * Handles plugin activation tasks.
 */
class Activator {

    /**
     * Activate the plugin.
     *
     * @param bool $network_wide Whether to activate network-wide.
     * @return void
     */
    public static function activate( bool $network_wide = false ): void {
        // Check user capabilities
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        if ( $network_wide && is_multisite() ) {
            self::activate_multisite();
        } else {
            self::activate_single_site();
        }

        // Set activation flag (used for redirect to welcome screen)
        set_transient( 'seo_campaign_hub_activation', true, 30 );

        // Flag to flush rewrite rules on next init
        set_transient( 'seo_campaign_hub_flush_rewrite_rules', true );

        /**
         * Fires after plugin activation.
         *
         * @param bool $network_wide Whether activated network-wide.
         */
        do_action( 'seo_campaign_hub_activated', $network_wide );
    }

    /**
     * Activate on a single site.
     *
     * @return void
     */
    private static function activate_single_site(): void {
        self::create_tables();
        self::set_default_options();
        self::create_directories();
        self::set_initial_data();
    }

    /**
     * Activate on all sites in a multisite network.
     *
     * @return void
     */
    private static function activate_multisite(): void {
        global $wpdb;

        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( (int) $blog_id );
            self::activate_single_site();
            restore_current_blog();
        }
    }

    /**
     * Create all custom database tables.
     *
     * @return void
     */
    private static function create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ( self::get_table_schemas() as $sql ) {
            // Replace {prefix} placeholder
            $sql = str_replace( '{prefix}', $wpdb->prefix, $sql );
            dbDelta( $sql );

            if ( $wpdb->last_error ) {
                error_log( 'SEO Campaign Hub — DB error: ' . $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }

        update_option( 'seo_campaign_hub_db_version', SEO_CAMPAIGN_HUB_VERSION );
    }

    /**
     * Return CREATE TABLE SQL statements keyed by table name.
     *
     * @return array<string, string>
     */
    private static function get_table_schemas(): array {
        global $wpdb;
        $c = $wpdb->get_charset_collate();

        return [
            'sch_campaigns' => "CREATE TABLE {prefix}sch_campaigns (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id BIGINT(20) UNSIGNED NOT NULL,
                campaign_key VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                status ENUM('draft','pending','active','paused','archived') DEFAULT 'draft',
                meta_title VARCHAR(150) DEFAULT NULL,
                meta_description TEXT,
                focus_keyword VARCHAR(255) DEFAULT NULL,
                seo_score INT(11) DEFAULT 0,
                readability_score INT(11) DEFAULT 0,
                content LONGTEXT,
                excerpt TEXT,
                featured_image BIGINT(20) UNSIGNED DEFAULT NULL,
                campaign_type VARCHAR(50) DEFAULT 'landing_page',
                template VARCHAR(100) DEFAULT 'default',
                permalink_structure VARCHAR(255) DEFAULT NULL,
                geo_targeting JSON DEFAULT NULL,
                device_targeting JSON DEFAULT NULL,
                language_targeting JSON DEFAULT NULL,
                ab_test_enabled TINYINT(1) DEFAULT 0,
                ab_test_variants JSON DEFAULT NULL,
                total_views INT(11) DEFAULT 0,
                unique_visitors INT(11) DEFAULT 0,
                total_clicks INT(11) DEFAULT 0,
                total_conversions INT(11) DEFAULT 0,
                conversion_rate DECIMAL(5,2) DEFAULT 0.00,
                meta_data JSON DEFAULT NULL,
                created_by BIGINT(20) UNSIGNED NOT NULL,
                modified_by BIGINT(20) UNSIGNED DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                published_at DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY campaign_key (campaign_key),
                UNIQUE KEY slug (slug),
                KEY post_id (post_id),
                KEY status (status),
                KEY campaign_type (campaign_type),
                KEY created_at (created_at)
            ) $c",

            'sch_offers' => "CREATE TABLE {prefix}sch_offers (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id BIGINT(20) UNSIGNED NOT NULL,
                offer_key VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                status ENUM('draft','pending','active','paused','expired','archived') DEFAULT 'draft',
                offer_type ENUM('affiliate','cpa','coupon','promotion','digital_product','physical_product') NOT NULL,
                description TEXT,
                short_description VARCHAR(255) DEFAULT NULL,
                destination_url TEXT NOT NULL,
                affiliate_url TEXT,
                cpa_url TEXT,
                coupon_code VARCHAR(100) DEFAULT NULL,
                promo_code VARCHAR(100) DEFAULT NULL,
                coupon_expiry DATETIME DEFAULT NULL,
                price DECIMAL(12,2) DEFAULT 0.00,
                sale_price DECIMAL(12,2) DEFAULT NULL,
                currency VARCHAR(3) DEFAULT 'USD',
                payout DECIMAL(10,2) DEFAULT 0.00,
                featured_image BIGINT(20) UNSIGNED DEFAULT NULL,
                logo_image BIGINT(20) UNSIGNED DEFAULT NULL,
                is_featured TINYINT(1) DEFAULT 0,
                is_sticky TINYINT(1) DEFAULT 0,
                display_order INT(11) DEFAULT 0,
                geo_targeting JSON DEFAULT NULL,
                device_targeting JSON DEFAULT NULL,
                language_targeting JSON DEFAULT NULL,
                rotation_enabled TINYINT(1) DEFAULT 0,
                rotation_weight INT(11) DEFAULT 1,
                rotation_impressions INT(11) DEFAULT 0,
                total_clicks INT(11) DEFAULT 0,
                unique_clicks INT(11) DEFAULT 0,
                total_conversions INT(11) DEFAULT 0,
                conversion_rate DECIMAL(5,2) DEFAULT 0.00,
                revenue DECIMAL(12,2) DEFAULT 0.00,
                meta_data JSON DEFAULT NULL,
                created_by BIGINT(20) UNSIGNED NOT NULL,
                modified_by BIGINT(20) UNSIGNED DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                published_at DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY offer_key (offer_key),
                UNIQUE KEY slug (slug),
                KEY post_id (post_id),
                KEY status (status),
                KEY offer_type (offer_type),
                KEY created_at (created_at)
            ) $c",

            'sch_links' => "CREATE TABLE {prefix}sch_links (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                link_key VARCHAR(32) NOT NULL,
                campaign_id BIGINT(20) UNSIGNED DEFAULT NULL,
                offer_id BIGINT(20) UNSIGNED DEFAULT NULL,
                destination_url TEXT NOT NULL,
                short_url VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                title VARCHAR(255) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                link_type ENUM('direct','cloaked','affiliate','cpa','tracking') DEFAULT 'direct',
                redirect_type ENUM('301','302','307') DEFAULT '301',
                is_active TINYINT(1) DEFAULT 1,
                is_public TINYINT(1) DEFAULT 1,
                utm_source VARCHAR(255) DEFAULT NULL,
                utm_medium VARCHAR(255) DEFAULT NULL,
                utm_campaign VARCHAR(255) DEFAULT NULL,
                utm_term VARCHAR(255) DEFAULT NULL,
                utm_content VARCHAR(255) DEFAULT NULL,
                password_hash VARCHAR(255) DEFAULT NULL,
                require_auth TINYINT(1) DEFAULT 0,
                total_clicks INT(11) DEFAULT 0,
                unique_clicks INT(11) DEFAULT 0,
                last_clicked DATETIME DEFAULT NULL,
                meta_data JSON DEFAULT NULL,
                created_by BIGINT(20) UNSIGNED NOT NULL,
                modified_by BIGINT(20) UNSIGNED DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY link_key (link_key),
                UNIQUE KEY slug (slug),
                KEY campaign_id (campaign_id),
                KEY offer_id (offer_id),
                KEY is_active (is_active),
                KEY created_at (created_at)
            ) $c",

            'sch_analytics' => "CREATE TABLE {prefix}sch_analytics (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                session_id VARCHAR(64) NOT NULL,
                visitor_id VARCHAR(64) DEFAULT NULL,
                user_id BIGINT(20) UNSIGNED DEFAULT NULL,
                event_type ENUM('page_view','click','conversion','view','impression','scroll','time_on_page','bounce','exit') NOT NULL,
                event_name VARCHAR(100) DEFAULT NULL,
                event_value DECIMAL(12,2) DEFAULT NULL,
                campaign_id BIGINT(20) UNSIGNED DEFAULT NULL,
                offer_id BIGINT(20) UNSIGNED DEFAULT NULL,
                link_id BIGINT(20) UNSIGNED DEFAULT NULL,
                post_id BIGINT(20) UNSIGNED DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT,
                referrer TEXT,
                landing_page TEXT,
                country CHAR(2) DEFAULT NULL,
                region VARCHAR(100) DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                device_type ENUM('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
                os VARCHAR(50) DEFAULT NULL,
                browser VARCHAR(50) DEFAULT NULL,
                time_on_page INT(11) DEFAULT 0,
                scroll_depth INT(11) DEFAULT 0,
                conversion_amount DECIMAL(12,2) DEFAULT NULL,
                utm_source VARCHAR(255) DEFAULT NULL,
                utm_medium VARCHAR(255) DEFAULT NULL,
                utm_campaign VARCHAR(255) DEFAULT NULL,
                utm_term VARCHAR(255) DEFAULT NULL,
                utm_content VARCHAR(255) DEFAULT NULL,
                meta_data JSON DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY session_id (session_id),
                KEY event_type (event_type),
                KEY campaign_id (campaign_id),
                KEY offer_id (offer_id),
                KEY link_id (link_id),
                KEY created_at (created_at),
                KEY country (country),
                KEY device_type (device_type)
            ) $c",

            'sch_qr_codes' => "CREATE TABLE {prefix}sch_qr_codes (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                qr_key VARCHAR(64) NOT NULL,
                campaign_id BIGINT(20) UNSIGNED DEFAULT NULL,
                offer_id BIGINT(20) UNSIGNED DEFAULT NULL,
                link_id BIGINT(20) UNSIGNED DEFAULT NULL,
                title VARCHAR(255) DEFAULT NULL,
                destination_url TEXT NOT NULL,
                short_url VARCHAR(255) DEFAULT NULL,
                size INT(11) DEFAULT 300,
                color VARCHAR(7) DEFAULT '#000000',
                bg_color VARCHAR(7) DEFAULT '#FFFFFF',
                format ENUM('png','svg','pdf') DEFAULT 'png',
                error_correction ENUM('L','M','Q','H') DEFAULT 'M',
                logo_image BIGINT(20) UNSIGNED DEFAULT NULL,
                total_scans INT(11) DEFAULT 0,
                unique_scans INT(11) DEFAULT 0,
                last_scanned DATETIME DEFAULT NULL,
                meta_data JSON DEFAULT NULL,
                created_by BIGINT(20) UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY qr_key (qr_key),
                KEY campaign_id (campaign_id),
                KEY offer_id (offer_id),
                KEY link_id (link_id),
                KEY created_at (created_at)
            ) $c",

            'sch_redirects' => "CREATE TABLE {prefix}sch_redirects (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                redirect_key VARCHAR(64) NOT NULL,
                campaign_id BIGINT(20) UNSIGNED DEFAULT NULL,
                offer_id BIGINT(20) UNSIGNED DEFAULT NULL,
                source_url TEXT NOT NULL,
                source_hash VARCHAR(64) NOT NULL,
                target_url TEXT NOT NULL,
                target_hash VARCHAR(64) NOT NULL,
                redirect_type ENUM('301','302','307','meta') DEFAULT '301',
                status ENUM('active','paused','archived') DEFAULT 'active',
                priority INT(11) DEFAULT 1,
                condition_type ENUM('always','geo','device','language','time','referrer','custom') DEFAULT 'always',
                condition_value JSON DEFAULT NULL,
                total_clicks INT(11) DEFAULT 0,
                last_clicked DATETIME DEFAULT NULL,
                meta_data JSON DEFAULT NULL,
                created_by BIGINT(20) UNSIGNED NOT NULL,
                modified_by BIGINT(20) UNSIGNED DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY redirect_key (redirect_key),
                KEY source_hash (source_hash),
                KEY status (status)
            ) $c",

            'sch_schemas' => "CREATE TABLE {prefix}sch_schemas (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                schema_key VARCHAR(64) NOT NULL,
                campaign_id BIGINT(20) UNSIGNED DEFAULT NULL,
                post_id BIGINT(20) UNSIGNED DEFAULT NULL,
                schema_type ENUM('Article','FAQ','Breadcrumb','Product','Review','Recipe','Event','HowTo','Video','Course','WebPage','BlogPosting') NOT NULL,
                schema_title VARCHAR(255) DEFAULT NULL,
                schema_description TEXT,
                schema_data JSON NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                is_default TINYINT(1) DEFAULT 0,
                meta_data JSON DEFAULT NULL,
                created_by BIGINT(20) UNSIGNED NOT NULL,
                modified_by BIGINT(20) UNSIGNED DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY schema_key (schema_key),
                KEY campaign_id (campaign_id),
                KEY post_id (post_id),
                KEY schema_type (schema_type),
                KEY is_active (is_active)
            ) $c",
        ];
    }

    /**
     * Set default plugin options (only if not already set).
     *
     * @return void
     */
    private static function set_default_options(): void {
        $defaults = [
            'version'               => SEO_CAMPAIGN_HUB_VERSION,
            'db_version'            => SEO_CAMPAIGN_HUB_VERSION,
            'enable_url_shortening' => true,
            'enable_analytics'      => true,
            'enable_qr_codes'       => true,
            'enable_schema'         => true,
            'enable_redirects'      => true,
            'default_redirect_type' => '301',
            'track_visitors'        => true,
            'track_clicks'          => true,
            'track_conversions'     => true,
            'default_country'       => 'US',
            'default_language'      => 'en',
            'analytics_retention'   => 90,
            'cache_enabled'         => true,
            'cache_expiration'      => 3600,
            'minify_assets'         => true,
            'defer_scripts'         => true,
            'lazy_load_images'      => true,
        ];

        foreach ( $defaults as $key => $value ) {
            $option_name = "seo_campaign_hub_{$key}";
            if ( get_option( $option_name ) === false ) {
                add_option( $option_name, $value );
            }
        }
    }

    /**
     * Create required plugin directories with security index files.
     *
     * @return void
     */
    private static function create_directories(): void {
        $directories = [
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'cache/',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'logs/',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/qr-codes/',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/exports/',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/imports/',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/temp/',
        ];

        foreach ( $directories as $directory ) {
            if ( ! file_exists( $directory ) ) {
                wp_mkdir_p( $directory );
            }

            // Always ensure index.php exists for directory protection
            $index = $directory . 'index.php';
            if ( ! file_exists( $index ) ) {
                file_put_contents( $index, '<?php // Silence is golden' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            }
        }
    }

    /**
     * Set initial plugin data.
     *
     * @return void
     */
    private static function set_initial_data(): void {
        // Store activation timestamp
        if ( get_option( 'seo_campaign_hub_activated_at' ) === false ) {
            add_option( 'seo_campaign_hub_activated_at', current_time( 'mysql' ) );
        }
    }
}

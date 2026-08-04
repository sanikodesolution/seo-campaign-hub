<?php
/**
 * Plugin Name: SEO Campaign Hub
 * Plugin URI: https://seocampaignhub.com
 * Description: Advanced SEO landing page builder with affiliate marketing, URL shortening, analytics, and campaign management
 * Version: 1.1.9
 * Author: SANI UL HASSAN
 * Author URI: https://seocampaignhub.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: seo-campaign-hub
 * Domain Path: /languages
 * Requires PHP: 8.2
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Network: false
 *
 * @package SEO_Campaign_Hub
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================
// PLUGIN CONSTANTS (static — safe to define early)
// ============================================

if ( ! defined( 'SEO_CAMPAIGN_HUB_VERSION' ) ) {
    define( 'SEO_CAMPAIGN_HUB_VERSION', '1.1.9' );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_PLUGIN_DIR' ) ) {
    define( 'SEO_CAMPAIGN_HUB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_PLUGIN_URL' ) ) {
    define( 'SEO_CAMPAIGN_HUB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_PLUGIN_BASENAME' ) ) {
    define( 'SEO_CAMPAIGN_HUB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_PLUGIN_FILE' ) ) {
    define( 'SEO_CAMPAIGN_HUB_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION' ) ) {
    define( 'SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION', '8.2.0' );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_MINIMUM_WP_VERSION' ) ) {
    define( 'SEO_CAMPAIGN_HUB_MINIMUM_WP_VERSION', '6.0.0' );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_REST_NAMESPACE' ) ) {
    define( 'SEO_CAMPAIGN_HUB_REST_NAMESPACE', 'seo-campaign-hub/v1' );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_CACHE_SHORT' ) ) {
    define( 'SEO_CAMPAIGN_HUB_CACHE_SHORT', 300 );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_CACHE_MEDIUM' ) ) {
    define( 'SEO_CAMPAIGN_HUB_CACHE_MEDIUM', 3600 );
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_CACHE_LONG' ) ) {
    define( 'SEO_CAMPAIGN_HUB_CACHE_LONG', 86400 );
}

// ============================================
// REQUIREMENT CHECKS
// ============================================

if ( version_compare( PHP_VERSION, SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION, '<' ) ) {
    add_action( 'admin_notices', 'seo_campaign_hub_php_version_error' );

    function seo_campaign_hub_php_version_error() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'SEO Campaign Hub Error:', 'seo-campaign-hub' ); ?></strong>
                <?php
                printf(
                    esc_html__( 'This plugin requires PHP version %1$s or higher. Your current PHP version is %2$s.', 'seo-campaign-hub' ),
                    esc_html( SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION ),
                    esc_html( PHP_VERSION )
                );
                ?>
            </p>
        </div>
        <?php
    }

    return;
}

global $wp_version;
if ( version_compare( $wp_version, SEO_CAMPAIGN_HUB_MINIMUM_WP_VERSION, '<' ) ) {
    add_action( 'admin_notices', 'seo_campaign_hub_wp_version_error' );

    function seo_campaign_hub_wp_version_error() {
        global $wp_version;
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'SEO Campaign Hub Error:', 'seo-campaign-hub' ); ?></strong>
                <?php
                printf(
                    esc_html__( 'This plugin requires WordPress version %1$s or higher. Your current WordPress version is %2$s.', 'seo-campaign-hub' ),
                    esc_html( SEO_CAMPAIGN_HUB_MINIMUM_WP_VERSION ),
                    esc_html( $wp_version )
                );
                ?>
            </p>
        </div>
        <?php
    }

    return;
}

// ============================================
// AUTOLOADER
// ============================================

require_once SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'includes/class-autoloader.php';

\SEO_Campaign_Hub\Core\Autoloader::register();

// ============================================
// ACTIVATION / DEACTIVATION / UNINSTALL HOOKS
// ============================================

register_activation_hook(
    SEO_CAMPAIGN_HUB_PLUGIN_FILE,
    array( 'SEO_Campaign_Hub\Core\Activator', 'activate' )
);

register_deactivation_hook(
    SEO_CAMPAIGN_HUB_PLUGIN_FILE,
    array( 'SEO_Campaign_Hub\Core\Deactivator', 'deactivate' )
);

// ============================================
// PLUGIN BOOT — inside plugins_loaded
// ============================================

add_action( 'plugins_loaded', 'seo_campaign_hub_boot', 10 );

function seo_campaign_hub_boot() {
    try {
        if ( ! defined( 'SEO_CAMPAIGN_HUB_TABLE_PREFIX' ) ) {
            global $wpdb;
            define( 'SEO_CAMPAIGN_HUB_TABLE_PREFIX', $wpdb->prefix . 'sch_' );
        }

        load_plugin_textdomain(
            'seo-campaign-hub',
            false,
            dirname( SEO_CAMPAIGN_HUB_PLUGIN_BASENAME ) . '/languages'
        );

        $GLOBALS['seo_campaign_hub'] = \SEO_Campaign_Hub\Core\Plugin::get_instance();

        do_action( 'seo_campaign_hub_loaded', $GLOBALS['seo_campaign_hub'] );
    } catch ( \Throwable $e ) {
        // Never take down WordPress if the plugin fails to boot.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'SEO Campaign Hub boot failed: ' . $e->getMessage() );
        }
        add_action(
            'admin_notices',
            static function () use ( $e ) {
                if ( ! current_user_can( 'activate_plugins' ) ) {
                    return;
                }
                echo '<div class="notice notice-error"><p><strong>SEO Campaign Hub:</strong> ';
                echo esc_html( $e->getMessage() );
                echo '</p></div>';
            }
        );
    }
}

// ============================================
// WORDPRESS INIT ACTION
// ============================================

add_action( 'init', function () {
    do_action( 'seo_campaign_hub_init' );
}, 10 );

// ============================================
// GLOBAL HELPER
// ============================================

if ( ! function_exists( 'seo_campaign_hub' ) ) {
    function seo_campaign_hub() {
        return $GLOBALS['seo_campaign_hub'] ?? null;
    }
}

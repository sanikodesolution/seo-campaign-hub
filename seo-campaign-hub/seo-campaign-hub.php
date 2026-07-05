<?php
/**
 * Plugin Name: SEO Campaign Hub
 * Plugin URI: https://seocampaignhub.com
 * Description: Advanced SEO landing page builder with affiliate marketing, URL shortening, analytics, and campaign management
 * Version: 1.0.0
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
    define( 'SEO_CAMPAIGN_HUB_VERSION', '1.0.0' );
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

// Cache expiration times (in seconds)
if ( ! defined( 'SEO_CAMPAIGN_HUB_CACHE_SHORT' ) ) {
    define( 'SEO_CAMPAIGN_HUB_CACHE_SHORT', 300 ); // 5 minutes
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_CACHE_MEDIUM' ) ) {
    define( 'SEO_CAMPAIGN_HUB_CACHE_MEDIUM', 3600 ); // 1 hour
}

if ( ! defined( 'SEO_CAMPAIGN_HUB_CACHE_LONG' ) ) {
    define( 'SEO_CAMPAIGN_HUB_CACHE_LONG', 86400 ); // 24 hours
}

// ============================================
// REQUIREMENT CHECKS
// ============================================

/**
 * Check PHP version compatibility — must run before autoloader.
 */
if ( version_compare( PHP_VERSION, SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION, '<' ) ) {
    add_action( 'admin_notices', 'seo_campaign_hub_php_version_error' );

    function seo_campaign_hub_php_version_error() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'SEO Campaign Hub Error:', 'seo-campaign-hub' ); ?></strong>
                <?php
                printf(
                    esc_html__(
                        'This plugin requires PHP version %1$s or higher. Your current PHP version is %2$s. Please upgrade your PHP version to use this plugin.',
                        'seo-campaign-hub'
                    ),
                    esc_html( SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION ),
                    esc_html( PHP_VERSION )
                );
                ?>
            </p>
        </div>
        <?php
    }

    return; // Stop execution — do NOT load autoloader or register hooks
}

/**
 * Check WordPress version compatibility.
 */
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
                    esc_html__(
                        'This plugin requires WordPress version %1$s or higher. Your current WordPress version is %2$s. Please upgrade WordPress to use this plugin.',
                        'seo-campaign-hub'
                    ),
                    esc_html( SEO_CAMPAIGN_HUB_MINIMUM_WP_VERSION ),
                    esc_html( $wp_version )
                );
                ?>
            </p>
        </div>
        <?php
    }

    return; // Stop execution
}

// ============================================
// AUTOLOADER
// ============================================

require_once SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'includes/class-autoloader.php';

\SEO_Campaign_Hub\Core\Autoloader::register();

// ============================================
// ACTIVATION / DEACTIVATION / UNINSTALL HOOKS
// Note: Must be registered at file load time — NOT inside any hook.
// ============================================

register_activation_hook(
    SEO_CAMPAIGN_HUB_PLUGIN_FILE,
    [ 'SEO_Campaign_Hub\Core\Activator', 'activate' ]
);

register_deactivation_hook(
    SEO_CAMPAIGN_HUB_PLUGIN_FILE,
    [ 'SEO_Campaign_Hub\Core\Deactivator', 'deactivate' ]
);

/**
 * Uninstall hook — uses the procedural function in uninstall.php.
 * WordPress calls uninstall.php directly; register_uninstall_hook()
 * simply flags the file. A class-based callback is only safe if the
 * class file is explicitly loaded inside uninstall.php itself.
 */
register_uninstall_hook( SEO_CAMPAIGN_HUB_PLUGIN_FILE, 'seo_campaign_hub_uninstall' );

// ============================================
// PLUGIN INITIALISATION — inside plugins_loaded
// ============================================

add_action( 'plugins_loaded', 'seo_campaign_hub_boot', 10 );

/**
 * Boot the plugin.
 *
 * Runs on plugins_loaded so that:
 *  - $wpdb is fully available (safe to define DB prefix constant here)
 *  - All other plugins are loaded (no dependency ordering issues)
 *  - Text domain is loaded before any translatable string is used
 */
function seo_campaign_hub_boot() {

    // ---- DB table prefix (needs $wpdb — define here, not at file top) ----
    if ( ! defined( 'SEO_CAMPAIGN_HUB_TABLE_PREFIX' ) ) {
        global $wpdb;
        define( 'SEO_CAMPAIGN_HUB_TABLE_PREFIX', $wpdb->prefix . 'sch_' );
    }

    // ---- Text domain ----
    load_plugin_textdomain(
        'seo-campaign-hub',
        false,
        dirname( SEO_CAMPAIGN_HUB_PLUGIN_BASENAME ) . '/languages'
    );

    // ---- Bootstrap main plugin instance ----
    $GLOBALS['seo_campaign_hub'] = \SEO_Campaign_Hub\Core\Plugin::get_instance();

    /**
     * Fires after SEO Campaign Hub is fully loaded.
     *
     * @param \SEO_Campaign_Hub\Core\Plugin $plugin The plugin instance.
     */
    do_action( 'seo_campaign_hub_loaded', $GLOBALS['seo_campaign_hub'] );
}

// ============================================
// WORDPRESS INIT ACTION
// ============================================

add_action( 'init', function () {
    /**
     * Fires during WordPress init — use for registering CPTs, taxonomies, etc.
     */
    do_action( 'seo_campaign_hub_init' );
}, 10 );

// ============================================
// GLOBAL HELPER (optional convenience accessor)
// ============================================

if ( ! function_exists( 'seo_campaign_hub' ) ) {
    /**
     * Return the main plugin instance.
     *
     * Usage: seo_campaign_hub()->some_method();
     *
     * @return \SEO_Campaign_Hub\Core\Plugin|null
     */
    function seo_campaign_hub() {
        return $GLOBALS['seo_campaign_hub'] ?? null;
    }
}

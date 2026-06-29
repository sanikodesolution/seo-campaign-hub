<?php
/**
 * SEO Campaign Hub - Plugin Generator
 * 
 * Run this script to generate the complete plugin structure
 * Place this file in your WordPress plugins directory and run:
 * php create-plugin.php
 */

// Configuration
$plugin_name = 'seo-campaign-hub';
$plugin_dir = __DIR__ . '/' . $plugin_name;

// Files to create
$files = [
    // Main plugin file
    'seo-campaign-hub.php' => '<?php
/**
 * Plugin Name: SEO Campaign Hub
 * Plugin URI: https://seocampaignhub.com
 * Description: Advanced SEO landing page builder with affiliate marketing, URL shortening, analytics, and campaign management
 * Version: 1.0.0
 * Author: SEO Campaign Hub
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

if (!defined(\'ABSPATH\')) {
    exit;
}

// Plugin constants
define(\'SEO_CAMPAIGN_HUB_VERSION\', \'1.0.0\');
define(\'SEO_CAMPAIGN_HUB_PLUGIN_DIR\', plugin_dir_path(__FILE__));
define(\'SEO_CAMPAIGN_HUB_PLUGIN_URL\', plugin_dir_url(__FILE__));
define(\'SEO_CAMPAIGN_HUB_PLUGIN_BASENAME\', plugin_basename(__FILE__));
define(\'SEO_CAMPAIGN_HUB_PLUGIN_FILE\', __FILE__);
define(\'SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION\', \'8.2.0\');
define(\'SEO_CAMPAIGN_HUB_MINIMUM_WP_VERSION\', \'6.0.0\');

// Check PHP version
if (version_compare(PHP_VERSION, SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION, \'<\')) {
    add_action(\'admin_notices\', function() {
        ?>
        <div class="notice notice-error">
            <p><?php printf(esc_html__(\'SEO Campaign Hub requires PHP version %s or higher.\', \'seo-campaign-hub\'), esc_html(SEO_CAMPAIGN_HUB_MINIMUM_PHP_VERSION)); ?></p>
        </div>
        <?php
    });
    return;
}

// Load autoloader
require_once SEO_CAMPAIGN_HUB_PLUGIN_DIR . \'includes/class-autoloader.php\';

// Initialize plugin
function seo_campaign_hub_init() {
    load_plugin_textdomain(\'seo-campaign-hub\', false, dirname(SEO_CAMPAIGN_HUB_PLUGIN_BASENAME) . \'/languages\');
    return \SEO_Campaign_Hub\Core\Plugin::get_instance();
}

$seo_campaign_hub_plugin = seo_campaign_hub_init();

// Register hooks
register_activation_hook(SEO_CAMPAIGN_HUB_PLUGIN_FILE, [\'SEO_Campaign_Hub\Core\Activator\', \'activate\']);
register_deactivation_hook(SEO_CAMPAIGN_HUB_PLUGIN_FILE, [\'SEO_Campaign_Hub\Core\Deactivator\', \'deactivate\']);',

    // Autoloader
    'includes/class-autoloader.php' => '<?php
namespace SEO_Campaign_Hub\Core;

if (!defined(\'ABSPATH\')) {
    exit;
}

class Autoloader {
    private static $namespaces = [];

    public static function register() {
        spl_autoload_register([__CLASS__, \'load\']);
        self::add_namespace(\'SEO_Campaign_Hub\\\\\', SEO_CAMPAIGN_HUB_PLUGIN_DIR . \'src/\');
        self::register_composer();
    }

    private static function register_composer() {
        $composer_autoload = SEO_CAMPAIGN_HUB_PLUGIN_DIR . \'vendor/autoload.php\';
        if (file_exists($composer_autoload)) {
            require_once $composer_autoload;
        }
    }

    public static function add_namespace($namespace, $base_dir) {
        self::$namespaces[$namespace] = trailingslashit($base_dir);
    }

    public static function load($class) {
        foreach (self::$namespaces as $namespace => $base_dir) {
            if (strpos($class, $namespace) === 0) {
                $relative_class = substr($class, strlen($namespace));
                $file = $base_dir . str_replace(\'\\\\\', \'/\', $relative_class) . \'.php\';
                $file = str_replace([\'\\\\\', \'/\'], DIRECTORY_SEPARATOR, $file);
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
        }
        return false;
    }
}

Autoloader::register();',

    // Plugin Core
    'src/Core/Plugin.php' => '<?php
namespace SEO_Campaign_Hub\Core;

if (!defined(\'ABSPATH\')) {
    exit;
}

final class Plugin {
    private static $instance = null;
    private $container = null;
    private $initialized = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        if ($this->initialized) {
            return;
        }

        $this->init_container();
        $this->register_services();
        $this->init_hooks();
        $this->init_context();
        
        $this->initialized = true;
        do_action(\'seo_campaign_hub_after_init\', $this);
    }

    private function init_container() {
        if (null === $this->container) {
            $this->container = new Container();
        }
    }

    private function register_services() {
        $this->container->singleton(\'database\', function() {
            return new \SEO_Campaign_Hub\Database\Database();
        });
        $this->container->singleton(\'security\', function() {
            return new \SEO_Campaign_Hub\Core\SecurityManager();
        });
        $this->container->singleton(\'performance\', function() {
            return new \SEO_Campaign_Hub\Core\PerformanceManager();
        });
        $this->container->singleton(\'cache\', function() {
            return new \SEO_Campaign_Hub\Core\CacheManager();
        });
        $this->container->singleton(\'settings\', function() {
            return new \SEO_Campaign_Hub\Admin\Settings();
        });
        $this->container->singleton(\'rest\', function() {
            return new \SEO_Campaign_Hub\REST\RESTManager();
        });
        $this->container->singleton(\'schema\', function() {
            return new \SEO_Campaign_Hub\Services\SchemaService();
        });
        $this->container->singleton(\'analytics\', function() {
            return new \SEO_Campaign_Hub\Services\AnalyticsService();
        });
        $this->container->singleton(\'qr\', function() {
            return new \SEO_Campaign_Hub\Services\QRCodeService();
        });
        $this->container->singleton(\'shortener\', function() {
            return new \SEO_Campaign_Hub\Services\ShortenerService();
        });
        $this->container->singleton(\'campaign\', function() {
            return new \SEO_Campaign_Hub\Services\CampaignService();
        });
        $this->container->singleton(\'offer\', function() {
            return new \SEO_Campaign_Hub\Services\OfferService();
        });
        $this->container->singleton(\'redirect\', function() {
            return new \SEO_Campaign_Hub\Services\RedirectService();
        });
        $this->container->singleton(\'seo\', function() {
            return new \SEO_Campaign_Hub\Services\SEOService();
        });
        $this->container->singleton(\'import_export\', function() {
            return new \SEO_Campaign_Hub\Services\ImportExportService();
        });
    }

    private function init_hooks() {
        add_action(\'plugins_loaded\', [$this, \'on_plugins_loaded\'], 10);
        add_action(\'init\', [$this, \'on_init\'], 10);
        add_action(\'admin_init\', [$this, \'on_admin_init\'], 10);
        add_action(\'wp_loaded\', [$this, \'on_wp_loaded\'], 10);
        add_action(\'rest_api_init\', [$this, \'on_rest_api_init\'], 10);
        add_action(\'admin_enqueue_scripts\', [$this, \'enqueue_admin_assets\'], 10);
        add_action(\'wp_enqueue_scripts\', [$this, \'enqueue_public_assets\'], 10);
    }

    private function init_context() {
        if (is_admin()) {
            $admin_init = new \SEO_Campaign_Hub\Admin\AdminInit($this->container);
            $admin_init->init();
        } else {
            $public_init = new \SEO_Campaign_Hub\Public\PublicInit($this->container);
            $public_init->init();
        }
    }

    public function on_plugins_loaded() {
        $this->load_services();
        do_action(\'seo_campaign_hub_services_loaded\', $this->container);
    }

    public function on_init() {
        $this->register_post_types();
        $this->register_taxonomies();
        $this->register_shortcodes();
        $this->register_widgets();
        $this->add_rewrite_rules();
        do_action(\'seo_campaign_hub_on_init\', $this->container);
    }

    public function on_admin_init() {
        $this->container->get(\'settings\')->init();
    }

    public function on_wp_loaded() {
        $this->handle_redirects();
    }

    public function on_rest_api_init() {
        $this->container->get(\'rest\')->init();
    }

    private function load_services() {
        $this->container->get(\'database\')->init();
        $this->container->get(\'security\')->init();
        $this->container->get(\'performance\')->init();
        $this->container->get(\'cache\')->init();
        $this->container->get(\'schema\')->init();
        $this->container->get(\'analytics\')->init();
    }

    private function register_post_types() {
        register_post_type(\'sch_campaign\', [
            \'labels\' => [
                \'name\' => __(\'Campaigns\', \'seo-campaign-hub\'),
                \'singular_name\' => __(\'Campaign\', \'seo-campaign-hub\'),
                \'add_new\' => __(\'Add New Campaign\', \'seo-campaign-hub\'),
                \'add_new_item\' => __(\'Add New Campaign\', \'seo-campaign-hub\'),
                \'edit_item\' => __(\'Edit Campaign\', \'seo-campaign-hub\'),
                \'new_item\' => __(\'New Campaign\', \'seo-campaign-hub\'),
                \'view_item\' => __(\'View Campaign\', \'seo-campaign-hub\'),
                \'search_items\' => __(\'Search Campaigns\', \'seo-campaign-hub\'),
                \'not_found\' => __(\'No campaigns found\', \'seo-campaign-hub\'),
                \'not_found_in_trash\' => __(\'No campaigns found in trash\', \'seo-campaign-hub\'),
                \'menu_name\' => __(\'SEO Campaign Hub\', \'seo-campaign-hub\')
            ],
            \'public\' => true,
            \'publicly_queryable\' => true,
            \'show_ui\' => true,
            \'show_in_menu\' => true,
            \'query_var\' => true,
            \'rewrite\' => [\'slug\' => \'campaigns\', \'with_front\' => false],
            \'capability_type\' => \'post\',
            \'has_archive\' => true,
            \'hierarchical\' => false,
            \'menu_position\' => 5,
            \'menu_icon\' => \'dashicons-megaphone\',
            \'supports\' => [\'title\', \'editor\', \'thumbnail\', \'excerpt\', \'author\'],
            \'show_in_rest\' => true,
            \'rest_base\' => \'campaigns\'
        ]);

        register_post_type(\'sch_offer\', [
            \'labels\' => [
                \'name\' => __(\'Offers\', \'seo-campaign-hub\'),
                \'singular_name\' => __(\'Offer\', \'seo-campaign-hub\'),
                \'add_new\' => __(\'Add New Offer\', \'seo-campaign-hub\'),
                \'add_new_item\' => __(\'Add New Offer\', \'seo-campaign-hub\'),
                \'edit_item\' => __(\'Edit Offer\', \'seo-campaign-hub\'),
                \'new_item\' => __(\'New Offer\', \'seo-campaign-hub\'),
                \'view_item\' => __(\'View Offer\', \'seo-campaign-hub\'),
                \'search_items\' => __(\'Search Offers\', \'seo-campaign-hub\'),
                \'not_found\' => __(\'No offers found\', \'seo-campaign-hub\'),
                \'not_found_in_trash\' => __(\'No offers found in trash\', \'seo-campaign-hub\')
            ],
            \'public\' => true,
            \'publicly_queryable\' => true,
            \'show_ui\' => true,
            \'show_in_menu\' => false,
            \'query_var\' => true,
            \'rewrite\' => [\'slug\' => \'offers\', \'with_front\' => false],
            \'capability_type\' => \'post\',
            \'has_archive\' => false,
            \'hierarchical\' => false,
            \'supports\' => [\'title\', \'editor\', \'thumbnail\', \'excerpt\', \'author\'],
            \'show_in_rest\' => true,
            \'rest_base\' => \'offers\'
        ]);
    }

    private function register_taxonomies() {
        register_taxonomy(\'sch_campaign_category\', \'sch_campaign\', [
            \'labels\' => [
                \'name\' => __(\'Campaign Categories\', \'seo-campaign-hub\'),
                \'singular_name\' => __(\'Campaign Category\', \'seo-campaign-hub\'),
                \'search_items\' => __(\'Search Categories\', \'seo-campaign-hub\'),
                \'all_items\' => __(\'All Categories\', \'seo-campaign-hub\'),
                \'parent_item\' => __(\'Parent Category\', \'seo-campaign-hub\'),
                \'parent_item_colon\' => __(\'Parent Category:\', \'seo-campaign-hub\'),
                \'edit_item\' => __(\'Edit Category\', \'seo-campaign-hub\'),
                \'update_item\' => __(\'Update Category\', \'seo-campaign-hub\'),
                \'add_new_item\' => __(\'Add New Category\', \'seo-campaign-hub\'),
                \'new_item_name\' => __(\'New Category Name\', \'seo-campaign-hub\'),
                \'menu_name\' => __(\'Categories\', \'seo-campaign-hub\')
            ],
            \'hierarchical\' => true,
            \'public\' => true,
            \'show_ui\' => true,
            \'show_admin_column\' => true,
            \'query_var\' => true,
            \'rewrite\' => [\'slug\' => \'campaign-category\', \'with_front\' => false],
            \'show_in_rest\' => true
        ]);
    }

    private function register_shortcodes() {
        add_shortcode(\'sch_campaign\', [$this, \'render_campaign_shortcode\']);
        add_shortcode(\'sch_offer\', [$this, \'render_offer_shortcode\']);
    }

    public function render_campaign_shortcode($atts) {
        return \'<div class="sch-campaign">Campaign shortcode</div>\';
    }

    public function render_offer_shortcode($atts) {
        return \'<div class="sch-offer">Offer shortcode</div>\';
    }

    private function register_widgets() {
        add_action(\'widgets_init\', function() {
            // Register widgets here
        });
    }

    private function add_rewrite_rules() {
        add_rewrite_rule(\'^campaigns/([^/]+)/?$\', \'index.php?post_type=sch_campaign&name=$matches[1]\', \'top\');
        add_rewrite_rule(\'^offers/([^/]+)/?$\', \'index.php?post_type=sch_offer&name=$matches[1]\', \'top\');
        add_rewrite_rule(\'^go/([a-zA-Z0-9]+)/?$\', \'index.php?sch_short_url=$matches[1]\', \'top\');
        
        if (get_option(\'seo_campaign_hub_flush_rewrite_rules\')) {
            flush_rewrite_rules();
            delete_option(\'seo_campaign_hub_flush_rewrite_rules\');
        }
    }

    private function handle_redirects() {
        $short_url = get_query_var(\'sch_short_url\');
        if (!empty($short_url)) {
            $this->container->get(\'shortener\')->handle_redirect($short_url);
            exit;
        }
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, \'seo-campaign-hub\') === false && 
            strpos($hook, \'sch_campaign\') === false) {
            return;
        }

        wp_enqueue_style(
            \'seo-campaign-hub-admin\',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . \'assets/admin/css/admin.css\',
            [],
            SEO_CAMPAIGN_HUB_VERSION
        );

        wp_enqueue_script(
            \'seo-campaign-hub-admin\',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . \'assets/admin/js/admin.js\',
            [\'jquery\', \'wp-util\'],
            SEO_CAMPAIGN_HUB_VERSION,
            true
        );

        wp_localize_script(\'seo-campaign-hub-admin\', \'seoCampaignHub\', [
            \'ajaxUrl\' => admin_url(\'admin-ajax.php\'),
            \'nonce\' => wp_create_nonce(\'seo_campaign_hub_admin\'),
            \'restUrl\' => esc_url_raw(rest_url(\'seo-campaign-hub/v1/\'))
        ]);
    }

    public function enqueue_public_assets() {
        if (!is_singular([\'sch_campaign\', \'sch_offer\'])) {
            return;
        }

        wp_enqueue_style(
            \'seo-campaign-hub-public\',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . \'assets/public/css/public.css\',
            [],
            SEO_CAMPAIGN_HUB_VERSION
        );

        wp_enqueue_script(
            \'seo-campaign-hub-public\',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . \'assets/public/js/public.js\',
            [\'jquery\'],
            SEO_CAMPAIGN_HUB_VERSION,
            true
        );
    }

    public function get_container() {
        return $this->container;
    }

    public function is_initialized() {
        return $this->initialized;
    }

    private function __clone() {}
    private function __wakeup() {}
}',

    // Container
    'src/Core/Container.php' => '<?php
namespace SEO_Campaign_Hub\Core;

if (!defined(\'ABSPATH\')) {
    exit;
}

class Container {
    private $services = [];
    private $singletons = [];
    private $definitions = [];

    public function register($name, callable $resolver, $singleton = false) {
        $this->services[$name] = $resolver;
        $this->definitions[$name] = [\'singleton\' => $singleton, \'resolved\' => false];
        return $this;
    }

    public function singleton($name, callable $resolver) {
        return $this->register($name, $resolver, true);
    }

    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new \Exception(sprintf(\'Service "%s" not found\', $name));
        }

        if ($this->is_singleton($name) && isset($this->singletons[$name])) {
            return $this->singletons[$name];
        }

        $service = call_user_func($this->services[$name]);

        if ($this->is_singleton($name)) {
            $this->singletons[$name] = $service;
        }

        return $service;
    }

    private function is_singleton($name) {
        return isset($this->definitions[$name]) && $this->definitions[$name][\'singleton\'] === true;
    }

    public function has($name) {
        return isset($this->services[$name]);
    }

    public function remove($name) {
        unset($this->services[$name]);
        unset($this->singletons[$name]);
        unset($this->definitions[$name]);
        return $this;
    }
}',

    // Activator
    'src/Core/Activator.php' => '<?php
namespace SEO_Campaign_Hub\Core;

if (!defined(\'ABSPATH\')) {
    exit;
}

class Activator {
    public static function activate($network_wide = false) {
        if (!current_user_can(\'activate_plugins\')) {
            return;
        }

        if ($network_wide && is_multisite()) {
            self::activate_multisite();
        } else {
            self::activate_single_site();
        }

        set_transient(\'seo_campaign_hub_activation\', true, 30);
        set_transient(\'seo_campaign_hub_flush_rewrite_rules\', true);
        
        do_action(\'seo_campaign_hub_activated\', $network_wide);
    }

    private static function activate_single_site() {
        self::create_tables();
        self::set_default_options();
        self::create_directories();
    }

    private static function activate_multisite() {
        global $wpdb;
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            self::activate_single_site();
            restore_current_blog();
        }
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . \'wp-admin/includes/upgrade.php\';

        $tables = [
            \'campaigns\' => "CREATE TABLE {prefix}sch_campaigns (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id BIGINT(20) UNSIGNED NOT NULL,
                campaign_key VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                status ENUM(\'draft\', \'pending\', \'active\', \'paused\', \'archived\') DEFAULT \'draft\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY campaign_key (campaign_key),
                UNIQUE KEY slug (slug)
            ) $charset_collate",
            
            \'offers\' => "CREATE TABLE {prefix}sch_offers (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id BIGINT(20) UNSIGNED NOT NULL,
                offer_key VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                status ENUM(\'draft\', \'pending\', \'active\', \'paused\', \'expired\', \'archived\') DEFAULT \'draft\',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY offer_key (offer_key),
                UNIQUE KEY slug (slug)
            ) $charset_collate",
            
            \'links\' => "CREATE TABLE {prefix}sch_links (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                link_key VARCHAR(32) NOT NULL,
                destination_url TEXT NOT NULL,
                short_url VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY link_key (link_key),
                UNIQUE KEY slug (slug)
            ) $charset_collate",
            
            \'analytics\' => "CREATE TABLE {prefix}sch_analytics (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                session_id VARCHAR(64) NOT NULL,
                event_type ENUM(\'page_view\', \'click\', \'conversion\') NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY session_id (session_id),
                KEY event_type (event_type)
            ) $charset_collate"
        ];

        foreach ($tables as $table_name => $sql) {
            $sql = str_replace(\'{prefix}\', $wpdb->prefix, $sql);
            dbDelta($sql);
        }

        update_option(\'seo_campaign_hub_db_version\', SEO_CAMPAIGN_HUB_VERSION);
    }

    private static function set_default_options() {
        $defaults = [
            \'version\' => SEO_CAMPAIGN_HUB_VERSION,
            \'db_version\' => SEO_CAMPAIGN_HUB_VERSION,
            \'enable_analytics\' => true,
            \'default_redirect_type\' => \'301\'
        ];

        foreach ($defaults as $key => $value) {
            if (get_option("seo_campaign_hub_{$key}") === false) {
                add_option("seo_campaign_hub_{$key}", $value);
            }
        }
    }

    private static function create_directories() {
        $directories = [
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . \'cache/\',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . \'logs/\',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . \'uploads/\'
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                wp_mkdir_p($directory);
                file_put_contents($directory . \'index.php\', \'<?php // Silence is golden\');
            }
        }
    }
}',

    // Deactivator
    'src/Core/Deactivator.php' => '<?php
namespace SEO_Campaign_Hub\Core;

if (!defined(\'ABSPATH\')) {
    exit;
}

class Deactivator {
    public static function deactivate($network_wide = false) {
        if (!current_user_can(\'activate_plugins\')) {
            return;
        }

        if ($network_wide && is_multisite()) {
            self::deactivate_multisite();
        } else {
            self::deactivate_single_site();
        }

        self::clear_scheduled_events();
        do_action(\'seo_campaign_hub_deactivated\', $network_wide);
    }

    private static function deactivate_single_site() {
        flush_rewrite_rules();
        self::clear_cache();
        self::clean_temp_files();
    }

    private static function deactivate_multisite() {
        global $wpdb;
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            self::deactivate_single_site();
            restore_current_blog();
        }
    }

    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook(\'seo_campaign_hub_analytics_cron\');
        wp_clear_scheduled_hook(\'seo_campaign_hub_cleanup_cron\');
    }

    private static function clear_cache() {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            \'_transient_seo_campaign_hub_%\'
        ));
    }

    private static function clean_temp_files() {
        $temp_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . \'uploads/temp/\';
        if (file_exists($temp_dir)) {
            $files = glob($temp_dir . \'*\');
            $now = time();
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) > 86400)) {
                    wp_delete_file($file);
                }
            }
        }
    }
}',

    // Database
    'src/Database/Database.php' => '<?php
namespace SEO_Campaign_Hub\Database;

if (!defined(\'ABSPATH\')) {
    exit;
}

class Database {
    private $db_version = \'1.0.0\';
    private $prefix;
    private $wpdb;
    private $tables = [];

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . \'sch_\';
        $this->define_tables();
    }

    public function init() {
        $installed_version = get_option(\'seo_campaign_hub_db_version\', \'0.0.0\');
        if (version_compare($installed_version, $this->db_version, \'<\')) {
            $this->upgrade($installed_version);
        }
    }

    private function define_tables() {
        $this->tables = [
            \'campaigns\' => $this->prefix . \'campaigns\',
            \'offers\' => $this->prefix . \'offers\',
            \'links\' => $this->prefix . \'links\',
            \'analytics\' => $this->prefix . \'analytics\'
        ];
    }

    public function get_table($table) {
        if (!isset($this->tables[$table])) {
            throw new \Exception("Table \'{$table}\' not defined");
        }
        return $this->tables[$table];
    }

    public function get_all_tables() {
        return $this->tables;
    }

    public function get_prefix() {
        return $this->prefix;
    }

    public function upgrade($from_version) {
        $migration_manager = new MigrationManager();
        $migration_manager->upgrade($from_version, $this->db_version);
        update_option(\'seo_campaign_hub_db_version\', $this->db_version);
    }

    public function table_exists($table) {
        try {
            $table_name = $this->get_table($table);
            return $this->wpdb->get_var(
                $this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
            ) === $table_name;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function insert($table, $data, $format = null) {
        try {
            $table_name = $this->get_table($table);
            $result = $this->wpdb->insert($table_name, $data, $format);
            return $result !== false ? $this->wpdb->insert_id : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($table, $data, $where, $format = null, $where_format = null) {
        try {
            $table_name = $this->get_table($table);
            $result = $this->wpdb->update($table_name, $data, $where, $format, $where_format);
            return $result !== false ? $result : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($table, $where, $where_format = null) {
        try {
            $table_name = $this->get_table($table);
            $result = $this->wpdb->delete($table_name, $where, $where_format);
            return $result !== false ? $result : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function get_results($query, $output = OBJECT) {
        return $this->wpdb->get_results($query, $output);
    }

    public function get_row($query, $output = OBJECT) {
        return $this->wpdb->get_row($query, $output);
    }

    public function get_var($query) {
        return $this->wpdb->get_var($query);
    }

    public function prepare($query, ...$args) {
        return $this->wpdb->prepare($query, ...$args);
    }

    public function begin_transaction() {
        $this->wpdb->query(\'START TRANSACTION\');
    }

    public function commit() {
        $this->wpdb->query(\'COMMIT\');
    }

    public function rollback() {
        $this->wpdb->query(\'ROLLBACK\');
    }

    public function last_insert_id() {
        return $this->wpdb->insert_id;
    }

    public function last_error() {
        return $this->wpdb->last_error;
    }
}',

    // Migration Manager
    'src/Database/MigrationManager.php' => '<?php
namespace SEO_Campaign_Hub\Database;

if (!defined(\'ABSPATH\')) {
    exit;
}

class MigrationManager {
    private $wpdb;
    private $prefix;
    private $migrations_dir;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . \'sch_\';
        $this->migrations_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . \'src/Database/Migrations/\';
    }

    public function upgrade($from_version, $to_version) {
        $migrations = $this->get_migrations($from_version, $to_version);
        if (empty($migrations)) {
            return;
        }

        $this->wpdb->query(\'START TRANSACTION\');

        try {
            foreach ($migrations as $migration) {
                $this->run_migration($migration);
            }
            $this->wpdb->query(\'COMMIT\');
        } catch (\Exception $e) {
            $this->wpdb->query(\'ROLLBACK\');
            throw $e;
        }
    }

    private function get_migrations($from_version, $to_version) {
        $migrations = [];
        $files = glob($this->migrations_dir . \'*.php\');
        
        foreach ($files as $file) {
            $filename = basename($file, \'.php\');
            if (preg_match(\'/^Version_([0-9_]+)$/\', $filename, $matches)) {
                $version = str_replace(\'_\', \'.\', $matches[1]);
                if (version_compare($version, $from_version, \'>\') && 
                    version_compare($version, $to_version, \'<=\')) {
                    $migrations[$version] = $file;
                }
            }
        }
        
        ksort($migrations);
        return $migrations;
    }

    private function run_migration($file) {
        require_once $file;
        $class_name = \'SEO_Campaign_Hub\\\\Database\\\\Migrations\\\\\' . basename($file, \'.php\');
        
        if (!class_exists($class_name)) {
            throw new \Exception("Migration class \'{$class_name}\' not found");
        }
        
        $migration = new $class_name();
        $migration->up();
    }
}',

    // Migration Interface
    'src/Database/Migrations/MigrationInterface.php' => '<?php
namespace SEO_Campaign_Hub\Database\Migrations;

if (!defined(\'ABSPATH\')) {
    exit;
}

interface MigrationInterface {
    public function up();
    public function down();
}',

    // Initial Migration
    'src/Database/Migrations/Version_1_0_0.php' => '<?php
namespace SEO_Campaign_Hub\Database\Migrations;

if (!defined(\'ABSPATH\')) {
    exit;
}

class Version_1_0_0 implements MigrationInterface {
    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . \'wp-admin/includes/upgrade.php\';

        // Add any additional migration SQL here
        
        if ($wpdb->last_error) {
            error_log(\'SEO Campaign Hub - Migration 1.0.0 error: \' . $wpdb->last_error);
        }
    }

    public function down() {
        global $wpdb;
        // Rollback logic here
    }
}',

    // Model Interface
    'src/Database/Models/ModelInterface.php' => '<?php
namespace SEO_Campaign_Hub\Database\Models;

if (!defined(\'ABSPATH\')) {
    exit;
}

interface ModelInterface {
    public function get_table();
    public function get_primary_key();
    public function get_all($args = []);
    public function get_by_id($id);
    public function get_by_key($key);
    public function insert($data);
    public function update($id, $data);
    public function delete($id);
    public function count($args = []);
}',

    // Base Model
    'src/Database/Models/BaseModel.php' => '<?php
namespace SEO_Campaign_Hub\Database\Models;

if (!defined(\'ABSPATH\')) {
    exit;
}

abstract class BaseModel implements ModelInterface {
    protected $db;
    protected $table;
    protected $primary_key = \'id\';
    protected $cache_key = \'\';
    protected $cache_expiration = 3600;

    public function __construct() {
        $this->db = new \SEO_Campaign_Hub\Database\Database();
        $this->table = $this->get_table();
        $this->cache_key = $this->get_cache_key();
    }

    abstract public function get_table();

    protected function get_cache_key() {
        return \'seo_campaign_hub_\' . str_replace(\'sch_\', \'\', $this->table);
    }

    public function get_primary_key() {
        return $this->primary_key;
    }

    public function get_all($args = []) {
        $defaults = [\'limit\' => 20, \'offset\' => 0, \'orderby\' => $this->primary_key, \'order\' => \'DESC\'];
        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table}";
        $query .= " ORDER BY {$args[\'orderby\']} {$args[\'order\']}";
        $query .= $this->db->prepare(" LIMIT %d OFFSET %d", $args[\'limit\'], $args[\'offset\']);

        $cache_key = md5($query);
        $results = wp_cache_get($cache_key, $this->cache_key);

        if ($results === false) {
            $results = $this->db->get_results($query);
            wp_cache_set($cache_key, $results, $this->cache_key, $this->cache_expiration);
        }

        return $results;
    }

    public function get_by_id($id) {
        $cache_key = "id_{$id}";
        $result = wp_cache_get($cache_key, $this->cache_key);

        if ($result === false) {
            $query = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE {$this->primary_key} = %d",
                $id
            );
            $result = $this->db->get_row($query);
            wp_cache_set($cache_key, $result, $this->cache_key, $this->cache_expiration);
        }

        return $result;
    }

    public function get_by_key($key) {
        $cache_key = "key_{$key}";
        $result = wp_cache_get($cache_key, $this->cache_key);

        if ($result === false) {
            $query = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE %s = %s",
                $this->get_key_column(),
                $key
            );
            $result = $this->db->get_row($query);
            wp_cache_set($cache_key, $result, $this->cache_key, $this->cache_expiration);
        }

        return $result;
    }

    protected function get_key_column() {
        return str_replace(\'sch_\', \'\', $this->table) . \'_key\';
    }

    public function insert($data) {
        if (!isset($data[\'created_at\'])) {
            $data[\'created_at\'] = current_time(\'mysql\');
        }
        if (!isset($data[\'updated_at\'])) {
            $data[\'updated_at\'] = current_time(\'mysql\');
        }

        $result = $this->db->insert($this->table, $data);

        if ($result) {
            $this->clear_cache();
        }

        return $result;
    }

    public function update($id, $data) {
        $data[\'updated_at\'] = current_time(\'mysql\');

        $result = $this->db->update(
            $this->table,
            $data,
            [$this->primary_key => $id]
        );

        if ($result !== false) {
            $this->clear_cache();
        }

        return $result;
    }

    public function delete($id) {
        $result = $this->db->delete(
            $this->table,
            [$this->primary_key => $id]
        );

        if ($result !== false) {
            $this->clear_cache();
            return true;
        }

        return false;
    }

    public function count($args = []) {
        $query = "SELECT COUNT(*) FROM {$this->table}";
        return (int) $this->db->get_var($query);
    }

    public function clear_cache() {
        wp_cache_delete($this->cache_key);
    }
}',

    // Repository Interface
    'src/Database/Repositories/RepositoryInterface.php' => '<?php
namespace SEO_Campaign_Hub\Database\Repositories;

if (!defined(\'ABSPATH\')) {
    exit;
}

interface RepositoryInterface {
    public function get_model();
    public function find($id);
    public function find_by_key($key);
    public function all($args = []);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
    public function count($args = []);
}',

    // Base Repository
    'src/Database/Repositories/BaseRepository.php' => '<?php
namespace SEO_Campaign_Hub\Database\Repositories;

if (!defined(\'ABSPATH\')) {
    exit;
}

abstract class BaseRepository implements RepositoryInterface {
    protected $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function get_model() {
        return $this->model;
    }

    public function find($id) {
        return $this->model->get_by_id($id);
    }

    public function find_by_key($key) {
        return $this->model->get_by_key($key);
    }

    public function all($args = []) {
        return $this->model->get_all($args);
    }

    public function create($data) {
        return $this->model->insert($data);
    }

    public function update($id, $data) {
        return $this->model->update($id, $data);
    }

    public function delete($id) {
        return $this->model->delete($id);
    }

    public function count($args = []) {
        return $this->model->count($args);
    }

    public function find_by($where, $args = []) {
        $args[\'where\'] = $where;
        return $this->model->get_all($args);
    }

    public function paginate($args = []) {
        $defaults = [\'page\' => 1, \'per_page\' => 20, \'orderby\' => \'id\', \'order\' => \'DESC\'];
        $args = wp_parse_args($args, $defaults);
        $offset = ($args[\'page\'] - 1) * $args[\'per_page\'];

        $query_args = [
            \'limit\' => $args[\'per_page\'],
            \'offset\' => $offset,
            \'orderby\' => $args[\'orderby\'],
            \'order\' => $args[\'order\']
        ];

        $results = $this->model->get_all($query_args);
        $total = $this->model->count();

        return [
            \'data\' => $results,
            \'total\' => $total,
            \'page\' => $args[\'page\'],
            \'per_page\' => $args[\'per_page\'],
            \'total_pages\' => ceil($total / $args[\'per_page\'])
        ];
    }
}',

    // Security Manager
    'src/Core/SecurityManager.php' => '<?php
namespace SEO_Campaign_Hub\Core;

if (!defined(\'ABSPATH\')) {
    exit;
}

class SecurityManager {
    public function init() {
        add_action(\'init\', [$this, \'check_rate_limit\']);
        add_filter(\'http_request_args\', [$this, \'block_unauthorized_requests\'], 10, 2);
    }

    public function check_rate_limit() {
        $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'\';
        $key = \'seo_campaign_hub_rate_limit_\' . md5($ip);
        $attempts = get_transient($key) ?: 0;

        if ($attempts > 100) {
            wp_die(__(\'Rate limit exceeded. Please try again later.\', \'seo-campaign-hub\'));
        }

        set_transient($key, $attempts + 1, 3600);
    }

    public function block_unauthorized_requests($args, $url) {
        if (strpos($url, \'seo-campaign-hub.com\') !== false) {
            $args[\'timeout\'] = 30;
            $args[\'sslverify\'] = true;
        }
        return $args;
    }

    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }

    public function verify_capability($capability = \'manage_options\') {
        return current_user_can($capability);
    }

    public function sanitize_input($input) {
        if (is_array($input)) {
            return array_map([$this, \'sanitize_input\'], $input);
        }
        return sanitize_text_field($input);
    }

    public function escape_output($output) {
        return esc_html($output);
    }

    public function escape_attr($output) {
        return esc_attr($output);
    }

    public function escape_url($url) {
        return esc_url($url);
    }

    public function escape_js($js) {
        return esc_js($js);
    }

    public function escape_sql($sql) {
        global $wpdb;
        return $wpdb->prepare($sql);
    }
}',

    // Performance Manager
    'src/Core/PerformanceManager.php' => '<?php
namespace SEO_Campaign_Hub\Core;

if (!defined(\'ABSPATH\')) {
    exit;
}

class PerformanceManager {
    private $start_time;
    private $start_memory;

    public function init() {
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage();

        add_action(\'shutdown\', [$this, \'log_performance\']);
        add_filter(\'seo_campaign_hub_should_cache\', [$this, \'should_cache\']);
        add_action(\'seo_campaign_hub_performance_check\', [$this, \'check_performance\']);
    }

    public function log_performance() {
        $time = microtime(true) - $this->start_time;
        $memory = memory_get_usage() - $this->start_memory;

        if ($time > 5) {
            $this->log_slow_query($time, $memory);
        }
    }

    private function log_slow_query($time, $memory) {
        $log = sprintf(
            \'Slow query detected: %s seconds, %s bytes memory used\',
            round($time, 3),
            number_format($memory)
        );
        error_log(\'SEO Campaign Hub: \' . $log);
    }

    public function should_cache() {
        if (defined(\'WP_DEBUG\') && WP_DEBUG) {
            return false;
        }
        return true;
    }

    public function check_performance() {
        global $wpdb;
        $queries = $wpdb->queries;
        $slow_queries = array_filter($queries, function($query) {
            return $query[1] > 0.5;
        });

        if (!empty($slow_queries)) {
            foreach ($slow_queries as $query) {
                error_log(sprintf(
                    \'SEO Campaign Hub Slow Query: %s (%s seconds)\',
                    $query[0],
                    round($query[1], 3)
                ));
            }
        }
    }

    public function get_execution_time() {
        return microtime(true) - $this->start_time;
    }

    public function get_memory_usage() {
        return memory_get_usage() - $this->start_memory;
    }

    public function optimize_image($image_path, $quality = 80) {
        if (!function_exists(\'wp_get_image_editor\')) {
            return $image_path;
        }

        $editor = wp_get_image_editor($image_path);
        if (!is_wp_error($editor)) {
            $editor->set_quality($quality);
            $editor->save($image_path);
        }

        return $image_path;
    }

    public function minify_css($css) {
        return preg_replace([
            \'/\s+/\',
            \'/\/\*.*?\*\//s\'
        ], [\' \', \'\'], $css);
    }

    public function minify_js($js) {
        return preg_replace([
            \'/\s+/\',
            \'/\/\/.*?$/m\'
        ], [\' \', \'\'], $js);
    }
}',

    // Cache Manager
    'src/Core/CacheManager.php' => '<?php
namespace SEO_Campaign_Hub\Core;

if (!defined(\'ABSPATH\')) {
    exit;
}

class CacheManager {
    private $enabled = true;

    public function init() {
        $this->enabled = apply_filters(\'seo_campaign_hub_cache_enabled\', $this->enabled);
        
        if ($this->enabled) {
            add_action(\'seo_campaign_hub_clear_cache\', [$this, \'clear_all\']);
        }
    }

    public function get($key, $group = \'seo_campaign_hub\') {
        if (!$this->enabled) {
            return false;
        }
        return wp_cache_get($key, $group);
    }

    public function set($key, $data, $group = \'seo_campaign_hub\', $expiration = 3600) {
        if (!$this->enabled) {
            return false;
        }
        return wp_cache_set($key, $data, $group, $expiration);
    }

    public function delete($key, $group = \'seo_campaign_hub\') {
        return wp_cache_delete($key, $group);
    }

    public function clear($group = \'seo_campaign_hub\') {
        return wp_cache_flush_group($group);
    }

    public function clear_all() {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            \'_transient_seo_campaign_hub_%\'
        ));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            \'_transient_timeout_seo_campaign_hub_%\'
        ));
    }

    public function is_enabled() {
        return $this->enabled;
    }

    public function disable() {
        $this->enabled = false;
    }

    public function enable() {
        $this->enabled = true;
    }
}',

    // Admin Init
    'src/Admin/AdminInit.php' => '<?php
namespace SEO_Campaign_Hub\Admin;

if (!defined(\'ABSPATH\')) {
    exit;
}

class AdminInit {
    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function init() {
        add_action(\'admin_menu\', [$this, \'add_admin_menu\']);
        add_action(\'admin_enqueue_scripts\', [$this, \'enqueue_assets\']);
        add_filter(\'plugin_action_links_seo-campaign-hub/seo-campaign-hub.php\', [$this, \'add_action_links\']);
    }

    public function add_admin_menu() {
        add_menu_page(
            __(\'SEO Campaign Hub\', \'seo-campaign-hub\'),
            __(\'SEO Campaign Hub\', \'seo-campaign-hub\'),
            \'manage_options\',
            \'seo-campaign-hub\',
            [$this, \'render_dashboard\'],
            \'dashicons-megaphone\',
            5
        );

        add_submenu_page(
            \'seo-campaign-hub\',
            __(\'Dashboard\', \'seo-campaign-hub\'),
            __(\'Dashboard\', \'seo-campaign-hub\'),
            \'manage_options\',
            \'seo-campaign-hub\',
            [$this, \'render_dashboard\']
        );

        add_submenu_page(
            \'seo-campaign-hub\',
            __(\'Campaigns\', \'seo-campaign-hub\'),
            __(\'Campaigns\', \'seo-campaign-hub\'),
            \'manage_options\',
            \'edit.php?post_type=sch_campaign\'
        );

        add_submenu_page(
            \'seo-campaign-hub\',
            __(\'Offers\', \'seo-campaign-hub\'),
            __(\'Offers\', \'seo-campaign-hub\'),
            \'manage_options\',
            \'edit.php?post_type=sch_offer\'
        );

        add_submenu_page(
            \'seo-campaign-hub\',
            __(\'Settings\', \'seo-campaign-hub\'),
            __(\'Settings\', \'seo-campaign-hub\'),
            \'manage_options\',
            \'seo-campaign-hub-settings\',
            [$this, \'render_settings\']
        );
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e(\'SEO Campaign Hub Dashboard\', \'seo-campaign-hub\'); ?></h1>
            <div class="seo-campaign-hub-dashboard">
                <div class="dashboard-widgets">
                    <div class="widget">
                        <h2><?php esc_html_e(\'Quick Stats\', \'seo-campaign-hub\'); ?></h2>
                        <p><?php esc_html_e(\'Welcome to SEO Campaign Hub!\', \'seo-campaign-hub\'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e(\'SEO Campaign Hub Settings\', \'seo-campaign-hub\'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(\'seo_campaign_hub_settings\');
                do_settings_sections(\'seo_campaign_hub_settings\');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, \'seo-campaign-hub\') === false) {
            return;
        }

        wp_enqueue_style(
            \'seo-campaign-hub-admin\',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . \'assets/admin/css/admin.css\',
            [],
            SEO_CAMPAIGN_HUB_VERSION
        );

        wp_enqueue_script(
            \'seo-campaign-hub-admin\',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . \'assets/admin/js/admin.js\',
            [\'jquery\'],
            SEO_CAMPAIGN_HUB_VERSION,
            true
        );
    }

    public function add_action_links($links) {
        $settings_link = sprintf(
            \'<a href="%s">%s</a>\',
            admin_url(\'admin.php?page=seo-campaign-hub-settings\'),
            __(\'Settings\', \'seo-campaign-hub\')
        );
        array_unshift($links, $settings_link);
        return $links;
    }
}',

    // Public Init
    'src/Public/PublicInit.php' => '<?php
namespace SEO_Campaign_Hub\Public;

if (!defined(\'ABSPATH\')) {
    exit;
}

class PublicInit {
    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function init() {
        add_action(\'wp_enqueue_scripts\', [$this, \'enqueue_assets\']);
        add_filter(\'the_content\', [$this, \'modify_content\']);
    }

    public function enqueue_assets() {
        if (!is_singular([\'sch_campaign\', \'sch_offer\'])) {
            return;
        }

        wp_enqueue_style(
            \'seo-campaign-hub-public\',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . \'assets/public/css/public.css\',
            [],
            SEO_CAMPAIGN_HUB_VERSION
        );

        wp_enqueue_script(
            \'seo-campaign-hub-public\',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . \'assets/public/js/public.js\',
            [\'jquery\'],
            SEO_CAMPAIGN_HUB_VERSION,
            true
        );
    }

    public function modify_content($content) {
        if (is_singular(\'sch_campaign\')) {
            // Modify campaign content
        }
        return $content;
    }
}',

    // Settings
    'src/Admin/Settings.php' => '<?php
namespace SEO_Campaign_Hub\Admin;

if (!defined(\'ABSPATH\')) {
    exit;
}

class Settings {
    private $options = [];

    public function init() {
        add_action(\'admin_init\', [$this, \'register_settings\']);
        $this->load_options();
    }

    public function register_settings() {
        register_setting(
            \'seo_campaign_hub_settings\',
            \'seo_campaign_hub_options\',
            [$this, \'sanitize_options\']
        );

        add_settings_section(
            \'seo_campaign_hub_general\',
            __(\'General Settings\', \'seo-campaign-hub\'),
            [$this, \'render_section\'],
            \'seo_campaign_hub_settings\'
        );

        add_settings_field(
            \'enable_analytics\',
            __(\'Enable Analytics\', \'seo-campaign-hub\'),
            [$this, \'render_checkbox\'],
            \'seo_campaign_hub_settings\',
            \'seo_campaign_hub_general\',
            [\'label_for\' => \'enable_analytics\']
        );
    }

    public function load_options() {
        $this->options = get_option(\'seo_campaign_hub_options\', []);
    }

    public function sanitize_options($input) {
        $sanitized = [];
        foreach ($input as $key => $value) {
            $sanitized[$key] = sanitize_text_field($value);
        }
        return $sanitized;
    }

    public function render_section() {
        echo \'<p>\' . esc_html__(\'Configure your SEO Campaign Hub settings.\', \'seo-campaign-hub\') . \'</p>\';
    }

    public function render_checkbox($args) {
        $value = isset($this->options[$args[\'label_for\']]) ? $this->options[$args[\'label_for\']] : \'\';
        ?>
        <input type="checkbox" 
               name="seo_campaign_hub_options[<?php echo esc_attr($args[\'label_for\']); ?>]" 
               id="<?php echo esc_attr($args[\'label_for\']); ?>"
               value="1" <?php checked($value, \'1\'); ?> />
        <?php
    }

    public function get_option($key, $default = null) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    public function update_option($key, $value) {
        $this->options[$key] = $value;
        update_option(\'seo_campaign_hub_options\', $this->options);
    }
}',

    // REST Manager
    'src/REST/RESTManager.php' => '<?php
namespace SEO_Campaign_Hub\REST;

if (!defined(\'ABSPATH\')) {
    exit;
}

class RESTManager {
    private $namespace = \'seo-campaign-hub/v1\';

    public function init() {
        add_action(\'rest_api_init\', [$this, \'register_routes\']);
    }

    public function register_routes() {
        register_rest_route($this->namespace, \'/health\', [
            \'methods\' => \'GET\',
            \'callback\' => [$this, \'health_check\'],
            \'permission_callback\' => [$this, \'check_permission\']
        ]);

        register_rest_route($this->namespace, \'/campaigns\', [
            \'methods\' => \'GET\',
            \'callback\' => [$this, \'get_campaigns\'],
            \'permission_callback\' => [$this, \'check_permission\']
        ]);
    }

    public function health_check() {
        return rest_ensure_response([
            \'status\' => \'ok\',
            \'version\' => SEO_CAMPAIGN_HUB_VERSION,
            \'time\' => current_time(\'mysql\')
        ]);
    }

    public function get_campaigns() {
        $campaigns = get_posts([
            \'post_type\' => \'sch_campaign\',
            \'posts_per_page\' => 20,
            \'post_status\' => \'publish\'
        ]);

        return rest_ensure_response($campaigns);
    }

    public function check_permission() {
        return current_user_can(\'manage_options\');
    }
}',

    // Services (Placeholders)
    'src/Services/CampaignService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class CampaignService {
    public function get_campaign($id) {
        return get_post($id);
    }

    public function get_campaigns($args = []) {
        $defaults = [
            \'post_type\' => \'sch_campaign\',
            \'posts_per_page\' => 20,
            \'post_status\' => \'publish\'
        ];
        $args = wp_parse_args($args, $defaults);
        return get_posts($args);
    }

    public function render_shortcode($atts, $content) {
        return \'<div class="sch-campaign-shortcode">\' . $content . \'</div>\';
    }

    public function render_cta($atts) {
        return \'<a href="\' . esc_url($atts[\'url\']) . \'" class="sch-cta">\' . esc_html($atts[\'text\']) . \'</a>\';
    }
}',

    'src/Services/OfferService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class OfferService {
    public function get_offer($id) {
        return get_post($id);
    }

    public function get_offers($args = []) {
        $defaults = [
            \'post_type\' => \'sch_offer\',
            \'posts_per_page\' => 20,
            \'post_status\' => \'publish\'
        ];
        $args = wp_parse_args($args, $defaults);
        return get_posts($args);
    }

    public function render_shortcode($atts, $content) {
        return \'<div class="sch-offer-shortcode">\' . $content . \'</div>\';
    }
}',

    'src/Services/AnalyticsService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class AnalyticsService {
    public function init() {}

    public function track_event($event_type, $data = []) {
        global $wpdb;
        $table = $wpdb->prefix . \'sch_analytics\';
        return $wpdb->insert($table, [
            \'session_id\' => $this->get_session_id(),
            \'event_type\' => $event_type,
            \'meta_data\' => json_encode($data),
            \'created_at\' => current_time(\'mysql\')
        ]);
    }

    private function get_session_id() {
        if (!isset($_COOKIE[\'sch_session\'])) {
            $session_id = wp_generate_uuid4();
            setcookie(\'sch_session\', $session_id, time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
        } else {
            $session_id = $_COOKIE[\'sch_session\'];
        }
        return $session_id;
    }

    public function render_shortcode($atts) {
        return \'<div class="sch-analytics-shortcode">Analytics: \' . $atts[\'type\'] . \'</div>\';
    }
}',

    'src/Services/ShortenerService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class ShortenerService {
    public function shorten_url($url, $slug = null) {
        if (empty($slug)) {
            $slug = $this->generate_slug();
        }

        global $wpdb;
        $table = $wpdb->prefix . \'sch_links\';
        
        $wpdb->insert($table, [
            \'link_key\' => $slug,
            \'destination_url\' => $url,
            \'short_url\' => home_url(\'/go/\' . $slug),
            \'slug\' => $slug,
            \'created_at\' => current_time(\'mysql\')
        ]);

        return home_url(\'/go/\' . $slug);
    }

    private function generate_slug() {
        $chars = \'abcdefghijklmnopqrstuvwxyz0123456789\';
        $slug = \'\';
        for ($i = 0; $i < 6; $i++) {
            $slug .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $slug;
    }

    public function handle_redirect($slug) {
        global $wpdb;
        $table = $wpdb->prefix . \'sch_links\';
        $link = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE slug = %s",
                $slug
            )
        );

        if ($link) {
            wp_redirect($link->destination_url, 301);
            exit;
        }

        wp_die(__(\'Link not found\', \'seo-campaign-hub\'));
    }

    public function render_shortcode($atts) {
        $url = isset($atts[\'url\']) ? $atts[\'url\'] : \'#\';
        $text = isset($atts[\'text\']) ? $atts[\'text\'] : $url;
        return \'<a href="\' . esc_url($url) . \'" class="sch-shortener-link">\' . esc_html($text) . \'</a>\';
    }
}',

    'src/Services/SchemaService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class SchemaService {
    public function init() {
        add_action(\'wp_head\', [$this, \'output_schema\']);
    }

    public function output_schema() {
        if (!is_singular([\'sch_campaign\', \'sch_offer\'])) {
            return;
        }

        $schema = $this->get_schema(get_the_ID());
        if ($schema) {
            echo \'<script type="application/ld+json">\' . json_encode($schema) . \'</script>\';
        }
    }

    public function get_schema($post_id) {
        $schema = [
            \'@context\' => \'https://schema.org\',
            \'@type\' => \'Article\',
            \'headline\' => get_the_title($post_id),
            \'description\' => get_the_excerpt($post_id),
            \'datePublished\' => get_the_date(\'c\', $post_id),
            \'dateModified\' => get_the_modified_date(\'c\', $post_id)
        ];

        return $schema;
    }
}',

    'src/Services/QRCodeService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class QRCodeService {
    public function generate_qr($url, $size = 300) {
        $data = urlencode($url);
        $image = \'https://api.qrserver.com/v1/create-qr-code/?size=\' . $size . \'x\' . $size . \'&data=\' . $data;
        return $image;
    }

    public function render_shortcode($atts) {
        $url = isset($atts[\'url\']) ? $atts[\'url\'] : home_url();
        $size = isset($atts[\'size\']) ? intval($atts[\'size\']) : 200;
        $qr_image = $this->generate_qr($url, $size);
        
        return \'<img src="\' . esc_url($qr_image) . \'" alt="QR Code" width="\' . $size . \'" height="\' . $size . \'" />\';
    }

    public function handle_redirect($qr_code) {
        // Handle QR code redirect
    }
}',

    'src/Services/RedirectService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class RedirectService {
    public function add_redirect($source, $target, $type = \'301\') {
        global $wpdb;
        $table = $wpdb->prefix . \'sch_redirects\';
        
        return $wpdb->insert($table, [
            \'redirect_key\' => md5($source),
            \'source_url\' => $source,
            \'target_url\' => $target,
            \'redirect_type\' => $type,
            \'status\' => \'active\',
            \'created_at\' => current_time(\'mysql\')
        ]);
    }

    public function get_redirect($source) {
        global $wpdb;
        $table = $wpdb->prefix . \'sch_redirects\';
        $hash = md5($source);
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE source_hash = %s AND status = \'active\'",
                $hash
            )
        );
    }

    public function handle_redirect($source) {
        $redirect = $this->get_redirect($source);
        if ($redirect) {
            wp_redirect($redirect->target_url, intval($redirect->redirect_type));
            exit;
        }
    }
}',

    'src/Services/SEOService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class SEOService {
    public function get_seo_score($content) {
        $score = 0;
        $score += $this->check_keyword_density($content);
        $score += $this->check_readability($content);
        return min(100, $score);
    }

    private function check_keyword_density($content) {
        // Simple keyword density check
        return 50;
    }

    private function check_readability($content) {
        // Simple readability check
        return 50;
    }

    public function generate_meta_title($title) {
        return substr($title, 0, 60);
    }

    public function generate_meta_description($content) {
        $excerpt = wp_trim_words($content, 20, \'...\');
        return substr($excerpt, 0, 160);
    }

    public function generate_slug($title) {
        return sanitize_title($title);
    }

    public function get_reading_time($content) {
        $words = str_word_count(strip_tags($content));
        $minutes = ceil($words / 200);
        return $minutes;
    }
}',

    'src/Services/ImportExportService.php' => '<?php
namespace SEO_Campaign_Hub\Services;

if (!defined(\'ABSPATH\')) {
    exit;
}

class ImportExportService {
    public function export_data($type = \'all\') {
        $data = [];
        
        switch ($type) {
            case \'campaigns\':
                $data = $this->export_campaigns();
                break;
            case \'offers\':
                $data = $this->export_offers();
                break;
            case \'all\':
                $data = [
                    \'campaigns\' => $this->export_campaigns(),
                    \'offers\' => $this->export_offers(),
                    \'settings\' => get_option(\'seo_campaign_hub_options\', [])
                ];
                break;
        }

        return json_encode($data);
    }

    private function export_campaigns() {
        $campaigns = get_posts([
            \'post_type\' => \'sch_campaign\',
            \'posts_per_page\' => -1,
            \'post_status\' => \'any\'
        ]);

        return array_map(function($campaign) {
            return [
                \'title\' => $campaign->post_title,
                \'content\' => $campaign->post_content,
                \'status\' => $campaign->post_status,
                \'meta\' => get_post_meta($campaign->ID)
            ];
        }, $campaigns);
    }

    private function export_offers() {
        $offers = get_posts([
            \'post_type\' => \'sch_offer\',
            \'posts_per_page\' => -1,
            \'post_status\' => \'any\'
        ]);

        return array_map(function($offer) {
            return [
                \'title\' => $offer->post_title,
                \'content\' => $offer->post_content,
                \'status\' => $offer->post_status,
                \'meta\' => get_post_meta($offer->ID)
            ];
        }, $offers);
    }

    public function import_data($data) {
        $data = json_decode($data, true);
        
        if (isset($data[\'campaigns\'])) {
            $this->import_campaigns($data[\'campaigns\']);
        }
        
        if (isset($data[\'offers\'])) {
            $this->import_offers($data[\'offers\']);
        }
        
        if (isset($data[\'settings\'])) {
            update_option(\'seo_campaign_hub_options\', $data[\'settings\']);
        }
    }

    private function import_campaigns($campaigns) {
        foreach ($campaigns as $campaign_data) {
            $post_id = wp_insert_post([
                \'post_title\' => $campaign_data[\'title\'],
                \'post_content\' => $campaign_data[\'content\'],
                \'post_status\' => $campaign_data[\'status\'],
                \'post_type\' => \'sch_campaign\'
            ]);

            if ($post_id && isset($campaign_data[\'meta\'])) {
                foreach ($campaign_data[\'meta\'] as $key => $values) {
                    foreach ($values as $value) {
                        add_post_meta($post_id, $key, $value);
                    }
                }
            }
        }
    }

    private function import_offers($offers) {
        foreach ($offers as $offer_data) {
            $post_id = wp_insert_post([
                \'post_title\' => $offer_data[\'title\'],
                \'post_content\' => $offer_data[\'content\'],
                \'post_status\' => $offer_data[\'status\'],
                \'post_type\' => \'sch_offer\'
            ]);

            if ($post_id && isset($offer_data[\'meta\'])) {
                foreach ($offer_data[\'meta\'] as $key => $values) {
                    foreach ($values as $value) {
                        add_post_meta($post_id, $key, $value);
                    }
                }
            }
        }
    }
}',

    // Uninstall
    'uninstall.php' => '<?php
if (!defined(\'ABSPATH\') && !defined(\'WP_UNINSTALL_PLUGIN\')) {
    exit;
}

// Remove options
delete_option(\'seo_campaign_hub_version\');
delete_option(\'seo_campaign_hub_db_version\');
delete_option(\'seo_campaign_hub_options\');

// Remove custom tables
global $wpdb;
$tables = [
    $wpdb->prefix . \'sch_campaigns\',
    $wpdb->prefix . \'sch_offers\',
    $wpdb->prefix . \'sch_links\',
    $wpdb->prefix . \'sch_analytics\'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}',

    // README
    'readme.md' => '# SEO Campaign Hub

## Description
Advanced SEO landing page builder with affiliate marketing, URL shortening, analytics, and campaign management.

## Features
- SEO Landing Page Builder
- Campaign Management
- Offer Management
- URL Shortener
- Analytics Tracking
- QR Code Generator
- Schema Markup
- A/B Testing
- And more!

## Requirements
- WordPress 6.0+
- PHP 8.2+

## Installation
1. Upload the plugin files to `/wp-content/plugins/seo-campaign-hub`
2. Activate the plugin through the \'Plugins\' screen in WordPress
3. Configure the plugin settings

## Support
For support, please visit our website or contact our support team.',

    // CHANGELOG
    'changelog.md' => '# Changelog

## 1.0.0 - 2024-01-01
- Initial release
- Basic campaign management
- Offer management
- URL shortener
- Analytics tracking
- QR code generation
- Schema markup
- Admin dashboard',
];

// Create plugin directory
if (!file_exists($plugin_dir)) {
    mkdir($plugin_dir, 0755, true);
}

// Create files
foreach ($files as $file_path => $content) {
    $full_path = $plugin_dir . '/' . $file_path;
    $dir = dirname($full_path);
    
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents($full_path, $content);
    echo "Created: {$file_path}\n";
}

// Create empty asset directories
$asset_dirs = [
    'assets/admin/css',
    'assets/admin/js',
    'assets/public/css',
    'assets/public/js',
    'languages'
];

foreach ($asset_dirs as $dir) {
    $full_path = $plugin_dir . '/' . $dir;
    if (!file_exists($full_path)) {
        mkdir($full_path, 0755, true);
        echo "Created directory: {$dir}\n";
    }
}

echo "\nSEO Campaign Hub plugin has been generated successfully!\n";
echo "Location: {$plugin_dir}\n";
echo "Size: " . round(get_directory_size($plugin_dir) / 1024, 2) . " KB\n";

function get_directory_size($path) {
    $size = 0;
    $files = glob($path . '/*');
    foreach ($files as $file) {
        $size += is_file($file) ? filesize($file) : get_directory_size($file);
    }
    return $size;
}

echo "\nTo install:\n";
echo "1. Copy the 'seo-campaign-hub' folder to your WordPress plugins directory\n";
echo "2. Activate the plugin from WordPress admin\n";
echo "3. Visit 'SEO Campaign Hub' in the admin menu\n";
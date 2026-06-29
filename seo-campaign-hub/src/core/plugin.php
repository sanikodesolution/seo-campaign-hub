<?php
/**
 * Main Plugin Class
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 *
 * Main plugin class responsible for bootstrapping the plugin
 * Implements the Singleton pattern
 */
final class Plugin {
    /**
     * Plugin instance (Singleton)
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Container instance
     *
     * @var Container|null
     */
    private $container = null;

    /**
     * Plugin initialization status
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Get plugin instance (Singleton pattern)
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor (Singleton)
     */
    private function __construct() {
        // Do nothing here, initialization happens in init()
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init() {
        if ($this->initialized) {
            return;
        }

        // Initialize dependency container
        $this->init_container();

        // Register core services
        $this->register_services();

        // Initialize WordPress hooks
        $this->init_hooks();

        // Initialize context-specific components
        $this->init_context();

        // Mark as initialized
        $this->initialized = true;

        /**
         * Fires after plugin initialization
         */
        do_action('seo_campaign_hub_after_init', $this);
    }

    /**
     * Initialize dependency container
     *
     * @return void
     */
    private function init_container() {
        if (null === $this->container) {
            $this->container = new Container();
        }
    }

    /**
     * Register core services
     *
     * @return void
     */
    private function register_services() {
        // Database Service
        $this->container->singleton('database', function() {
            return new \SEO_Campaign_Hub\Database\Database();
        });

        // Security Manager
        $this->container->singleton('security', function() {
            return new \SEO_Campaign_Hub\Core\SecurityManager();
        });

        // Performance Manager
        $this->container->singleton('performance', function() {
            return new \SEO_Campaign_Hub\Core\PerformanceManager();
        });

        // Cache Manager
        $this->container->singleton('cache', function() {
            return new \SEO_Campaign_Hub\Core\CacheManager();
        });

        // Settings Service
        $this->container->singleton('settings', function() {
            return new \SEO_Campaign_Hub\Admin\Settings();
        });

        // REST Manager
        $this->container->singleton('rest', function() {
            return new \SEO_Campaign_Hub\REST\RESTManager();
        });

        // Schema Service
        $this->container->singleton('schema', function() {
            return new \SEO_Campaign_Hub\Services\SchemaService();
        });

        // Analytics Service
        $this->container->singleton('analytics', function() {
            return new \SEO_Campaign_Hub\Services\AnalyticsService();
        });

        // QR Code Service
        $this->container->singleton('qr', function() {
            return new \SEO_Campaign_Hub\Services\QRCodeService();
        });

        // URL Shortener Service
        $this->container->singleton('shortener', function() {
            return new \SEO_Campaign_Hub\Services\ShortenerService();
        });

        // Campaign Service
        $this->container->singleton('campaign', function() {
            return new \SEO_Campaign_Hub\Services\CampaignService();
        });

        // Offer Service
        $this->container->singleton('offer', function() {
            return new \SEO_Campaign_Hub\Services\OfferService();
        });

        // Redirect Service
        $this->container->singleton('redirect', function() {
            return new \SEO_Campaign_Hub\Services\RedirectService();
        });

        // SEO Service
        $this->container->singleton('seo', function() {
            return new \SEO_Campaign_Hub\Services\SEOService();
        });

        // Import/Export Service
        $this->container->singleton('import_export', function() {
            return new \SEO_Campaign_Hub\Services\ImportExportService();
        });
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Hook into WordPress
        add_action('plugins_loaded', [$this, 'on_plugins_loaded'], 10);
        add_action('init', [$this, 'on_init'], 10);
        add_action('admin_init', [$this, 'on_admin_init'], 10);
        add_action('wp_loaded', [$this, 'on_wp_loaded'], 10);
        add_action('rest_api_init', [$this, 'on_rest_api_init'], 10);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 10);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets'], 10);
    }

    /**
     * Initialize context-specific components
     *
     * @return void
     */
    private function init_context() {
        if (is_admin()) {
            // Initialize admin components
            $admin_init = new \SEO_Campaign_Hub\Admin\AdminInit($this->container);
            $admin_init->init();
        } else {
            // Initialize public components
            $public_init = new \SEO_Campaign_Hub\Public\PublicInit($this->container);
            $public_init->init();
        }
    }

    /**
     * Handle plugins_loaded action
     *
     * @return void
     */
    public function on_plugins_loaded() {
        // Load plugin services
        $this->load_services();

        /**
         * Fires after plugin services are loaded
         */
        do_action('seo_campaign_hub_services_loaded', $this->container);
    }

    /**
     * Handle init action
     *
     * @return void
     */
    public function on_init() {
        // Register custom post types
        $this->register_post_types();

        // Register custom taxonomies
        $this->register_taxonomies();

        // Register shortcodes
        $this->register_shortcodes();

        // Register widgets
        $this->register_widgets();

        // Add rewrite rules
        $this->add_rewrite_rules();

        /**
         * Fires during WordPress init
         */
        do_action('seo_campaign_hub_on_init', $this->container);
    }

    /**
     * Handle admin_init action
     *
     * @return void
     */
    public function on_admin_init() {
        // Initialize admin-specific functionality
        $this->container->get('settings')->init();
    }

    /**
     * Handle wp_loaded action
     *
     * @return void
     */
    public function on_wp_loaded() {
        // Handle redirects if needed
        $this->handle_redirects();
    }

    /**
     * Handle REST API initialization
     *
     * @return void
     */
    public function on_rest_api_init() {
        $this->container->get('rest')->init();
    }

    /**
     * Load plugin services
     *
     * @return void
     */
    private function load_services() {
        // Initialize database
        $this->container->get('database')->init();

        // Initialize security
        $this->container->get('security')->init();

        // Initialize performance
        $this->container->get('performance')->init();

        // Initialize caching
        $this->container->get('cache')->init();

        // Initialize schema
        $this->container->get('schema')->init();

        // Initialize analytics
        $this->container->get('analytics')->init();
    }

    /**
     * Register custom post types
     *
     * @return void
     */
    private function register_post_types() {
        // Register Campaign post type
        $campaign_args = [
            'labels' => [
                'name'               => __('Campaigns', 'seo-campaign-hub'),
                'singular_name'      => __('Campaign', 'seo-campaign-hub'),
                'add_new'            => __('Add New Campaign', 'seo-campaign-hub'),
                'add_new_item'       => __('Add New Campaign', 'seo-campaign-hub'),
                'edit_item'          => __('Edit Campaign', 'seo-campaign-hub'),
                'new_item'           => __('New Campaign', 'seo-campaign-hub'),
                'view_item'          => __('View Campaign', 'seo-campaign-hub'),
                'search_items'       => __('Search Campaigns', 'seo-campaign-hub'),
                'not_found'          => __('No campaigns found', 'seo-campaign-hub'),
                'not_found_in_trash' => __('No campaigns found in trash', 'seo-campaign-hub'),
                'menu_name'          => __('SEO Campaign Hub', 'seo-campaign-hub')
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'campaigns', 'with_front' => false],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-megaphone',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'show_in_rest'       => true,
            'rest_base'          => 'campaigns'
        ];
        register_post_type('sch_campaign', $campaign_args);

        // Register Offer post type
        $offer_args = [
            'labels' => [
                'name'               => __('Offers', 'seo-campaign-hub'),
                'singular_name'      => __('Offer', 'seo-campaign-hub'),
                'add_new'            => __('Add New Offer', 'seo-campaign-hub'),
                'add_new_item'       => __('Add New Offer', 'seo-campaign-hub'),
                'edit_item'          => __('Edit Offer', 'seo-campaign-hub'),
                'new_item'           => __('New Offer', 'seo-campaign-hub'),
                'view_item'          => __('View Offer', 'seo-campaign-hub'),
                'search_items'       => __('Search Offers', 'seo-campaign-hub'),
                'not_found'          => __('No offers found', 'seo-campaign-hub'),
                'not_found_in_trash' => __('No offers found in trash', 'seo-campaign-hub')
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'offers', 'with_front' => false],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'show_in_rest'       => true,
            'rest_base'          => 'offers'
        ];
        register_post_type('sch_offer', $offer_args);
    }

    /**
     * Register custom taxonomies
     *
     * @return void
     */
    private function register_taxonomies() {
        // Campaign Category
        register_taxonomy('sch_campaign_category', 'sch_campaign', [
            'labels' => [
                'name'              => __('Campaign Categories', 'seo-campaign-hub'),
                'singular_name'     => __('Campaign Category', 'seo-campaign-hub'),
                'search_items'      => __('Search Categories', 'seo-campaign-hub'),
                'all_items'         => __('All Categories', 'seo-campaign-hub'),
                'parent_item'       => __('Parent Category', 'seo-campaign-hub'),
                'parent_item_colon' => __('Parent Category:', 'seo-campaign-hub'),
                'edit_item'         => __('Edit Category', 'seo-campaign-hub'),
                'update_item'       => __('Update Category', 'seo-campaign-hub'),
                'add_new_item'      => __('Add New Category', 'seo-campaign-hub'),
                'new_item_name'     => __('New Category Name', 'seo-campaign-hub'),
               
<?php
/**
 * Main Plugin Class
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Plugin
 *
 * Main plugin class responsible for bootstrapping the plugin.
 * Implements the Singleton pattern.
 *
 * @final
 */
final class Plugin {

    /**
     * Plugin instance (Singleton).
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Dependency injection container.
     *
     * @var Container|null
     */
    private ?Container $container = null;

    /**
     * Whether the plugin has been initialized.
     *
     * @var bool
     */
    private bool $initialized = false;

    // =========================================================
    // SINGLETON
    // =========================================================

    /**
     * Get or create the single plugin instance.
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * Private constructor — use get_instance().
     */
    private function __construct() {}

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserialization.
     */
    public function __wakeup(): void {
        throw new \RuntimeException( 'Cannot unserialize singleton.' );
    }

    // =========================================================
    // BOOT
    // =========================================================

    /**
     * Initialize the plugin — runs exactly once.
     *
     * @return void
     */
    public function init(): void {
        if ( $this->initialized ) {
            return;
        }

        $this->init_container();
        $this->register_services();
        $this->load_services();      // services load immediately (we are already on plugins_loaded)
        $this->init_hooks();
        $this->init_context();

        $this->initialized = true;

        /**
         * Fires after the plugin is fully initialized.
         *
         * @param Plugin $plugin The plugin instance.
         */
        do_action( 'seo_campaign_hub_after_init', $this );
    }

    // =========================================================
    // CONTAINER
    // =========================================================

    /**
     * Initialize the dependency injection container.
     *
     * @return void
     */
    private function init_container(): void {
        if ( null === $this->container ) {
            $this->container = new Container();
        }
    }

    /**
     * Return the container (for external access if needed).
     *
     * @return Container|null
     */
    public function get_container(): ?Container {
        return $this->container;
    }

    // =========================================================
    // SERVICE REGISTRATION
    // =========================================================

    /**
     * Register all plugin services as singletons in the container.
     *
     * Services are registered as lazy closures — they are only
     * instantiated when first resolved via $container->get().
     *
     * @return void
     */
    private function register_services(): void {

        $this->container->singleton( 'database', function () {
            return new \SEO_Campaign_Hub\Database\Database();
        } );

        $this->container->singleton( 'security', function () {
            return new SecurityManager();
        } );

        $this->container->singleton( 'performance', function () {
            return new PerformanceManager();
        } );

        $this->container->singleton( 'cache', function () {
            return new CacheManager();
        } );

        $this->container->singleton( 'settings', function () {
            return new \SEO_Campaign_Hub\Admin\Settings();
        } );

        $this->container->singleton( 'rest', function () {
            return new \SEO_Campaign_Hub\REST\RESTManager();
        } );

        $this->container->singleton( 'schema', function () {
            return new \SEO_Campaign_Hub\Services\SchemaService();
        } );

        $this->container->singleton( 'analytics', function () {
            return new \SEO_Campaign_Hub\Services\AnalyticsService();
        } );

        $this->container->singleton( 'qr', function () {
            return new \SEO_Campaign_Hub\Services\QRCodeService();
        } );

        $this->container->singleton( 'shortener', function () {
            return new \SEO_Campaign_Hub\Services\ShortenerService();
        } );

        $this->container->singleton( 'campaign', function () {
            return new \SEO_Campaign_Hub\Services\CampaignService();
        } );

        $this->container->singleton( 'offer', function () {
            return new \SEO_Campaign_Hub\Services\OfferService();
        } );

        $this->container->singleton( 'redirect', function () {
            return new \SEO_Campaign_Hub\Services\RedirectService();
        } );

        $this->container->singleton( 'seo', function () {
            return new \SEO_Campaign_Hub\Services\SEOService();
        } );

        $this->container->singleton( 'import_export', function () {
            return new \SEO_Campaign_Hub\Services\ImportExportService();
        } );

        $this->container->singleton( 'google_drive', function () {
            return new \SEO_Campaign_Hub\Services\GoogleDriveService();
        } );

        $this->container->singleton( 'cloud_backup', function () {
            return new \SEO_Campaign_Hub\Services\CloudBackupService(
                $this->container->get( 'google_drive' ),
                $this->container->get( 'import_export' )
            );
        } );

        $this->container->singleton( 'backup_scheduler', function () {
            return new \SEO_Campaign_Hub\Services\BackupSchedulerService(
                $this->container->get( 'google_drive' )
            );
        } );

        $this->container->singleton( 'social_share', function () {
            return new \SEO_Campaign_Hub\Services\SocialShareService();
        } );

        $this->container->singleton( 'image_optimization', function () {
            return new \SEO_Campaign_Hub\Services\ImageOptimizationService();
        } );
    }

    // =========================================================
    // SERVICE LOADING
    // =========================================================

    /**
     * Boot core services that need to run on every request.
     *
     * NOTE: on_plugins_loaded() was removed — Plugin::get_instance() is
     * already called from inside plugins_loaded in the main plugin file,
     * so registering another plugins_loaded listener here would never fire.
     *
     * @return void
     */
    private function load_services(): void {
        $services = [ 'database', 'security', 'performance', 'cache', 'schema', 'analytics' ];

        foreach ( $services as $service ) {
            try {
                $this->container->get( $service )->init();
            } catch ( \Throwable $e ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log( 'SEO Campaign Hub service "' . $service . '" failed: ' . $e->getMessage() );
                }
            }
        }

        /**
         * Fires after core services are loaded.
         *
         * @param Container $container The DI container.
         */
        do_action( 'seo_campaign_hub_services_loaded', $this->container );

        try {
            $this->container->get( 'cloud_backup' )->init();
            $this->container->get( 'backup_scheduler' )->init();
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'SEO Campaign Hub cloud backup failed: ' . $e->getMessage() );
            }
        }

        try {
            $this->container->get( 'image_optimization' )->init();
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'SEO Campaign Hub image optimization failed: ' . $e->getMessage() );
            }
        }
    }

    // =========================================================
    // HOOKS
    // =========================================================

    /**
     * Register WordPress action/filter hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'init',                  [ $this, 'on_init' ],            10 );
        add_action( 'admin_init',            [ $this, 'on_admin_init' ],      10 );
        // Short-link redirects need parsed query vars, so template_redirect
        // (not wp_loaded, which fires before the request is parsed).
        add_action( 'template_redirect',     [ $this, 'on_wp_loaded' ],       0 );
        add_action( 'init',                  [ $this, 'serve_ads_txt' ],      0 );
        add_action( 'rest_api_init',         [ $this, 'on_rest_api_init' ],   10 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ], 10 );
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_public_assets' ], 10 );
        add_action( 'wp_head',              [ $this, 'output_header_scripts' ], 99 );
        add_action( 'wp_footer',            [ $this, 'output_footer_scripts' ], 99 );

        // Flush rewrite rules once after activation
        add_action( 'init', [ $this, 'maybe_flush_rewrite_rules' ], 99 );
    }

    // =========================================================
    // CONTEXT INIT
    // =========================================================

    /**
     * Initialize admin or frontend components depending on context.
     *
     * NOTE: `Public` is a reserved PHP keyword and cannot be used as a
     * namespace segment. The src/Public/ directory MUST be renamed to
     * src/Frontend/ and PublicInit.php namespace updated to match.
     *
     * @return void
     */
    private function init_context(): void {
        if ( is_admin() ) {
            $init = new \SEO_Campaign_Hub\Admin\AdminInit( $this->container );
        } else {
            // Requires: src/Frontend/PublicInit.php
            $init = new \SEO_Campaign_Hub\Frontend\PublicInit( $this->container );
        }
        $init->init();
    }

    // =========================================================
    // ADS.TXT
    // =========================================================

    /**
     * Serve ads.txt content when the URL path is /ads.txt.
     *
     * Fires early on `init` (priority 0) so it responds before
     * WordPress routes to a 404 or other template.
     *
     * @return void
     */
    public function serve_ads_txt(): void {
        // Only respond to /ads.txt requests.
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $path = parse_url( $request_uri, PHP_URL_PATH );

        if ( '/ads.txt' !== $path ) {
            return;
        }

        $options = get_option( 'seo_campaign_hub_options', [] );

        // Check if ads.txt feature is enabled.
        if ( empty( $options['enable_ads_txt'] ) || '1' !== (string) $options['enable_ads_txt'] ) {
            return; // Let WordPress handle normally (may 404).
        }

        $content = isset( $options['ads_txt_content'] ) ? (string) $options['ads_txt_content'] : '';

        // Serve as plain text (required by ads.txt spec).
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'X-Robots-Tag: noindex' );
        header( 'Cache-Control: public, max-age=86400' ); // Cache 24h.
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text file.
        exit;
    }

    // =========================================================
    // ACTION CALLBACKS
    // =========================================================

    /**
     * WordPress `init` hook — register CPTs, taxonomies, shortcodes, rewrite rules.
     *
     * @return void
     */
    public function on_init(): void {
        $this->register_post_types();
        $this->register_taxonomies();
        $this->register_shortcodes();
        $this->add_rewrite_rules();

        do_action( 'seo_campaign_hub_on_init', $this->container );
    }

    /**
     * WordPress `admin_init` hook.
     *
     * @return void
     */
    public function on_admin_init(): void {
        $this->container->get( 'settings' )->init();
    }

    /**
     * WordPress `wp_loaded` hook — handle short-link redirects.
     *
     * @return void
     */
    public function on_wp_loaded(): void {
        $this->handle_redirects();
    }

    /**
     * WordPress `rest_api_init` hook — register REST routes.
     *
     * @return void
     */
    public function on_rest_api_init(): void {
        $this->container->get( 'rest' )->init();
    }

    /**
     * Flush rewrite rules once after plugin activation.
     *
     * @return void
     */
    public function maybe_flush_rewrite_rules(): void {
        if ( get_transient( 'seo_campaign_hub_flush_rewrite_rules' ) ) {
            flush_rewrite_rules();
            delete_transient( 'seo_campaign_hub_flush_rewrite_rules' );
        }
    }

    // =========================================================
    // CUSTOM POST TYPES
    // =========================================================

    /**
     * Register custom post types.
     *
     * @return void
     */
    private function register_post_types(): void {

        // ---- Campaign ----
        register_post_type( 'sch_campaign', [
            'labels' => [
                'name'               => __( 'Campaigns', 'seo-campaign-hub' ),
                'singular_name'      => __( 'Campaign', 'seo-campaign-hub' ),
                'add_new'            => __( 'Add New', 'seo-campaign-hub' ),
                'add_new_item'       => __( 'Add New Campaign', 'seo-campaign-hub' ),
                'edit_item'          => __( 'Edit Campaign', 'seo-campaign-hub' ),
                'new_item'           => __( 'New Campaign', 'seo-campaign-hub' ),
                'view_item'          => __( 'View Campaign', 'seo-campaign-hub' ),
                'search_items'       => __( 'Search Campaigns', 'seo-campaign-hub' ),
                'not_found'          => __( 'No campaigns found', 'seo-campaign-hub' ),
                'not_found_in_trash' => __( 'No campaigns found in trash', 'seo-campaign-hub' ),
                'menu_name'          => __( 'SEO Campaign Hub', 'seo-campaign-hub' ),
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'campaigns', 'with_front' => false ],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-megaphone',
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author' ],
            'show_in_rest'       => true,
            'rest_base'          => 'sch-campaigns',
        ] );

        // ---- Offer ----
        register_post_type( 'sch_offer', [
            'labels' => [
                'name'               => __( 'Offers', 'seo-campaign-hub' ),
                'singular_name'      => __( 'Offer', 'seo-campaign-hub' ),
                'add_new'            => __( 'Add New', 'seo-campaign-hub' ),
                'add_new_item'       => __( 'Add New Offer', 'seo-campaign-hub' ),
                'edit_item'          => __( 'Edit Offer', 'seo-campaign-hub' ),
                'new_item'           => __( 'New Offer', 'seo-campaign-hub' ),
                'view_item'          => __( 'View Offer', 'seo-campaign-hub' ),
                'search_items'       => __( 'Search Offers', 'seo-campaign-hub' ),
                'not_found'          => __( 'No offers found', 'seo-campaign-hub' ),
                'not_found_in_trash' => __( 'No offers found in trash', 'seo-campaign-hub' ),
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=sch_campaign',
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'offers', 'with_front' => false ],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author' ],
            'show_in_rest'       => true,
            'rest_base'          => 'sch-offers',
        ] );
    }

    // =========================================================
    // TAXONOMIES
    // =========================================================

    /**
     * Register custom taxonomies.
     *
     * @return void
     */
    private function register_taxonomies(): void {

        // ---- Campaign Category ----
        register_taxonomy( 'sch_campaign_category', 'sch_campaign', [
            'labels' => [
                'name'              => __( 'Campaign Categories', 'seo-campaign-hub' ),
                'singular_name'     => __( 'Campaign Category', 'seo-campaign-hub' ),
                'search_items'      => __( 'Search Categories', 'seo-campaign-hub' ),
                'all_items'         => __( 'All Categories', 'seo-campaign-hub' ),
                'parent_item'       => __( 'Parent Category', 'seo-campaign-hub' ),
                'parent_item_colon' => __( 'Parent Category:', 'seo-campaign-hub' ),
                'edit_item'         => __( 'Edit Category', 'seo-campaign-hub' ),
                'update_item'       => __( 'Update Category', 'seo-campaign-hub' ),
                'add_new_item'      => __( 'Add New Category', 'seo-campaign-hub' ),
                'new_item_name'     => __( 'New Category Name', 'seo-campaign-hub' ),
                'menu_name'         => __( 'Categories', 'seo-campaign-hub' ),
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'campaign-category' ],
            'show_in_rest'      => true,
        ] );

        // ---- Campaign Tag ----
        register_taxonomy( 'sch_campaign_tag', 'sch_campaign', [
            'labels' => [
                'name'                       => __( 'Campaign Tags', 'seo-campaign-hub' ),
                'singular_name'              => __( 'Campaign Tag', 'seo-campaign-hub' ),
                'search_items'               => __( 'Search Tags', 'seo-campaign-hub' ),
                'all_items'                  => __( 'All Tags', 'seo-campaign-hub' ),
                'edit_item'                  => __( 'Edit Tag', 'seo-campaign-hub' ),
                'update_item'                => __( 'Update Tag', 'seo-campaign-hub' ),
                'add_new_item'               => __( 'Add New Tag', 'seo-campaign-hub' ),
                'new_item_name'              => __( 'New Tag Name', 'seo-campaign-hub' ),
                'separate_items_with_commas' => __( 'Separate tags with commas', 'seo-campaign-hub' ),
                'add_or_remove_items'        => __( 'Add or remove tags', 'seo-campaign-hub' ),
                'choose_from_most_used'      => __( 'Choose from most used tags', 'seo-campaign-hub' ),
                'menu_name'                  => __( 'Tags', 'seo-campaign-hub' ),
            ],
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'campaign-tag' ],
            'show_in_rest'      => true,
        ] );

        // ---- Offer Category ----
        register_taxonomy( 'sch_offer_category', 'sch_offer', [
            'labels' => [
                'name'              => __( 'Offer Categories', 'seo-campaign-hub' ),
                'singular_name'     => __( 'Offer Category', 'seo-campaign-hub' ),
                'search_items'      => __( 'Search Categories', 'seo-campaign-hub' ),
                'all_items'         => __( 'All Categories', 'seo-campaign-hub' ),
                'edit_item'         => __( 'Edit Category', 'seo-campaign-hub' ),
                'update_item'       => __( 'Update Category', 'seo-campaign-hub' ),
                'add_new_item'      => __( 'Add New Category', 'seo-campaign-hub' ),
                'new_item_name'     => __( 'New Category Name', 'seo-campaign-hub' ),
                'menu_name'         => __( 'Offer Categories', 'seo-campaign-hub' ),
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'offer-category' ],
            'show_in_rest'      => true,
        ] );
    }

    // =========================================================
    // SHORTCODES
    // =========================================================

    /**
     * Register plugin shortcodes.
     *
     * @return void
     */
    private function register_shortcodes(): void {
        add_shortcode( 'sch_campaign',    [ $this, 'shortcode_campaign' ] );
        add_shortcode( 'sch_offer',       [ $this, 'shortcode_offer' ] );
        add_shortcode( 'sch_offers',      [ $this, 'shortcode_offers' ] );
        add_shortcode( 'sch_short_link',  [ $this, 'shortcode_short_link' ] );
        add_shortcode( 'sch_qr_code',     [ $this, 'shortcode_qr_code' ] );
    }

    /**
     * Render [sch_campaign] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcode_campaign( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0, 'slug' => '' ], $atts, 'sch_campaign' );
        ob_start();
        // Template output — override via theme/plugin
        do_action( 'seo_campaign_hub_render_campaign', $atts );
        return ob_get_clean();
    }

    /**
     * Render [sch_offer] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcode_offer( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0, 'slug' => '' ], $atts, 'sch_offer' );
        ob_start();
        do_action( 'seo_campaign_hub_render_offer', $atts );
        return ob_get_clean();
    }

    /**
     * Render [sch_offers] shortcode — offer listing.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcode_offers( array $atts ): string {
        $atts = shortcode_atts( [
            'campaign_id' => 0,
            'limit'       => 10,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ], $atts, 'sch_offers' );
        ob_start();
        do_action( 'seo_campaign_hub_render_offers', $atts );
        return ob_get_clean();
    }

    /**
     * Render [sch_short_link] shortcode.
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Enclosed content used as link label.
     * @return string
     */
    public function shortcode_short_link( array $atts, string $content = '' ): string {
        $atts = shortcode_atts( [
            'url'    => '',
            'target' => '_blank',
            'rel'    => 'nofollow noopener',
            'class'  => '',
        ], $atts, 'sch_short_link' );

        if ( empty( $atts['url'] ) ) {
            return '';
        }

        $label = ! empty( $content ) ? $content : esc_url( $atts['url'] );

        return sprintf(
            '<a href="%s" target="%s" rel="%s" class="sch-short-link %s">%s</a>',
            esc_url( $atts['url'] ),
            esc_attr( $atts['target'] ),
            esc_attr( $atts['rel'] ),
            esc_attr( $atts['class'] ),
            wp_kses_post( $label )
        );
    }

    /**
     * Render [sch_qr_code] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcode_qr_code( array $atts ): string {
        $atts = shortcode_atts( [
            'id'   => 0,
            'size' => 200,
        ], $atts, 'sch_qr_code' );
        ob_start();
        do_action( 'seo_campaign_hub_render_qr_code', $atts );
        return ob_get_clean();
    }

    // =========================================================
    // REWRITE RULES
    // =========================================================

    /**
     * Add custom rewrite rules for short links.
     *
     * Pattern: /go/{slug} → index.php?sch_redirect=1&sch_slug={slug}
     *
     * @return void
     */
    private function add_rewrite_rules(): void {
        $prefix = get_option( 'seo_campaign_hub_shortener_prefix', 'go' );
        $prefix = preg_quote( trim( (string) $prefix, '/' ) ?: 'go', '#' );

        add_rewrite_rule(
            '^' . $prefix . '/([a-zA-Z0-9_-]+)/?$',
            'index.php?sch_redirect=1&sch_slug=$matches[1]',
            'top'
        );

        // Register query vars
        add_filter( 'query_vars', function ( array $vars ): array {
            $vars[] = 'sch_redirect';
            $vars[] = 'sch_slug';
            return $vars;
        } );
    }

    // =========================================================
    // REDIRECTS
    // =========================================================

    /**
     * Handle short-link redirects on wp_loaded.
     *
     * @return void
     */
    private function handle_redirects(): void {
        $is_redirect = get_query_var( 'sch_redirect' );
        $slug        = get_query_var( 'sch_slug' );

        if ( ! $is_redirect || empty( $slug ) ) {
            return;
        }

        // Delegate to the Shortener Service, which owns /go/{slug} links
        $this->container->get( 'shortener' )->handle_redirect( sanitize_text_field( $slug ) );
    }

    // =========================================================
    // ASSETS
    // =========================================================

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook_suffix Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets( string $hook_suffix ): void {
        // Only load on plugin pages
        if ( strpos( $hook_suffix, 'seo-campaign-hub' ) === false
            && strpos( $hook_suffix, 'sch_campaign' ) === false
            && strpos( $hook_suffix, 'sch_offer' ) === false
        ) {
            return;
        }

        $version = SEO_CAMPAIGN_HUB_VERSION;
        $url     = SEO_CAMPAIGN_HUB_PLUGIN_URL;

        // Admin CSS
        wp_enqueue_style(
            'seo-campaign-hub-admin',
            $url . 'assets/admin/css/admin.css',
            [],
            $version
        );

        // React SPA bundle (built with Vite)
        wp_enqueue_script(
            'seo-campaign-hub-admin',
            $url . 'assets/admin/js/admin.js',
            [],
            $version,
            true
        );

        // Pass data to JS
        wp_localize_script( 'seo-campaign-hub-admin', 'schAdmin', [
            'apiUrl'   => esc_url_raw( rest_url( SEO_CAMPAIGN_HUB_REST_NAMESPACE ) ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'version'  => $version,
            'pluginUrl' => esc_url( $url ),
            'debug'    => defined( 'WP_DEBUG' ) && WP_DEBUG,
        ] );
    }

    /**
     * Enqueue frontend scripts and styles.
     *
     * @return void
     */
    public function enqueue_public_assets(): void {
        $version = SEO_CAMPAIGN_HUB_VERSION;
        $url     = SEO_CAMPAIGN_HUB_PLUGIN_URL;

        $analytics_enabled = true;
        try {
            $analytics_enabled = (bool) $this->container->get( 'analytics' )->is_enabled();
        } catch ( \Throwable $e ) {
            $analytics_enabled = (bool) get_option( 'seo_campaign_hub_enable_analytics', true );
        }

        $is_plugin_content = is_singular( [ 'sch_campaign', 'sch_offer' ] )
            || is_post_type_archive( 'sch_campaign' );

        // Load tracker site-wide when analytics is on so page views are recorded.
        if ( ! $analytics_enabled && ! $is_plugin_content ) {
            return;
        }

        wp_enqueue_style(
            'seo-campaign-hub-public',
            $url . 'assets/public/css/public.css',
            [],
            $version
        );

        wp_enqueue_script(
            'seo-campaign-hub-public',
            $url . 'assets/public/js/public.js',
            [ 'jquery' ],
            $version,
            true
        );

        $post_id     = is_singular() ? (int) get_queried_object_id() : 0;
        $campaign_id = 0;
        $offer_id    = 0;

        if ( is_singular( 'sch_campaign' ) ) {
            $campaign_id = $post_id;
        } elseif ( is_singular( 'sch_offer' ) ) {
            $offer_id = $post_id;
        }

        wp_localize_script( 'seo-campaign-hub-public', 'seoCampaignHubPublic', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'apiUrl'     => esc_url_raw( rest_url( SEO_CAMPAIGN_HUB_REST_NAMESPACE ) ),
            'nonce'      => wp_create_nonce( 'seo_campaign_hub_public' ),
            'tracking'   => $analytics_enabled,
            'postId'     => $post_id,
            'campaignId' => $campaign_id,
            'offerId'    => $offer_id,
        ] );

        // Google Analytics (GA4) injection.
        $this->maybe_enqueue_google_analytics( $campaign_id, $offer_id );
    }

    /**
     * Conditionally enqueue the Google Analytics GA4 script.
     *
     * @param int $campaign_id Current campaign ID (0 if none).
     * @param int $offer_id    Current offer ID (0 if none).
     * @return void
     */
    private function maybe_enqueue_google_analytics( int $campaign_id, int $offer_id ): void {
        $options        = get_option( 'seo_campaign_hub_options', [] );
        $ga_enabled     = ! empty( $options['enable_google_analytics'] );
        $measurement_id = isset( $options['ga_measurement_id'] ) ? trim( (string) $options['ga_measurement_id'] ) : '';

        if ( ! $ga_enabled || $measurement_id === '' ) {
            return;
        }

        // Validate Measurement ID format (G-XXXXXXXXXX).
        if ( ! preg_match( '/^G-[A-Z0-9]+$/i', $measurement_id ) ) {
            return;
        }

        // Enqueue the gtag.js loader.
        wp_enqueue_script(
            'google-analytics-gtag',
            'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $measurement_id ),
            [],
            null,
            [ 'strategy' => 'async' ]
        );

        // Build the config object.
        $config = [];
        $custom_json = isset( $options['ga_custom_dimensions'] ) ? trim( (string) $options['ga_custom_dimensions'] ) : '';
        if ( $custom_json !== '' ) {
            $decoded = json_decode( $custom_json, true );
            if ( is_array( $decoded ) ) {
                $config = $decoded;
            }
        }

        $config_json = ! empty( $config ) ? wp_json_encode( $config ) : '{}';

        // Build custom event calls.
        $custom_events = '';
        if ( ! empty( $options['ga_track_campaigns'] ) && $campaign_id > 0 ) {
            $custom_events .= sprintf(
                "gtag('event','sch_campaign_view',{'campaign_id':%d});\n",
                $campaign_id
            );
        }
        if ( ! empty( $options['ga_track_offer_clicks'] ) && $offer_id > 0 ) {
            $custom_events .= sprintf(
                "gtag('event','sch_offer_view',{'offer_id':%d});\n",
                $offer_id
            );
        }

        // Inline initialization script.
        $inline = sprintf(
            "window.dataLayer=window.dataLayer||[];\n"
            . "function gtag(){dataLayer.push(arguments);}\n"
            . "gtag('js',new Date());\n"
            . "gtag('config',%s,%s);\n"
            . "%s",
            wp_json_encode( $measurement_id ),
            $config_json,
            $custom_events
        );

        wp_add_inline_script( 'google-analytics-gtag', $inline, 'after' );

        // Expose GA settings so public.js can fire events client-side.
        wp_localize_script( 'seo-campaign-hub-public', 'seoCampaignHubGA', [
            'enabled'         => true,
            'measurementId'   => $measurement_id,
            'trackCampaigns'  => ! empty( $options['ga_track_campaigns'] ),
            'trackOfferClicks' => ! empty( $options['ga_track_offer_clicks'] ),
            'trackShortLinks' => ! empty( $options['ga_track_short_links'] ),
        ] );
    }

    /**
     * Output custom header scripts inside <head>.
     *
     * @return void
     */
    public function output_header_scripts(): void {
        if ( is_admin() ) {
            return;
        }

        $options = get_option( 'seo_campaign_hub_options', [] );
        $scripts = isset( $options['header_scripts'] ) ? trim( (string) $options['header_scripts'] ) : '';

        if ( $scripts === '' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw script/meta tags entered by admin.
        echo "\n<!-- SEO Campaign Hub - Header Scripts -->\n" . $scripts . "\n<!-- /SEO Campaign Hub - Header Scripts -->\n";
    }

    /**
     * Output custom footer scripts before </body>.
     *
     * @return void
     */
    public function output_footer_scripts(): void {
        if ( is_admin() ) {
            return;
        }

        $options = get_option( 'seo_campaign_hub_options', [] );
        $scripts = isset( $options['footer_scripts'] ) ? trim( (string) $options['footer_scripts'] ) : '';

        if ( $scripts === '' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw script/meta tags entered by admin.
        echo "\n<!-- SEO Campaign Hub - Footer Scripts -->\n" . $scripts . "\n<!-- /SEO Campaign Hub - Footer Scripts -->\n";
    }
}

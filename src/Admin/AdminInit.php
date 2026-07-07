<?php
/**
 * Admin Initialization
 *
 * @package SEO_Campaign_Hub\Admin
 */

namespace SEO_Campaign_Hub\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use SEO_Campaign_Hub\Core\Container;

/**
 * Class AdminInit
 *
 * Handles admin initialization and menu setup.
 */
class AdminInit {

    /**
     * DI Container instance.
     *
     * @var Container
     */
    private Container $container;

    /**
     * Constructor.
     *
     * @param Container $container DI container.
     */
    public function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Register all admin hooks.
     *
     * @return void
     */
    public function init(): void {
        add_action( 'admin_menu',          [ $this, 'add_admin_menu' ] );
        add_action( 'admin_head',          [ $this, 'add_admin_styles' ] );
        add_action( 'admin_footer',        [ $this, 'add_admin_footer_scripts' ] );

        add_filter(
            'plugin_action_links_' . SEO_CAMPAIGN_HUB_PLUGIN_BASENAME,
            [ $this, 'add_action_links' ]
        );
    }

    // =========================================================
    // ADMIN MENU
    // =========================================================

    /**
     * Register admin menu and submenus.
     *
     * @return void
     */
    public function add_admin_menu(): void {
        add_menu_page(
            __( 'SEO Campaign Hub', 'seo-campaign-hub' ),
            __( 'SEO Campaign Hub', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub',
            [ $this, 'render_dashboard' ],
            'dashicons-megaphone',
            5
        );

        // Dashboard (replaces auto-generated duplicate)
        add_submenu_page(
            'seo-campaign-hub',
            __( 'Dashboard', 'seo-campaign-hub' ),
            __( 'Dashboard', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub',
            [ $this, 'render_dashboard' ]
        );

        // Campaigns — links to CPT list table
        add_submenu_page(
            'seo-campaign-hub',
            __( 'Campaigns', 'seo-campaign-hub' ),
            __( 'Campaigns', 'seo-campaign-hub' ),
            'manage_options',
            'edit.php?post_type=sch_campaign'
        );

        // Offers — links to CPT list table
        add_submenu_page(
            'seo-campaign-hub',
            __( 'Offers', 'seo-campaign-hub' ),
            __( 'Offers', 'seo-campaign-hub' ),
            'manage_options',
            'edit.php?post_type=sch_offer'
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'URL Shortener', 'seo-campaign-hub' ),
            __( 'URL Shortener', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-shortener',
            [ $this, 'render_shortener' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'QR Codes', 'seo-campaign-hub' ),
            __( 'QR Codes', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-qr-codes',
            [ $this, 'render_qr_codes' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Analytics', 'seo-campaign-hub' ),
            __( 'Analytics', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-analytics',
            [ $this, 'render_analytics' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Settings', 'seo-campaign-hub' ),
            __( 'Settings', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-settings',
            [ $this, 'render_settings' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Import/Export', 'seo-campaign-hub' ),
            __( 'Import/Export', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-import-export',
            [ $this, 'render_import_export' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Help', 'seo-campaign-hub' ),
            __( 'Help', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-help',
            [ $this, 'render_help' ]
        );
    }

    // =========================================================
    // PAGE RENDERERS
    // =========================================================

    /** @return void */
    public function render_dashboard(): void {
        $this->render_view( 'dashboard', [ 'page_title' => __( 'Dashboard', 'seo-campaign-hub' ) ] );
    }

    /** @return void */
    public function render_shortener(): void {
        $this->render_view( 'shortener', [ 'page_title' => __( 'URL Shortener', 'seo-campaign-hub' ) ] );
    }

    /** @return void */
    public function render_qr_codes(): void {
        $this->render_view( 'qr-codes', [ 'page_title' => __( 'QR Codes', 'seo-campaign-hub' ) ] );
    }

    /** @return void */
    public function render_analytics(): void {
        $this->render_view( 'analytics', [ 'page_title' => __( 'Analytics', 'seo-campaign-hub' ) ] );
    }

    /** @return void */
    public function render_settings(): void {
        $this->render_view( 'settings', [ 'page_title' => __( 'Settings', 'seo-campaign-hub' ) ] );
    }

    /** @return void */
    public function render_import_export(): void {
        $this->render_view( 'import-export', [ 'page_title' => __( 'Import / Export', 'seo-campaign-hub' ) ] );
    }

    /** @return void */
    public function render_help(): void {
        $this->render_view( 'help', [ 'page_title' => __( 'Help & Support', 'seo-campaign-hub' ) ] );
    }

    // =========================================================
    // VIEW RENDERING
    // =========================================================

    /**
     * Load a view file, falling back to inline placeholder.
     *
     * @param string               $view View slug (maps to src/Admin/Views/{view}.php).
     * @param array<string, mixed> $data Variables extracted into view scope.
     * @return void
     */
    private function render_view( string $view, array $data = [] ): void {
        $view_file = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Admin/Views/' . $view . '.php';

        if ( file_exists( $view_file ) ) {
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            extract( $data, EXTR_SKIP );
            include $view_file;
            return;
        }

        $this->render_placeholder( $view, $data );
    }

    /**
     * Render a placeholder page when the view file doesn't exist yet.
     *
     * @param string               $view View slug.
     * @param array<string, mixed> $data Page data.
     * @return void
     */
    private function render_placeholder( string $view, array $data = [] ): void {
        $page_title = isset( $data['page_title'] )
            ? (string) $data['page_title']
            : ucwords( str_replace( '-', ' ', $view ) );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $page_title ); ?></h1>
            <div class="seo-campaign-hub-admin">
                <div class="seo-campaign-hub-content">
                    <div class="seo-campaign-hub-placeholder">
                        <?php
                        printf(
                            /* translators: %s: section name */
                            esc_html__( 'The %s section is coming soon.', 'seo-campaign-hub' ),
                            '<strong>' . esc_html( $page_title ) . '</strong>'
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // =========================================================
    // PLUGIN ACTION LINKS
    // =========================================================

    /**
     * Add Settings and Dashboard links on the Plugins list page.
     *
     * @param string[] $links Existing action links.
     * @return string[]
     */
    public function add_action_links( array $links ): array {
        $prepend = [
            sprintf(
                '<a href="%s">%s</a>',
                esc_url( admin_url( 'admin.php?page=seo-campaign-hub' ) ),
                esc_html__( 'Dashboard', 'seo-campaign-hub' )
            ),
            sprintf(
                '<a href="%s">%s</a>',
                esc_url( admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ),
                esc_html__( 'Settings', 'seo-campaign-hub' )
            ),
        ];

        return array_merge( $prepend, $links );
    }

    // =========================================================
    // INLINE STYLES & SCRIPTS
    // =========================================================

    /**
     * Output minimal admin CSS in <head>.
     *
     * @return void
     */
    public function add_admin_styles(): void {
        // Only output on plugin pages
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'seo-campaign-hub' ) === false ) {
            return;
        }
        ?>
        <style id="seo-campaign-hub-admin-inline">
            .seo-campaign-hub-admin { background:#fff; padding:20px; margin:20px 0; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,.1); }
            .seo-campaign-hub-content { max-width:1200px; }
            .seo-campaign-hub-placeholder { padding:40px; text-align:center; background:#f9f9f9; border-radius:4px; margin:20px 0; }
            .seo-campaign-hub-dashboard-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:20px; margin:20px 0; }
            .seo-campaign-hub-stat-box { padding:20px; background:#f5f5f5; border-radius:4px; text-align:center; }
            .seo-campaign-hub-stat-box .stat-number { font-size:28px; font-weight:700; color:#007cba; }
            .seo-campaign-hub-stat-box .stat-label { color:#666; margin-top:5px; }
            .seo-campaign-hub-notice { padding:12px; margin:10px 0; border-radius:4px; }
            .seo-campaign-hub-notice.success { background:#d4edda; border:1px solid #c3e6cb; color:#155724; }
            .seo-campaign-hub-notice.error   { background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; }
            .seo-campaign-hub-notice.info    { background:#d1ecf1; border:1px solid #bee5eb; color:#0c5460; }
        </style>
        <?php
    }

    /**
     * Output minimal admin JS before </body>.
     *
     * @return void
     */
    public function add_admin_footer_scripts(): void {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'seo-campaign-hub' ) === false ) {
            return;
        }
        ?>
        <script id="seo-campaign-hub-admin-footer">
        /* global jQuery */
        jQuery( function( $ ) {
            $( document ).trigger( 'sch:admin:ready' );
        } );
        </script>
        <?php
    }

    // =========================================================
    // ACCESSOR
    // =========================================================

    /**
     * Return the DI container.
     *
     * @return Container
     */
    public function get_container(): Container {
        return $this->container;
    }
}

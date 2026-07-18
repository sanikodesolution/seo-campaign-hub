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

        // URL Shortener page form handlers (admin-post.php)
        add_action( 'admin_post_sch_shortener_create', [ $this, 'handle_shortener_create' ] );
        add_action( 'admin_post_sch_shortener_delete', [ $this, 'handle_shortener_delete' ] );
        add_action( 'admin_post_sch_shortener_toggle', [ $this, 'handle_shortener_toggle' ] );

        // Import / Export handlers
        add_action( 'admin_post_sch_export_data', [ $this, 'handle_export_data' ] );
        add_action( 'admin_post_sch_import_data', [ $this, 'handle_import_data' ] );

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
        $shortener = $this->container->get( 'shortener' );

        $this->render_view( 'shortener', [
            'page_title' => __( 'URL Shortener', 'seo-campaign-hub' ),
            'links'      => $shortener->get_links( [
                'is_active' => null, // include inactive links too
                'limit'     => 100,
            ] ),
            'prefix'     => get_option( 'seo_campaign_hub_shortener_prefix', 'go' ),
        ] );
    }

    /** @return void */
    public function render_qr_codes(): void {
        $this->render_view( 'qr-codes', [ 'page_title' => __( 'QR Codes', 'seo-campaign-hub' ) ] );
    }

    /** @return void */
    public function render_analytics(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only date filter.
        $days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
        if ( ! in_array( $days, [ 7, 30, 90 ], true ) ) {
            $days = 30;
        }

        $analytics = $this->container->get( 'analytics' );
        $summary   = $analytics->get_dashboard_summary( $days );

        // Resolve display labels for top entities.
        $summary['top_campaigns'] = $this->enrich_top_campaigns( $summary['top_campaigns'] ?? [] );
        $summary['top_offers']    = $this->enrich_top_offers( $summary['top_offers'] ?? [] );
        $summary['top_links']     = $this->enrich_top_links( $summary['top_links'] ?? [] );

        $this->render_view( 'analytics', [
            'page_title' => __( 'Analytics', 'seo-campaign-hub' ),
            'days'       => $days,
            'summary'    => $summary,
        ] );
    }

    /**
     * Enrich top campaign rows with titles and edit links.
     *
     * @param array<int, array<string, mixed>> $rows Raw rows.
     * @return array<int, array<string, mixed>>
     */
    private function enrich_top_campaigns( array $rows ): array {
        foreach ( $rows as &$row ) {
            $id = (int) ( $row['campaign_id'] ?? 0 );
            $post = $id ? get_post( $id ) : null;
            if ( ! $post || $post->post_type !== 'sch_campaign' ) {
                // Custom table IDs may not match post IDs — show ID only.
                $row['label'] = $id ? sprintf( __( 'Campaign #%d', 'seo-campaign-hub' ), $id ) : __( 'Unknown', 'seo-campaign-hub' );
                $row['edit_url'] = '';
                continue;
            }
            $row['label'] = get_the_title( $post );
            $row['edit_url'] = get_edit_post_link( $post->ID, 'raw' ) ?: '';
        }
        unset( $row );
        return $rows;
    }

    /**
     * Enrich top offer rows with titles and edit links.
     *
     * @param array<int, array<string, mixed>> $rows Raw rows.
     * @return array<int, array<string, mixed>>
     */
    private function enrich_top_offers( array $rows ): array {
        foreach ( $rows as &$row ) {
            $id = (int) ( $row['offer_id'] ?? 0 );
            $post = $id ? get_post( $id ) : null;
            if ( ! $post || $post->post_type !== 'sch_offer' ) {
                $row['label'] = $id ? sprintf( __( 'Offer #%d', 'seo-campaign-hub' ), $id ) : __( 'Unknown', 'seo-campaign-hub' );
                $row['edit_url'] = '';
                continue;
            }
            $row['label'] = get_the_title( $post );
            $row['edit_url'] = get_edit_post_link( $post->ID, 'raw' ) ?: '';
        }
        unset( $row );
        return $rows;
    }

    /**
     * Enrich top short-link rows with URLs.
     *
     * @param array<int, array<string, mixed>> $rows Raw rows.
     * @return array<int, array<string, mixed>>
     */
    private function enrich_top_links( array $rows ): array {
        $shortener = $this->container->get( 'shortener' );
        foreach ( $rows as &$row ) {
            $id = (int) ( $row['link_id'] ?? 0 );
            $link = $id ? $shortener->get_link( $id ) : null;
            if ( ! $link ) {
                $row['label'] = $id ? sprintf( __( 'Link #%d', 'seo-campaign-hub' ), $id ) : __( 'Unknown', 'seo-campaign-hub' );
                $row['edit_url'] = admin_url( 'admin.php?page=seo-campaign-hub-shortener' );
                continue;
            }
            $row['label'] = ! empty( $link->title ) ? $link->title : $link->short_url;
            $row['edit_url'] = admin_url( 'admin.php?page=seo-campaign-hub-shortener' );
        }
        unset( $row );
        return $rows;
    }

    /** @return void */
    public function render_settings(): void {
        $this->render_view( 'settings', [ 'page_title' => __( 'Settings', 'seo-campaign-hub' ) ] );
    }

    /** @return void */
    public function render_import_export(): void {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only notice display.
        $notice  = isset( $_GET['sch_notice'] ) ? sanitize_key( wp_unslash( $_GET['sch_notice'] ) ) : '';
        $message = isset( $_GET['sch_message'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['sch_message'] ) ) ) : '';
        // phpcs:enable

        $this->render_view( 'import-export', [
            'page_title' => __( 'Import / Export', 'seo-campaign-hub' ),
            'notice'     => $notice,
            'message'    => $message,
            'counts'     => [
                'campaigns' => (int) ( wp_count_posts( 'sch_campaign' )->publish ?? 0 )
                    + (int) ( wp_count_posts( 'sch_campaign' )->draft ?? 0 ),
                'offers'    => (int) ( wp_count_posts( 'sch_offer' )->publish ?? 0 )
                    + (int) ( wp_count_posts( 'sch_offer' )->draft ?? 0 ),
                'links'     => $this->count_short_links(),
            ],
        ] );
    }

    /**
     * Count short links safely.
     *
     * @return int
     */
    private function count_short_links(): int {
        try {
            $db = $this->container->get( 'database' );
            if ( ! $db->table_exists( 'links' ) ) {
                return 0;
            }
            return (int) $db->get_table_row_count( 'links' );
        } catch ( \Throwable $e ) {
            return 0;
        }
    }

    /**
     * Stream a JSON export download.
     *
     * @return void
     */
    public function handle_export_data(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_export_data' );

        $type = isset( $_POST['export_type'] ) ? sanitize_key( wp_unslash( $_POST['export_type'] ) ) : 'all';
        $allowed = [ 'all', 'campaigns', 'offers', 'links', 'analytics', 'settings' ];
        if ( ! in_array( $type, $allowed, true ) ) {
            $type = 'all';
        }

        $service = $this->container->get( 'import_export' );
        $json    = $service->export_data( $type, [ 'limit' => 1000 ] );
        $filename = 'seo-campaign-hub-' . $type . '-' . gmdate( 'Y-m-d' ) . '.json';

        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $json ) );
        echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download body.
        exit;
    }

    /**
     * Handle JSON import upload.
     *
     * @return void
     */
    public function handle_import_data(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_import_data' );

        if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
            $this->redirect_to_import_export( [ 'sch_notice' => 'missing_file' ] );
        }

        $file = $_FILES['import_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $size = isset( $file['size'] ) ? (int) $file['size'] : 0;
        $name = isset( $file['name'] ) ? (string) $file['name'] : '';
        $tmp  = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

        if ( $size <= 0 || $size > 5 * MB_IN_BYTES ) {
            $this->redirect_to_import_export( [ 'sch_notice' => 'file_too_large' ] );
        }

        $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
        if ( 'json' !== $ext ) {
            $this->redirect_to_import_export( [ 'sch_notice' => 'invalid_type' ] );
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $json = file_get_contents( $tmp );
        if ( false === $json || '' === trim( $json ) ) {
            $this->redirect_to_import_export( [ 'sch_notice' => 'empty_file' ] );
        }

        $conflict = isset( $_POST['conflict'] ) ? sanitize_key( wp_unslash( $_POST['conflict'] ) ) : 'update';
        if ( ! in_array( $conflict, [ 'update', 'skip' ], true ) ) {
            $conflict = 'update';
        }

        $include_settings = ! empty( $_POST['include_settings'] );

        $service = $this->container->get( 'import_export' );
        $result  = $service->import_data(
            $json,
            [
                'conflict'         => $conflict,
                'include_settings' => $include_settings,
            ]
        );

        $this->redirect_to_import_export(
            [
                'sch_notice'  => ! empty( $result['success'] ) ? 'imported' : 'import_failed',
                'sch_message' => rawurlencode( (string) ( $result['message'] ?? '' ) ),
            ]
        );
    }

    /**
     * Redirect back to Import/Export page.
     *
     * @param array<string, string> $args Query args.
     * @return void
     */
    private function redirect_to_import_export( array $args = [] ): void {
        wp_safe_redirect(
            add_query_arg( $args, admin_url( 'admin.php?page=seo-campaign-hub-import-export' ) )
        );
        exit;
    }

    /** @return void */
    public function render_help(): void {
        $this->render_view( 'help', [
            'page_title' => __( 'Help & Support', 'seo-campaign-hub' ),
            'status'     => $this->get_help_system_status(),
        ] );
    }

    /**
     * Gather safe diagnostics for the Help page.
     *
     * @return array<string, mixed>
     */
    private function get_help_system_status(): array {
        global $wp_version;

        $db = null;
        try {
            $db = $this->container->get( 'database' );
        } catch ( \Throwable $e ) {
            $db = null;
        }

        $required_tables = [ 'campaigns', 'offers', 'links', 'analytics', 'qr_codes', 'redirects', 'schemas' ];
        $tables          = [];
        foreach ( $required_tables as $key ) {
            $tables[ $key ] = $db ? (bool) $db->table_exists( $key ) : false;
        }

        $analytics_enabled = true;
        try {
            $analytics_enabled = (bool) $this->container->get( 'analytics' )->is_enabled();
        } catch ( \Throwable $e ) {
            $analytics_enabled = (bool) get_option( 'seo_campaign_hub_enable_analytics', true );
        }

        return [
            'plugin_version'     => defined( 'SEO_CAMPAIGN_HUB_VERSION' ) ? SEO_CAMPAIGN_HUB_VERSION : '',
            'wp_version'         => (string) $wp_version,
            'php_version'        => PHP_VERSION,
            'php_ok'             => version_compare( PHP_VERSION, '8.2.0', '>=' ),
            'wp_ok'              => version_compare( (string) $wp_version, '6.0.0', '>=' ),
            'tables'             => $tables,
            'tables_ok'          => ! in_array( false, $tables, true ),
            'shortener_prefix'   => (string) get_option( 'seo_campaign_hub_shortener_prefix', 'go' ),
            'shortener_enabled'  => (bool) get_option( 'seo_campaign_hub_enable_shortener', true ),
            'analytics_enabled'  => $analytics_enabled,
            'elementor_active'   => did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ),
            'permalink_structure'=> (string) get_option( 'permalink_structure', '' ),
            'pretty_permalinks'  => (string) get_option( 'permalink_structure', '' ) !== '',
        ];
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
    // URL SHORTENER FORM HANDLERS
    // =========================================================

    /**
     * Handle short link creation from the admin form.
     *
     * @return void
     */
    public function handle_shortener_create(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_shortener_create' );

        $destination = isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : '';
        $slug        = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

        if ( empty( $destination ) ) {
            $this->redirect_to_shortener( [ 'sch_notice' => 'missing_url' ] );
        }

        $shortener = $this->container->get( 'shortener' );

        if ( ! empty( $slug ) && ! $shortener->is_slug_available( $slug ) ) {
            $this->redirect_to_shortener( [ 'sch_notice' => 'slug_taken' ] );
        }

        $data = [];
        foreach ( [ 'title', 'redirect_type', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' ] as $field ) {
            if ( ! empty( $_POST[ $field ] ) ) {
                $data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
            }
        }

        $short_url = $shortener->shorten_url( $destination, $slug, $data );

        if ( $short_url ) {
            $this->redirect_to_shortener( [
                'sch_notice' => 'created',
                'sch_url'    => rawurlencode( $short_url ),
            ] );
        }

        $this->redirect_to_shortener( [ 'sch_notice' => 'create_failed' ] );
    }

    /**
     * Handle short link deletion.
     *
     * @return void
     */
    public function handle_shortener_delete(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }

        $id = isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0;
        check_admin_referer( 'sch_shortener_delete_' . $id );

        $deleted = $this->container->get( 'shortener' )->delete_link( $id );

        $this->redirect_to_shortener( [ 'sch_notice' => $deleted ? 'deleted' : 'delete_failed' ] );
    }

    /**
     * Handle short link activate/deactivate toggle.
     *
     * @return void
     */
    public function handle_shortener_toggle(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }

        $id = isset( $_GET['link_id'] ) ? absint( $_GET['link_id'] ) : 0;
        check_admin_referer( 'sch_shortener_toggle_' . $id );

        $shortener = $this->container->get( 'shortener' );
        $link      = $shortener->get_link( $id );

        if ( $link ) {
            $shortener->update_link( $id, [ 'is_active' => $link->is_active ? 0 : 1 ] );
            $this->redirect_to_shortener( [ 'sch_notice' => 'updated' ] );
        }

        $this->redirect_to_shortener( [ 'sch_notice' => 'not_found' ] );
    }

    /**
     * Redirect back to the URL Shortener admin page.
     *
     * @param array<string, string> $args Extra query args.
     * @return void
     */
    private function redirect_to_shortener( array $args = [] ): void {
        $url = add_query_arg(
            $args,
            admin_url( 'admin.php?page=seo-campaign-hub-shortener' )
        );
        wp_safe_redirect( $url );
        exit;
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

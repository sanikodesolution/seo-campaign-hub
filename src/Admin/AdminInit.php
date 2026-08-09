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
        add_action( 'admin_notices',       [ $this, 'render_setup_notice' ] );

        // URL Shortener page form handlers (admin-post.php)
        add_action( 'admin_post_sch_shortener_create', [ $this, 'handle_shortener_create' ] );
        add_action( 'admin_post_sch_shortener_delete', [ $this, 'handle_shortener_delete' ] );
        add_action( 'admin_post_sch_shortener_toggle', [ $this, 'handle_shortener_toggle' ] );

        // Import / Export handlers
        add_action( 'admin_post_sch_export_data', [ $this, 'handle_export_data' ] );
        add_action( 'admin_post_sch_import_data', [ $this, 'handle_import_data' ] );

        add_action( 'admin_post_sch_url_replace_rule_add', [ $this, 'handle_url_replace_rule_add' ] );
        add_action( 'admin_post_sch_url_replace_rule_delete', [ $this, 'handle_url_replace_rule_delete' ] );
        add_action( 'admin_post_sch_url_replace_rule_toggle', [ $this, 'handle_url_replace_rule_toggle' ] );
        add_action( 'admin_post_sch_url_replace_db', [ $this, 'handle_url_replace_db' ] );

        add_action( 'admin_post_sch_cloud_backup_settings', [ $this, 'handle_cloud_backup_settings' ] );
        add_action( 'admin_post_sch_cloud_backup_now', [ $this, 'handle_cloud_backup_now' ] );
        add_action( 'admin_post_sch_cloud_backup_schedule', [ $this, 'handle_cloud_backup_schedule' ] );
        add_action( 'admin_post_sch_google_disconnect', [ $this, 'handle_google_disconnect' ] );
        add_action( 'admin_init', [ $this, 'maybe_handle_google_oauth_callback' ] );

        add_action( 'admin_post_sch_image_opt_settings', [ $this, 'handle_image_opt_settings' ] );
        add_action( 'admin_post_sch_image_to_svg_settings', [ $this, 'handle_image_to_svg_settings' ] );
        add_action( 'admin_post_sch_public_shortener_settings', [ $this, 'handle_public_shortener_settings' ] );
        add_action( 'wp_ajax_sch_image_optimize_batch', [ $this, 'ajax_image_optimize_batch' ] );
        add_action( 'admin_post_sch_social_share_settings', [ $this, 'handle_social_share_settings' ] );
        add_action( 'admin_post_sch_web_push_settings', [ $this, 'handle_web_push_settings' ] );
        add_action( 'admin_post_sch_web_push_send', [ $this, 'handle_web_push_send' ] );
        add_action( 'admin_post_sch_web_push_send_post', [ $this, 'handle_web_push_send_post' ] );

        add_action( 'add_meta_boxes', [ $this, 'add_web_push_metabox' ] );
        add_action( 'save_post_post', [ $this, 'save_web_push_metabox' ], 10, 2 );

        add_filter( 'post_row_actions', [ $this, 'add_post_share_row_actions' ], 20, 2 );
        add_filter( 'manage_post_posts_columns', [ $this, 'add_post_share_column' ] );
        add_action( 'manage_post_posts_custom_column', [ $this, 'render_post_share_column' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_post_share_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_image_opt_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_image_to_svg_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_public_shortener_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_cloud_backup_assets' ] );

        add_filter(
            'plugin_action_links_' . SEO_CAMPAIGN_HUB_PLUGIN_BASENAME,
            [ $this, 'add_action_links' ]
        );
    }

    /**
     * Show a one-time setup notice for WP post + short link campaigns.
     *
     * @return void
     */
    public function render_setup_notice(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! get_transient( 'seo_campaign_hub_show_setup_notice' ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked below.
        if ( isset( $_GET['sch_dismiss_setup'], $_GET['_wpnonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sch_dismiss_setup' )
        ) {
            delete_transient( 'seo_campaign_hub_show_setup_notice' );
            return;
        }

        $dismiss_url = wp_nonce_url(
            add_query_arg( 'sch_dismiss_setup', '1' ),
            'sch_dismiss_setup'
        );
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong><?php esc_html_e( 'SEO Campaign Hub 1.1.0 is ready.', 'seo-campaign-hub' ); ?></strong>
                <?php esc_html_e( 'Recommended workflow for WP post campaigns:', 'seo-campaign-hub' ); ?>
            </p>
            <ol style="margin-left:1.5em">
                <li><?php esc_html_e( 'Create your SEO landing posts/pages (one per language if needed).', 'seo-campaign-hub' ); ?></li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>">
                        <?php esc_html_e( 'Settings → Permalinks → Save Changes', 'seo-campaign-hub' ); ?>
                    </a>
                    <?php esc_html_e( '(flush rewrite rules once)', 'seo-campaign-hub' ); ?>
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-shortener' ) ); ?>">
                        <?php esc_html_e( 'Create a short link', 'seo-campaign-hub' ); ?>
                    </a>
                    <?php esc_html_e( 'pointing to your post, plus optional language/country rules.', 'seo-campaign-hub' ); ?>
                </li>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-analytics' ) ); ?>">
                        <?php esc_html_e( 'Track country + language in Analytics', 'seo-campaign-hub' ); ?>
                    </a>
                </li>
            </ol>
            <p>
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-shortener' ) ); ?>">
                    <?php esc_html_e( 'Open URL Shortener', 'seo-campaign-hub' ); ?>
                </a>
                <a class="button" href="<?php echo esc_url( $dismiss_url ); ?>">
                    <?php esc_html_e( 'Dismiss', 'seo-campaign-hub' ); ?>
                </a>
            </p>
        </div>
        <?php
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
            __( 'Image Optimization', 'seo-campaign-hub' ),
            __( 'Image Optimization', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-image-opt',
            [ $this, 'render_image_optimization' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Image to SVG', 'seo-campaign-hub' ),
            __( 'Image to SVG', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-image-to-svg',
            [ $this, 'render_image_to_svg' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Public URL Shortener', 'seo-campaign-hub' ),
            __( 'Public Shortener', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-public-shortener',
            [ $this, 'render_public_shortener' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Social Share', 'seo-campaign-hub' ),
            __( 'Social Share', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-social-share',
            [ $this, 'render_social_share' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Web Push', 'seo-campaign-hub' ),
            __( 'Web Push', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-web-push',
            [ $this, 'render_web_push' ]
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
            __( 'URL Replace', 'seo-campaign-hub' ),
            __( 'URL Replace', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-url-replace',
            [ $this, 'render_url_replace' ]
        );

        add_submenu_page(
            'seo-campaign-hub',
            __( 'Cloud Backup', 'seo-campaign-hub' ),
            __( 'Cloud Backup', 'seo-campaign-hub' ),
            'manage_options',
            'seo-campaign-hub-cloud-backup',
            [ $this, 'render_cloud_backup' ]
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
            'enabled_languages' => $shortener->get_enabled_languages(),
            'default_priority'  => $shortener->get_default_redirect_priority(),
            'smart_redirects_enabled' => $shortener->is_smart_redirects_enabled(),
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
    public function render_image_optimization(): void {
        $optimizer = null;
        try {
            $optimizer = $this->container->get( 'image_optimization' );
        } catch ( \Throwable $e ) {
            $optimizer = null;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice = isset( $_GET['sch_notice'] ) ? sanitize_key( wp_unslash( $_GET['sch_notice'] ) ) : '';

        $this->render_view( 'image-optimization', [
            'page_title' => __( 'Image Optimization', 'seo-campaign-hub' ),
            'optimizer'  => $optimizer,
            'notice'     => $notice,
        ] );
    }

    /** @return void */
    public function render_image_to_svg(): void {
        $service = null;
        try {
            $service = $this->container->get( 'image_to_svg' );
        } catch ( \Throwable $e ) {
            $service = null;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice = isset( $_GET['sch_notice'] ) ? sanitize_key( wp_unslash( $_GET['sch_notice'] ) ) : '';

        $this->render_view( 'image-to-svg', [
            'page_title' => __( 'Image to SVG', 'seo-campaign-hub' ),
            'service'    => $service,
            'notice'     => $notice,
        ] );
    }

    /** @return void */
    public function render_public_shortener(): void {
        $service = null;
        try {
            $service = $this->container->get( 'public_shortener' );
        } catch ( \Throwable $e ) {
            $service = null;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice = isset( $_GET['sch_notice'] ) ? sanitize_key( wp_unslash( $_GET['sch_notice'] ) ) : '';

        $this->render_view( 'public-url-shortener', [
            'page_title' => __( 'Public URL Shortener', 'seo-campaign-hub' ),
            'service'    => $service,
            'notice'     => $notice,
        ] );
    }

    /**
     * Save Image → SVG settings from dedicated admin page.
     *
     * @return void
     */
    public function handle_image_to_svg_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_image_to_svg_settings' );

        $options = get_option( 'seo_campaign_hub_options', [] );
        if ( ! is_array( $options ) ) {
            $options = [];
        }

        $options['enable_image_to_svg'] = isset( $_POST['enable_image_to_svg'] ) ? '1' : '0';
        update_option( 'seo_campaign_hub_options', $options );

        wp_safe_redirect(
            add_query_arg(
                [ 'page' => 'seo-campaign-hub-image-to-svg', 'sch_notice' => 'saved' ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Save public URL shortener settings from dedicated admin page.
     *
     * @return void
     */
    public function handle_public_shortener_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_public_shortener_settings' );

        $options = get_option( 'seo_campaign_hub_options', [] );
        if ( ! is_array( $options ) ) {
            $options = [];
        }

        $options['enable_public_url_shortener']     = isset( $_POST['enable_public_url_shortener'] ) ? '1' : '0';
        $options['public_shortener_same_site_only'] = isset( $_POST['public_shortener_same_site_only'] ) ? '1' : '0';
        $rate = isset( $_POST['public_shortener_rate_limit'] ) ? absint( wp_unslash( $_POST['public_shortener_rate_limit'] ) ) : 10;
        $options['public_shortener_rate_limit'] = max( 1, min( 100, $rate ) );

        update_option( 'seo_campaign_hub_options', $options );

        wp_safe_redirect(
            add_query_arg(
                [ 'page' => 'seo-campaign-hub-public-shortener', 'sch_notice' => 'saved' ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Save Image Optimization settings from dedicated page.
     *
     * @return void
     */
    public function handle_image_opt_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_image_opt_settings' );

        $options = get_option( 'seo_campaign_hub_options', [] );
        if ( ! is_array( $options ) ) {
            $options = [];
        }

        $options['image_opt_auto_upload'] = isset( $_POST['image_opt_auto_upload'] ) ? '1' : '0';
        $options['image_opt_serve_webp']  = isset( $_POST['image_opt_serve_webp'] ) ? '1' : '0';
        $quality = isset( $_POST['image_opt_quality'] ) ? absint( wp_unslash( $_POST['image_opt_quality'] ) ) : 82;
        $options['image_opt_quality'] = max( 60, min( 90, $quality ) );

        update_option( 'seo_campaign_hub_options', $options );

        wp_safe_redirect(
            add_query_arg(
                [ 'page' => 'seo-campaign-hub-image-opt', 'sch_notice' => 'saved' ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * AJAX: optimize a small batch of pending attachments.
     *
     * @return void
     */
    public function ajax_image_optimize_batch(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Forbidden.', 'seo-campaign-hub' ) ], 403 );
        }
        check_ajax_referer( 'sch_image_optimize_batch', 'nonce' );

        try {
            /** @var \SEO_Campaign_Hub\Services\ImageOptimizationService $optimizer */
            $optimizer = $this->container->get( 'image_optimization' );
        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'message' => __( 'Service unavailable.', 'seo-campaign-hub' ) ], 500 );
        }

        if ( ! $optimizer->supports_webp() ) {
            wp_send_json_error( [ 'message' => __( 'WebP not supported on this server.', 'seo-campaign-hub' ) ] );
        }

        $ids       = $optimizer->get_pending_ids( 3 );
        $processed = 0;
        $success   = 0;
        $failed    = 0;

        foreach ( $ids as $id ) {
            ++$processed;
            $result = $optimizer->optimize_attachment( (int) $id );
            if ( ! empty( $result['success'] ) ) {
                ++$success;
            } else {
                ++$failed;
                update_post_meta( (int) $id, '_sch_webp_skip', '1' );
            }
        }

        $remaining = count( $optimizer->get_pending_ids( 500 ) );
        if ( $processed > 0 ) {
            $optimizer->save_last_run(
                [
                    'processed' => $processed,
                    'success'   => $success,
                    'failed'    => $failed,
                ]
            );
        }

        wp_send_json_success(
            [
                'processed' => $processed,
                'success'   => $success,
                'failed'    => $failed,
                'remaining' => (int) $remaining,
            ]
        );
    }

    /**
     * Assets for Image Optimization admin page.
     *
     * @param string $hook_suffix Hook.
     * @return void
     */
    public function enqueue_image_opt_assets( string $hook_suffix ): void {
        if ( strpos( $hook_suffix, 'seo-campaign-hub-image-opt' ) === false ) {
            return;
        }

        $pending = 0;
        try {
            $pending = $this->container->get( 'image_optimization' )->get_stats()['pending'];
        } catch ( \Throwable $e ) {
            $pending = 0;
        }

        $version = defined( 'SEO_CAMPAIGN_HUB_VERSION' ) ? SEO_CAMPAIGN_HUB_VERSION : '1.0.0';
        $url     = defined( 'SEO_CAMPAIGN_HUB_PLUGIN_URL' ) ? SEO_CAMPAIGN_HUB_PLUGIN_URL : '';

        wp_enqueue_script(
            'seo-campaign-hub-image-opt',
            $url . 'assets/admin/js/admin-image-opt.js',
            [],
            $version,
            true
        );
        wp_localize_script(
            'seo-campaign-hub-image-opt',
            'schImageOpt',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'sch_image_optimize_batch' ),
                'pending' => (string) (int) $pending,
            ]
        );
    }

    /**
     * Assets for Image → SVG admin page (reuse public converter).
     *
     * @param string $hook_suffix Hook.
     * @return void
     */
    public function enqueue_image_to_svg_assets( string $hook_suffix ): void {
        if ( strpos( $hook_suffix, 'seo-campaign-hub-image-to-svg' ) === false ) {
            return;
        }

        try {
            $this->container->get( 'image_to_svg' )->enqueue_assets();
        } catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        }
    }

    /**
     * Enqueue Google Drive Sync popup script on Cloud Backup only.
     *
     * @param string $hook_suffix Admin hook.
     * @return void
     */
    public function enqueue_cloud_backup_assets( string $hook_suffix ): void {
        if ( strpos( $hook_suffix, 'seo-campaign-hub-cloud-backup' ) === false ) {
            return;
        }

        $version = defined( 'SEO_CAMPAIGN_HUB_VERSION' ) ? SEO_CAMPAIGN_HUB_VERSION : '1.0.0';
        $url     = defined( 'SEO_CAMPAIGN_HUB_PLUGIN_URL' ) ? SEO_CAMPAIGN_HUB_PLUGIN_URL : '';

        wp_enqueue_script(
            'seo-campaign-hub-admin-cloud-backup',
            $url . 'assets/admin/js/admin-cloud-backup.js',
            [],
            $version,
            true
        );
    }

    /**
     * Assets for Public URL Shortener admin page.
     *
     * @param string $hook_suffix Hook.
     * @return void
     */
    public function enqueue_public_shortener_assets( string $hook_suffix ): void {
        if ( strpos( $hook_suffix, 'seo-campaign-hub-public-shortener' ) === false ) {
            return;
        }

        try {
            $this->container->get( 'public_shortener' )->enqueue_assets();
        } catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        }
    }

    /** @return void */
    public function render_settings(): void {
        $settings = null;
        try {
            $settings = $this->container->get( 'settings' );
        } catch ( \Throwable $e ) {
            $settings = null;
        }

        $this->render_view( 'settings', [
            'page_title' => __( 'Settings', 'seo-campaign-hub' ),
            'settings'   => $settings,
        ] );
    }

    /** @return void */
    public function render_url_replace(): void {
        $service = null;
        try {
            $service = $this->container->get( 'url_replace' );
        } catch ( \Throwable $e ) {
            $service = null;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $notice  = isset( $_GET['sch_notice'] ) ? sanitize_key( wp_unslash( $_GET['sch_notice'] ) ) : '';
        $message = isset( $_GET['sch_message'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['sch_message'] ) ) ) : '';
        // phpcs:enable

        $result = get_transient( 'sch_url_replace_result_' . get_current_user_id() );
        $counts = ( is_array( $result ) && isset( $result['counts'] ) && is_array( $result['counts'] ) ) ? $result['counts'] : [];

        $this->render_view(
            'url-replace',
            [
                'page_title' => __( 'URL Replace', 'seo-campaign-hub' ),
                'rules'      => $service instanceof \SEO_Campaign_Hub\Services\UrlReplaceService ? $service->get_rules() : [],
                'notice'     => $notice,
                'message'    => $message,
                'counts'     => $counts,
            ]
        );
    }

    /**
     * @return void
     */
    private function redirect_to_url_replace( array $args = [] ): void {
        wp_safe_redirect(
            add_query_arg( $args, admin_url( 'admin.php?page=seo-campaign-hub-url-replace' ) )
        );
        exit;
    }

    /** @return void */
    public function handle_url_replace_rule_add(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_url_replace_rule_add' );

        $from = isset( $_POST['find_url'] ) ? wp_unslash( $_POST['find_url'] ) : '';
        $to   = isset( $_POST['replace_url'] ) ? wp_unslash( $_POST['replace_url'] ) : '';
        $from = sanitize_text_field( $from );
        $to   = esc_url_raw( $to ) !== '' ? esc_url_raw( $to ) : sanitize_text_field( $to );

        $result = $this->container->get( 'url_replace' )->add_rule( $from, $to );
        $this->redirect_to_url_replace(
            [
                'sch_notice'  => ! empty( $result['ok'] ) ? 'saved' : 'error',
                'sch_message' => rawurlencode( (string) ( $result['message'] ?? '' ) ),
            ]
        );
    }

    /** @return void */
    public function handle_url_replace_rule_delete(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_url_replace_rule_delete' );

        $id = isset( $_GET['rule_id'] ) ? sanitize_key( wp_unslash( $_GET['rule_id'] ) ) : '';
        $this->container->get( 'url_replace' )->delete_rule( $id );
        $this->redirect_to_url_replace( [ 'sch_notice' => 'deleted' ] );
    }

    /** @return void */
    public function handle_url_replace_rule_toggle(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_url_replace_rule_toggle' );

        $id = isset( $_GET['rule_id'] ) ? sanitize_key( wp_unslash( $_GET['rule_id'] ) ) : '';
        $this->container->get( 'url_replace' )->toggle_rule( $id );
        $this->redirect_to_url_replace( [ 'sch_notice' => 'saved' ] );
    }

    /** @return void */
    public function handle_url_replace_db(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_url_replace_db' );

        $from    = isset( $_POST['find_url'] ) ? sanitize_text_field( wp_unslash( $_POST['find_url'] ) ) : '';
        $to_raw  = isset( $_POST['replace_url'] ) ? wp_unslash( $_POST['replace_url'] ) : '';
        $to      = esc_url_raw( $to_raw ) !== '' ? esc_url_raw( $to_raw ) : sanitize_text_field( $to_raw );
        $apply   = isset( $_POST['sch_apply'] );
        $dry     = ! $apply;
        $confirm = ! empty( $_POST['sch_confirm'] );

        if ( $apply && ! $confirm ) {
            $this->redirect_to_url_replace(
                [
                    'sch_notice'  => 'error',
                    'sch_message' => rawurlencode( __( 'Check the confirmation box before Apply.', 'seo-campaign-hub' ) ),
                ]
            );
        }

        $result = $this->container->get( 'url_replace' )->replace_in_database( $from, $to, $dry );
        set_transient( 'sch_url_replace_result_' . get_current_user_id(), $result, 10 * MINUTE_IN_SECONDS );

        $this->redirect_to_url_replace(
            [
                'sch_notice'  => ! empty( $result['ok'] ) ? ( $dry ? 'dry_ok' : 'db_ok' ) : 'error',
                'sch_message' => rawurlencode( (string) ( $result['message'] ?? '' ) ),
            ]
        );
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

    /** @return void */
    public function render_cloud_backup(): void {
        $drive         = null;
        $cloud_backup  = null;
        try {
            $drive         = $this->container->get( 'google_drive' );
            $cloud_backup  = $this->container->get( 'cloud_backup' );
        } catch ( \Throwable $e ) {
            $drive         = null;
            $cloud_backup  = null;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $notice  = isset( $_GET['sch_notice'] ) ? sanitize_key( wp_unslash( $_GET['sch_notice'] ) ) : '';
        $message = isset( $_GET['sch_message'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['sch_message'] ) ) ) : '';
        // phpcs:enable

        $this->render_view( 'cloud-backup', [
            'page_title'   => __( 'Cloud Backup', 'seo-campaign-hub' ),
            'drive'        => $drive,
            'cloud_backup' => $cloud_backup,
            'notice'       => $notice,
            'message'      => $message,
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

    /**
     * Redirect to Cloud Backup admin page.
     *
     * @param array<string, string> $args Query args.
     * @return void
     */
    private function redirect_to_cloud_backup( array $args = [] ): void {
        wp_safe_redirect(
            add_query_arg( $args, admin_url( 'admin.php?page=seo-campaign-hub-cloud-backup' ) )
        );
        exit;
    }

    /**
     * Complete Google OAuth when user returns from Google.
     *
     * @return void
     */
    public function maybe_handle_google_oauth_callback(): void {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! isset( $_GET['page'] ) || 'seo-campaign-hub-cloud-backup' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['error'] ) && ! isset( $_GET['code'] ) ) {
            if ( isset( $_GET['error_description'] ) ) {
                $error_desc = sanitize_text_field( wp_unslash( $_GET['error_description'] ) );
            } else {
                $error_desc = sanitize_text_field( wp_unslash( (string) $_GET['error'] ) );
            }
            $this->redirect_to_cloud_backup(
                [
                    'sch_notice'  => 'oauth_fail',
                    'sch_message' => rawurlencode( $error_desc !== '' ? $error_desc : __( 'Google authorization was cancelled.', 'seo-campaign-hub' ) ),
                ]
            );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! isset( $_GET['code'] ) ) {
            return;
        }

        try {
            $drive  = $this->container->get( 'google_drive' );
            $result = $drive->handle_oauth_callback();
        } catch ( \Throwable $e ) {
            $this->redirect_to_cloud_backup(
                [
                    'sch_notice'  => 'oauth_fail',
                    'sch_message' => rawurlencode( $e->getMessage() ),
                ]
            );
        }

        $this->redirect_to_cloud_backup(
            [
                'sch_notice'  => ! empty( $result['success'] ) ? 'oauth_ok' : 'oauth_fail',
                'sch_message' => rawurlencode( (string) ( $result['message'] ?? '' ) ),
            ]
        );
    }

    /**
     * Save Google Drive / folder settings.
     *
     * @return void
     */
    public function handle_cloud_backup_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_cloud_backup_settings' );

        $drive = $this->container->get( 'google_drive' );

        $drive->update_settings(
            [
                'google_client_id'     => isset( $_POST['google_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['google_client_id'] ) ) : '',
                'google_client_secret' => isset( $_POST['google_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['google_client_secret'] ) ) : '',
                'parent_folder'        => isset( $_POST['parent_folder'] ) ? sanitize_text_field( wp_unslash( $_POST['parent_folder'] ) ) : 'seo-campaign-hub-backups',
                'subfolder'            => isset( $_POST['subfolder'] ) ? sanitize_text_field( wp_unslash( $_POST['subfolder'] ) ) : $drive->default_site_folder_name(),
                'retention'            => isset( $_POST['retention'] ) ? max( 1, min( 50, absint( $_POST['retention'] ) ) ) : 5,
            ]
        );

        $this->redirect_to_cloud_backup( [ 'sch_notice' => 'saved' ] );
    }

    /**
     * Manual backup to Google Drive.
     *
     * @return void
     */
    public function handle_cloud_backup_now(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_cloud_backup_now' );

        $result = $this->container->get( 'cloud_backup' )->run_plugin_backup();

        $this->redirect_to_cloud_backup(
            [
                'sch_notice'  => ! empty( $result['success'] ) ? 'backup_ok' : 'backup_fail',
                'sch_message' => rawurlencode( (string) ( $result['message'] ?? '' ) ),
            ]
        );
    }

    /**
     * Save backup schedule and reschedule cron.
     *
     * @return void
     */
    public function handle_cloud_backup_schedule(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_cloud_backup_schedule' );

        $drive = $this->container->get( 'google_drive' );
        $freq  = isset( $_POST['schedule_frequency'] ) ? sanitize_key( wp_unslash( $_POST['schedule_frequency'] ) ) : 'daily';
        if ( ! in_array( $freq, [ 'daily', 'weekly' ], true ) ) {
            $freq = 'daily';
        }

        $drive->update_settings(
            [
                'schedule_enabled'    => ! empty( $_POST['schedule_enabled'] ) ? '1' : '0',
                'schedule_frequency'  => $freq,
            ]
        );

        $this->container->get( 'backup_scheduler' )->reschedule();

        $this->redirect_to_cloud_backup( [ 'sch_notice' => 'saved' ] );
    }

    /**
     * Disconnect Google Drive.
     *
     * @return void
     */
    public function handle_google_disconnect(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_google_disconnect' );

        $this->container->get( 'google_drive' )->disconnect();
        $this->container->get( 'backup_scheduler' )->clear();

        $this->redirect_to_cloud_backup( [ 'sch_notice' => 'disconnected' ] );
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

        if ( ! empty( $_POST['redirect_priority'] ) ) {
            $data['redirect_priority'] = sanitize_text_field( wp_unslash( $_POST['redirect_priority'] ) );
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via ShortenerService.
        $raw_rules = isset( $_POST['targeting_rules'] ) ? wp_unslash( $_POST['targeting_rules'] ) : [];
        if ( is_array( $raw_rules ) ) {
            $data['targeting_rules'] = $shortener->sanitize_targeting_rules( $raw_rules );
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
    // SOCIAL SHARE (POSTS LIST)
    // =========================================================

    /**
     * Append browser share icons to Posts list row actions.
     *
     * @param array<string, string> $actions Existing actions.
     * @param \WP_Post              $post    Post.
     * @return array<string, string>
     */
    public function add_post_share_row_actions( array $actions, $post ): array {
        if ( ! ( $post instanceof \WP_Post ) ) {
            return $actions;
        }

        try {
            $share = $this->container->get( 'social_share' );
            if ( ! $share instanceof \SEO_Campaign_Hub\Services\SocialShareService ) {
                return $actions;
            }
            return array_merge( $actions, $share->get_row_action_links( $post ) );
        } catch ( \Throwable $e ) {
            return $actions;
        }
    }

    /**
     * Add Share column on Posts list.
     *
     * @param array<string, string> $columns Columns.
     * @return array<string, string>
     */
    public function add_post_share_column( array $columns ): array {
        try {
            $share = $this->container->get( 'social_share' );
            if ( ! $share instanceof \SEO_Campaign_Hub\Services\SocialShareService || ! $share->is_enabled() ) {
                return $columns;
            }
        } catch ( \Throwable $e ) {
            return $columns;
        }

        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'title' ) {
                $new['sch_share'] = __( 'Share', 'seo-campaign-hub' );
            }
        }
        if ( ! isset( $new['sch_share'] ) ) {
            $new['sch_share'] = __( 'Share', 'seo-campaign-hub' );
        }
        return $new;
    }

    /**
     * Render Share column cell.
     *
     * @param string $column  Column key.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function render_post_share_column( string $column, int $post_id ): void {
        if ( $column !== 'sch_share' ) {
            return;
        }
        $post = get_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) ) {
            echo '—';
            return;
        }
        try {
            $share = $this->container->get( 'social_share' );
            if ( ! $share instanceof \SEO_Campaign_Hub\Services\SocialShareService ) {
                echo '—';
                return;
            }
            echo $share->get_column_html( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in service.
        } catch ( \Throwable $e ) {
            echo '—';
        }
    }

    /** @return void */
    public function render_social_share(): void {
        $share = null;
        try {
            $share = $this->container->get( 'social_share' );
        } catch ( \Throwable $e ) {
            $share = null;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice = isset( $_GET['sch_notice'] ) ? sanitize_key( wp_unslash( $_GET['sch_notice'] ) ) : '';

        $this->render_view( 'social-share', [
            'page_title' => __( 'Social Share', 'seo-campaign-hub' ),
            'share'      => $share,
            'notice'     => $notice,
        ] );
    }

    /**
     * Save Social Share settings from dedicated page.
     *
     * @return void
     */
    public function handle_social_share_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_social_share_settings' );

        $options = get_option( 'seo_campaign_hub_options', [] );
        if ( ! is_array( $options ) ) {
            $options = [];
        }

        $options['enable_admin_social_share'] = isset( $_POST['enable_admin_social_share'] ) ? '1' : '0';

        $allowed = [ 'facebook', 'x', 'linkedin', 'pinterest', 'whatsapp', 'blogger', 'telegram', 'quora', 'reddit', 'email', 'copy' ];
        $raw     = isset( $_POST['social_share_networks'] ) ? wp_unslash( $_POST['social_share_networks'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $clean   = [];
        if ( is_array( $raw ) ) {
            foreach ( $raw as $item ) {
                $item = sanitize_key( (string) $item );
                if ( in_array( $item, $allowed, true ) ) {
                    $clean[] = $item;
                }
            }
        }
        $options['social_share_networks'] = $clean !== [] ? $clean : $allowed;

        update_option( 'seo_campaign_hub_options', $options );

        wp_safe_redirect(
            add_query_arg(
                [ 'page' => 'seo-campaign-hub-social-share', 'sch_notice' => 'saved' ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /** @return void */
    public function render_web_push(): void {
        $push = null;
        try {
            $push = $this->container->get( 'web_push' );
        } catch ( \Throwable $e ) {
            $push = null;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice = isset( $_GET['sch_notice'] ) ? sanitize_key( wp_unslash( $_GET['sch_notice'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice_msg = isset( $_GET['sch_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['sch_msg'] ) ) : '';

        $this->render_view( 'web-push', [
            'page_title' => __( 'Web Push', 'seo-campaign-hub' ),
            'push'       => $push,
            'notice'     => $notice,
            'notice_msg' => $notice_msg,
        ] );
    }

    /**
     * Save Web Push settings.
     *
     * @return void
     */
    public function handle_web_push_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_web_push_settings' );

        $options = get_option( 'seo_campaign_hub_options', [] );
        if ( ! is_array( $options ) ) {
            $options = [];
        }

        $options['enable_web_push']             = isset( $_POST['enable_web_push'] ) ? '1' : '0';
        $options['onesignal_app_id']            = isset( $_POST['onesignal_app_id'] ) ? sanitize_text_field( wp_unslash( $_POST['onesignal_app_id'] ) ) : '';
        $options['onesignal_rest_api_key']      = isset( $_POST['onesignal_rest_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['onesignal_rest_api_key'] ) ) : '';
        $options['web_push_auto_notify']        = isset( $_POST['web_push_auto_notify'] ) ? '1' : '0';
        $options['web_push_soft_prompt']        = isset( $_POST['web_push_soft_prompt'] ) ? '1' : '0';
        $options['web_push_soft_prompt_delay']  = isset( $_POST['web_push_soft_prompt_delay'] )
            ? (string) max( 0, min( 120, (int) $_POST['web_push_soft_prompt_delay'] ) )
            : '8';

        update_option( 'seo_campaign_hub_options', $options );

        wp_safe_redirect(
            add_query_arg(
                [ 'page' => 'seo-campaign-hub-web-push', 'sch_notice' => 'saved' ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Manual Web Push send from admin page.
     *
     * @return void
     */
    public function handle_web_push_send(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_web_push_send' );

        $title   = isset( $_POST['push_title'] ) ? sanitize_text_field( wp_unslash( $_POST['push_title'] ) ) : '';
        $message = isset( $_POST['push_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['push_message'] ) ) : '';
        $url     = isset( $_POST['push_url'] ) ? esc_url_raw( wp_unslash( $_POST['push_url'] ) ) : '';

        try {
            $push   = $this->container->get( 'web_push' );
            $result = $push instanceof \SEO_Campaign_Hub\Services\OneSignalWebPushService
                ? $push->send_notification(
                    [
                        'title'   => $title,
                        'message' => $message,
                        'url'     => $url,
                    ]
                )
                : [ 'ok' => false, 'message' => __( 'Web Push service unavailable.', 'seo-campaign-hub' ) ];
        } catch ( \Throwable $e ) {
            $result = [ 'ok' => false, 'message' => $e->getMessage() ];
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page'       => 'seo-campaign-hub-web-push',
                    'sch_notice' => ! empty( $result['ok'] ) ? 'sent' : 'error',
                    'sch_msg'    => (string) ( $result['message'] ?? '' ),
                ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }

    /**
     * Send push for a specific post from the editor.
     *
     * @return void
     */
    public function handle_web_push_send_post(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'seo-campaign-hub' ) );
        }
        check_admin_referer( 'sch_web_push_send_post' );

        $post_id = isset( $_REQUEST['post_id'] ) ? (int) $_REQUEST['post_id'] : 0;
        if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( esc_html__( 'Invalid post.', 'seo-campaign-hub' ) );
        }

        try {
            $push   = $this->container->get( 'web_push' );
            $result = $push instanceof \SEO_Campaign_Hub\Services\OneSignalWebPushService
                ? $push->send_for_post( $post_id )
                : [ 'ok' => false, 'message' => __( 'Web Push service unavailable.', 'seo-campaign-hub' ) ];
        } catch ( \Throwable $e ) {
            $result = [ 'ok' => false, 'message' => $e->getMessage() ];
        }

        $redirect = get_edit_post_link( $post_id, 'raw' );
        if ( ! is_string( $redirect ) || $redirect === '' ) {
            $redirect = admin_url( 'edit.php' );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'sch_push' => ! empty( $result['ok'] ) ? 'sent' : 'error',
                    'sch_msg'  => (string) ( $result['message'] ?? '' ),
                ],
                $redirect
            )
        );
        exit;
    }

    /**
     * Register Web Push metabox on posts.
     *
     * @return void
     */
    public function add_web_push_metabox(): void {
        add_meta_box(
            'sch_web_push',
            __( 'Web Push (OneSignal)', 'seo-campaign-hub' ),
            [ $this, 'render_web_push_metabox' ],
            'post',
            'side',
            'default'
        );
    }

    /**
     * @param \WP_Post $post Post.
     * @return void
     */
    public function render_web_push_metabox( $post ): void {
        if ( ! ( $post instanceof \WP_Post ) ) {
            return;
        }

        wp_nonce_field( 'sch_web_push_metabox', 'sch_web_push_metabox_nonce' );
        $skip = get_post_meta( $post->ID, \SEO_Campaign_Hub\Services\OneSignalWebPushService::META_SKIP, true ) === '1';
        $sent = (string) get_post_meta( $post->ID, \SEO_Campaign_Hub\Services\OneSignalWebPushService::META_SENT, true );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $flash = isset( $_GET['sch_push'] ) ? sanitize_key( wp_unslash( $_GET['sch_push'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $flash_msg = isset( $_GET['sch_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['sch_msg'] ) ) : '';
        if ( $flash === 'sent' ) {
            echo '<p style="color:#157347"><strong>' . esc_html( $flash_msg !== '' ? $flash_msg : __( 'Notification sent.', 'seo-campaign-hub' ) ) . '</strong></p>';
        } elseif ( $flash === 'error' ) {
            echo '<p style="color:#b32d2e"><strong>' . esc_html( $flash_msg !== '' ? $flash_msg : __( 'Send failed.', 'seo-campaign-hub' ) ) . '</strong></p>';
        }
        ?>
        <p>
            <label>
                <input type="checkbox" name="sch_webpush_skip" value="1" <?php checked( $skip ); ?> />
                <?php esc_html_e( 'Don’t notify on publish', 'seo-campaign-hub' ); ?>
            </label>
        </p>
        <?php if ( $sent !== '' ) : ?>
            <p class="description">
                <?php
                printf(
                    /* translators: %s: datetime */
                    esc_html__( 'Last push: %s', 'seo-campaign-hub' ),
                    esc_html( $sent )
                );
                ?>
            </p>
        <?php endif; ?>
        <?php if ( $post->post_status === 'publish' && current_user_can( 'manage_options' ) ) : ?>
            <p>
                <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sch_web_push_send_post&post_id=' . (int) $post->ID ), 'sch_web_push_send_post' ) ); ?>">
                    <?php esc_html_e( 'Send push now', 'seo-campaign-hub' ); ?>
                </a>
            </p>
        <?php endif; ?>
        <p class="description">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-campaign-hub-web-push' ) ); ?>">
                <?php esc_html_e( 'Web Push settings', 'seo-campaign-hub' ); ?>
            </a>
        </p>
        <?php
    }

    /**
     * Save Web Push metabox fields.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post.
     * @return void
     */
    public function save_web_push_metabox( int $post_id, $post ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! ( $post instanceof \WP_Post ) || $post->post_type !== 'post' ) {
            return;
        }
        if ( ! isset( $_POST['sch_web_push_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sch_web_push_metabox_nonce'] ) ), 'sch_web_push_metabox' ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $skip = isset( $_POST['sch_webpush_skip'] ) ? '1' : '0';
        update_post_meta( $post_id, \SEO_Campaign_Hub\Services\OneSignalWebPushService::META_SKIP, $skip );
    }

    /**
     * Enqueue share icons CSS/JS on Posts → All Posts only.
     *
     * @param string $hook_suffix Admin hook.
     * @return void
     */
    public function enqueue_post_share_assets( string $hook_suffix ): void {
        if ( $hook_suffix !== 'edit.php' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';
        if ( $post_type !== 'post' ) {
            return;
        }

        try {
            $share = $this->container->get( 'social_share' );
            if ( ! $share instanceof \SEO_Campaign_Hub\Services\SocialShareService || ! $share->is_enabled() ) {
                return;
            }
        } catch ( \Throwable $e ) {
            return;
        }

        $version = defined( 'SEO_CAMPAIGN_HUB_VERSION' ) ? SEO_CAMPAIGN_HUB_VERSION : '1.0.0';
        $url     = defined( 'SEO_CAMPAIGN_HUB_PLUGIN_URL' ) ? SEO_CAMPAIGN_HUB_PLUGIN_URL : '';

        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style(
            'seo-campaign-hub-admin-share',
            $url . 'assets/admin/css/admin.css',
            [ 'dashicons' ],
            $version
        );
        wp_enqueue_script(
            'seo-campaign-hub-admin-share',
            $url . 'assets/admin/js/admin-share.js',
            [],
            $version,
            true
        );
        wp_localize_script(
            'seo-campaign-hub-admin-share',
            'schShare',
            [
                'copied' => __( 'Copied!', 'seo-campaign-hub' ),
                'failed' => __( 'Copy failed', 'seo-campaign-hub' ),
            ]
        );
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

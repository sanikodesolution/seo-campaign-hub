<?php
/**
 * Admin Initialization
 *
 * @package SEO_Campaign_Hub\Admin
 */

namespace SEO_Campaign_Hub\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminInit
 *
 * Handles admin initialization and menu setup
 */
class AdminInit {
    /**
     * Container instance
     *
     * @var \SEO_Campaign_Hub\Core\Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param \SEO_Campaign_Hub\Core\Container $container
     */
    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * Initialize admin features
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('plugin_action_links_seo-campaign-hub/seo-campaign-hub.php', [$this, 'add_action_links']);
        add_action('admin_head', [$this, 'add_admin_styles']);
        add_action('admin_footer', [$this, 'add_admin_footer_scripts']);
    }

    /**
     * Add admin menu items
     *
     * @return void
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('SEO Campaign Hub', 'seo-campaign-hub'),
            __('SEO Campaign Hub', 'seo-campaign-hub'),
            'manage_options',
            'seo-campaign-hub',
            [$this, 'render_dashboard'],
            'dashicons-megaphone',
            5
        );

        // Dashboard submenu
        add_submenu_page(
            'seo-campaign-hub',
            __('Dashboard', 'seo-campaign-hub'),
            __('Dashboard', 'seo-campaign-hub'),
            'manage_options',
            'seo-campaign-hub',
            [$this, 'render_dashboard']
        );

        // Campaigns submenu (links to post type)
        add_submenu_page(
            'seo-campaign-hub',
            __('Campaigns', 'seo-campaign-hub'),
            __('Campaigns', 'seo-campaign-hub'),
            'manage_options',
            'edit.php?post_type=sch_campaign'
        );

        // Offers submenu (links to post type)
        add_submenu_page(
            'seo-campaign-hub',
            __('Offers', 'seo-campaign-hub'),
            __('Offers', 'seo-campaign-hub'),
            'manage_options',
            'edit.php?post_type=sch_offer'
        );

        // URL Shortener
        add_submenu_page(
            'seo-campaign-hub',
            __('URL Shortener', 'seo-campaign-hub'),
            __('URL Shortener', 'seo-campaign-hub'),
            'manage_options',
            'seo-campaign-hub-shortener',
            [$this, 'render_shortener']
        );

        // QR Codes
        add_submenu_page(
            'seo-campaign-hub',
            __('QR Codes', 'seo-campaign-hub'),
            __('QR Codes', 'seo-campaign-hub'),
            'manage_options',
            'seo-campaign-hub-qr-codes',
            [$this, 'render_qr_codes']
        );

        // Analytics
        add_submenu_page(
            'seo-campaign-hub',
            __('Analytics', 'seo-campaign-hub'),
            __('Analytics', 'seo-campaign-hub'),
            'manage_options',
            'seo-campaign-hub-analytics',
            [$this, 'render_analytics']
        );

        // Settings
        add_submenu_page(
            'seo-campaign-hub',
            __('Settings', 'seo-campaign-hub'),
            __('Settings', 'seo-campaign-hub'),
            'manage_options',
            'seo-campaign-hub-settings',
            [$this, 'render_settings']
        );

        // Import/Export
        add_submenu_page(
            'seo-campaign-hub',
            __('Import/Export', 'seo-campaign-hub'),
            __('Import/Export', 'seo-campaign-hub'),
            'manage_options',
            'seo-campaign-hub-import-export',
            [$this, 'render_import_export']
        );

        // Help
        add_submenu_page(
            'seo-campaign-hub',
            __('Help', 'seo-campaign-hub'),
            __('Help', 'seo-campaign-hub'),
            'manage_options',
            'seo-campaign-hub-help',
            [$this, 'render_help']
        );
    }

    /**
     * Render dashboard
     *
     * @return void
     */
    public function render_dashboard() {
        $this->render_view('dashboard', [
            'page_title' => __('SEO Campaign Hub Dashboard', 'seo-campaign-hub')
        ]);
    }

    /**
     * Render shortener
     *
     * @return void
     */
    public function render_shortener() {
        $this->render_view('shortener', [
            'page_title' => __('URL Shortener', 'seo-campaign-hub')
        ]);
    }

    /**
     * Render QR codes
     *
     * @return void
     */
    public function render_qr_codes() {
        $this->render_view('qr-codes', [
            'page_title' => __('QR Codes', 'seo-campaign-hub')
        ]);
    }

    /**
     * Render analytics
     *
     * @return void
     */
    public function render_analytics() {
        $this->render_view('analytics', [
            'page_title' => __('Analytics', 'seo-campaign-hub')
        ]);
    }

    /**
     * Render settings
     *
     * @return void
     */
    public function render_settings() {
        $this->render_view('settings', [
            'page_title' => __('Settings', 'seo-campaign-hub')
        ]);
    }

    /**
     * Render import/export
     *
     * @return void
     */
    public function render_import_export() {
        $this->render_view('import-export', [
            'page_title' => __('Import/Export', 'seo-campaign-hub')
        ]);
    }

    /**
     * Render help
     *
     * @return void
     */
    public function render_help() {
        $this->render_view('help', [
            'page_title' => __('Help & Support', 'seo-campaign-hub')
        ]);
    }

    /**
     * Render a view
     *
     * @param string $view View name
     * @param array  $data Data to pass to view
     * @return void
     */
    private function render_view($view, $data = []) {
        $view_file = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Admin/Views/' . $view . '.php';
        
        if (!file_exists($view_file)) {
            $this->render_default_view($view, $data);
            return;
        }

        extract($data);
        include $view_file;
    }

    /**
     * Render default view when view file doesn't exist
     *
     * @param string $view View name
     * @param array  $data Data to pass to view
     * @return void
     */
    private function render_default_view($view, $data = []) {
        $page_title = $data['page_title'] ?? ucwords(str_replace('-', ' ', $view));
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($page_title); ?></h1>
            <div class="seo-campaign-hub-admin">
                <div class="seo-campaign-hub-content">
                    <p><?php esc_html_e('Welcome to SEO Campaign Hub!', 'seo-campaign-hub'); ?></p>
                    <div class="seo-campaign-hub-placeholder">
                        <?php
                        printf(
                            /* translators: %s: View name */
                            esc_html__('This is the %s section. Content will be available soon.', 'seo-campaign-hub'),
                            '<strong>' . esc_html($view) . '</strong>'
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page
     * @return void
     */
    public function enqueue_assets($hook) {
        // Only enqueue on plugin pages
        if (strpos($hook, 'seo-campaign-hub') === false && 
            strpos($hook, 'sch_campaign') === false &&
            strpos($hook, 'sch_offer') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'seo-campaign-hub-admin',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . 'assets/admin/css/admin.css',
            [],
            SEO_CAMPAIGN_HUB_VERSION
        );

        wp_enqueue_style(
            'seo-campaign-hub-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // JavaScript
        wp_enqueue_script(
            'seo-campaign-hub-admin',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . 'assets/admin/js/admin.js',
            ['jquery', 'wp-util', 'wp-api', 'wp-api-fetch'],
            SEO_CAMPAIGN_HUB_VERSION,
            true
        );

        // Localize script
        wp_localize_script('seo-campaign-hub-admin', 'seoCampaignHub', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seo_campaign_hub_admin'),
            'restUrl' => esc_url_raw(rest_url(SEO_CAMPAIGN_HUB_REST_NAMESPACE . '/')),
            'siteUrl' => home_url('/'),
            'adminUrl' => admin_url('/'),
            'version' => SEO_CAMPAIGN_HUB_VERSION,
            'strings' => [
                'confirmDelete' => __('Are you sure you want to delete this?', 'seo-campaign-hub'),
                'saveSuccess' => __('Saved successfully!', 'seo-campaign-hub'),
                'saveError' => __('Error saving data.', 'seo-campaign-hub'),
                'loading' => __('Loading...', 'seo-campaign-hub'),
                'processing' => __('Processing...', 'seo-campaign-hub'),
                'copied' => __('Copied to clipboard!', 'seo-campaign-hub'),
                'copy' => __('Copy', 'seo-campaign-hub')
            ]
        ]);
    }

    /**
     * Add action links to plugin list
     *
     * @param array $links Existing links
     * @return array
     */
    public function add_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=seo-campaign-hub-settings'),
            __('Settings', 'seo-campaign-hub')
        );
        
        $dashboard_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=seo-campaign-hub'),
            __('Dashboard', 'seo-campaign-hub')
        );

        array_unshift($links, $settings_link);
        array_unshift($links, $dashboard_link);
        
        return $links;
    }

    /**
     * Add custom admin styles
     *
     * @return void
     */
    public function add_admin_styles() {
        ?>
        <style>
            .seo-campaign-hub-admin {
                background: #fff;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .seo-campaign-hub-content {
                max-width: 1200px;
            }
            .seo-campaign-hub-placeholder {
                padding: 40px;
                text-align: center;
                background: #f9f9f9;
                border-radius: 4px;
                margin: 20px 0;
            }
            .seo-campaign-hub-widget {
                background: #f5f5f5;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
                border-left: 4px solid #007cba;
            }
            .seo-campaign-hub-widget h3 {
                margin-top: 0;
            }
            .seo-campaign-hub-dashboard-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .seo-campaign-hub-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin: 20px 0;
            }
            .seo-campaign-hub-stat-box {
                flex: 1;
                min-width: 150px;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 4px;
                text-align: center;
            }
            .seo-campaign-hub-stat-box .stat-number {
                font-size: 28px;
                font-weight: bold;
                color: #007cba;
            }
            .seo-campaign-hub-stat-box .stat-label {
                color: #666;
                margin-top: 5px;
            }
            .seo-campaign-hub-notice {
                padding: 12px;
                margin: 10px 0;
                border-radius: 4px;
            }
            .seo-campaign-hub-notice.success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            .seo-campaign-hub-notice.error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            .seo-campaign-hub-notice.info {
                background: #d1ecf1;
                border: 1px solid #bee5eb;
                color: #0c5460;
            }
        </style>
        <?php
    }

    /**
     * Add admin footer scripts
     *
     * @return void
     */
    public function add_admin_footer_scripts() {
        // Only add on plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'seo-campaign-hub') === false) {
            return;
        }
        ?>
        <script>
            jQuery(document).ready(function($) {
                // Add any additional admin JavaScript here
                console.log('SEO Campaign Hub Admin Loaded');
            });
        </script>
        <?php
    }

    /**
     * Get container instance
     *
     * @return \SEO_Campaign_Hub\Core\Container
     */
    public function get_container() {
        return $this->container;
    }
}
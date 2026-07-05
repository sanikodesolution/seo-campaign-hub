<?php
/**
 * Public Initialization
 *
 * @package SEOCampaignHub\Public
 */

namespace SEOCampaignHub\Public;

use SEOCampaignHub\Core\Container;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PublicInit
 *
 * Handles public-facing functionality
 */
class PublicInit {
    /**
     * Container instance
     *
     * @var Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Initialize public features
     *
     * @return void
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_head', [$this, 'add_meta_tags'], 1);
        add_action('wp_head', [$this, 'add_open_graph'], 2);
        add_action('wp_head', [$this, 'add_schema_markup'], 3);
        add_filter('the_content', [$this, 'modify_content']);
        add_filter('excerpt_length', [$this, 'custom_excerpt_length'], 999);
        add_filter('excerpt_more', [$this, 'custom_excerpt_more']);
        add_action('template_redirect', [$this, 'handle_custom_redirects']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('parse_request', [$this, 'parse_request']);
        add_action('init', [$this, 'register_shortcodes']);
    }

    /**
     * Enqueue public assets
     *
     * @return void
     */
    public function enqueue_assets() {
        // Check if constants are defined
        if (!defined('SEO_CAMPAIGN_HUB_PLUGIN_URL') || !defined('SEO_CAMPAIGN_HUB_VERSION')) {
            return;
        }

        // Only enqueue on relevant pages
        if (!is_singular(['sch_campaign', 'sch_offer']) &&
            !is_post_type_archive(['sch_campaign', 'sch_offer']) &&
            !is_search()) {
            return;
        }

        // Enqueue public CSS
        $css_file = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'assets/public/css/public.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'seo-campaign-hub-public',
                SEO_CAMPAIGN_HUB_PLUGIN_URL . 'assets/public/css/public.css',
                [],
                SEO_CAMPAIGN_HUB_VERSION
            );
        }

        // Enqueue public JavaScript
        $js_file = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'assets/public/js/public.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'seo-campaign-hub-public',
                SEO_CAMPAIGN_HUB_PLUGIN_URL . 'assets/public/js/public.js',
                ['jquery'],
                SEO_CAMPAIGN_HUB_VERSION,
                true
            );

            // Localize script
            wp_localize_script('seo-campaign-hub-public', 'seoCampaignHubPublic', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('seo_campaign_hub_public'),
                'siteUrl' => home_url('/'),
                'tracking' => get_option('seo_campaign_hub_track_visitors', true),
                'strings' => [
                    'loading' => __('Loading...', 'seo-campaign-hub'),
                    'error' => __('An error occurred.', 'seo-campaign-hub'),
                    'success' => __('Success!', 'seo-campaign-hub')
                ]
            ]);
        }

        // Add inline styles
        wp_add_inline_style('seo-campaign-hub-public', $this->get_inline_styles());
    }

    /**
     * Add meta tags to head
     *
     * @return void
     */
    public function add_meta_tags() {
        if (!is_singular(['sch_campaign', 'sch_offer'])) {
            return;
        }

        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);

        // Get meta data
        $meta_title = get_post_meta($post_id, '_seo_campaign_hub_meta_title', true);
        $meta_description = get_post_meta($post_id, '_seo_campaign_hub_meta_description', true);
        $meta_keywords = get_post_meta($post_id, '_seo_campaign_hub_meta_keywords', true);
        $canonical_url = get_post_meta($post_id, '_seo_campaign_hub_canonical_url', true);

        // Default to post data if meta not set
        if (empty($meta_title)) {
            $meta_title = get_the_title($post_id);
        }

        if (empty($meta_description)) {
            $excerpt = get_the_excerpt($post_id);
            if (empty($excerpt)) {
                $content = get_the_content(null, false, $post_id);
                $excerpt = wp_trim_words(strip_tags($content), 20, '...');
            } else {
                $excerpt = wp_trim_words($excerpt, 20, '...');
            }
            $meta_description = $excerpt;
        }

        // Output meta tags
        echo '<title>' . esc_html($meta_title) . '</title>' . "\n";

        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
        }

        if (!empty($meta_keywords)) {
            echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '" />' . "\n";
        }

        if (!empty($canonical_url)) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        } else {
            echo '<link rel="canonical" href="' . esc_url(get_permalink($post_id)) . '" />' . "\n";
        }

        // Robots meta
        $noindex = get_post_meta($post_id, '_seo_campaign_hub_noindex', true);
        $nofollow = get_post_meta($post_id, '_seo_campaign_hub_nofollow', true);

        if ($noindex || $nofollow) {
            $robots = [];
            if ($noindex) $robots[] = 'noindex';
            if ($nofollow) $robots[] = 'nofollow';
            echo '<meta name="robots" content="' . esc_attr(implode(', ', $robots)) . '" />' . "\n";
        }
    }

    /**
     * Add schema markup
     *
     * @return void
     */
    public function add_schema_markup() {
        global $wpdb;

        $enabled = get_option('seo_campaign_hub_enable_schema', true);

        if (!$enabled) {
            return;
        }

        if (!is_singular(['sch_campaign', 'sch_offer'])) {
            return;
        }

        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);

        // Check if table exists
        $table_name = $wpdb->prefix . 'sch_schemas';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Generate basic schema without DB query
            $schema = $this->generate_basic_schema($post_id, $post_type);
            echo '<script type="application/ld+json">' . json_encode($schema) . '</script>' . "\n";
            return;
        }

        // Get schema data from database
        $schema_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT schema_data FROM {$wpdb->prefix}sch_schemas
                WHERE post_id = %d AND is_active = 1
                ORDER BY is_default DESC LIMIT 1",
                $post_id
            )
        );

        if ($schema_data && !empty($schema_data->schema_data)) {
            $schema = json_decode($schema_data->schema_data, true);

            // Replace placeholders
            $schema = $this->replace_schema_placeholders($schema, $post_id);

            echo '<script type="application/ld+json">' . json_encode($schema) . '</script>' . "\n";
            return;
        }

        // Fallback: Generate basic schema
        $schema = $this->generate_basic_schema($post_id, $post_type);
        echo '<script type="application/ld+json">' . json_encode($schema) . '</script>' . "\n";
    }

    /**
     * Handle custom redirects
     *
     * @return void
     */
    public function handle_custom_redirects() {
        global $wp_query;

        // Check for short URL redirect
        if (isset($wp_query->query_vars['sch_short_url'])) {
            $slug = $wp_query->query_vars['sch_short_url'];
            try {
                $shortener = $this->container->get('shortener');
                if ($shortener && method_exists($shortener, 'handle_redirect')) {
                    $shortener->handle_redirect($slug);
                    exit;
                }
            } catch (Exception $e) {
                // Log error or fallback
                wp_die(__('Short URL not found.', 'seo-campaign-hub'));
            }
            exit;
        }

        // Check for QR code redirect
        if (isset($wp_query->query_vars['sch_qr_code'])) {
            $qr_code = $wp_query->query_vars['sch_qr_code'];
            try {
                $qr = $this->container->get('qr');
                if ($qr && method_exists($qr, 'handle_redirect')) {
                    $qr->handle_redirect($qr_code);
                    exit;
                }
            } catch (Exception $e) {
                wp_die(__('QR code not found.', 'seo-campaign-hub'));
            }
            exit;
        }

        // Check for redirect rules
        try {
            $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
            $redirect = $this->container->get('redirect');
            if ($redirect && method_exists($redirect, 'get_redirect')) {
                $result = $redirect->get_redirect($current_url);
                if ($result && !empty($result->target_url)) {
                    wp_redirect($result->target_url, intval($result->redirect_type ?? 301));
                    exit;
                }
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }

    /**
     * Parse request
     *
     * @param \WP $wp WordPress object
     * @return void
     */
    public function parse_request($wp) {
        if (!empty($wp->query_vars['sch_short_url'])) {
            $this->handle_custom_redirects();
        }

        if (!empty($wp->query_vars['sch_qr_code'])) {
            $this->handle_custom_redirects();
        }

        if (!empty($wp->query_vars['sch_api'])) {
            try {
                $rest = $this->container->get('rest');
                if ($rest && method_exists($rest, 'handle_api_request')) {
                    // Add nonce verification for security
                    if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'seo_campaign_hub_api')) {
                        wp_die(__('Invalid request.', 'seo-campaign-hub'));
                    }
                    $rest->handle_api_request($wp->query_vars['sch_api']);
                    exit;
                }
            } catch (Exception $e) {
                wp_die(__('API error.', 'seo-campaign-hub'));
            }
            exit;
        }
    }

    /**
     * Get container instance
     *
     * @return Container
     */
    public function get_container() {
        return $this->container;
    }
}

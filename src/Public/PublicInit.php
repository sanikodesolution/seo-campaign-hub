<?php
/**
 * Public Initialization
 *
 * @package SEO_Campaign_Hub\Public
 */

namespace SEO_Campaign_Hub\Public;

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
     * Initialize public features
     *
     * @return void
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_head', [$this, 'add_meta_tags']);
        add_action('wp_head', [$this, 'add_open_graph']);
        add_action('wp_head', [$this, 'add_schema_markup']);
        add_filter('the_content', [$this, 'modify_content']);
        add_filter('excerpt_length', [$this, 'custom_excerpt_length'], 999);
        add_filter('excerpt_more', [$this, 'custom_excerpt_more']);
        add_action('template_redirect', [$this, 'handle_custom_redirects']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('parse_request', [$this, 'parse_request']);
        
        // Register shortcodes if not already registered
        add_action('init', [$this, 'register_shortcodes']);
    }

    /**
     * Enqueue public assets
     *
     * @return void
     */
    public function enqueue_assets() {
        // Only enqueue on relevant pages
        if (!is_singular(['sch_campaign', 'sch_offer']) && 
            !is_post_type_archive(['sch_campaign', 'sch_offer']) &&
            !is_search()) {
            return;
        }

        // Enqueue public CSS
        wp_enqueue_style(
            'seo-campaign-hub-public',
            SEO_CAMPAIGN_HUB_PLUGIN_URL . 'assets/public/css/public.css',
            [],
            SEO_CAMPAIGN_HUB_VERSION
        );

        // Enqueue public JavaScript
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

        // Add inline styles for dark mode support
        wp_add_inline_style('seo-campaign-hub-public', $this->get_inline_styles());
    }

    /**
     * Get inline styles
     *
     * @return string
     */
    private function get_inline_styles() {
        $styles = '
            /* SEO Campaign Hub - Custom Styles */
            .sch-campaign {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .sch-campaign .sch-campaign-title {
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 20px;
            }
            
            .sch-campaign .sch-campaign-meta {
                color: #777;
                font-size: 14px;
                margin-bottom: 20px;
            }
            
            .sch-campaign .sch-campaign-content {
                line-height: 1.8;
                font-size: 16px;
            }
            
            .sch-offer {
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .sch-offer .sch-offer-title {
                font-size: 20px;
                font-weight: 600;
                margin: 0 0 10px 0;
            }
            
            .sch-offer .sch-offer-price {
                font-size: 24px;
                font-weight: 700;
                color: #007cba;
            }
            
            .sch-offer .sch-offer-actions {
                margin-top: 15px;
            }
            
            .sch-offer .sch-offer-actions .sch-btn {
                display: inline-block;
                padding: 10px 25px;
                background: #007cba;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
                transition: all 0.3s ease;
            }
            
            .sch-offer .sch-offer-actions .sch-btn:hover {
                background: #005a87;
                transform: translateY(-2px);
            }
            
            .sch-counter {
                display: flex;
                gap: 20px;
                justify-content: center;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 8px;
                margin: 20px 0;
            }
            
            .sch-counter-item {
                text-align: center;
            }
            
            .sch-counter-item .number {
                font-size: 36px;
                font-weight: 700;
                color: #007cba;
                display: block;
            }
            
            .sch-counter-item .label {
                font-size: 14px;
                color: #777;
            }
            
            .sch-cta {
                display: inline-block;
                padding: 12px 30px;
                background: #007cba;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            
            .sch-cta:hover {
                background: #005a87;
                transform: translateY(-2px);
            }
            
            .sch-cta.success {
                background: #28a745;
            }
            
            .sch-cta.success:hover {
                background: #1e7e34;
            }
            
            .sch-cta.danger {
                background: #dc3545;
            }
            
            .sch-cta.danger:hover {
                background: #bd2130;
            }
            
            .sch-qr-code {
                display: inline-block;
                padding: 15px;
                background: #fff;
                border: 1px solid #e5e5e5;
                border-radius: 8px;
            }
            
            .sch-qr-code img {
                display: block;
                max-width: 100%;
                height: auto;
            }
            
            .sch-short-link {
                display: inline-block;
                padding: 5px 15px;
                background: #f5f5f5;
                border-radius: 4px;
                color: #007cba;
                text-decoration: none;
                transition: all 0.3s ease;
            }
            
            .sch-short-link:hover {
                background: #e5e5e5;
                color: #005a87;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .sch-campaign .sch-campaign-title {
                    font-size: 24px;
                }
                
                .sch-counter {
                    flex-wrap: wrap;
                }
                
                .sch-offer .sch-offer-actions .sch-btn {
                    width: 100%;
                    text-align: center;
                }
            }
            
            @media (max-width: 480px) {
                .sch-campaign {
                    padding: 10px;
                }
                
                .sch-campaign .sch-campaign-title {
                    font-size: 20px;
                }
                
                .sch-counter-item .number {
                    font-size: 28px;
                }
            }
        ';

        return $styles;
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
            $meta_description = wp_trim_words(get_the_excerpt($post_id), 20, '...');
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
     * Add Open Graph tags
     *
     * @return void
     */
    public function add_open_graph() {
        if (!is_singular(['sch_campaign', 'sch_offer'])) {
            return;
        }

        $post_id = get_the_ID();
        $post = get_post($post_id);

        // Get OG data
        $og_title = get_post_meta($post_id, '_seo_campaign_hub_og_title', true);
        $og_description = get_post_meta($post_id, '_seo_campaign_hub_og_description', true);
        $og_image = get_post_meta($post_id, '_seo_campaign_hub_og_image', true);
        $og_type = get_post_meta($post_id, '_seo_campaign_hub_og_type', true);

        // Default values
        if (empty($og_title)) {
            $og_title = get_the_title($post_id);
        }

        if (empty($og_description)) {
            $og_description = wp_trim_words(get_the_excerpt($post_id), 20, '...');
        }

        if (empty($og_image)) {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            if ($thumbnail_id) {
                $og_image = wp_get_attachment_url($thumbnail_id, 'large');
            }
        }

        if (empty($og_type)) {
            $og_type = 'article';
        }

        // Output OG tags
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
        echo '<meta property="og:type" content="' . esc_attr($og_type) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post_id)) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
        
        if (!empty($og_image)) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
            echo '<meta property="og:image:width" content="1200" />' . "\n";
            echo '<meta property="og:image:height" content="630" />' . "\n";
        }

        // Twitter Card
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($og_description) . '" />' . "\n";
        
        if (!empty($og_image)) {
            echo '<meta name="twitter:image" content="' . esc_url($og_image) . '" />' . "\n";
        }
    }

    /**
     * Add schema markup
     *
     * @return void
     */
    public function add_schema_markup() {
        $enabled = get_option('seo_campaign_hub_enable_schema', true);
        
        if (!$enabled) {
            return;
        }

        if (!is_singular(['sch_campaign', 'sch_offer'])) {
            return;
        }

        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);

        // Get schema data from database
        global $wpdb;
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
     * Replace schema placeholders with actual data
     *
     * @param array $schema Schema data
     * @param int   $post_id Post ID
     * @return array
     */
    private function replace_schema_placeholders($schema, $post_id) {
        $post = get_post($post_id);
        $author = get_userdata($post->post_author);
        $thumbnail_id = get_post_thumbnail_id($post_id);

        // Prepare replacements
        $replacements = [
            '{post_title}' => get_the_title($post_id),
            '{post_excerpt}' => wp_trim_words(get_the_excerpt($post_id), 30, '...'),
            '{post_content}' => wp_trim_words(strip_tags($post->post_content), 50, '...'),
            '{post_date}' => get_the_date('c', $post_id),
            '{modified_date}' => get_the_modified_date('c', $post_id),
            '{author_name}' => $author ? $author->display_name : '',
            '{author_url}' => $author ? get_author_posts_url($author->ID) : '',
            '{featured_image}' => $thumbnail_id ? wp_get_attachment_url($thumbnail_id, 'large') : '',
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url('/'),
            '{post_url}' => get_permalink($post_id)
        ];

        // Recursive replace
        $schema = $this->recursive_replace($schema, $replacements);

        return $schema;
    }

    /**
     * Recursive replace in array
     *
     * @param array $data Data to process
     * @param array $replacements Replacements map
     * @return array
     */
    private function recursive_replace($data, $replacements) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursive_replace($value, $replacements);
            } elseif (is_string($value)) {
                $data[$key] = str_replace(array_keys($replacements), array_values($replacements), $value);
            }
        }
        return $data;
    }

    /**
     * Generate basic schema
     *
     * @param int    $post_id Post ID
     * @param string $post_type Post type
     * @return array
     */
    private function generate_basic_schema($post_id, $post_type) {
        $post = get_post($post_id);
        $author = get_userdata($post->post_author);
        $thumbnail_id = get_post_thumbnail_id($post_id);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $post_type === 'sch_campaign' ? 'Article' : 'Product',
            'headline' => get_the_title($post_id),
            'description' => wp_trim_words(get_the_excerpt($post_id), 30, '...'),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'author' => [
                '@type' => 'Person',
                'name' => $author ? $author->display_name : '',
                'url' => $author ? get_author_posts_url($author->ID) : ''
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id)
            ]
        ];

        // Add image if available
        if ($thumbnail_id) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => wp_get_attachment_url($thumbnail_id, 'large')
            ];
        }

        return $schema;
    }

    /**
     * Modify content
     *
     * @param string $content The post content
     * @return string
     */
    public function modify_content($content) {
        if (!is_singular(['sch_campaign', 'sch_offer'])) {
            return $content;
        }

        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);

        // Add reading time
        $show_reading_time = get_option('seo_campaign_hub_show_reading_time', true);
        if ($show_reading_time) {
            $reading_time = $this->calculate_reading_time($content);
            $reading_time_html = '<div class="sch-reading-time"><span class="dashicons dashicons-clock"></span> ' . 
                                sprintf(__('%s min read', 'seo-campaign-hub'), $reading_time) . 
                                '</div>';
            $content = $reading_time_html . $content;
        }

        // Add table of contents for campaigns
        if ($post_type === 'sch_campaign') {
            $show_toc = get_post_meta($post_id, '_seo_campaign_hub_show_toc', true);
            if ($show_toc !== 'no') {
                $toc = $this->generate_table_of_contents($content);
                if ($toc) {
                    $content = $toc . $content;
                }
            }
        }

        return $content;
    }

    /**
     * Calculate reading time
     *
     * @param string $content Content to analyze
     * @return int
     */
    private function calculate_reading_time($content) {
        $words = str_word_count(strip_tags($content));
        $minutes = ceil($words / 200);
        return max(1, $minutes);
    }

    /**
     * Generate table of contents
     *
     * @param string $content Content to parse
     * @return string
     */
    private function generate_table_of_contents($content) {
        // Find all H2 and H3 headings
        preg_match_all('/<h([2-3])[^>]*>(.*?)<\/h\1>/i', $content, $matches);

        if (empty($matches[0])) {
            return '';
        }

        $toc = '<div class="sch-table-of-contents">';
        $toc .= '<h3>' . __('Table of Contents', 'seo-campaign-hub') . '</h3>';
        $toc .= '<ul>';

        $index = 0;
        foreach ($matches[0] as $i => $heading) {
            $level = (int) $matches[1][$i];
            $text = strip_tags($matches[2][$i]);
            $id = 'sch-toc-' . $index++;
            
            // Add ID to heading
            $content = str_replace($heading, '<h' . $level . ' id="' . $id . '">' . $text . '</h' . $level . '>', $content);
            
            $toc .= '<li class="sch-toc-level-' . $level . '">';
            $toc .= '<a href="#' . $id . '">' . esc_html($text) . '</a>';
            $toc .= '</li>';
        }

        $toc .= '</ul>';
        $toc .= '</div>';

        return $toc;
    }

    /**
     * Custom excerpt length
     *
     * @param int $length Default excerpt length
     * @return int
     */
    public function custom_excerpt_length($length) {
        if (is_post_type_archive(['sch_campaign', 'sch_offer'])) {
            return 30;
        }
        return $length;
    }

    /**
     * Custom excerpt more
     *
     * @param string $more Default excerpt more text
     * @return string
     */
    public function custom_excerpt_more($more) {
        if (is_post_type_archive(['sch_campaign', 'sch_offer'])) {
            return '... <a href="' . get_permalink() . '" class="sch-read-more">' . __('Read More', 'seo-campaign-hub') . '</a>';
        }
        return $more;
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
            $this->container->get('shortener')->handle_redirect($slug);
            exit;
        }

        // Check for QR code redirect
        if (isset($wp_query->query_vars['sch_qr_code'])) {
            $qr_code = $wp_query->query_vars['sch_qr_code'];
            $this->container->get('qr')->handle_redirect($qr_code);
            exit;
        }

        // Check for redirect rules
        $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
        $redirect = $this->container->get('redirect')->get_redirect($current_url);
        if ($redirect) {
            wp_redirect($redirect->target_url, intval($redirect->redirect_type));
            exit;
        }
    }

    /**
     * Add custom query vars
     *
     * @param array $vars Query variables
     * @return array
     */
    public function add_query_vars($vars) {
        $vars[] = 'sch_short_url';
        $vars[] = 'sch_qr_code';
        $vars[] = 'sch_api';
        return $vars;
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
            $this->container->get('rest')->handle_api_request($wp->query_vars['sch_api']);
            exit;
        }
    }

    /**
     * Register shortcodes
     *
     * @return void
     */
    public function register_shortcodes() {
        // Campaign shortcodes
        add_shortcode('sch_campaign', [$this, 'render_campaign_shortcode']);
        add_shortcode('sch_offer', [$this, 'render_offer_shortcode']);
        add_shortcode('sch_counter', [$this, 'render_counter_shortcode']);
        add_shortcode('sch_cta', [$this, 'render_cta_shortcode']);
        add_shortcode('sch_qr', [$this, 'render_qr_shortcode']);
        add_shortcode('sch_link', [$this, 'render_link_shortcode']);
        add_shortcode('sch_analytics', [$this, 'render_analytics_shortcode']);
        add_shortcode('sch_campaigns', [$this, 'render_campaigns_shortcode']);
        add_shortcode('sch_offers', [$this, 'render_offers_shortcode']);
    }

    /**
     * Render campaign shortcode
     *
     * @param array  $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public function render_campaign_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
            'title' => '',
            'show' => 'full',
            'layout' => 'default',
            'class' => ''
        ], $atts);

        if (!empty($atts['slug'])) {
            $campaign = get_page_by_path($atts['slug'], OBJECT, 'sch_campaign');
            if ($campaign) {
                $atts['id'] = $campaign->ID;
            }
        }

        if (empty($atts['id'])) {
            return '<p>' . __('Campaign not found.', 'seo-campaign-hub') . '</p>';
        }

        $campaign = get_post($atts['id']);
        if (!$campaign || $campaign->post_type !== 'sch_campaign') {
            return '<p>' . __('Campaign not found.', 'seo-campaign-hub') . '</p>';
        }

        ob_start();
        ?>
        <div class="sch-campaign-shortcode sch-campaign-<?php echo esc_attr($atts['layout']); ?> <?php echo esc_attr($atts['class']); ?>">
            <?php if ($atts['show'] === 'full' || $atts['show'] === 'title'): ?>
                <h2 class="sch-campaign-title">
                    <a href="<?php echo esc_url(get_permalink($campaign->ID)); ?>">
                        <?php echo esc_html($campaign->post_title); ?>
                    </a>
                </h2>
            <?php endif; ?>

            <?php if ($atts['show'] === 'full' || $atts['show'] === 'excerpt'): ?>
                <div class="sch-campaign-excerpt">
                    <?php echo wp_kses_post(wp_trim_words($campaign->post_excerpt ?: $campaign->post_content, 30, '...')); ?>
                    <a href="<?php echo esc_url(get_permalink($campaign->ID)); ?>" class="sch-read-more">
                        <?php esc_html_e('Read More', 'seo-campaign-hub'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($atts['show'] === 'full'): ?>
                <div class="sch-campaign-content">
                    <?php echo apply_filters('the_content', $campaign->post_content); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render offer shortcode
     *
     * @param array  $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public function render_offer_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => 0,
            'slug' => '',
            'style' => 'default',
            'layout' => 'box',
            'buttons' => 'yes',
            'class' => ''
        ], $atts);

        if (!empty($atts['slug'])) {
            $offer = get_page_by_path($atts['slug'], OBJECT, 'sch_offer');
            if ($offer) {
                $atts['id'] = $offer->ID;
            }
        }

        if (empty($atts['id'])) {
            return '<p>' . __('Offer not found.', 'seo-campaign-hub') . '</p>';
        }

        $offer = get_post($atts['id']);
        if (!$offer || $offer->post_type !== 'sch_offer') {
            return '<p>' . __('Offer not found.', 'seo-campaign-hub') . '</p>';
        }

        // Get offer meta
        $offer_type = get_post_meta($offer->ID, '_seo_campaign_hub_offer_type', true);
        $price = get_post_meta($offer->ID, '_seo_campaign_hub_price', true);
        $sale_price = get_post_meta($offer->ID, '_seo_campaign_hub_sale_price', true);
        $destination_url = get_post_meta($offer->ID, '_seo_campaign_hub_destination_url', true);
        $affiliate_url = get_post_meta($offer->ID, '_seo_campaign_hub_affiliate_url', true);
        $coupon_code = get_post_meta($offer->ID, '_seo_campaign_hub_coupon_code', true);
        $promo_code = get_post_meta($offer->ID, '_seo_campaign_hub_promo_code', true);

        $button_url = $destination_url ?: $affiliate_url ?: '#';
        $button_text = __('Get Offer', 'seo-campaign-hub');

        ob_start();
        ?>
        <div class="sch-offer-shortcode sch-offer-style-<?php echo esc_attr($atts['style']); ?> <?php echo esc_attr($atts['class']); ?>">
            <div class="sch-offer-inner">
                <?php if (has_post_thumbnail($offer->ID)): ?>
                    <div class="sch-offer-image">
                        <?php echo get_the_post_thumbnail($offer->ID, 'medium'); ?>
                    </div>
                <?php endif; ?>

                <div class="sch-offer-details">
                    <h3 class="sch-offer-title"><?php echo esc_html($offer->post_title); ?></h3>
                    
                    <?php if (!empty($price)): ?>
                        <div class="sch-offer-price">
                            <?php if (!empty($sale_price)): ?>
                                <span class="sale-price"><?php echo esc_html($sale_price); ?></span>
                                <span class="regular-price"><?php echo esc_html($price); ?></span>
                            <?php else: ?>
                                <span class="price"><?php echo esc_html($price); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($offer->post_excerpt)): ?>
                        <div class="sch-offer-description">
                            <?php echo wp_kses_post($offer->post_excerpt); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($coupon_code) || !empty($promo_code)): ?>
                        <div class="sch-offer-codes">
                            <?php if (!empty($coupon_code)): ?>
                                <div class="sch-coupon-code">
                                    <strong><?php esc_html_e('Coupon:', 'seo-campaign-hub'); ?></strong>
                                    <code><?php echo esc_html($coupon_code); ?></code>
                                    <button class="sch-copy-code" data-code="<?php echo esc_attr($coupon_code); ?>">
                                        <?php esc_html_e('Copy', 'seo-campaign-hub'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($promo_code)): ?>
                                <div class="sch-promo-code">
                                    <strong><?php esc_html_e('Promo:', 'seo-campaign-hub'); ?></strong>
                                    <code><?php echo esc_html($promo_code); ?></code>
                                    <button class="sch-copy-code" data-code="<?php echo esc_attr($promo_code); ?>">
                                        <?php esc_html_e('Copy', 'seo-campaign-hub'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($atts['buttons'] === 'yes'): ?>
                        <div class="sch-offer-actions">
                            <a href="<?php echo esc_url($button_url); ?>" 
                               class="sch-btn primary" 
                               target="_blank"
                               rel="nofollow sponsored">
                                <?php echo esc_html($button_text); ?>
                            </a>
                            
                            <?php if (!empty($offer->ID)): ?>
                                <a href="<?php echo esc_url(get_permalink($offer->ID)); ?>" 
                                   class="sch-btn outline">
                                    <?php esc_html_e('View Details', 'seo-campaign-hub'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render counter shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_counter_shortcode($atts) {
        $atts = shortcode_atts([
            'target' => '',
            'end' => '',
            'start' => '0',
            'prefix' => '',
            'suffix' => '',
            'format' => 'number',
            'class' => ''
        ], $atts);

        if (empty($atts['target']) && empty($atts['end'])) {
            return '<p>' . __('Please specify a target date or value.', 'seo-campaign-hub') . '</p>';
        }

        ob_start();
        ?>
        <div class="sch-counter-shortcode <?php echo esc_attr($atts['class']); ?>">
            <?php if (!empty($atts['target'])): ?>
                <!-- Countdown timer -->
                <div class="sch-counter-timer" 
                     data-target="<?php echo esc_attr($atts['target']); ?>"
                     data-format="<?php echo esc_attr($atts['format']); ?>"
                     data-expired-text="<?php esc_attr_e('Expired', 'seo-campaign-hub'); ?>">
                </div>
            <?php else: ?>
                <!-- Number counter -->
                <div class="sch-counter-number" 
                     data-start="<?php echo esc_attr($atts['start']); ?>"
                     data-end="<?php echo esc_attr($atts['end']); ?>"
                     data-prefix="<?php echo esc_attr($atts['prefix']); ?>"
                     data-suffix="<?php echo esc_attr($atts['suffix']); ?>">
                    <span class="prefix"><?php echo esc_html($atts['prefix']); ?></span>
                    <span class="number">0</span>
                    <span class="suffix"><?php echo esc_html($atts['suffix']); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render CTA shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_cta_shortcode($atts) {
        $atts = shortcode_atts([
            'text' => __('Learn More', 'seo-campaign-hub'),
            'url' => '#',
            'target' => '_self',
            'class' => 'primary',
            'style' => 'default',
            'icon' => '',
            'track' => 'yes',
            'rel' => ''
        ], $atts);

        $rel_attrs = [];
        if (!empty($atts['rel'])) {
            $rel_attrs[] = $atts['rel'];
        }
        if ($atts['track'] === 'yes') {
            $rel_attrs[] = 'noopener';
        }
        $rel = !empty($rel_attrs) ? ' rel="' . esc_attr(implode(' ', $rel_attrs)) . '"' : '';

        ob_start();
        ?>
        <a href="<?php echo esc_url($atts['url']); ?>" 
           target="<?php echo esc_attr($atts['target']); ?>" 
           class="sch-cta sch-cta-<?php echo esc_attr($atts['class']); ?> sch-cta-<?php echo esc_attr($atts['style']); ?>"
           data-track="<?php echo esc_attr($atts['track']); ?>"
           <?php echo $rel; ?>>
            <?php if (!empty($atts['icon'])): ?>
                <i class="<?php echo esc_attr($atts['icon']); ?>"></i>
            <?php endif; ?>
            <?php echo esc_html($atts['text']); ?>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Render QR code shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_qr_shortcode($atts) {
        $atts = shortcode_atts([
            'url' => '',
            'size' => 200,
            'color' => '#000000',
            'bg' => '#FFFFFF',
            'label' => '',
            'class' => ''
        ], $atts);

        if (empty($atts['url'])) {
            $atts['url'] = home_url('/');
        }

        $qr_url = $this->container->get('qr')->generate_qr($atts['url'], $atts['size']);

        ob_start();
        ?>
        <div class="sch-qr-code-shortcode <?php echo esc_attr($atts['class']); ?>">
            <div class="sch-qr-code">
                <img src="<?php echo esc_url($qr_url); ?>" 
                     alt="<?php echo esc_attr($atts['label'] ?: __('QR Code', 'seo-campaign-hub')); ?>" 
                     width="<?php echo esc_attr($atts['size']); ?>" 
                     height="<?php echo esc_attr($atts['size']); ?>" />
                <?php if (!empty($atts['label'])): ?>
                    <p class="sch-qr-label"><?php echo esc_html($atts['label']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render link shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_link_shortcode($atts) {
        $atts = shortcode_atts([
            'url' => '',
            'text' => '',
            'type' => 'direct',
            'track' => 'yes',
            'class' => '',
            'target' => '_self'
        ], $atts);

        if (empty($atts['url'])) {
            return '<p>' . __('Please specify a URL.', 'seo-campaign-hub') . '</p>';
        }

        $link_text = !empty($atts['text']) ? $atts['text'] : $atts['url'];
        $track_attr = $atts['track'] === 'yes' ? ' data-track="yes"' : '';

        ob_start();
        ?>
        <a href="<?php echo esc_url($atts['url']); ?>" 
           target="<?php echo esc_attr($atts['target']); ?>" 
           class="sch-short-link sch-link-<?php echo esc_attr($atts['type']); ?> <?php echo esc_attr($atts['class']); ?>"
           <?php echo $track_attr; ?>>
            <?php echo esc_html($link_text); ?>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Render analytics shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_analytics_shortcode($atts) {
        $atts = shortcode_atts([
            'type' => 'simple',
            'period' => '30',
            'id' => 0,
            'show' => 'stats',
            'class' => ''
        ], $atts);

        ob_start();
        ?>
        <div class="sch-analytics-shortcode sch-analytics-<?php echo esc_attr($atts['type']); ?> <?php echo esc_attr($atts['class']); ?>">
            <div class="sch-analytics-container" 
                 data-type="<?php echo esc_attr($atts['type']); ?>"
                 data-period="<?php echo esc_attr($atts['period']); ?>"
                 data-id="<?php echo esc_attr($atts['id']); ?>"
                 data-show="<?php echo esc_attr($atts['show']); ?>">
                <div class="sch-analytics-loading"><?php esc_html_e('Loading analytics...', 'seo-campaign-hub'); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render campaigns list shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_campaigns_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 5,
            'category' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'show' => 'excerpt',
            'class' => ''
        ], $atts);

        $args = [
            'post_type' => 'sch_campaign',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish'
        ];

        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'sch_campaign_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                ]
            ];
        }

        $campaigns = get_posts($args);

        if (empty($campaigns)) {
            return '<p>' . __('No campaigns found.', 'seo-campaign-hub') . '</p>';
        }

        ob_start();
        ?>
        <div class="sch-campaigns-list <?php echo esc_attr($atts['class']); ?>">
            <?php foreach ($campaigns as $campaign): ?>
                <div class="sch-campaign-item">
                    <h3 class="sch-campaign-item-title">
                        <a href="<?php echo esc_url(get_permalink($campaign->ID)); ?>">
                            <?php echo esc_html($campaign->post_title); ?>
                        </a>
                    </h3>
                    
                    <?php if ($atts['show'] === 'excerpt' || $atts['show'] === 'full'): ?>
                        <div class="sch-campaign-item-excerpt">
                            <?php echo wp_kses_post(wp_trim_words($campaign->post_excerpt ?: $campaign->post_content, 20, '...')); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($atts['show'] === 'full'): ?>
                        <div class="sch-campaign-item-content">
                            <?php echo apply_filters('the_content', $campaign->post_content); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="sch-campaign-item-meta">
                        <span class="sch-campaign-item-date">
                            <?php echo get_the_date('', $campaign->ID); ?>
                        </span>
                        <a href="<?php echo esc_url(get_permalink($campaign->ID)); ?>" class="sch-read-more">
                            <?php esc_html_e('Read More', 'seo-campaign-hub'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render offers list shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_offers_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 5,
            'type' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'layout' => 'grid',
            'class' => ''
        ], $atts);

        $args = [
            'post_type' => 'sch_offer',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish'
        ];

        if (!empty($atts['type'])) {
            $args['meta_query'] = [
                [
                    'key' => '_seo_campaign_hub_offer_type',
                    'value' => $atts['type']
                ]
            ];
        }

        $offers = get_posts($args);

        if (empty($offers)) {
            return '<p>' . __('No offers found.', 'seo-campaign-hub') . '</p>';
        }

        ob_start();
        ?>
        <div class="sch-offers-list sch-offers-layout-<?php echo esc_attr($atts['layout']); ?> <?php echo esc_attr($atts['class']); ?>">
            <?php foreach ($offers as $offer): ?>
                <?php echo $this->render_offer_shortcode(['id' => $offer->ID, 'buttons' => 'yes']); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
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

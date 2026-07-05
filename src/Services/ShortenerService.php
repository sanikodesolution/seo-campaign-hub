<?php
/**
 * URL Shortener Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ShortenerService
 *
 * Handles URL shortening operations
 */
class ShortenerService {
    /**
     * Database instance
     *
     * @var \SEO_Campaign_Hub\Database\Database
     */
    private $db;

    /**
     * Cache instance
     *
     * @var \SEO_Campaign_Hub\Core\CacheManager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new \SEO_Campaign_Hub\Database\Database();
        $this->cache = new \SEO_Campaign_Hub\Core\CacheManager();
    }

    /**
     * Shorten a URL
     *
     * @param string $url URL to shorten
     * @param string $slug Custom slug (optional)
     * @param array  $data Additional data
     * @return string|false
     */
    public function shorten_url($url, $slug = '', $data = []) {
        // Check if URL shortening is enabled
        if (!get_option('seo_campaign_hub_enable_shortener', true)) {
            return $url;
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Generate slug if not provided
        if (empty($slug)) {
            $slug = $this->generate_slug();
        } else {
            // Check if slug is available
            if (!$this->is_slug_available($slug)) {
                return false;
            }
            $slug = sanitize_title($slug);
        }

        // Get prefix
        $prefix = get_option('seo_campaign_hub_shortener_prefix', 'go');

        // Prepare data
        $link_data = [
            'link_key' => 'link_' . uniqid(),
            'destination_url' => $url,
            'short_url' => home_url('/' . $prefix . '/' . $slug),
            'slug' => $slug,
            'title' => isset($data['title']) ? sanitize_text_field($data['title']) : '',
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'link_type' => isset($data['link_type']) ? sanitize_text_field($data['link_type']) : 'direct',
            'redirect_type' => isset($data['redirect_type']) ? sanitize_text_field($data['redirect_type']) : get_option('seo_campaign_hub_default_redirect_type', '301'),
            'is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1,
            'is_public' => isset($data['is_public']) ? intval($data['is_public']) : 1,
            'utm_source' => isset($data['utm_source']) ? sanitize_text_field($data['utm_source']) : '',
            'utm_medium' => isset($data['utm_medium']) ? sanitize_text_field($data['utm_medium']) : '',
            'utm_campaign' => isset($data['utm_campaign']) ? sanitize_text_field($data['utm_campaign']) : '',
            'utm_term' => isset($data['utm_term']) ? sanitize_text_field($data['utm_term']) : '',
            'utm_content' => isset($data['utm_content']) ? sanitize_text_field($data['utm_content']) : '',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];

        // Set expiration if provided
        if (!empty($data['expires_at'])) {
            $link_data['expires_at'] = $data['expires_at'];
        }

        // Insert into database
        $result = $this->db->insert('links', $link_data);

        if ($result) {
            $this->cache->clear('links');
            do_action('seo_campaign_hub_link_created', $result, $link_data);
            return $link_data['short_url'];
        }

        return false;
    }

    /**
     * Generate a unique slug
     *
     * @param int $length Length of slug
     * @return string
     */
    public function generate_slug($length = 6) {
        $slug_length = get_option('seo_campaign_hub_shortener_slug_length', $length);
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $slug = '';

        do {
            $slug = '';
            for ($i = 0; $i < $slug_length; $i++) {
                $slug .= $chars[rand(0, strlen($chars) - 1)];
            }
        } while (!$this->is_slug_available($slug));

        return $slug;
    }

    /**
     * Check if slug is available
     *
     * @param string $slug Slug to check
     * @return bool
     */
    public function is_slug_available($slug) {
        $slug = sanitize_title($slug);
        
        $link = $this->db->get_row(
            $this->db->prepare(
                "SELECT id FROM {$this->db->get_table('links')} WHERE slug = %s",
                $slug
            )
        );

        return !$link;
    }

    /**
     * Get link by slug
     *
     * @param string $slug Link slug
     * @return object|null
     */
    public function get_link_by_slug($slug) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->get_table('links')} WHERE slug = %s",
                $slug
            )
        );
    }

    /**
     * Get link by ID
     *
     * @param int $id Link ID
     * @return object|null
     */
    public function get_link($id) {
        $cache_key = 'link_' . $id;
        $link = $this->cache->get($cache_key);

        if ($link === false) {
            $link = $this->db->get_row(
                $this->db->prepare(
                    "SELECT * FROM {$this->db->get_table('links')} WHERE id = %d",
                    $id
                )
            );
            $this->cache->set($cache_key, $link, 'links', 3600);
        }

        return $link;
    }

    /**
     * Get all links
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_links($args = []) {
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'is_active' => 1
        ];

        $args = wp_parse_args($args, $defaults);
        $where = [];

        if (isset($args['is_active'])) {
            $where[] = $this->db->prepare(
                "is_active = %d",
                intval($args['is_active'])
            );
        }

        if (!empty($args['link_type'])) {
            $where[] = $this->db->prepare(
                "link_type = %s",
                $args['link_type']
            );
        }

        if (!empty($args['search'])) {
            $search = '%' . $this->db->escape($args['search']) . '%';
            $where[] = $this->db->prepare(
                "(slug LIKE %s OR destination_url LIKE %s OR title LIKE %s)",
                $search,
                $search,
                $search
            );
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "
            SELECT * FROM {$this->db->get_table('links')}
            {$where_clause}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d
        ";

        $query = $this->db->prepare($query, $args['limit'], $args['offset']);
        return $this->db->get_results($query);
    }

    /**
     * Update link
     *
     * @param int   $id Link ID
     * @param array $data Link data
     * @return int|false
     */
    public function update_link($id, $data) {
        $link = $this->get_link($id);

        if (!$link) {
            return false;
        }

        // Set modified by
        $data['modified_by'] = get_current_user_id();

        // Update
        $result = $this->db->update('links', $data, ['id' => $id]);

        if ($result !== false) {
            $this->cache->delete('link_' . $id);
            $this->cache->clear('links');
            do_action('seo_campaign_hub_link_updated', $id, $data);
        }

        return $result;
    }

    /**
     * Delete link
     *
     * @param int $id Link ID
     * @return bool
     */
    public function delete_link($id) {
        $link = $this->get_link($id);

        if (!$link) {
            return false;
        }

        $result = $this->db->delete('links', ['id' => $id]);

        if ($result) {
            $this->cache->delete('link_' . $id);
            $this->cache->clear('links');
            do_action('seo_campaign_hub_link_deleted', $id);
        }

        return $result;
    }

    /**
     * Handle redirect for short URL
     *
     * @param string $slug Link slug
     * @return void
     */
    public function handle_redirect($slug) {
        $link = $this->get_link_by_slug($slug);

        if (!$link) {
            wp_die(__('Link not found.', 'seo-campaign-hub'), 404);
        }

        // Check if expired
        if (!empty($link->expires_at) && strtotime($link->expires_at) < time()) {
            wp_die(__('This link has expired.', 'seo-campaign-hub'), 410);
        }

        // Check if active
        if (!$link->is_active) {
            wp_die(__('This link is not active.', 'seo-campaign-hub'), 403);
        }

        // Check if requires authentication
        if ($link->require_auth && !is_user_logged_in()) {
            wp_die(__('You must be logged in to access this link.', 'seo-campaign-hub'), 401);
        }

        // Check password if set
        if (!empty($link->password_hash)) {
            if (!isset($_POST['sch_password'])) {
                $this->show_password_form($slug);
                exit;
            }

            if (!wp_check_password($_POST['sch_password'], $link->password_hash)) {
                $this->show_password_form($slug, __('Incorrect password.', 'seo-campaign-hub'));
                exit;
            }
        }

        // Increment click count
        $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->db->get_table('links')} 
                SET total_clicks = total_clicks + 1,
                    unique_clicks = unique_clicks + 1,
                    last_clicked = NOW()
                WHERE id = %d",
                $link->id
            )
        );

        // Track click event
        $analytics = new AnalyticsService();
        $analytics->track_event('click', [
            'link_id' => $link->id,
            'event_name' => 'short_url_redirect'
        ]);

        // Build destination URL with UTM parameters
        $destination_url = $link->destination_url;
        $utm_params = [];

        if (!empty($link->utm_source)) {
            $utm_params['utm_source'] = $link->utm_source;
        }
        if (!empty($link->utm_medium)) {
            $utm_params['utm_medium'] = $link->utm_medium;
        }
        if (!empty($link->utm_campaign)) {
            $utm_params['utm_campaign'] = $link->utm_campaign;
        }
        if (!empty($link->utm_term)) {
            $utm_params['utm_term'] = $link->utm_term;
        }
        if (!empty($link->utm_content)) {
            $utm_params['utm_content'] = $link->utm_content;
        }

        if (!empty($utm_params)) {
            $parsed_url = parse_url($destination_url);
            $query = $parsed_url['query'] ?? '';
            parse_str($query, $existing_params);
            $all_params = array_merge($existing_params, $utm_params);
            $destination_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
            if (!empty($all_params)) {
                $destination_url .= '?' . http_build_query($all_params);
            }
            if (isset($parsed_url['fragment'])) {
                $destination_url .= '#' . $parsed_url['fragment'];
            }
        }

        // Redirect
        $redirect_type = intval($link->redirect_type) ?: 301;
        wp_redirect($destination_url, $redirect_type);
        exit;
    }

    /**
     * Show password form
     *
     * @param string $slug Link slug
     * @param string $error Error message
     * @return void
     */
    private function show_password_form($slug, $error = '') {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php esc_html_e('Protected Link', 'seo-campaign-hub'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
                .sch-password-form { background: #f9f9f9; padding: 30px; border-radius: 8px; border: 1px solid #ddd; }
                .sch-password-form h2 { margin-top: 0; }
                .sch-password-form .error { color: #d63638; margin-bottom: 15px; }
                .sch-password-form input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
                .sch-password-form input[type="submit"] { background: #007cba; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
                .sch-password-form input[type="submit"]:hover { background: #005a87; }
            </style>
        </head>
        <body>
            <div class="sch-password-form">
                <h2><?php esc_html_e('This link is password protected', 'seo-campaign-hub'); ?></h2>
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo esc_html($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="password" name="sch_password" placeholder="<?php esc_attr_e('Enter password', 'seo-campaign-hub'); ?>" required>
                    <input type="submit" value="<?php esc_attr_e('Submit', 'seo-campaign-hub'); ?>">
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode($atts) {
        $url = isset($atts['url']) ? $atts['url'] : '';
        $text = isset($atts['text']) ? $atts['text'] : $url;

        if (empty($url)) {
            return '<p>' . __('Please specify a URL.', 'seo-campaign-hub') . '</p>';
        }

        ob_start();
        ?>
        <a href="<?php echo esc_url($url); ?>" class="sch-short-link" target="_blank">
            <?php echo esc_html($text); ?>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Get link statistics
     *
     * @param int $id Link ID
    
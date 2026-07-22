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
            'redirect_priority' => $this->sanitize_redirect_priority($data['redirect_priority'] ?? ''),
            'targeting_rules' => wp_json_encode($this->sanitize_targeting_rules($data['targeting_rules'] ?? [])),
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

        $analytics = new AnalyticsService();
        $language  = $analytics->detect_visitor_language();
        $country   = $analytics->get_visitor_country_code();
        $resolved  = $this->resolve_destination( $link, $language, $country );

        // Track click event
        $analytics->track_event('click', [
            'link_id' => $link->id,
            'event_name' => 'short_url_redirect',
            'language' => $language !== '' ? $language : null,
            'country' => $country !== '' ? $country : null,
            'meta_data' => [
                'matched_rule_type' => $resolved['matched'],
            ],
        ]);

        // Build destination URL with UTM parameters
        $destination_url = $resolved['url'];
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
            $destination_url = ($parsed_url['scheme'] ?? 'https') . '://' . ($parsed_url['host'] ?? '');
            if (!empty($parsed_url['port'])) {
                $destination_url .= ':' . $parsed_url['port'];
            }
            $destination_url .= $parsed_url['path'] ?? '';
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
     * Whether smart language/country redirects are enabled.
     *
     * @return bool
     */
    public function is_smart_redirects_enabled() {
        $nested = get_option('seo_campaign_hub_options', []);
        if (is_array($nested) && array_key_exists('enable_smart_redirects', $nested)) {
            return (string) $nested['enable_smart_redirects'] === '1';
        }
        return (bool) get_option('seo_campaign_hub_enable_smart_redirects', true);
    }

    /**
     * Default redirect priority from settings.
     *
     * @return string language|country
     */
    public function get_default_redirect_priority() {
        $nested = get_option('seo_campaign_hub_options', []);
        if (is_array($nested) && !empty($nested['default_redirect_priority'])) {
            return $this->sanitize_redirect_priority($nested['default_redirect_priority']);
        }
        return $this->sanitize_redirect_priority(
            (string) get_option('seo_campaign_hub_default_redirect_priority', 'language')
        );
    }

    /**
     * Enabled language codes from Localization settings.
     *
     * @return string[]
     */
    public function get_enabled_languages() {
        $defaults = ['en', 'es', 'pt', 'fr', 'de', 'it', 'nl', 'pl', 'ru', 'ar', 'he', 'tr', 'fa'];
        $nested = get_option('seo_campaign_hub_options', []);
        $raw = null;

        if (is_array($nested) && isset($nested['enabled_languages'])) {
            $raw = $nested['enabled_languages'];
        } else {
            $raw = get_option('seo_campaign_hub_enabled_languages', $defaults);
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $raw);
        }

        if (!is_array($raw)) {
            return $defaults;
        }

        $codes = [];
        foreach ($raw as $code) {
            $code = strtolower(substr(sanitize_text_field((string) $code), 0, 2));
            if (preg_match('/^[a-z]{2}$/', $code)) {
                $codes[] = $code;
            }
        }

        $codes = array_values(array_unique($codes));
        return !empty($codes) ? $codes : $defaults;
    }

    /**
     * Sanitize redirect priority.
     *
     * @param string $priority Priority value.
     * @return string
     */
    public function sanitize_redirect_priority($priority) {
        $priority = sanitize_key((string) $priority);
        return in_array($priority, ['language', 'country'], true) ? $priority : 'language';
    }

    /**
     * Sanitize targeting rules list.
     *
     * @param mixed $rules Raw rules.
     * @return array<int, array{type:string,match:string,url:string}>
     */
    public function sanitize_targeting_rules($rules) {
        if (is_string($rules)) {
            $decoded = json_decode($rules, true);
            $rules = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($rules)) {
            return [];
        }

        $clean = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $type = isset($rule['type']) ? sanitize_key((string) $rule['type']) : '';
            if (!in_array($type, ['language', 'country'], true)) {
                continue;
            }

            $match = isset($rule['match']) ? sanitize_text_field((string) $rule['match']) : '';
            if ($type === 'language') {
                $match = strtolower(substr($match, 0, 2));
                if (!preg_match('/^[a-z]{2}$/', $match)) {
                    continue;
                }
            } else {
                $match = strtoupper(substr($match, 0, 2));
                if (!preg_match('/^[A-Z]{2}$/', $match)) {
                    continue;
                }
            }

            $url = isset($rule['url']) ? esc_url_raw((string) $rule['url']) : '';
            if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $clean[] = [
                'type' => $type,
                'match' => $match,
                'url' => $url,
            ];
        }

        return $clean;
    }

    /**
     * Resolve the redirect destination for a short link.
     *
     * @param object $link     Link row.
     * @param string $language Visitor language (2-letter) or empty.
     * @param string $country  Visitor country (2-letter) or empty.
     * @return array{url:string,matched:string}
     */
    public function resolve_destination($link, $language = '', $country = '') {
        $fallback = isset($link->destination_url) ? (string) $link->destination_url : '';

        if (!$this->is_smart_redirects_enabled()) {
            return ['url' => $fallback, 'matched' => 'default'];
        }

        $rules = $this->sanitize_targeting_rules(
            isset($link->targeting_rules) ? $link->targeting_rules : []
        );

        if (empty($rules)) {
            return ['url' => $fallback, 'matched' => 'default'];
        }

        $priority = $this->sanitize_redirect_priority(
            isset($link->redirect_priority) ? $link->redirect_priority : $this->get_default_redirect_priority()
        );
        $secondary = $priority === 'language' ? 'country' : 'language';

        foreach ([$priority, $secondary] as $type) {
            $visitor_value = $type === 'language' ? strtolower($language) : strtoupper($country);
            if ($visitor_value === '') {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule['type'] !== $type) {
                    continue;
                }
                if ($rule['match'] === $visitor_value) {
                    return [
                        'url' => $rule['url'],
                        'matched' => $type,
                    ];
                }
            }
        }

        return ['url' => $fallback, 'matched' => 'default'];
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
     * Get link statistics.
     *
     * @param int $id Link ID.
     * @return array<string, mixed>
     */
    public function get_link_stats( $id ) {
        return [];
    }
}
    
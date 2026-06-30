<?php
/**
 * REST API Manager
 *
 * @package SEO_Campaign_Hub\REST
 */

namespace SEO_Campaign_Hub\REST;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RESTManager
 *
 * Handles REST API endpoints for the plugin
 */
class RESTManager {
    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'seo-campaign-hub/v1';

    /**
     * Container instance
     *
     * @var \SEO_Campaign_Hub\Core\Container
     */
    private $container;

    /**
     * Constructor
     */
    public function __construct() {
        $this->container = null;
    }

    /**
     * Initialize REST API
     *
     * @return void
     */
    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_filter('rest_pre_serve_request', [$this, 'add_cors_headers'], 10, 2);
        add_filter('rest_authentication_errors', [$this, 'check_authentication']);
    }

    /**
     * Set container
     *
     * @param \SEO_Campaign_Hub\Core\Container $container
     * @return void
     */
    public function set_container($container) {
        $this->container = $container;
    }

    /**
     * Register REST routes
     *
     * @return void
     */
    public function register_routes() {
        // Health check
        register_rest_route($this->namespace, '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check'],
            'permission_callback' => '__return_true'
        ]);

        // Campaigns
        register_rest_route($this->namespace, '/campaigns', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_campaigns'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_campaign'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/campaigns/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_campaign'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_campaign'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_campaign'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        // Offers
        register_rest_route($this->namespace, '/offers', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_offers'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_offer'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/offers/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_offer'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_offer'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_offer'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        // URL Shortener
        register_rest_route($this->namespace, '/shorten', [
            'methods' => 'POST',
            'callback' => [$this, 'shorten_url'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/links', [
            'methods' => 'GET',
            'callback' => [$this, 'get_links'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/links/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_link'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_link'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        // QR Codes
        register_rest_route($this->namespace, '/qr-codes', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_qr_codes'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'generate_qr_code'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/qr-codes/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_qr_code'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_qr_code'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        // Analytics
        register_rest_route($this->namespace, '/analytics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_analytics'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/analytics/events', [
            'methods' => 'POST',
            'callback' => [$this, 'track_event'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route($this->namespace, '/analytics/summary', [
            'methods' => 'GET',
            'callback' => [$this, 'get_analytics_summary'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        // Schema
        register_rest_route($this->namespace, '/schemas', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_schemas'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_schema'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/schemas/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_schema'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_schema'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_schema'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        // Redirects
        register_rest_route($this->namespace, '/redirects', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_redirects'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_redirect'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/redirects/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_redirect'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_redirect'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_redirect'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        // Settings
        register_rest_route($this->namespace, '/settings', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_permission']
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_settings'],
                'permission_callback' => [$this, 'check_permission']
            ]
        ]);

        // Import/Export
        register_rest_route($this->namespace, '/export', [
            'methods' => 'GET',
            'callback' => [$this, 'export_data'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/import', [
            'methods' => 'POST',
            'callback' => [$this, 'import_data'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        // Search
        register_rest_route($this->namespace, '/search', [
            'methods' => 'GET',
            'callback' => [$this, 'search'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        // Stats
        register_rest_route($this->namespace, '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }

    /**
     * Health check endpoint
     *
     * @return \WP_REST_Response
     */
    public function health_check() {
        return rest_ensure_response([
            'status' => 'ok',
            'version' => SEO_CAMPAIGN_HUB_VERSION,
            'time' => current_time('mysql'),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version')
        ]);
    }

    /**
     * Check permission
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Check authentication
     *
     * @param mixed $errors Authentication errors
     * @return mixed
     */
    public function check_authentication($errors) {
        if (empty($errors)) {
            return $errors;
        }
        return $errors;
    }

    /**
     * Add CORS headers
     *
     * @param bool           $served Whether the request has been served
     * @param \WP_REST_Response $result Response object
     * @return bool
     */
    public function add_cors_headers($served, $result) {
        if (defined('SEO_CAMPAIGN_HUB_ALLOW_CORS') && SEO_CAMPAIGN_HUB_ALLOW_CORS) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        }
        return $served;
    }

    /**
     * Get campaigns
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_campaigns($request) {
        $args = [
            'post_type' => 'sch_campaign',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
            'post_status' => $request->get_param('status') ?: 'publish'
        ];

        if ($request->get_param('search')) {
            $args['s'] = $request->get_param('search');
        }

        if ($request->get_param('category')) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'sch_campaign_category',
                    'field' => 'slug',
                    'terms' => $request->get_param('category')
                ]
            ];
        }

        $campaigns = get_posts($args);
        $total = wp_count_posts('sch_campaign');

        return rest_ensure_response([
            'data' => $campaigns,
            'total' => (int) $total->publish,
            'page' => (int) $args['paged'],
            'per_page' => (int) $args['posts_per_page']
        ]);
    }

    /**
     * Get single campaign
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_campaign($request) {
        $campaign = get_post($request->get_param('id'));

        if (!$campaign || $campaign->post_type !== 'sch_campaign') {
            return new \WP_REST_Response(['message' => 'Campaign not found'], 404);
        }

        // Get meta data
        $meta = get_post_meta($campaign->ID);
        $campaign->meta = $meta;

        return rest_ensure_response($campaign);
    }

    /**
     * Create campaign
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function create_campaign($request) {
        $data = $request->get_json_params();

        $post_data = [
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content'] ?? ''),
            'post_excerpt' => sanitize_textarea_field($data['excerpt'] ?? ''),
            'post_status' => sanitize_text_field($data['status'] ?? 'draft'),
            'post_type' => 'sch_campaign'
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return new \WP_REST_Response(['message' => $post_id->get_error_message()], 400);
        }

        // Save meta data
        if (!empty($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, sanitize_text_field($value));
            }
        }

        return rest_ensure_response([
            'id' => $post_id,
            'message' => __('Campaign created successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Update campaign
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function update_campaign($request) {
        $id = $request->get_param('id');
        $data = $request->get_json_params();

        $campaign = get_post($id);

        if (!$campaign || $campaign->post_type !== 'sch_campaign') {
            return new \WP_REST_Response(['message' => 'Campaign not found'], 404);
        }

        $post_data = [
            'ID' => $id,
            'post_title' => sanitize_text_field($data['title'] ?? $campaign->post_title),
            'post_content' => wp_kses_post($data['content'] ?? $campaign->post_content),
            'post_excerpt' => sanitize_textarea_field($data['excerpt'] ?? $campaign->post_excerpt),
            'post_status' => sanitize_text_field($data['status'] ?? $campaign->post_status)
        ];

        $result = wp_update_post($post_data);

        if (is_wp_error($result)) {
            return new \WP_REST_Response(['message' => $result->get_error_message()], 400);
        }

        // Update meta data
        if (!empty($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                update_post_meta($id, $key, sanitize_text_field($value));
            }
        }

        return rest_ensure_response([
            'id' => $id,
            'message' => __('Campaign updated successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Delete campaign
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function delete_campaign($request) {
        $id = $request->get_param('id');

        $campaign = get_post($id);

        if (!$campaign || $campaign->post_type !== 'sch_campaign') {
            return new \WP_REST_Response(['message' => 'Campaign not found'], 404);
        }

        $result = wp_delete_post($id, true);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to delete campaign'], 400);
        }

        return rest_ensure_response([
            'message' => __('Campaign deleted successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Get offers
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_offers($request) {
        $args = [
            'post_type' => 'sch_offer',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
            'post_status' => $request->get_param('status') ?: 'publish'
        ];

        if ($request->get_param('search')) {
            $args['s'] = $request->get_param('search');
        }

        if ($request->get_param('type')) {
            $args['meta_query'] = [
                [
                    'key' => '_seo_campaign_hub_offer_type',
                    'value' => $request->get_param('type')
                ]
            ];
        }

        $offers = get_posts($args);
        $total = wp_count_posts('sch_offer');

        return rest_ensure_response([
            'data' => $offers,
            'total' => (int) $total->publish,
            'page' => (int) $args['paged'],
            'per_page' => (int) $args['posts_per_page']
        ]);
    }

    /**
     * Get single offer
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_offer($request) {
        $offer = get_post($request->get_param('id'));

        if (!$offer || $offer->post_type !== 'sch_offer') {
            return new \WP_REST_Response(['message' => 'Offer not found'], 404);
        }

        $meta = get_post_meta($offer->ID);
        $offer->meta = $meta;

        return rest_ensure_response($offer);
    }

    /**
     * Create offer
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function create_offer($request) {
        $data = $request->get_json_params();

        $post_data = [
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content'] ?? ''),
            'post_excerpt' => sanitize_textarea_field($data['excerpt'] ?? ''),
            'post_status' => sanitize_text_field($data['status'] ?? 'draft'),
            'post_type' => 'sch_offer'
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return new \WP_REST_Response(['message' => $post_id->get_error_message()], 400);
        }

        // Save meta data
        if (!empty($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, sanitize_text_field($value));
            }
        }

        return rest_ensure_response([
            'id' => $post_id,
            'message' => __('Offer created successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Update offer
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function update_offer($request) {
        $id = $request->get_param('id');
        $data = $request->get_json_params();

        $offer = get_post($id);

        if (!$offer || $offer->post_type !== 'sch_offer') {
            return new \WP_REST_Response(['message' => 'Offer not found'], 404);
        }

        $post_data = [
            'ID' => $id,
            'post_title' => sanitize_text_field($data['title'] ?? $offer->post_title),
            'post_content' => wp_kses_post($data['content'] ?? $offer->post_content),
            'post_excerpt' => sanitize_textarea_field($data['excerpt'] ?? $offer->post_excerpt),
            'post_status' => sanitize_text_field($data['status'] ?? $offer->post_status)
        ];

        $result = wp_update_post($post_data);

        if (is_wp_error($result)) {
            return new \WP_REST_Response(['message' => $result->get_error_message()], 400);
        }

        // Update meta data
        if (!empty($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                update_post_meta($id, $key, sanitize_text_field($value));
            }
        }

        return rest_ensure_response([
            'id' => $id,
            'message' => __('Offer updated successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Delete offer
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function delete_offer($request) {
        $id = $request->get_param('id');

        $offer = get_post($id);

        if (!$offer || $offer->post_type !== 'sch_offer') {
            return new \WP_REST_Response(['message' => 'Offer not found'], 404);
        }

        $result = wp_delete_post($id, true);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to delete offer'], 400);
        }

        return rest_ensure_response([
            'message' => __('Offer deleted successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Shorten URL
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function shorten_url($request) {
        $data = $request->get_json_params();
        $url = esc_url_raw($data['url'] ?? '');
        $slug = sanitize_text_field($data['slug'] ?? '');

        if (empty($url)) {
            return new \WP_REST_Response(['message' => 'URL is required'], 400);
        }

        if (!$this->container || !$this->container->has('shortener')) {
            return new \WP_REST_Response(['message' => 'Shortener service not available'], 503);
        }

        $short_url = $this->container->get('shortener')->shorten_url($url, $slug);

        return rest_ensure_response([
            'original_url' => $url,
            'short_url' => $short_url,
            'slug' => $slug
        ]);
    }

    /**
     * Get links
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_links($request) {
        global $wpdb;

        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $offset = ($page - 1) * $per_page;

        $table = $wpdb->prefix . 'sch_links';
        $query = "SELECT * FROM {$table}";

        if ($request->get_param('search')) {
            $search = '%' . $wpdb->esc_like($request->get_param('search')) . '%';
            $query .= $wpdb->prepare(
                " WHERE destination_url LIKE %s OR slug LIKE %s",
                $search,
                $search
            );
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM ({$query}) as count_table");
        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $links = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));

        return rest_ensure_response([
            'data' => $links,
            'total' => (int) $total,
            'page' => (int) $page,
            'per_page' => (int) $per_page
        ]);
    }

    /**
     * Get single link
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_link($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'sch_links';
        $link = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );

        if (!$link) {
            return new \WP_REST_Response(['message' => 'Link not found'], 404);
        }

        return rest_ensure_response($link);
    }

    /**
     * Delete link
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function delete_link($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'sch_links';
        $result = $wpdb->delete($table, ['id' => $id]);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to delete link'], 400);
        }

        return rest_ensure_response([
            'message' => __('Link deleted successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Get QR codes
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_qr_codes($request) {
        global $wpdb;

        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $offset = ($page - 1) * $per_page;

        $table = $wpdb->prefix . 'sch_qr_codes';
        $query = "SELECT * FROM {$table}";

        if ($request->get_param('search')) {
            $search = '%' . $wpdb->esc_like($request->get_param('search')) . '%';
            $query .= $wpdb->prepare(
                " WHERE title LIKE %s OR destination_url LIKE %s",
                $search,
                $search
            );
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM ({$query}) as count_table");
        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $qr_codes = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));

        return rest_ensure_response([
            'data' => $qr_codes,
            'total' => (int) $total,
            'page' => (int) $page,
            'per_page' => (int) $per_page
        ]);
    }

    /**
     * Get single QR code
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_qr_code($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'sch_qr_codes';
        $qr_code = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );

        if (!$qr_code) {
            return new \WP_REST_Response(['message' => 'QR Code not found'], 404);
        }

        return rest_ensure_response($qr_code);
    }

    /**
     * Generate QR code
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function generate_qr_code($request) {
        $data = $request->get_json_params();
        $url = esc_url_raw($data['url'] ?? '');
        $size = intval($data['size'] ?? 300);
        $color = sanitize_hex_color($data['color'] ?? '#000000');
        $bg_color = sanitize_hex_color($data['bg_color'] ?? '#FFFFFF');

        if (empty($url)) {
            return new \WP_REST_Response(['message' => 'URL is required'], 400);
        }

        if (!$this->container || !$this->container->has('qr')) {
            return new \WP_REST_Response(['message' => 'QR service not available'], 503);
        }

        $qr_image = $this->container->get('qr')->generate_qr($url, $size, $color, $bg_color);

        return rest_ensure_response([
            'url' => $url,
            'qr_image' => $qr_image,
            'size' => $size,
            'color' => $color,
            'bg_color' => $bg_color
        ]);
    }

    /**
     * Delete QR code
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function delete_qr_code($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'sch_qr_codes';
        $result = $wpdb->delete($table, ['id' => $id]);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to delete QR code'], 400);
        }

        return rest_ensure_response([
            'message' => __('QR code deleted successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Get analytics
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_analytics($request) {
        global $wpdb;

        $per_page = $request->get_param('per_page') ?: 50;
        $page = $request->get_param('page') ?: 1;
        $offset = ($page - 1) * $per_page;
        $days = intval($request->get_param('days') ?: 30);

        $table = $wpdb->prefix . 'sch_analytics';
        $query = "SELECT * FROM {$table}";

        if ($days > 0) {
            $date = date('Y-m-d H:i:s', strtotime("-$days days"));
            $query .= $wpdb->prepare(" WHERE created_at > %s", $date);
        }

        if ($request->get_param('event_type')) {
            if (strpos($query, 'WHERE') === false) {
                $query .= " WHERE";
            } else {
                $query .= " AND";
            }
            $query .= $wpdb->prepare(
                " event_type = %s",
                $request->get_param('event_type')
            );
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM ({$query}) as count_table");
        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $analytics = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));

        return rest_ensure_response([
            'data' => $analytics,
            'total' => (int) $total,
            'page' => (int) $page,
            'per_page' => (int) $per_page
        ]);
    }

    /**
     * Track event
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function track_event($request) {
        $data = $request->get_json_params();
        $event_type = sanitize_text_field($data['event_type'] ?? '');
        $event_data = $data['data'] ?? [];

        if (empty($event_type)) {
            return new \WP_REST_Response(['message' => 'Event type is required'], 400);
        }

        if (!$this->container || !$this->container->has('analytics')) {
            return new \WP_REST_Response(['message' => 'Analytics service not available'], 503);
        }

        $result = $this->container->get('analytics')->track_event($event_type, $event_data);

        return rest_ensure_response([
            'success' => $result !== false,
            'message' => $result ? __('Event tracked successfully', 'seo-campaign-hub') : __('Failed to track event', 'seo-campaign-hub')
        ]);
    }

    /**
     * Get analytics summary
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_analytics_summary($request) {
        global $wpdb;

        $days = intval($request->get_param('days') ?: 30);
        $table = $wpdb->prefix . 'sch_analytics';
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Total events
        $total_events = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at > %s",
                $date
            )
        );

        // Events by type
        $events_by_type = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_type, COUNT(*) as count 
                FROM {$table} 
                WHERE created_at > %s 
                GROUP BY event_type",
                $date
            ),
            ARRAY_A
        );

        // Unique visitors
        $unique_visitors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT visitor_id) FROM {$table} WHERE created_at > %s AND visitor_id IS NOT NULL",
                $date
            )
        );

        // Top campaigns
        $top_campaigns = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT campaign_id, COUNT(*) as count 
                FROM {$table} 
                WHERE created_at > %s AND campaign_id IS NOT NULL 
                GROUP BY campaign_id 
                ORDER BY count DESC 
                LIMIT 5",
                $date
            ),
            ARRAY_A
        );

        return rest_ensure_response([
            'total_events' => (int) $total_events,
            'unique_visitors' => (int) $unique_visitors,
            'events_by_type' => $events_by_type,
            'top_campaigns' => $top_campaigns,
            'period_days' => $days
        ]);
    }

    /**
     * Get schemas
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_schemas($request) {
        global $wpdb;

        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $offset = ($page - 1) * $per_page;

        $table = $wpdb->prefix . 'sch_schemas';
        $query = "SELECT * FROM {$table}";

        if ($request->get_param('type')) {
            $query .= $wpdb->prepare(
                " WHERE schema_type = %s",
                $request->get_param('type')
            );
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM ({$query}) as count_table");
        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $schemas = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));

        return rest_ensure_response([
            'data' => $schemas,
            'total' => (int) $total,
            'page' => (int) $page,
            'per_page' => (int) $per_page
        ]);
    }

    /**
     * Get single schema
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_schema($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'sch_schemas';
        $schema = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );

        if (!$schema) {
            return new \WP_REST_Response(['message' => 'Schema not found'], 404);
        }

        return rest_ensure_response($schema);
    }

    /**
     * Create schema
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function create_schema($request) {
        global $wpdb;

        $data = $request->get_json_params();
        $table = $wpdb->prefix . 'sch_schemas';

        $insert_data = [
            'schema_key' => sanitize_text_field($data['schema_key'] ?? 'schema_' . uniqid()),
            'schema_type' => sanitize_text_field($data['schema_type'] ?? 'Article'),
            'schema_title' => sanitize_text_field($data['schema_title'] ?? ''),
            'schema_description' => sanitize_textarea_field($data['schema_description'] ?? ''),
            'schema_data' => wp_json_encode($data['schema_data'] ?? []),
            'post_id' => intval($data['post_id'] ?? 0),
            'campaign_id' => intval($data['campaign_id'] ?? 0),
            'is_active' => intval($data['is_active'] ?? 1),
            'is_default' => intval($data['is_default'] ?? 0),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];

        $result = $wpdb->insert($table, $insert_data);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to create schema'], 400);
        }

        return rest_ensure_response([
            'id' => $wpdb->insert_id,
            'message' => __('Schema created successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Update schema
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function update_schema($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $data = $request->get_json_params();
        $table = $wpdb->prefix . 'sch_schemas';

        $schema = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        if (!$schema) {
            return new \WP_REST_Response(['message' => 'Schema not found'], 404);
        }

        $update_data = [];
        if (isset($data['schema_type'])) {
            $update_data['schema_type'] = sanitize_text_field($data['schema_type']);
        }
        if (isset($data['schema_title'])) {
            $update_data['schema_title'] = sanitize_text_field($data['schema_title']);
        }
        if (isset($data['schema_description'])) {
            $update_data['schema_description'] = sanitize_textarea_field($data['schema_description']);
        }
        if (isset($data['schema_data'])) {
            $update_data['schema_data'] = wp_json_encode($data['schema_data']);
        }
        if (isset($data['is_active'])) {
            $update_data['is_active'] = intval($data['is_active']);
        }
        if (isset($data['is_default'])) {
            $update_data['is_default'] = intval($data['is_default']);
        }
        $update_data['modified_by'] = get_current_user_id();
        $update_data['updated_at'] = current_time('mysql');

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new \WP_REST_Response(['message' => 'Failed to update schema'], 400);
        }

        return rest_ensure_response([
            'message' => __('Schema updated successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Delete schema
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function delete_schema($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'sch_schemas';
        $result = $wpdb->delete($table, ['id' => $id]);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to delete schema'], 400);
        }

        return rest_ensure_response([
            'message' => __('Schema deleted successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Get redirects
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_redirects($request) {
        global $wpdb;

        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $offset = ($page - 1) * $per_page;

        $table = $wpdb->prefix . 'sch_redirects';
        $query = "SELECT * FROM {$table}";

        if ($request->get_param('status')) {
            $query .= $wpdb->prepare(
                " WHERE status = %s",
                $request->get_param('status')
            );
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM ({$query}) as count_table");
        $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $redirects = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));

        return rest_ensure_response([
            'data' => $redirects,
            'total' => (int) $total,
            'page' => (int) $page,
            'per_page' => (int) $per_page
        ]);
    }

    /**
     * Get single redirect
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_redirect($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'sch_redirects';
        $redirect = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );

        if (!$redirect) {
            return new \WP_REST_Response(['message' => 'Redirect not found'], 404);
        }

        return rest_ensure_response($redirect);
    }

    /**
     * Create redirect
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function create_redirect($request) {
        global $wpdb;

        $data = $request->get_json_params();
        $table = $wpdb->prefix . 'sch_redirects';

        $source_url = esc_url_raw($data['source_url'] ?? '');
        $target_url = esc_url_raw($data['target_url'] ?? '');

        if (empty($source_url) || empty($target_url)) {
            return new \WP_REST_Response(['message' => 'Source and target URLs are required'], 400);
        }

        $insert_data = [
            'redirect_key' => 'redirect_' . uniqid(),
            'source_url' => $source_url,
            'source_hash' => md5($source_url),
            'target_url' => $target_url,
            'target_hash' => md5($target_url),
            'redirect_type' => sanitize_text_field($data['redirect_type'] ?? '301'),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'priority' => intval($data['priority'] ?? 1),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];

        $result = $wpdb->insert($table, $insert_data);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to create redirect'], 400);
        }

        return rest_ensure_response([
            'id' => $wpdb->insert_id,
            'message' => __('Redirect created successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Update redirect
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function update_redirect($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $data = $request->get_json_params();
        $table = $wpdb->prefix . 'sch_redirects';

        $redirect = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));

        if (!$redirect) {
            return new \WP_REST_Response(['message' => 'Redirect not found'], 404);
        }

        $update_data = [];
        if (isset($data['target_url'])) {
            $update_data['target_url'] = esc_url_raw($data['target_url']);
            $update_data['target_hash'] = md5($update_data['target_url']);
        }
        if (isset($data['redirect_type'])) {
            $update_data['redirect_type'] = sanitize_text_field($data['redirect_type']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }
        if (isset($data['priority'])) {
            $update_data['priority'] = intval($data['priority']);
        }
        $update_data['modified_by'] = get_current_user_id();
        $update_data['updated_at'] = current_time('mysql');

        $result = $wpdb->update($table, $update_data, ['id' => $id]);

        if ($result === false) {
            return new \WP_REST_Response(['message' => 'Failed to update redirect'], 400);
        }

        return rest_ensure_response([
            'message' => __('Redirect updated successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Delete redirect
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function delete_redirect($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'sch_redirects';
        $result = $wpdb->delete($table, ['id' => $id]);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to delete redirect'], 400);
        }

        return rest_ensure_response([
            'message' => __('Redirect deleted successfully', 'seo-campaign-hub')
        ]);
    }

    /**
     * Get settings
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_settings($request) {
        $settings = get_option('seo_campaign_hub_options', []);
        return rest_ensure_response($settings);
    }

    /**
     * Update settings
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function update_settings($request) {
        $data = $request->get_json_params();
        $settings = get_option('seo_campaign_hub_options', []);

        foreach ($data as $key => $value) {
            $settings[$key] = sanitize_text_field($value);
        }

        $result = update_option('seo_campaign_hub_options', $settings);

        if (!$result) {
            return new \WP_REST_Response(['message' => 'Failed to update settings'], 400);
        }

        return rest_ensure_response([
            'message' => __('Settings updated successfully', 'seo-campaign-hub'),
            'settings' => $settings
        ]);
    }

    /**
     * Export data
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function export_data($request) {
        $type = $request->get_param('type') ?: 'all';

        if (!$this->container || !$this->container->has('import_export')) {
            return new \WP_REST_Response(['message' => 'Export service not available'], 503);
        }

        $data = $this->container->get('import_export')->export_data($type);

        return rest_ensure_response([
            'data' => $data,
            'type' => $type,
            'timestamp' => current_time('mysql')
        ]);
    }

    /**
     * Import data
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function import_data($request) {
        $data = $request->get_json_params();

        if (empty($data) || !isset($data['data'])) {
            return new \WP_REST_Response(['message' => 'Import data is required'], 400);
        }

        if (!$this->container || !$this->container->has('import_export')) {
            return new \WP_REST_Response(['message' => 'Import service not available'], 503);
        }

        $result = $this->container->get('import_export')->import_data($data['data']);

        return rest_ensure_response([
            'success' => $result,
            'message' => $result ? __('Import completed successfully', 'seo-campaign-hub') : __('Import failed', 'seo-campaign-hub')
        ]);
    }

    /**
     * Search
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function search($request) {
        global $wpdb;

        $query = $request->get_param('q') ?: '';
        $type = $request->get_param('type') ?: 'all';

        if (empty($query)) {
            return rest_ensure_response(['data' => []]);
        }

        $results = [];
        $search = '%' . $wpdb->esc_like($query) . '%';

        // Search campaigns
        if ($type === 'all' || $type === 'campaigns') {
            $campaigns = get_posts([
                'post_type' => 'sch_campaign',
                's' => $query,
                'posts_per_page' => 10
            ]);
            foreach ($campaigns as $campaign) {
                $results[] = [
                    'type' => 'campaign',
                    'id' => $campaign->ID,
                    'title' => $campaign->post_title,
                    'url' => get_permalink($campaign->ID)
                ];
            }
        }

        // Search offers
        if ($type === 'all' || $type === 'offers') {
            $offers = get_posts([
                'post_type' => 'sch_offer',
                's' => $query,
                'posts_per_page' => 10
            ]);
            foreach ($offers as $offer) {
                $results[] = [
                    'type' => 'offer',
                    'id' => $offer->ID,
                    'title' => $offer->post_title,
                    'url' => get_permalink($offer->ID)
                ];
            }
        }

        return rest_ensure_response($results);
    }

    /**
     * Get stats
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_stats($request) {
        global $wpdb;

        // Get counts
        $campaign_count = wp_count_posts('sch_campaign');
        $offer_count = wp_count_posts('sch_offer');
        $link_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sch_links");
        $analytics_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sch_analytics");
        $qr_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sch_qr_codes");
        $redirect_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sch_redirects");

        // Get recent activity
        $recent_analytics = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}sch_analytics 
            ORDER BY created_at DESC 
            LIMIT 10"
        );

        return rest_ensure_response([
            'counts' => [
                'campaigns' => (int) $campaign_count->publish,
                'offers' => (int) $offer_count->publish,
                'links' => (int) $link_count,
                'analytics' => (int) $analytics_count,
                'qr_codes' => (int) $qr_count,
                'redirects' => (int) $redirect_count
            ],
            'recent_activity' => $recent_analytics,
            'timestamp' => current_time('mysql')
        ]);
    }

    /**
     * Handle API request
     *
     * @param string $request API request
     * @return void
     */
    public function handle_api_request($request) {
        // This is a fallback for legacy API requests
        // The REST API should be used instead
        wp_die(
            __('API endpoint moved to REST API.', 'seo-campaign-hub'),
            __('API Moved', 'seo-campaign-hub'),
            ['response' => 301]
        );
    }
}
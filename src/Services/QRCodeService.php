<?php
/**
 * QR Code Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QRCodeService
 *
 * Handles QR code generation operations
 */
class QRCodeService {
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
     * Initialize QR code service
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_seo_campaign_hub_generate_qr', [$this, 'ajax_generate_qr']);
        add_action('wp_ajax_nopriv_seo_campaign_hub_generate_qr', [$this, 'ajax_generate_qr']);
    }

    /**
     * Generate QR code
     *
     * @param string $url URL to encode
     * @param int    $size QR code size
     * @param string $color QR code color
     * @param string $bg_color Background color
     * @param string $format Output format (png, svg, pdf)
     * @return string|false
     */
    public function generate_qr($url, $size = 300, $color = '#000000', $bg_color = '#FFFFFF', $format = 'png') {
        // Check if QR codes are enabled
        if (!get_option('seo_campaign_hub_enable_qr_codes', true)) {
            return false;
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Use external API for QR code generation
        $api_url = 'https://api.qrserver.com/v1/create-qr-code/';
        $query_args = [
            'size' => $size . 'x' . $size,
            'data' => $url,
            'color' => $color,
            'bgcolor' => $bg_color,
            'format' => $format,
            'margin' => 10
        ];

        $full_url = $api_url . '?' . http_build_query($query_args);

        // Check cache
        $cache_key = 'qr_' . md5($full_url);
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Download QR code
        $response = wp_remote_get($full_url, [
            'timeout' => 30,
            'sslverify' => true
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if (empty($body)) {
            return false;
        }

        // Store in cache
        $this->cache->set($cache_key, $body, 'qr_codes', 86400);

        // Return the data
        return $body;
    }

    /**
     * Generate QR code and save to database
     *
     * @param array $data QR code data
     * @return int|false
     */
    public function create_qr_code($data) {
        // Validate data
        if (empty($data['destination_url'])) {
            return false;
        }

        // Generate QR key
        $data['qr_key'] = 'qr_' . uniqid();

        // Set defaults
        if (!isset($data['size'])) {
            $data['size'] = get_option('seo_campaign_hub_qr_code_size', 300);
        }

        if (!isset($data['color'])) {
            $data['color'] = get_option('seo_campaign_hub_qr_code_color', '#000000');
        }

        if (!isset($data['bg_color'])) {
            $data['bg_color'] = get_option('seo_campaign_hub_qr_code_bg_color', '#FFFFFF');
        }

        if (!isset($data['format'])) {
            $data['format'] = get_option('seo_campaign_hub_qr_code_format', 'png');
        }

        if (!isset($data['error_correction'])) {
            $data['error_correction'] = 'M';
        }

        // Set created by
        $data['created_by'] = get_current_user_id();

        // Generate QR code image
        $qr_data = $this->generate_qr(
            $data['destination_url'],
            $data['size'],
            $data['color'],
            $data['bg_color'],
            $data['format']
        );

        if (!$qr_data) {
            return false;
        }

        // Save QR code file
        $filename = $data['qr_key'] . '.' . $data['format'];
        $upload_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/qr-codes/';
        $filepath = $upload_dir . $filename;

        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        file_put_contents($filepath, $qr_data);

        // Insert into database
        $result = $this->db->insert('qr_codes', $data);

        if ($result) {
            $this->cache->clear('qr_codes');
            do_action('seo_campaign_hub_qr_code_created', $result, $data);
        }

        return $result;
    }

    /**
     * Get QR code by ID
     *
     * @param int $id QR code ID
     * @return object|null
     */
    public function get_qr_code($id) {
        $cache_key = 'qr_code_' . $id;
        $qr_code = $this->cache->get($cache_key);

        if ($qr_code === false) {
            $qr_code = $this->db->get_row(
                $this->db->prepare(
                    "SELECT * FROM {$this->db->get_table('qr_codes')} WHERE id = %d",
                    $id
                )
            );
            $this->cache->set($cache_key, $qr_code, 'qr_codes', 3600);
        }

        return $qr_code;
    }

    /**
     * Get QR code by key
     *
     * @param string $key QR code key
     * @return object|null
     */
    public function get_qr_code_by_key($key) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->get_table('qr_codes')} WHERE qr_key = %s",
                $key
            )
        );
    }

    /**
     * Get all QR codes
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_qr_codes($args = []) {
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $where = [];

        if (!empty($args['campaign_id'])) {
            $where[] = $this->db->prepare(
                "campaign_id = %d",
                $args['campaign_id']
            );
        }

        if (!empty($args['offer_id'])) {
            $where[] = $this->db->prepare(
                "offer_id = %d",
                $args['offer_id']
            );
        }

        if (!empty($args['search'])) {
            $search = '%' . $this->db->escape($args['search']) . '%';
            $where[] = $this->db->prepare(
                "(title LIKE %s OR destination_url LIKE %s)",
                $search,
                $search
            );
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "
            SELECT * FROM {$this->db->get_table('qr_codes')}
            {$where_clause}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d
        ";

        $query = $this->db->prepare($query, $args['limit'], $args['offset']);
        return $this->db->get_results($query);
    }

    /**
     * Update QR code
     *
     * @param int   $id QR code ID
     * @param array $data QR code data
     * @return int|false
     */
    public function update_qr_code($id, $data) {
        $qr_code = $this->get_qr_code($id);

        if (!$qr_code) {
            return false;
        }

        // Update
        $result = $this->db->update('qr_codes', $data, ['id' => $id]);

        if ($result !== false) {
            $this->cache->delete('qr_code_' . $id);
            $this->cache->clear('qr_codes');
            do_action('seo_campaign_hub_qr_code_updated', $id, $data);
        }

        return $result;
    }

    /**
     * Delete QR code
     *
     * @param int $id QR code ID
     * @return bool
     */
    public function delete_qr_code($id) {
        $qr_code = $this->get_qr_code($id);

        if (!$qr_code) {
            return false;
        }

        // Delete file
        $upload_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/qr-codes/';
        $filepath = $upload_dir . $qr_code->qr_key . '.' . $qr_code->format;
        if (file_exists($filepath)) {
            wp_delete_file($filepath);
        }

        // Delete from database
        $result = $this->db->delete('qr_codes', ['id' => $id]);

        if ($result) {
            $this->cache->delete('qr_code_' . $id);
            $this->cache->clear('qr_codes');
            do_action('seo_campaign_hub_qr_code_deleted', $id);
        }

        return $result;
    }

    /**
     * Handle redirect for QR code
     *
     * @param string $key QR code key
     * @return void
     */
    public function handle_redirect($key) {
        $qr_code = $this->get_qr_code_by_key($key);

        if (!$qr_code) {
            wp_die(__('QR code not found.', 'seo-campaign-hub'), 404);
        }

        // Increment scan count
        $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->db->get_table('qr_codes')} 
                SET total_scans = total_scans + 1,
                    unique_scans = unique_scans + 1,
                    last_scanned = NOW()
                WHERE id = %d",
                $qr_code->id
            )
        );

        // Track scan event
        $analytics = new AnalyticsService();
        $analytics->track_event('qr_scan', [
            'qr_code_id' => $qr_code->id,
            'event_name' => 'qr_code_scan'
        ]);

        // Redirect
        wp_redirect($qr_code->destination_url, 301);
        exit;
    }

    /**
     * AJAX generate QR code
     *
     * @return void
     */
    public function ajax_generate_qr() {
        check_ajax_referer('seo_campaign_hub_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $size = isset($_POST['size']) ? intval($_POST['size']) : 300;
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '#000000';
        $bg_color = isset($_POST['bg_color']) ? sanitize_hex_color($_POST['bg_color']) : '#FFFFFF';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'png';

        if (empty($url)) {
            wp_send_json_error(['message' => 'URL is required']);
        }

        $qr_data = $this->generate_qr($url, $size, $color, $bg_color, $format);

        if (!$qr_data) {
            wp_send_json_error(['message' => 'Failed to generate QR code']);
        }

        $base64 = base64_encode($qr_data);
        $data_uri = 'data:image/' . $format . ';base64,' . $base64;

        wp_send_json_success([
            'image' => $data_uri,
            'format' => $format,
            'size' => $size
        ]);
    }

    /**
     * Render QR code shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode($atts) {
        $url = isset($atts['url']) ? $atts['url'] : home_url();
        $size = isset($atts['size']) ? intval($atts['size']) : 200;
        $label = isset($atts['label']) ? $atts['label'] : '';

        if (empty($url)) {
            return '<p>' . __('Please specify a URL.', 'seo-campaign-hub') . '</p>';
        }

        $qr_data = $this->generate_qr($url, $size);

        if (!$qr_data) {
            return '<p>' . __('Failed to generate QR code.', 'seo-campaign-hub') . '</p>';
        }

        $base64 = base64_encode($qr_data);
        $data_uri = 'data:image/png;base64,' . $base64;

        ob_start();
        ?>
        <div class="sch-qr-code-shortcode">
            <div class="sch-qr-code">
                <img src="<?php echo esc_url($data_uri); ?>" 
                     alt="<?php echo esc_attr($label ?: __('QR Code', 'seo-campaign-hub')); ?>" 
                     width="<?php echo esc_attr($size); ?>" 
                     height="<?php echo esc_attr($size); ?>" />
                <?php if (!empty($label)): ?>
                    <p class="sch-qr-label"><?php echo esc_html($label); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get QR code statistics
     *
     * @param int $id QR code ID
     * @return array
     */
    public function get_qr_stats($id) {
        $qr_code = $this->get_qr_code($id);

        if (!$qr_code) {
            return [];
        }

        return [
            'total_scans' => (int) $qr_code->total_scans,
            'unique_scans' => (int) $qr_code->unique_scans,
            'last_scanned' => $qr_code->last_scanned,
            'created_at' => $qr_code->created_at
        ];
    }
}
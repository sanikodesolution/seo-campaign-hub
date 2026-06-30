<?php
/**
 * Redirect Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RedirectService
 *
 * Handles redirect management operations
 */
class RedirectService {
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
     * Initialize redirect service
     *
     * @return void
     */
    public function init() {
        add_action('template_redirect', [$this, 'handle_redirects']);
        add_action('seo_campaign_hub_redirect_created', [$this, 'clear_redirect_cache']);
    }

    /**
     * Create a redirect
     *
     * @param array $data Redirect data
     * @return int|false
     */
    public function create_redirect($data) {
        // Validate data
        if (empty($data['source_url']) || empty($data['target_url'])) {
            return false;
        }

        // Generate redirect key
        $data['redirect_key'] = 'red_' . uniqid();

        // Generate hashes
        $data['source_hash'] = md5($data['source_url']);
        $data['target_hash'] = md5($data['target_url']);

        // Set defaults
        if (!isset($data['redirect_type'])) {
            $data['redirect_type'] = get_option('seo_campaign_hub_default_redirect_type', '301');
        }

        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        if (!isset($data['priority'])) {
            $data['priority'] = 1;
        }

        if (!isset($data['condition_type'])) {
            $data['condition_type'] = 'always';
        }

        // Set created by
        $data['created_by'] = get_current_user_id();

        // Check if redirect already exists
        $existing = $this->get_redirect_by_source($data['source_url']);
        if ($existing) {
            return false;
        }

        // Insert into database
        $result = $this->db->insert('redirects', $data);

        if ($result) {
            $this->cache->clear('redirects');
            do_action('seo_campaign_hub_redirect_created', $result, $data);
        }

        return $result;
    }

    /**
     * Get redirect by ID
     *
     * @param int $id Redirect ID
     * @return object|null
     */
    public function get_redirect($id) {
        $cache_key = 'redirect_' . $id;
        $redirect = $this->cache->get($cache_key);

        if ($redirect === false) {
            $redirect = $this->db->get_row(
                $this->db->prepare(
                    "SELECT * FROM {$this->db->get_table('redirects')} WHERE id = %d",
                    $id
                )
            );
            $this->cache->set($cache_key, $redirect, 'redirects', 3600);
        }

        return $redirect;
    }

    /**
     * Get redirect by source URL
     *
     * @param string $source_url Source URL
     * @return object|null
     */
    public function get_redirect_by_source($source_url) {
        $hash = md5($source_url);
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->get_table('redirects')} 
                WHERE source_hash = %s AND status = 'active'",
                $hash
            )
        );
    }

    /**
     * Get all redirects
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_redirects($args = []) {
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => 'active'
        ];

        $args = wp_parse_args($args, $defaults);
        $where = [];

        if (!empty($args['status'])) {
            $where[] = $this->db->prepare(
                "status = %s",
                $args['status']
            );
        }

        if (!empty($args['redirect_type'])) {
            $where[] = $this->db->prepare(
                "redirect_type = %s",
                $args['redirect_type']
            );
        }

        if (!empty($args['search'])) {
            $search = '%' . $this->db->escape($args['search']) . '%';
            $where[] = $this->db->prepare(
                "(source_url LIKE %s OR target_url LIKE %s)",
                $search,
                $search
            );
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "
            SELECT * FROM {$this->db->get_table('redirects')}
            {$where_clause}
            ORDER BY priority DESC, {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d
        ";

        $query = $this->db->prepare($query, $args['limit'], $args['offset']);
        return $this->db->get_results($query);
    }

    /**
     * Update redirect
     *
     * @param int   $id Redirect ID
     * @param array $data Redirect data
     * @return int|false
     */
    public function update_redirect($id, $data) {
        $redirect = $this->get_redirect($id);

        if (!$redirect) {
            return false;
        }

        // Update hashes if source or target changed
        if (isset($data['source_url'])) {
            $data['source_hash'] = md5($data['source_url']);
        }

        if (isset($data['target_url'])) {
            $data['target_hash'] = md5($data['target_url']);
        }

        // Set modified by
        $data['modified_by'] = get_current_user_id();

        // Update
        $result = $this->db->update('redirects', $data, ['id' => $id]);

        if ($result !== false) {
            $this->cache->delete('redirect_' . $id);
            $this->cache->clear('redirects');
            do_action('seo_campaign_hub_redirect_updated', $id, $data);
        }

        return $result;
    }

    /**
     * Delete redirect
     *
     * @param int $id Redirect ID
     * @return bool
     */
    public function delete_redirect($id) {
        $redirect = $this->get_redirect($id);

        if (!$redirect) {
            return false;
        }

        $result = $this->db->delete('redirects', ['id' => $id]);

        if ($result) {
            $this->cache->delete('redirect_' . $id);
            $this->cache->clear('redirects');
            do_action('seo_campaign_hub_redirect_deleted', $id);
        }

        return $result;
    }

    /**
     * Handle redirects
     *
     * @return void
     */
    public function handle_redirects() {
        $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));

        // Skip if not a valid URL
        if (empty($current_url)) {
            return;
        }

        // Get redirect
        $redirect = $this->get_redirect_by_source($current_url);

        if (!$redirect) {
            return;
        }

        // Check conditions
        if (!$this->check_conditions($redirect)) {
            return;
        }

        // Increment click count
        $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->db->get_table('redirects')} 
                SET total_clicks = total_clicks + 1,
                    last_clicked = NOW()
                WHERE id = %d",
                $redirect->id
            )
        );

        // Track redirect
        $analytics = new AnalyticsService();
        $analytics->track_event('redirect', [
            'redirect_id' => $redirect->id,
            'event_name' => 'redirect_triggered'
        ]);

        // Perform redirect
        $status_code = intval($redirect->redirect_type) ?: 301;
        wp_redirect($redirect->target_url, $status_code);
        exit;
    }

    /**
     * Check redirect conditions
     *
     * @param object $redirect Redirect object
     * @return bool
     */
    private function check_conditions($redirect) {
        if ($redirect->condition_type === 'always') {
            return true;
        }

        $condition_value = json_decode($redirect->condition_value, true);

        if (!$condition_value) {
            return true;
        }

        switch ($redirect->condition_type) {
            case 'geo':
                return $this->check_geo_condition($condition_value);
            case 'device':
                return $this->check_device_condition($condition_value);
            case 'language':
                return $this->check_language_condition($condition_value);
            case 'time':
                return $this->check_time_condition($condition_value);
            case 'referrer':
                return $this->check_referrer_condition($condition_value);
            default:
                return true;
        }
    }

    /**
     * Check geo condition
     *
     * @param array $condition Condition data
     * @return bool
     */
    private function check_geo_condition($condition) {
        if (empty($condition['countries'])) {
            return true;
        }

        // Get user IP and country
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $country = $this->get_ip_country($ip);

        return in_array($country, $condition['countries']);
    }

    /**
     * Check device condition
     *
     * @param array $condition Condition data
     * @return bool
     */
    private function check_device_condition($condition) {
        if (empty($condition['devices'])) {
            return true;
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device = $this->get_device_type($user_agent);

        return in_array($device, $condition['devices']);
    }

    /**
     * Check language condition
     *
     * @param array $condition Condition data
     * @return bool
     */
    private function check_language_condition($condition) {
        if (empty($condition['languages'])) {
            return true;
        }

        $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $language = substr($language, 0, 2);

        return in_array($language, $condition['languages']);
    }

    /**
     * Check time condition
     *
     * @param array $condition Condition data
     * @return bool
     */
    private function check_time_condition($condition) {
        if (empty($condition['start']) || empty($condition['end'])) {
            return true;
        }

        $now = time();
        $start = strtotime($condition['start']);
        $end = strtotime($condition['end']);

        return $now >= $start && $now <= $end;
    }

    /**
     * Check referrer condition
     *
     * @param array $condition Condition data
     * @return bool
     */
    private function check_referrer_condition($condition) {
        if (empty($condition['referrers'])) {
            return true;
        }

        $referrer = $_SERVER['HTTP_REFERER'] ?? '';

        if (empty($referrer)) {
            return false;
        }

        foreach ($condition['referrers'] as $pattern) {
            if (strpos($referrer, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get IP country
     *
     * @param string $ip IP address
     * @return string
     */
    private function get_ip_country($ip) {
        // Use free IP geolocation API
        $response = wp_remote_get('http://ip-api.com/json/' . $ip, [
            'timeout' => 5
        ]);

        if (is_wp_error($response)) {
            return 'US'; // Default fallback
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return isset($data['countryCode']) ? $data['countryCode'] : 'US';
    }

    /**
     * Get device type
     *
     * @param string $user_agent User agent string
     * @return string
     */
    private function get_device_type($user_agent) {
        if (strpos($user_agent, 'Mobile') !== false) {
            return 'mobile';
        } elseif (strpos($user_agent, 'Tablet') !== false) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Clear redirect cache
     *
     * @return void
     */
    public function clear_redirect_cache() {
        $this->cache->clear('redirects');
    }

    /**
     * Get redirect statistics
     *
     * @param int $id Redirect ID
     * @return array
     */
    public function get_redirect_stats($id) {
        $redirect = $this->get_redirect($id);

        if (!$redirect) {
            return [];
        }

        return [
            'total_clicks' => (int) $redirect->total_clicks,
            'last_clicked' => $redirect->last_clicked,
            'created_at' => $redirect->created_at,
            'updated_at' => $redirect->updated_at
        ];
    }

    /**
     * Import redirects from CSV
     *
     * @param string $file_path CSV file path
     * @return array
     */
    public function import_redirects($file_path) {
        if (!file_exists($file_path)) {
            return ['success' => 0, 'failed' => 0, 'errors' => []];
        }

        $handle = fopen($file_path, 'r');
        $headers = fgetcsv($handle);
        $imported = 0;
        $failed = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            if (empty($data['source_url']) || empty($data['target_url'])) {
                $failed++;
                $errors[] = 'Missing source or target URL';
                continue;
            }

            $result = $this->create_redirect([
                'source_url' => $data['source_url'],
                'target_url' => $data['target_url'],
                'redirect_type' => isset($data['redirect_type']) ? $data['redirect_type'] : '301'
            ]);

            if ($result) {
                $imported++;
            } else {
                $failed++;
                $errors[] = 'Failed to import: ' . $data['source_url'];
            }
        }

        fclose($handle);

        return [
            'success' => $imported,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Export redirects to CSV
     *
     * @return string
     */
    public function export_redirects() {
        $redirects = $this->get_redirects(['limit' => 9999]);
        $export_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/exports/';
        $filename = 'redirects_' . date('Y-m-d') . '.csv';
        $filepath = $export_dir . $filename;

        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $handle = fopen($filepath, 'w');
        fputcsv($handle, ['source_url', 'target_url', 'redirect_type', 'status']);

        foreach ($redirects as $redirect) {
            fputcsv($handle, [
                $redirect->source_url,
                $redirect->target_url,
                $redirect->redirect_type,
                $redirect->status
            ]);
        }

        fclose($handle);

        return $filepath;
    }
}
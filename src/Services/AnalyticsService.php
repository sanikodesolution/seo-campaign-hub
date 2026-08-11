<?php
/**
 * Analytics Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AnalyticsService
 *
 * Handles analytics tracking and reporting
 */
class AnalyticsService {
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
     * Session ID
     *
     * @var string
     */
    private $session_id;

    /**
     * Visitor ID
     *
     * @var string
     */
    private $visitor_id;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new \SEO_Campaign_Hub\Database\Database();
        $this->cache = new \SEO_Campaign_Hub\Core\CacheManager();
        // Lazy — do not touch cookies during plugins_loaded (can break boot).
        $this->session_id = '';
        $this->visitor_id = '';
    }

    /**
     * Ensure session/visitor IDs exist when tracking runs.
     *
     * @return void
     */
    private function ensure_visitor_context() {
        if ($this->session_id === '') {
            $this->session_id = $this->get_session_id();
        }
        if ($this->visitor_id === '') {
            $this->visitor_id = $this->get_visitor_id();
        }
    }

    /**
     * Initialize analytics
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_seo_campaign_hub_track', [$this, 'ajax_track_event']);
        add_action('wp_ajax_nopriv_seo_campaign_hub_track', [$this, 'ajax_track_event']);
        // Tracking UI lives in assets/public/js/public.js — avoid a second footer tracker.
        add_action('seo_campaign_hub_analytics_cron', [$this, 'process_analytics_cron']);

        if (!wp_next_scheduled('seo_campaign_hub_analytics_cron')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'seo_campaign_hub_analytics_cron');
        }
    }

    /**
     * Whether analytics collection is enabled.
     *
     * @return bool
     */
    public function is_enabled() {
        $nested = get_option('seo_campaign_hub_options', []);
        if (is_array($nested) && array_key_exists('enable_analytics', $nested)) {
            return (bool) $nested['enable_analytics'];
        }

        return (bool) get_option('seo_campaign_hub_enable_analytics', true);
    }

    /**
     * Quick visitor overview for the Dashboard widget.
     *
     * @return array{enabled:bool,today_visitors:int,today_views:int,week_visitors:int,week_views:int,month_visitors:int,month_views:int,all_visitors:int,all_views:int}
     */
    public function get_visitor_overview(): array {
        if ( ! $this->db->table_exists( 'analytics' ) ) {
            return [
                'enabled'        => $this->is_enabled(),
                'today_visitors' => 0, 'today_views' => 0,
                'week_visitors'  => 0, 'week_views'  => 0,
                'month_visitors' => 0, 'month_views' => 0,
                'all_visitors'   => 0, 'all_views'   => 0,
            ];
        }

        global $wpdb;
        $t     = $wpdb->prefix . 'sch_analytics';
        $today = gmdate( 'Y-m-d 00:00:00' );
        $week  = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
        $month = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );

        $sql = "SELECT
            COUNT(DISTINCT CASE WHEN created_at >= %s THEN visitor_id END) AS today_visitors,
            SUM(CASE WHEN created_at >= %s AND event_type IN ('page_view','view') THEN 1 ELSE 0 END) AS today_views,
            COUNT(DISTINCT CASE WHEN created_at >= %s THEN visitor_id END) AS week_visitors,
            SUM(CASE WHEN created_at >= %s AND event_type IN ('page_view','view') THEN 1 ELSE 0 END) AS week_views,
            COUNT(DISTINCT CASE WHEN created_at >= %s THEN visitor_id END) AS month_visitors,
            SUM(CASE WHEN created_at >= %s AND event_type IN ('page_view','view') THEN 1 ELSE 0 END) AS month_views,
            COUNT(DISTINCT visitor_id) AS all_visitors,
            SUM(CASE WHEN event_type IN ('page_view','view') THEN 1 ELSE 0 END) AS all_views
            FROM {$t}";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $row = $wpdb->get_row( $wpdb->prepare( $sql, $today, $today, $week, $week, $month, $month ), ARRAY_A );

        return [
            'enabled'        => $this->is_enabled(),
            'today_visitors' => (int) ( $row['today_visitors'] ?? 0 ),
            'today_views'    => (int) ( $row['today_views'] ?? 0 ),
            'week_visitors'  => (int) ( $row['week_visitors'] ?? 0 ),
            'week_views'     => (int) ( $row['week_views'] ?? 0 ),
            'month_visitors' => (int) ( $row['month_visitors'] ?? 0 ),
            'month_views'    => (int) ( $row['month_views'] ?? 0 ),
            'all_visitors'   => (int) ( $row['all_visitors'] ?? 0 ),
            'all_views'      => (int) ( $row['all_views'] ?? 0 ),
        ];
    }

    /**
     * Get session ID
     *
     * @return string
     */
    private function get_session_id() {
        $existing = isset($_COOKIE['sch_session']) ? sanitize_text_field(wp_unslash($_COOKIE['sch_session'])) : '';
        if ($this->is_valid_uuid($existing)) {
            return $existing;
        }

        $session_id = wp_generate_uuid4();
        $this->set_tracking_cookie('sch_session', $session_id, DAY_IN_SECONDS);
        return $session_id;
    }

    /**
     * Get visitor ID
     *
     * @return string
     */
    private function get_visitor_id() {
        $existing = isset($_COOKIE['sch_visitor']) ? sanitize_text_field(wp_unslash($_COOKIE['sch_visitor'])) : '';
        if ($this->is_valid_uuid($existing)) {
            return $existing;
        }

        $visitor_id = wp_generate_uuid4();
        $this->set_tracking_cookie('sch_visitor', $visitor_id, YEAR_IN_SECONDS);
        return $visitor_id;
    }

    /**
     * Track event
     *
     * @param string $event_type Event type
     * @param array  $data Event data
     * @return int|false
     */
    public function track_event($event_type, $data = []) {
        if (!$this->is_enabled()) {
            return false;
        }

        $this->ensure_visitor_context();

        if ($this->get_option_bool('ignore_bots', true) && $this->is_bot()) {
            return false;
        }

        $data = is_array($data) ? $data : [];
        $data = $this->normalize_event_payload($data);
        $event_type = $this->normalize_event_type($event_type, $data);

        if (!$event_type) {
            return false;
        }

        if (!$this->is_event_type_allowed($event_type)) {
            return false;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($this->get_option_bool('anonymize_ip', true)) {
            $ip = $this->anonymize_ip($ip);
        }

        $user_id = get_current_user_id();
        $campaign_id = $this->nullable_id($data['campaign_id'] ?? 0);
        $offer_id = $this->nullable_id($data['offer_id'] ?? 0);
        $link_id = $this->nullable_id($data['link_id'] ?? 0);
        $post_id = $this->nullable_id($data['post_id'] ?? 0);

        $event = [
            'session_id' => substr((string) $this->session_id, 0, 64),
            'visitor_id' => substr((string) $this->visitor_id, 0, 64),
            'user_id' => $user_id ? (int) $user_id : null,
            'event_type' => $event_type,
            'event_name' => isset($data['event_name']) ? sanitize_text_field((string) $data['event_name']) : null,
            'event_value' => isset($data['event_value']) ? floatval($data['event_value']) : null,
            'campaign_id' => $campaign_id,
            'offer_id' => $offer_id,
            'link_id' => $link_id,
            'post_id' => $post_id,
            'ip_address' => $ip ? substr($ip, 0, 45) : null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 500) : null,
            'referrer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : null,
            'landing_page' => isset($data['landing_page']) ? esc_url_raw((string) $data['landing_page']) : null,
            'country' => isset($data['country']) ? strtoupper(substr(sanitize_text_field((string) $data['country']), 0, 2)) : null,
            'region' => isset($data['region']) ? sanitize_text_field((string) $data['region']) : null,
            'city' => isset($data['city']) ? sanitize_text_field((string) $data['city']) : null,
            'device_type' => $this->get_device_type(),
            'os' => isset($data['os']) ? sanitize_text_field((string) $data['os']) : null,
            'browser' => isset($data['browser']) ? sanitize_text_field((string) $data['browser']) : null,
            'time_on_page' => isset($data['time_on_page']) ? max(0, intval($data['time_on_page'])) : 0,
            'scroll_depth' => isset($data['scroll_depth']) ? min(100, max(0, intval($data['scroll_depth']))) : 0,
            'conversion_amount' => isset($data['conversion_amount']) ? floatval($data['conversion_amount']) : null,
            'utm_source' => $this->sanitize_utm($data['utm_source'] ?? ($data['utm']['source'] ?? '')),
            'utm_medium' => $this->sanitize_utm($data['utm_medium'] ?? ($data['utm']['medium'] ?? '')),
            'utm_campaign' => $this->sanitize_utm($data['utm_campaign'] ?? ($data['utm']['campaign'] ?? '')),
            'utm_term' => $this->sanitize_utm($data['utm_term'] ?? ($data['utm']['term'] ?? '')),
            'utm_content' => $this->sanitize_utm($data['utm_content'] ?? ($data['utm']['content'] ?? '')),
            'meta_data' => isset($data['meta_data']) ? wp_json_encode($data['meta_data']) : null,
        ];

        // Prefer payload UTM; fall back to current request query only when present.
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $utm_key) {
            if (empty($event[$utm_key]) && !empty($_GET[$utm_key])) {
                $event[$utm_key] = $this->sanitize_utm(wp_unslash($_GET[$utm_key]));
            }
            if ($event[$utm_key] === '') {
                $event[$utm_key] = null;
            }
        }

        // Resolve the visitor's country from their IP when not explicitly provided.
        if (empty($event['country'])) {
            $geo = $this->geolocate($this->get_client_ip());
            if ($geo['country'] !== '') {
                $event['country'] = $geo['country'];
                if (empty($event['region']) && $geo['region'] !== '') {
                    $event['region'] = $geo['region'];
                }
                if (empty($event['city']) && $geo['city'] !== '') {
                    $event['city'] = $geo['city'];
                }
            }
        }

        if (!empty($event['country']) && !preg_match('/^[A-Z]{2}$/', $event['country'])) {
            $event['country'] = null;
        }

        // Only write language when the 1.1.0 column exists.
        if ($this->column_exists('language')) {
            $language = isset($data['language'])
                ? strtolower(substr(sanitize_text_field((string) $data['language']), 0, 2))
                : '';
            if ($language === '') {
                $language = $this->detect_visitor_language();
            }
            $event['language'] = preg_match('/^[a-z]{2}$/', $language) ? $language : null;
        }

        $result = $this->db->insert('analytics', $event);

        if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SEO Campaign Hub analytics insert failed: ' . $this->db->last_error()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        return $result;
    }

    /**
     * AJAX track event
     *
     * @return void
     */
    public function ajax_track_event() {
        check_ajax_referer('seo_campaign_hub_public', 'nonce');

        if (!$this->is_enabled()) {
            wp_send_json_error(['message' => 'Analytics disabled'], 403);
        }

        if (!$this->allow_tracking_request()) {
            wp_send_json_error(['message' => 'Rate limit exceeded'], 429);
        }

        $event_type = isset($_POST['event_type']) ? sanitize_text_field(wp_unslash($_POST['event_type'])) : '';
        $raw = isset($_POST['data']) ? wp_unslash($_POST['data']) : '';
        $data = is_string($raw) ? json_decode($raw, true) : [];
        if (!is_array($data)) {
            $data = [];
        }

        if ($event_type === 'batch') {
            $tracked = 0;
            $events = isset($data[0]) ? $data : [];
            foreach (array_slice($events, 0, 25) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $type = isset($item['type']) ? (string) $item['type'] : (isset($item['event_type']) ? (string) $item['event_type'] : '');
                $payload = isset($item['data']) && is_array($item['data']) ? $item['data'] : $item;
                if ($this->track_event($type, $payload)) {
                    $tracked++;
                }
            }
            if ($tracked > 0) {
                wp_send_json_success(['message' => 'Events tracked', 'count' => $tracked]);
            }
            wp_send_json_error(['message' => 'Failed to track events']);
        }

        if ($event_type === '') {
            wp_send_json_error(['message' => 'Event type is required']);
        }

        $result = $this->track_event($event_type, $data);

        if ($result) {
            wp_send_json_success(['message' => 'Event tracked successfully']);
        }

        wp_send_json_error(['message' => 'Failed to track event']);
    }

    /**
     * Legacy footer tracker hook — intentionally empty.
     * Public tracking is handled by assets/public/js/public.js.
     *
     * @return void
     */
    public function add_tracking_script() {
        // No-op: public.js owns frontend tracking.
    }

    /**
     * Process analytics cron
     *
     * @return void
     */
    public function process_analytics_cron() {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_analytics';

        $retention_days = (int) get_option('seo_campaign_hub_analytics_retention_days', 0);
        if ($retention_days <= 0) {
            $retention_days = (int) get_option('seo_campaign_hub_analytics_retention', 90);
        }

        if ($retention_days > 0) {
            $date = gmdate('Y-m-d H:i:s', time() - ($retention_days * DAY_IN_SECONDS));
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table} WHERE created_at < %s",
                    $date
                )
            );
        }

        $this->update_statistics();
    }

    /**
     * Update campaign and offer statistics
     *
     * @return void
     */
    public function update_statistics() {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'sch_analytics';
        $campaigns_table = $wpdb->prefix . 'sch_campaigns';
        $offers_table = $wpdb->prefix . 'sch_offers';

        // Update campaign statistics
        $wpdb->query(
            "UPDATE {$campaigns_table} c
            SET 
                total_views = (
                    SELECT COUNT(*) FROM {$analytics_table} a 
                    WHERE a.campaign_id = c.id AND a.event_type = 'page_view'
                ),
                unique_visitors = (
                    SELECT COUNT(DISTINCT a.visitor_id) FROM {$analytics_table} a 
                    WHERE a.campaign_id = c.id AND a.event_type = 'page_view'
                ),
                total_clicks = (
                    SELECT COUNT(*) FROM {$analytics_table} a 
                    WHERE a.campaign_id = c.id AND a.event_type = 'click'
                ),
                total_conversions = (
                    SELECT COUNT(*) FROM {$analytics_table} a 
                    WHERE a.campaign_id = c.id AND a.event_type = 'conversion'
                ),
                conversion_rate = CASE 
                    WHEN total_views > 0 THEN (total_conversions / total_views) * 100 
                    ELSE 0 
                END
            WHERE c.id IN (
                SELECT DISTINCT campaign_id FROM {$analytics_table} WHERE campaign_id IS NOT NULL
            )"
        );

        // Update offer statistics
        $wpdb->query(
            "UPDATE {$offers_table} o
            SET 
                total_clicks = (
                    SELECT COUNT(*) FROM {$analytics_table} a 
                    WHERE a.offer_id = o.id AND a.event_type = 'click'
                ),
                unique_clicks = (
                    SELECT COUNT(DISTINCT a.visitor_id) FROM {$analytics_table} a 
                    WHERE a.offer_id = o.id AND a.event_type = 'click'
                ),
                total_conversions = (
                    SELECT COUNT(*) FROM {$analytics_table} a 
                    WHERE a.offer_id = o.id AND a.event_type = 'conversion'
                ),
                conversion_rate = CASE 
                    WHEN total_clicks > 0 THEN (total_conversions / total_clicks) * 100 
                    ELSE 0 
                END,
                revenue = (
                    SELECT COALESCE(SUM(a.conversion_amount), 0) FROM {$analytics_table} a 
                    WHERE a.offer_id = o.id AND a.event_type = 'conversion'
                )
            WHERE o.id IN (
                SELECT DISTINCT offer_id FROM {$analytics_table} WHERE offer_id IS NOT NULL
            )"
        );
    }

    /**
     * Get analytics data
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_analytics($args = []) {
        $defaults = [
            'limit' => 50,
            'offset' => 0,
            'event_type' => '',
            'campaign_id' => 0,
            'offer_id' => 0,
            'start_date' => '',
            'end_date' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $where = [];

        $allowed_orderby = ['created_at', 'event_type', 'id'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = strtoupper((string) $args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $limit = max(1, min(200, (int) $args['limit']));
        $offset = max(0, (int) $args['offset']);

        if (!empty($args['event_type'])) {
            $where[] = $this->db->prepare(
                "event_type = %s",
                $this->normalize_event_type($args['event_type'], [])
            );
        }

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

        if (!empty($args['start_date'])) {
            $where[] = $this->db->prepare(
                "created_at >= %s",
                $args['start_date']
            );
        }

        if (!empty($args['end_date'])) {
            $where[] = $this->db->prepare(
                "created_at <= %s",
                $args['end_date']
            );
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "
            SELECT * FROM {$this->db->get_table('analytics')}
            {$where_clause}
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d
        ";

        $query = $this->db->prepare($query, $limit, $offset);
        $results = $this->db->get_results($query);

        // Get total count
        $count_query = "
            SELECT COUNT(*) FROM {$this->db->get_table('analytics')}
            {$where_clause}
        ";
        $total = $this->db->get_var($count_query);

        return [
            'data' => $results ?: [],
            'total' => (int) $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Dashboard summary for the Analytics admin page.
     *
     * @param int $days Number of days (7, 30, or 90).
     * @return array<string, mixed>
     */
    public function get_dashboard_summary($days = 30) {
        $days = in_array((int) $days, [7, 30, 90], true) ? (int) $days : 30;
        $summary = $this->get_summary($days);

        global $wpdb;
        $table = $wpdb->prefix . 'sch_analytics';
        $date = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        $page_views = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at > %s AND event_type IN ('page_view','view')",
                $date
            )
        );
        $sessions = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE created_at > %s AND session_id IS NOT NULL",
                $date
            )
        );
        $clicks = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at > %s AND event_type = 'click'",
                $date
            )
        );
        $conversions = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at > %s AND event_type = 'conversion'",
                $date
            )
        );
        $conversion_rate = $page_views > 0 ? round(($conversions / $page_views) * 100, 2) : 0.0;

        $top_links = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT link_id, COUNT(*) as count
                FROM {$table}
                WHERE created_at > %s AND link_id IS NOT NULL
                GROUP BY link_id
                ORDER BY count DESC
                LIMIT 5",
                $date
            ),
            ARRAY_A
        );

        $top_countries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT country, COUNT(*) as count
                FROM {$table}
                WHERE created_at > %s AND country IS NOT NULL AND country <> ''
                GROUP BY country
                ORDER BY count DESC
                LIMIT 10",
                $date
            ),
            ARRAY_A
        );

        $top_languages = [];
        if ($this->column_exists('language')) {
            $top_languages = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT language, COUNT(*) as count
                    FROM {$table}
                    WHERE created_at > %s AND language IS NOT NULL AND language <> ''
                    GROUP BY language
                    ORDER BY count DESC
                    LIMIT 10",
                    $date
                ),
                ARRAY_A
            );
        }

        $table_exists = $this->db->table_exists('analytics');

        return array_merge($summary, [
            'enabled' => $this->is_enabled(),
            'table_exists' => $table_exists,
            'page_views' => $page_views,
            'sessions' => $sessions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'conversion_rate' => $conversion_rate,
            'top_links' => $top_links ?: [],
            'top_countries' => $top_countries ?: [],
            'top_languages' => $top_languages ?: [],
            'period_days' => $days,
        ]);
    }

    /**
     * Detect the visitor's preferred language from Accept-Language.
     *
     * @return string Two-letter lowercase code, or empty string.
     */
    public function detect_visitor_language() {
        $header = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE']))
            : '';

        if ($header === '') {
            return '';
        }

        // Take the first language tag (e.g. "en-US,en;q=0.9" → "en").
        $parts = preg_split('/\s*,\s*/', $header);
        $primary = isset($parts[0]) ? strtolower(trim((string) $parts[0])) : '';
        $primary = preg_replace('/;.*$/', '', $primary);
        $code = substr((string) $primary, 0, 2);

        return preg_match('/^[a-z]{2}$/', $code) ? $code : '';
    }

    /**
     * Resolve visitor country code via geo lookup.
     *
     * @return string Two-letter uppercase country code, or empty string.
     */
    public function get_visitor_country_code() {
        $geo = $this->geolocate($this->get_client_ip());
        return isset($geo['country']) ? (string) $geo['country'] : '';
    }

    /**
     * Whether an analytics table column exists (cached per request).
     *
     * @param string $column Column name.
     * @return bool
     */
    private function column_exists($column) {
        static $cache = [];

        $column = sanitize_key($column);
        if (isset($cache[$column])) {
            return $cache[$column];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sch_analytics';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $exists = (bool) $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
        $cache[$column] = $exists;
        return $exists;
    }

    /**
     * Get analytics summary
     *
     * @param int $days Number of days
     * @return array
     */
    public function get_summary($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_analytics';
        $days = max(1, min(365, (int) $days));
        $date = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        if (!$this->db->table_exists('analytics')) {
            return [
                'total_events' => 0,
                'unique_visitors' => 0,
                'events_by_type' => [],
                'top_campaigns' => [],
                'top_offers' => [],
                'daily_stats' => [],
                'period_days' => $days,
            ];
        }

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

        // Top offers
        $top_offers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT offer_id, COUNT(*) as count 
                FROM {$table} 
                WHERE created_at > %s AND offer_id IS NOT NULL 
                GROUP BY offer_id 
                ORDER BY count DESC 
                LIMIT 5",
                $date
            ),
            ARRAY_A
        );

        // Daily stats
        $daily_stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT visitor_id) as unique_visitors,
                    SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) as page_views,
                    SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks,
                    SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) as conversions
                FROM {$table} 
                WHERE created_at > %s 
                GROUP BY DATE(created_at)
                ORDER BY date DESC",
                $date
            ),
            ARRAY_A
        );

        return [
            'total_events' => (int) $total_events,
            'unique_visitors' => (int) $unique_visitors,
            'events_by_type' => $events_by_type ?: [],
            'top_campaigns' => $top_campaigns ?: [],
            'top_offers' => $top_offers ?: [],
            'daily_stats' => $daily_stats ?: [],
            'period_days' => $days
        ];
    }

    /**
     * Get device type
     *
     * @return string
     */
    private function get_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($user_agent, 'Mobile') !== false) {
            return 'mobile';
        } elseif (strpos($user_agent, 'Tablet') !== false) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Check if request is from bot
     *
     * @return bool
     */
    private function is_bot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $bots = [
            'bot', 'crawler', 'spider', 'googlebot', 'bingbot', 'yandex',
            'baidu', 'duckduckbot', 'slurp', 'facebookexternalhit',
            'twitterbot', 'linkedinbot', 'pinterest', 'applebot'
        ];
        
        $user_agent = strtolower($user_agent);
        foreach ($bots as $bot) {
            if (strpos($user_agent, $bot) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Anonymize IP address
     *
     * @param string $ip IP address
     * @return string
     */
    private function anonymize_ip($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, strrpos($ip, ':')) . ':0000';
        }
        return $ip;
    }

    /**
     * Resolve the real client IP, honouring common proxy headers.
     *
     * @return string
     */
    private function get_client_ip() {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $value = sanitize_text_field(wp_unslash($_SERVER[$key]));
            // X-Forwarded-For can be a comma-separated list; the first entry is the client.
            if (strpos($value, ',') !== false) {
                $value = trim(explode(',', $value)[0]);
            }
            if (filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }
        return '';
    }

    /**
     * Look up country/region/city for an IP address, with caching.
     *
     * Results (including misses) are cached in a transient so redirects stay
     * fast and the provider's rate limit is respected.
     *
     * @param string $ip IP address.
     * @return array{country:string,region:string,city:string}
     */
    private function geolocate($ip) {
        $empty = ['country' => '', 'region' => '', 'city' => ''];

        if (!$this->get_option_bool('geo_tracking', true)) {
            return $empty;
        }

        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return $empty;
        }

        // Skip private / reserved ranges (localhost, LAN) — no public geo data.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $empty;
        }

        $cache_key = 'sch_geo_' . md5($ip);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return wp_parse_args($cached, $empty);
        }

        /**
         * Filter the resolved geolocation for an IP address.
         *
         * Return an array with country/region/city keys to short-circuit the
         * default remote lookup (for example, using a local GeoIP database).
         *
         * @param array|null $geo Pre-resolved geo data, or null to use the default provider.
         * @param string     $ip  IP address being resolved.
         */
        $geo = apply_filters('seo_campaign_hub_geolocate_ip', null, $ip);

        if (!is_array($geo)) {
            $geo = $this->geolocate_remote($ip);
        }

        $geo = wp_parse_args(is_array($geo) ? $geo : [], $empty);
        $geo['country'] = strtoupper(substr((string) $geo['country'], 0, 2));
        $geo['region'] = substr(sanitize_text_field((string) $geo['region']), 0, 100);
        $geo['city'] = substr(sanitize_text_field((string) $geo['city']), 0, 100);

        // Cache hits for a week; misses for an hour so transient failures retry sooner.
        set_transient($cache_key, $geo, $geo['country'] !== '' ? WEEK_IN_SECONDS : HOUR_IN_SECONDS);

        return $geo;
    }

    /**
     * Query the default geolocation provider (ip-api.com) for an IP address.
     *
     * @param string $ip IP address.
     * @return array
     */
    private function geolocate_remote($ip) {
        $response = wp_remote_get(
            'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,countryCode,regionName,city',
            ['timeout' => 3]
        );

        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            return [];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return [];
        }

        return [
            'country' => $data['countryCode'] ?? '',
            'region' => $data['regionName'] ?? '',
            'city' => $data['city'] ?? '',
        ];
    }

    /**
     * Normalize frontend payload aliases to schema fields.
     *
     * @param array $data Raw payload.
     * @return array
     */
    private function normalize_event_payload(array $data) {
        if (isset($data['seconds']) && !isset($data['time_on_page'])) {
            $data['time_on_page'] = $data['seconds'];
        }
        if (isset($data['depth']) && !isset($data['scroll_depth'])) {
            $data['scroll_depth'] = $data['depth'];
        }
        if (isset($data['url']) && !isset($data['landing_page'])) {
            $data['landing_page'] = $data['url'];
        }
        if (!empty($data['title']) && empty($data['event_name'])) {
            $data['event_name'] = $data['title'];
        }
        if (!empty($data['text']) && empty($data['meta_data']['link_text'])) {
            $data['meta_data'] = is_array($data['meta_data'] ?? null) ? $data['meta_data'] : [];
            $data['meta_data']['link_text'] = $data['text'];
        }
        return $data;
    }

    /**
     * Normalize event type to schema enum values.
     *
     * @param string $event_type Incoming type.
     * @param array  $data       Payload.
     * @return string|null
     */
    private function normalize_event_type($event_type, array $data = []) {
        $type = strtolower(trim((string) $event_type));
        $map = [
            'external_link' => 'click',
            'email_link' => 'click',
            'qr_scan' => 'click',
            'redirect' => 'click',
            'scroll_bottom' => 'scroll',
            'pageview' => 'page_view',
            'page-view' => 'page_view',
        ];

        if (isset($map[$type])) {
            $type = $map[$type];
        }

        // Preserve original name for normalized click/scroll variants.
        if (in_array($event_type, ['external_link', 'email_link', 'qr_scan', 'redirect', 'scroll_bottom'], true)
            && empty($data['event_name'])
        ) {
            // Caller may still pass event_name separately.
        }

        return $this->is_event_type_allowed($type) ? $type : null;
    }

    /**
     * @param string $type Event type.
     * @return bool
     */
    private function is_event_type_allowed($type) {
        return in_array($type, [
            'page_view', 'click', 'conversion', 'view', 'impression',
            'scroll', 'time_on_page', 'bounce', 'exit',
        ], true);
    }

    /**
     * @param mixed $id ID value.
     * @return int|null
     */
    private function nullable_id($id) {
        $id = intval($id);
        return $id > 0 ? $id : null;
    }

    /**
     * @param mixed $value UTM value.
     * @return string
     */
    private function sanitize_utm($value) {
        return sanitize_text_field(substr((string) $value, 0, 255));
    }

    /**
     * @param string $key Option key without prefix.
     * @param bool   $default Default value.
     * @return bool
     */
    private function get_option_bool($key, $default = true) {
        $nested = get_option('seo_campaign_hub_options', []);
        if (is_array($nested) && array_key_exists($key, $nested)) {
            return (bool) $nested[$key];
        }
        return (bool) get_option('seo_campaign_hub_' . $key, $default);
    }

    /**
     * Soft rate-limit for anonymous tracking posts.
     *
     * @return bool
     */
    private function allow_tracking_request() {
        if (is_user_logged_in()) {
            return true;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($ip === '') {
            return true;
        }

        $key = 'sch_track_rl_' . md5($ip);
        $count = (int) get_transient($key);
        if ($count > 300) {
            return false;
        }
        set_transient($key, $count + 1, HOUR_IN_SECONDS);
        return true;
    }

    /**
     * @param string $name Cookie name.
     * @param string $value Cookie value.
     * @param int    $ttl Seconds.
     * @return void
     */
    private function set_tracking_cookie($name, $value, $ttl) {
        if (headers_sent()) {
            return;
        }

        $options = [
            'expires' => time() + $ttl,
            'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
            'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        setcookie($name, $value, $options);
    }

    /**
     * @param string $value Candidate UUID.
     * @return bool
     */
    private function is_valid_uuid($value) {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        );
    }

    /**
     * Render analytics shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode($atts) {
        $type = isset($atts['type']) ? $atts['type'] : 'simple';
        $days = isset($atts['days']) ? intval($atts['days']) : 30;
        $campaign_id = isset($atts['campaign_id']) ? intval($atts['campaign_id']) : 0;

        $data = $this->get_summary($days);

        ob_start();
        ?>
        <div class="sch-analytics-shortcode">
            <?php if ($type === 'simple'): ?>
                <div class="sch-analytics-stats">
                    <div class="sch-stat">
                        <span class="stat-label"><?php esc_html_e('Visitors', 'seo-campaign-hub'); ?></span>
                        <span class="stat-number"><?php echo esc_html($data['unique_visitors']); ?></span>
                    </div>
                    <div class="sch-stat">
                        <span class="stat-label"><?php esc_html_e('Events', 'seo-campaign-hub'); ?></span>
                        <span class="stat-number"><?php echo esc_html($data['total_events']); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="sch-analytics-full">
                    <h3><?php esc_html_e('Analytics Summary', 'seo-campaign-hub'); ?></h3>
                    <div class="sch-analytics-grid">
                        <div class="sch-stat-box">
                            <span class="stat-number"><?php echo esc_html($data['unique_visitors']); ?></span>
                            <span class="stat-label"><?php esc_html_e('Unique Visitors', 'seo-campaign-hub'); ?></span>
                        </div>
                        <div class="sch-stat-box">
                            <span class="stat-number"><?php echo esc_html($data['total_events']); ?></span>
                            <span class="stat-label"><?php esc_html_e('Total Events', 'seo-campaign-hub'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
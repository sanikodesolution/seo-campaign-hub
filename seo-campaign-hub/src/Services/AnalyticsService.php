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
        $this->session_id = $this->get_session_id();
        $this->visitor_id = $this->get_visitor_id();
    }

    /**
     * Initialize analytics
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_seo_campaign_hub_track', [$this, 'ajax_track_event']);
        add_action('wp_ajax_nopriv_seo_campaign_hub_track', [$this, 'ajax_track_event']);
        add_action('wp_footer', [$this, 'add_tracking_script']);
        add_action('seo_campaign_hub_analytics_cron', [$this, 'process_analytics_cron']);
    }

    /**
     * Get session ID
     *
     * @return string
     */
    private function get_session_id() {
        if (!isset($_COOKIE['sch_session'])) {
            $session_id = wp_generate_uuid4();
            setcookie('sch_session', $session_id, time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
        } else {
            $session_id = $_COOKIE['sch_session'];
        }
        return $session_id;
    }

    /**
     * Get visitor ID
     *
     * @return string
     */
    private function get_visitor_id() {
        if (!isset($_COOKIE['sch_visitor'])) {
            $visitor_id = wp_generate_uuid4();
            setcookie('sch_visitor', $visitor_id, time() + 31536000, COOKIEPATH, COOKIE_DOMAIN);
        } else {
            $visitor_id = $_COOKIE['sch_visitor'];
        }
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
        // Check if analytics is enabled
        if (!get_option('seo_campaign_hub_enable_analytics', true)) {
            return false;
        }

        // Check if should ignore bots
        if (get_option('seo_campaign_hub_ignore_bots', true) && $this->is_bot()) {
            return false;
        }

        // Anonymize IP if enabled
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (get_option('seo_campaign_hub_anonymize_ip', true)) {
            $ip = $this->anonymize_ip($ip);
        }

        // Prepare event data
        $event = [
            'session_id' => $this->session_id,
            'visitor_id' => $this->visitor_id,
            'user_id' => get_current_user_id() ?: 0,
            'event_type' => $event_type,
            'event_name' => isset($data['event_name']) ? $data['event_name'] : '',
            'event_value' => isset($data['event_value']) ? floatval($data['event_value']) : 0,
            'campaign_id' => isset($data['campaign_id']) ? intval($data['campaign_id']) : 0,
            'offer_id' => isset($data['offer_id']) ? intval($data['offer_id']) : 0,
            'link_id' => isset($data['link_id']) ? intval($data['link_id']) : 0,
            'post_id' => isset($data['post_id']) ? intval($data['post_id']) : 0,
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'landing_page' => isset($data['landing_page']) ? $data['landing_page'] : '',
            'country' => isset($data['country']) ? $data['country'] : '',
            'region' => isset($data['region']) ? $data['region'] : '',
            'city' => isset($data['city']) ? $data['city'] : '',
            'device_type' => $this->get_device_type(),
            'device_brand' => isset($data['device_brand']) ? $data['device_brand'] : '',
            'device_model' => isset($data['device_model']) ? $data['device_model'] : '',
            'os' => isset($data['os']) ? $data['os'] : '',
            'os_version' => isset($data['os_version']) ? $data['os_version'] : '',
            'browser' => isset($data['browser']) ? $data['browser'] : '',
            'browser_version' => isset($data['browser_version']) ? $data['browser_version'] : '',
            'screen_resolution' => isset($data['screen_resolution']) ? $data['screen_resolution'] : '',
            'time_on_page' => isset($data['time_on_page']) ? intval($data['time_on_page']) : 0,
            'scroll_depth' => isset($data['scroll_depth']) ? intval($data['scroll_depth']) : 0,
            'load_time' => isset($data['load_time']) ? intval($data['load_time']) : 0,
            'conversion_id' => isset($data['conversion_id']) ? $data['conversion_id'] : '',
            'conversion_amount' => isset($data['conversion_amount']) ? floatval($data['conversion_amount']) : 0,
            'conversion_currency' => isset($data['conversion_currency']) ? $data['conversion_currency'] : 'USD',
            'utm_source' => isset($_GET['utm_source']) ? $_GET['utm_source'] : '',
            'utm_medium' => isset($_GET['utm_medium']) ? $_GET['utm_medium'] : '',
            'utm_campaign' => isset($_GET['utm_campaign']) ? $_GET['utm_campaign'] : '',
            'utm_term' => isset($_GET['utm_term']) ? $_GET['utm_term'] : '',
            'utm_content' => isset($_GET['utm_content']) ? $_GET['utm_content'] : '',
            'meta_data' => isset($data['meta_data']) ? wp_json_encode($data['meta_data']) : null
        ];

        // Insert into database
        return $this->db->insert('analytics', $event);
    }

    /**
     * AJAX track event
     *
     * @return void
     */
    public function ajax_track_event() {
        check_ajax_referer('seo_campaign_hub_public', 'nonce');

        $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
        $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : [];

        if (empty($event_type)) {
            wp_send_json_error(['message' => 'Event type is required']);
        }

        $result = $this->track_event($event_type, $data);

        if ($result) {
            wp_send_json_success(['message' => 'Event tracked successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to track event']);
        }
    }

    /**
     * Add tracking script to footer
     *
     * @return void
     */
    public function add_tracking_script() {
        if (!get_option('seo_campaign_hub_enable_analytics', true)) {
            return;
        }

        if (is_admin()) {
            return;
        }

        ?>
        <script>
            (function() {
                var sch = {
                    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                    nonce: '<?php echo wp_create_nonce('seo_campaign_hub_public'); ?>',
                    events: [],
                    
                    track: function(eventType, data) {
                        this.events.push({
                            type: eventType,
                            data: data || {},
                            time: Date.now()
                        });
                        
                        // Send batch every 5 seconds or when page unloads
                        if (this.events.length >= 10) {
                            this.sendBatch();
                        }
                    },
                    
                    sendBatch: function() {
                        if (this.events.length === 0) {
                            return;
                        }
                        
                        var events = this.events;
                        this.events = [];
                        
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', this.ajaxUrl, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        
                        var data = new URLSearchParams();
                        data.append('action', 'seo_campaign_hub_track');
                        data.append('nonce', this.nonce);
                        data.append('event_type', 'batch');
                        data.append('data', JSON.stringify(events));
                        
                        xhr.send(data.toString());
                    }
                };
                
                // Auto-send on page unload
                window.addEventListener('beforeunload', function() {
                    sch.sendBatch();
                });
                
                // Send every 5 seconds
                setInterval(function() {
                    sch.sendBatch();
                }, 5000);
                
                // Track page view
                sch.track('page_view', {
                    url: window.location.href,
                    title: document.title
                });
                
                // Track time on page
                var startTime = Date.now();
                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) {
                        var timeOnPage = Math.round((Date.now() - startTime) / 1000);
                        if (timeOnPage > 5) {
                            sch.track('time_on_page', {
                                seconds: timeOnPage,
                                url: window.location.href
                            });
                        }
                    }
                });
                
                // Track scroll depth
                var maxScroll = 0;
                var scrollTimeout;
                window.addEventListener('scroll', function() {
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(function() {
                        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
                        var scrollPercent = Math.round((scrollTop / docHeight) * 100);
                        
                        if (scrollPercent > maxScroll) {
                            maxScroll = scrollPercent;
                            if (maxScroll > 0 && maxScroll % 25 === 0) {
                                sch.track('scroll', {
                                    depth: maxScroll,
                                    url: window.location.href
                                });
                            }
                        }
                    }, 500);
                });
                
                // Track clicks on links
                document.addEventListener('click', function(e) {
                    var target = e.target;
                    while (target && target.tagName !== 'A') {
                        target = target.parentElement;
                    }
                    
                    if (target && target.href) {
                        var data = {
                            url: target.href,
                            text: target.textContent.trim()
                        };
                        
                        // Check if external link
                        if (target.href.indexOf(window.location.hostname) === -1) {
                            sch.track('external_link', data);
                        } else {
                            sch.track('click', data);
                        }
                    }
                });
                
                // Expose for other scripts
                window.seoCampaignHub = sch;
            })();
        </script>
        <?php
    }

    /**
     * Process analytics cron
     *
     * @return void
     */
    public function process_analytics_cron() {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_analytics';
        
        // Delete old data if retention is set
        $retention_days = get_option('seo_campaign_hub_analytics_retention_days', 90);
        if ($retention_days > 0) {
            $date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table} WHERE created_at < %s",
                    $date
                )
            );
        }
        
        // Update campaign and offer statistics
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

        if (!empty($args['event_type'])) {
            $where[] = $this->db->prepare(
                "event_type = %s",
                $args['event_type']
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
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d
        ";

        $query = $this->db->prepare($query, $args['limit'], $args['offset']);
        $results = $this->db->get_results($query);

        // Get total count
        $count_query = "
            SELECT COUNT(*) FROM {$this->db->get_table('analytics')}
            {$where_clause}
        ";
        $total = $this->db->get_var($count_query);

        return [
            'data' => $results,
            'total' => (int) $total,
            'limit' => $args['limit'],
            'offset' => $args['offset']
        ];
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
            'events_by_type' => $events_by_type,
            'top_campaigns' => $top_campaigns,
            'top_offers' => $top_offers,
            'daily_stats' => $daily_stats,
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
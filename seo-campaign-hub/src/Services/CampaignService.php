<?php
/**
 * Campaign Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CampaignService
 *
 * Handles campaign management operations
 */
class CampaignService {
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
     * Get campaign by ID
     *
     * @param int $id Campaign ID
     * @return object|null
     */
    public function get_campaign($id) {
        $cache_key = 'campaign_' . $id;
        $campaign = $this->cache->get($cache_key);

        if ($campaign === false) {
            $campaign = $this->db->get_row(
                $this->db->prepare(
                    "SELECT * FROM {$this->db->get_table('campaigns')} WHERE id = %d",
                    $id
                )
            );
            $this->cache->set($cache_key, $campaign, 'campaigns', 3600);
        }

        return $campaign;
    }

    /**
     * Get campaign by post ID
     *
     * @param int $post_id Post ID
     * @return object|null
     */
    public function get_campaign_by_post_id($post_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->get_table('campaigns')} WHERE post_id = %d",
                $post_id
            )
        );
    }

    /**
     * Get campaign by slug
     *
     * @param string $slug Campaign slug
     * @return object|null
     */
    public function get_campaign_by_slug($slug) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->get_table('campaigns')} WHERE slug = %s",
                $slug
            )
        );
    }

    /**
     * Get all campaigns
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_campaigns($args = []) {
        $defaults = [
            'status' => 'active',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $where = [];

        if (!empty($args['status'])) {
            $where[] = $this->db->prepare(
                "status = %s",
                $args['status']
            );
        }

        if (!empty($args['campaign_type'])) {
            $where[] = $this->db->prepare(
                "campaign_type = %s",
                $args['campaign_type']
            );
        }

        if (!empty($args['search'])) {
            $search = '%' . $this->db->escape($args['search']) . '%';
            $where[] = $this->db->prepare(
                "(title LIKE %s OR slug LIKE %s)",
                $search,
                $search
            );
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "
            SELECT * FROM {$this->db->get_table('campaigns')}
            {$where_clause}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d
        ";

        $query = $this->db->prepare($query, $args['limit'], $args['offset']);
        return $this->db->get_results($query);
    }

    /**
     * Create campaign
     *
     * @param array $data Campaign data
     * @return int|false
     */
    public function create_campaign($data) {
        // Validate data
        if (empty($data['title'])) {
            return false;
        }

        // Generate slug
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['title']);
        }

        // Generate campaign key
        $data['campaign_key'] = 'camp_' . uniqid();

        // Set defaults
        if (!isset($data['status'])) {
            $data['status'] = 'draft';
        }

        if (!isset($data['campaign_type'])) {
            $data['campaign_type'] = 'landing_page';
        }

        // Set created by
        $data['created_by'] = get_current_user_id();

        // Insert
        $result = $this->db->insert('campaigns', $data);

        if ($result) {
            $this->cache->clear('campaigns');
            do_action('seo_campaign_hub_campaign_created', $result, $data);
        }

        return $result;
    }

    /**
     * Update campaign
     *
     * @param int   $id Campaign ID
     * @param array $data Campaign data
     * @return int|false
     */
    public function update_campaign($id, $data) {
        $campaign = $this->get_campaign($id);

        if (!$campaign) {
            return false;
        }

        // Set modified by
        $data['modified_by'] = get_current_user_id();

        // Update
        $result = $this->db->update('campaigns', $data, ['id' => $id]);

        if ($result !== false) {
            $this->cache->delete('campaign_' . $id);
            $this->cache->clear('campaigns');
            do_action('seo_campaign_hub_campaign_updated', $id, $data);
        }

        return $result;
    }

    /**
     * Delete campaign
     *
     * @param int $id Campaign ID
     * @return bool
     */
    public function delete_campaign($id) {
        $campaign = $this->get_campaign($id);

        if (!$campaign) {
            return false;
        }

        // Delete associated post
        if (!empty($campaign->post_id)) {
            wp_delete_post($campaign->post_id, true);
        }

        // Delete from database
        $result = $this->db->delete('campaigns', ['id' => $id]);

        if ($result) {
            $this->cache->delete('campaign_' . $id);
            $this->cache->clear('campaigns');
            do_action('seo_campaign_hub_campaign_deleted', $id);
        }

        return $result;
    }

    /**
     * Get campaign stats
     *
     * @param int $id Campaign ID
     * @return array
     */
    public function get_campaign_stats($id) {
        $campaign = $this->get_campaign($id);

        if (!$campaign) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sch_analytics';

        $stats = [
            'views' => (int) $campaign->total_views,
            'unique_visitors' => (int) $campaign->unique_visitors,
            'clicks' => (int) $campaign->total_clicks,
            'conversions' => (int) $campaign->total_conversions,
            'conversion_rate' => (float) $campaign->conversion_rate
        ];

        // Get recent analytics
        $recent = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                WHERE campaign_id = %d 
                ORDER BY created_at DESC 
                LIMIT 10",
                $id
            )
        );

        $stats['recent_activity'] = $recent;

        return $stats;
    }

    /**
     * Increment campaign view count
     *
     * @param int $id Campaign ID
     * @return bool
     */
    public function increment_views($id) {
        return $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->db->get_table('campaigns')} 
                SET total_views = total_views + 1, 
                    unique_visitors = unique_visitors + 1 
                WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Increment campaign click count
     *
     * @param int $id Campaign ID
     * @return bool
     */
    public function increment_clicks($id) {
        return $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->db->get_table('campaigns')} 
                SET total_clicks = total_clicks + 1 
                WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Increment campaign conversion count
     *
     * @param int $id Campaign ID
     * @return bool
     */
    public function increment_conversions($id) {
        return $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->db->get_table('campaigns')} 
                SET total_conversions = total_conversions + 1,
                    conversion_rate = (total_conversions + 1) / total_views * 100
                WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Render campaign shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode($atts) {
        $id = isset($atts['id']) ? intval($atts['id']) : 0;
        $campaign = $this->get_campaign($id);

        if (!$campaign) {
            return '<p>' . __('Campaign not found.', 'seo-campaign-hub') . '</p>';
        }

        ob_start();
        ?>
        <div class="sch-campaign-shortcode">
            <h2><?php echo esc_html($campaign->title); ?></h2>
            <?php if (!empty($campaign->excerpt)): ?>
                <div class="sch-campaign-excerpt">
                    <?php echo wp_kses_post($campaign->excerpt); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($campaign->content)): ?>
                <div class="sch-campaign-content">
                    <?php echo apply_filters('the_content', $campaign->content); ?>
                </div>
            <?php endif; ?>
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
    public function render_counter($atts) {
        $target = isset($atts['target']) ? $atts['target'] : '';
        $start = isset($atts['start']) ? intval($atts['start']) : 0;
        $end = isset($atts['end']) ? intval($atts['end']) : 0;
        $prefix = isset($atts['prefix']) ? $atts['prefix'] : '';
        $suffix = isset($atts['suffix']) ? $atts['suffix'] : '';

        ob_start();
        ?>
        <div class="sch-counter-shortcode" data-start="<?php echo esc_attr($start); ?>" data-end="<?php echo esc_attr($end); ?>">
            <span class="sch-counter-prefix"><?php echo esc_html($prefix); ?></span>
            <span class="sch-counter-number"><?php echo esc_html($start); ?></span>
            <span class="sch-counter-suffix"><?php echo esc_html($suffix); ?></span>
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
    public function render_cta($atts) {
        $text = isset($atts['text']) ? $atts['text'] : __('Learn More', 'seo-campaign-hub');
        $url = isset($atts['url']) ? $atts['url'] : '#';
        $class = isset($atts['class']) ? $atts['class'] : 'btn-primary';

        ob_start();
        ?>
        <a href="<?php echo esc_url($url); ?>" class="sch-cta <?php echo esc_attr($class); ?>">
            <?php echo esc_html($text); ?>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Get campaign offers
     *
     * @param int $campaign_id Campaign ID
     * @return array
     */
    public function get_campaign_offers($campaign_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_campaign_offers';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE campaign_id = %d ORDER BY display_order ASC",
                $campaign_id
            )
        );
    }

    /**
     * Add offer to campaign
     *
     * @param int $campaign_id Campaign ID
     * @param int $offer_id Offer ID
     * @param array $data Additional data
     * @return int|false
     */
    public function add_offer_to_campaign($campaign_id, $offer_id, $data = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_campaign_offers';

        $insert_data = [
            'campaign_id' => $campaign_id,
            'offer_id' => $offer_id,
            'display_position' => isset($data['display_position']) ? intval($data['display_position']) : 0,
            'display_style' => isset($data['display_style']) ? $data['display_style'] : 'default',
            'display_order' => isset($data['display_order']) ? intval($data['display_order']) : 0,
            'is_primary' => isset($data['is_primary']) ? intval($data['is_primary']) : 0,
            'is_featured' => isset($data['is_featured']) ? intval($data['is_featured']) : 0,
            'rotation_weight' => isset($data['rotation_weight']) ? intval($data['rotation_weight']) : 1
        ];

        $result = $wpdb->insert($table, $insert_data);

        if ($result) {
            $this->cache->delete('campaign_offers_' . $campaign_id);
        }

        return $result;
    }

    /**
     * Remove offer from campaign
     *
     * @param int $campaign_id Campaign ID
     * @param int $offer_id Offer ID
     * @return bool
     */
    public function remove_offer_from_campaign($campaign_id, $offer_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_campaign_offers';

        $result = $wpdb->delete(
            $table,
            [
                'campaign_id' => $campaign_id,
                'offer_id' => $offer_id
            ]
        );

        if ($result) {
            $this->cache->delete('campaign_offers_' . $campaign_id);
        }

        return $result;
    }
}
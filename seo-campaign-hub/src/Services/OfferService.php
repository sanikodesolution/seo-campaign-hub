<?php
/**
 * Offer Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class OfferService
 *
 * Handles offer management operations
 */
class OfferService {
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
     * Get offer by ID
     *
     * @param int $id Offer ID
     * @return object|null
     */
    public function get_offer($id) {
        $cache_key = 'offer_' . $id;
        $offer = $this->cache->get($cache_key);

        if ($offer === false) {
            $offer = $this->db->get_row(
                $this->db->prepare(
                    "SELECT * FROM {$this->db->get_table('offers')} WHERE id = %d",
                    $id
                )
            );
            $this->cache->set($cache_key, $offer, 'offers', 3600);
        }

        return $offer;
    }

    /**
     * Get offer by post ID
     *
     * @param int $post_id Post ID
     * @return object|null
     */
    public function get_offer_by_post_id($post_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->get_table('offers')} WHERE post_id = %d",
                $post_id
            )
        );
    }

    /**
     * Get offer by slug
     *
     * @param string $slug Offer slug
     * @return object|null
     */
    public function get_offer_by_slug($slug) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->get_table('offers')} WHERE slug = %s",
                $slug
            )
        );
    }

    /**
     * Get all offers
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_offers($args = []) {
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

        if (!empty($args['offer_type'])) {
            $where[] = $this->db->prepare(
                "offer_type = %s",
                $args['offer_type']
            );
        }

        if (!empty($args['is_featured'])) {
            $where[] = $this->db->prepare(
                "is_featured = %d",
                intval($args['is_featured'])
            );
        }

        if (!empty($args['search'])) {
            $search = '%' . $this->db->escape($args['search']) . '%';
            $where[] = $this->db->prepare(
                "(title LIKE %s OR slug LIKE %s OR description LIKE %s)",
                $search,
                $search,
                $search
            );
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "
            SELECT * FROM {$this->db->get_table('offers')}
            {$where_clause}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d
        ";

        $query = $this->db->prepare($query, $args['limit'], $args['offset']);
        return $this->db->get_results($query);
    }

    /**
     * Get active offers
     *
     * @param int $limit Number of offers
     * @return array
     */
    public function get_active_offers($limit = 20) {
        return $this->get_offers([
            'status' => 'active',
            'limit' => $limit
        ]);
    }

    /**
     * Get featured offers
     *
     * @param int $limit Number of offers
     * @return array
     */
    public function get_featured_offers($limit = 10) {
        return $this->get_offers([
            'status' => 'active',
            'is_featured' => 1,
            'limit' => $limit
        ]);
    }

    /**
     * Create offer
     *
     * @param array $data Offer data
     * @return int|false
     */
    public function create_offer($data) {
        // Validate data
        if (empty($data['title'])) {
            return false;
        }

        if (empty($data['offer_type'])) {
            return false;
        }

        // Generate slug
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['title']);
        }

        // Generate offer key
        $data['offer_key'] = 'off_' . uniqid();

        // Set defaults
        if (!isset($data['status'])) {
            $data['status'] = 'draft';
        }

        if (!isset($data['currency'])) {
            $data['currency'] = 'USD';
        }

        // Set created by
        $data['created_by'] = get_current_user_id();

        // Insert
        $result = $this->db->insert('offers', $data);

        if ($result) {
            $this->cache->clear('offers');
            do_action('seo_campaign_hub_offer_created', $result, $data);
        }

        return $result;
    }

    /**
     * Update offer
     *
     * @param int   $id Offer ID
     * @param array $data Offer data
     * @return int|false
     */
    public function update_offer($id, $data) {
        $offer = $this->get_offer($id);

        if (!$offer) {
            return false;
        }

        // Set modified by
        $data['modified_by'] = get_current_user_id();

        // Update
        $result = $this->db->update('offers', $data, ['id' => $id]);

        if ($result !== false) {
            $this->cache->delete('offer_' . $id);
            $this->cache->clear('offers');
            do_action('seo_campaign_hub_offer_updated', $id, $data);
        }

        return $result;
    }

    /**
     * Delete offer
     *
     * @param int $id Offer ID
     * @return bool
     */
    public function delete_offer($id) {
        $offer = $this->get_offer($id);

        if (!$offer) {
            return false;
        }

        // Delete associated post
        if (!empty($offer->post_id)) {
            wp_delete_post($offer->post_id, true);
        }

        // Delete from database
        $result = $this->db->delete('offers', ['id' => $id]);

        if ($result) {
            $this->cache->delete('offer_' . $id);
            $this->cache->clear('offers');
            do_action('seo_campaign_hub_offer_deleted', $id);
        }

        return $result;
    }

    /**
     * Get offer stats
     *
     * @param int $id Offer ID
     * @return array
     */
    public function get_offer_stats($id) {
        $offer = $this->get_offer($id);

        if (!$offer) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sch_analytics';

        $stats = [
            'clicks' => (int) $offer->total_clicks,
            'unique_clicks' => (int) $offer->unique_clicks,
            'conversions' => (int) $offer->total_conversions,
            'conversion_rate' => (float) $offer->conversion_rate,
            'revenue' => (float) $offer->revenue
        ];

        // Get recent activity
        $recent = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                WHERE offer_id = %d 
                ORDER BY created_at DESC 
                LIMIT 10",
                $id
            )
        );

        $stats['recent_activity'] = $recent;

        return $stats;
    }

    /**
     * Increment offer click count
     *
     * @param int $id Offer ID
     * @return bool
     */
    public function increment_clicks($id) {
        return $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->db->get_table('offers')} 
                SET total_clicks = total_clicks + 1,
                    unique_clicks = unique_clicks + 1 
                WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Increment offer conversion count
     *
     * @param int   $id Offer ID
     * @param float $amount Conversion amount
     * @return bool
     */
    public function increment_conversions($id, $amount = 0) {
        $query = "UPDATE {$this->db->get_table('offers')} 
                  SET total_conversions = total_conversions + 1,
                      conversion_rate = (total_conversions + 1) / total_clicks * 100";

        if ($amount > 0) {
            $query .= ", revenue = revenue + " . floatval($amount);
        }

        $query .= $this->db->prepare(" WHERE id = %d", $id);

        return $this->db->query($query);
    }

    /**
     * Get offer destination URL
     *
     * @param int $id Offer ID
     * @return string
     */
    public function get_destination_url($id) {
        $offer = $this->get_offer($id);

        if (!$offer) {
            return '';
        }

        // Check for rotation
        if ($offer->rotation_enabled) {
            return $this->get_rotated_url($id);
        }

        // Check for affiliate URL
        if (!empty($offer->affiliate_url)) {
            return $offer->affiliate_url;
        }

        // Check for CPA URL
        if (!empty($offer->cpa_url)) {
            return $offer->cpa_url;
        }

        return $offer->destination_url;
    }

    /**
     * Get rotated URL for offer
     *
     * @param int $id Offer ID
     * @return string
     */
    private function get_rotated_url($id) {
        // Get all offers in rotation
        global $wpdb;
        $table = $wpdb->prefix . 'sch_campaign_offers';
        $offers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE offer_id = %d AND rotation_weight > 0",
                $id
            )
        );

        if (empty($offers)) {
            $offer = $this->get_offer($id);
            return $offer->destination_url;
        }

        // Random selection based on weight
        $total_weight = array_sum(array_column($offers, 'rotation_weight'));
        $random = mt_rand(1, $total_weight);
        $current_weight = 0;

        foreach ($offers as $offer_item) {
            $current_weight += $offer_item->rotation_weight;
            if ($random <= $current_weight) {
                return $offer_item->destination_url;
            }
        }

        // Fallback
        return $offers[0]->destination_url;
    }

    /**
     * Validate coupon code
     *
     * @param int    $id Offer ID
     * @param string $code Coupon code
     * @return bool
     */
    public function validate_coupon($id, $code) {
        $offer = $this->get_offer($id);

        if (!$offer || empty($offer->coupon_code)) {
            return false;
        }

        // Check if coupon matches
        if ($offer->coupon_code !== $code) {
            return false;
        }

        // Check if expired
        if (!empty($offer->coupon_expiry)) {
            $expiry = strtotime($offer->coupon_expiry);
            if ($expiry < time()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Render offer shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode($atts) {
        $id = isset($atts['id']) ? intval($atts['id']) : 0;
        $offer = $this->get_offer($id);

        if (!$offer) {
            return '<p>' . __('Offer not found.', 'seo-campaign-hub') . '</p>';
        }

        $destination_url = $this->get_destination_url($id);

        ob_start();
        ?>
        <div class="sch-offer-shortcode">
            <div class="sch-offer-inner">
                <?php if (!empty($offer->title)): ?>
                    <h3 class="sch-offer-title"><?php echo esc_html($offer->title); ?></h3>
                <?php endif; ?>

                <?php if (!empty($offer->short_description)): ?>
                    <div class="sch-offer-description">
                        <?php echo wp_kses_post($offer->short_description); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($offer->price)): ?>
                    <div class="sch-offer-price">
                        <?php if (!empty($offer->sale_price)): ?>
                            <span class="sale-price"><?php echo esc_html($offer->sale_price); ?></span>
                            <span class="regular-price"><?php echo esc_html($offer->price); ?></span>
                        <?php else: ?>
                            <span class="price"><?php echo esc_html($offer->price); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($offer->coupon_code)): ?>
                    <div class="sch-offer-coupon">
                        <strong><?php esc_html_e('Coupon:', 'seo-campaign-hub'); ?></strong>
                        <code><?php echo esc_html($offer->coupon_code); ?></code>
                    </div>
                <?php endif; ?>

                <div class="sch-offer-actions">
                    <a href="<?php echo esc_url($destination_url); ?>" 
                       class="sch-btn primary" 
                       target="_blank"
                       rel="nofollow sponsored">
                        <?php esc_html_e('Get Offer', 'seo-campaign-hub'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
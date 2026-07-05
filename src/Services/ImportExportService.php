<?php
/**
 * Import/Export Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ImportExportService
 *
 * Handles import and export operations
 */
class ImportExportService {
    /**
     * Database instance
     *
     * @var \SEO_Campaign_Hub\Database\Database
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new \SEO_Campaign_Hub\Database\Database();
    }

    /**
     * Export data
     *
     * @param string $type Export type (all, campaigns, offers, links, analytics, settings)
     * @param array  $args Export arguments
     * @return array|string
     */
    public function export_data($type = 'all', $args = []) {
        $data = [];

        switch ($type) {
            case 'campaigns':
                $data['campaigns'] = $this->export_campaigns($args);
                break;
            
            case 'offers':
                $data['offers'] = $this->export_offers($args);
                break;
            
            case 'links':
                $data['links'] = $this->export_links($args);
                break;
            
            case 'analytics':
                $data['analytics'] = $this->export_analytics($args);
                break;
            
            case 'settings':
                $data['settings'] = $this->export_settings();
                break;
            
            case 'all':
            default:
                $data = [
                    'campaigns' => $this->export_campaigns($args),
                    'offers' => $this->export_offers($args),
                    'links' => $this->export_links($args),
                    'settings' => $this->export_settings(),
                    'exported_at' => current_time('mysql'),
                    'version' => SEO_CAMPAIGN_HUB_VERSION
                ];
                break;
        }

        // Return as JSON
        return wp_json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Export campaigns
     *
     * @param array $args Export arguments
     * @return array
     */
    private function export_campaigns($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_campaigns';
        
        $where = [];
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare("status = %s", $args['status']);
        }
        if (!empty($args['campaign_type'])) {
            $where[] = $wpdb->prepare("campaign_type = %s", $args['campaign_type']);
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $campaigns = $wpdb->get_results(
            "SELECT * FROM {$table} {$where_clause} ORDER BY id ASC",
            ARRAY_A
        );

        // Get related offers
        $campaign_offers_table = $wpdb->prefix . 'sch_campaign_offers';
        foreach ($campaigns as &$campaign) {
            $campaign['offers'] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$campaign_offers_table} WHERE campaign_id = %d",
                    $campaign['id']
                ),
                ARRAY_A
            );
        }

        return $campaigns;
    }

    /**
     * Export offers
     *
     * @param array $args Export arguments
     * @return array
     */
    private function export_offers($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_offers';
        
        $where = [];
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare("status = %s", $args['status']);
        }
        if (!empty($args['offer_type'])) {
            $where[] = $wpdb->prepare("offer_type = %s", $args['offer_type']);
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return $wpdb->get_results(
            "SELECT * FROM {$table} {$where_clause} ORDER BY id ASC",
            ARRAY_A
        );
    }

    /**
     * Export links
     *
     * @param array $args Export arguments
     * @return array
     */
    private function export_links($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_links';
        
        $where = [];
        if (isset($args['is_active'])) {
            $where[] = $wpdb->prepare("is_active = %d", intval($args['is_active']));
        }
        if (!empty($args['link_type'])) {
            $where[] = $wpdb->prepare("link_type = %s", $args['link_type']);
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return $wpdb->get_results(
            "SELECT * FROM {$table} {$where_clause} ORDER BY id ASC",
            ARRAY_A
        );
    }

    /**
     * Export analytics
     *
     * @param array $args Export arguments
     * @return array
     */
    private function export_analytics($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_analytics';
        
        $where = [];
        if (!empty($args['start_date'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $args['start_date']);
        }
        if (!empty($args['end_date'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $args['end_date']);
        }
        if (!empty($args['event_type'])) {
            $where[] = $wpdb->prepare("event_type = %s", $args['event_type']);
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $limit = isset($args['limit']) ? 'LIMIT ' . intval($args['limit']) : '';
        
        return $wpdb->get_results(
            "SELECT * FROM {$table} {$where_clause} ORDER BY created_at DESC {$limit}",
            ARRAY_A
        );
    }

    /**
     * Export settings
     *
     * @return array
     */
    private function export_settings() {
        $settings = [];
        $options = [
            'seo_campaign_hub_version',
            'seo_campaign_hub_db_version',
            'seo_campaign_hub_options'
        ];

        foreach ($options as $option) {
            $settings[$option] = get_option($option);
        }

        return $settings;
    }

    /**
     * Import data
     *
     * @param string $data JSON data to import
     * @param array  $args Import arguments
     * @return array
     */
    public function import_data($data, $args = []) {
        $data = json_decode($data, true);
        
        if (!is_array($data)) {
            return [
                'success' => false,
                'message' => __('Invalid data format.', 'seo-campaign-hub')
            ];
        }

        $results = [
            'imported' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Import campaigns
        if (isset($data['campaigns'])) {
            $this->import_campaigns($data['campaigns'], $args, $results);
        }

        // Import offers
        if (isset($data['offers'])) {
            $this->import_offers($data['offers'], $args, $results);
        }

        // Import links
        if (isset($data['links'])) {
            $this->import_links($data['links'], $args, $results);
        }

        // Import settings
        if (isset($data['settings'])) {
            $this->import_settings($data['settings'], $results);
        }

        return $results;
    }

    /**
     * Import campaigns
     *
     * @param array $campaigns Campaigns to import
     * @param array $args Import arguments
     * @param array $results Results reference
     * @return void
     */
    private function import_campaigns($campaigns, $args, &$results) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_campaigns';
        $post_type = 'sch_campaign';

        foreach ($campaigns as $campaign) {
            // Check if campaign already exists by key
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE campaign_key = %s",
                    $campaign['campaign_key']
                )
            );

            if ($exists) {
                // Update existing campaign
                $campaign_data = $campaign;
                unset($campaign_data['id']);
                unset($campaign_data['created_at']);
                unset($campaign_data['updated_at']);
                
                $result = $wpdb->update($table, $campaign_data, ['id' => $exists]);
                if ($result !== false) {
                    $results['imported']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = sprintf(
                        __('Failed to update campaign: %s', 'seo-campaign-hub'),
                        $campaign['title']
                    );
                }
                continue;
            }

            // Create WordPress post
            $post_data = [
                'post_title' => $campaign['title'],
                'post_content' => $campaign['content'] ?? '',
                'post_excerpt' => $campaign['excerpt'] ?? '',
                'post_status' => $campaign['status'] ?? 'draft',
                'post_type' => $post_type
            ];

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    __('Failed to create post for campaign: %s', 'seo-campaign-hub'),
                    $campaign['title']
                );
                continue;
            }

            // Insert campaign data
            $campaign_data = $campaign;
            $campaign_data['post_id'] = $post_id;
            unset($campaign_data['id']);
            unset($campaign_data['created_at']);
            unset($campaign_data['updated_at']);

            $result = $wpdb->insert($table, $campaign_data);

            if ($result) {
                $results['imported']++;
                
                // Import campaign offers if present
                if (!empty($campaign['offers'])) {
                    $this->import_campaign_offers($campaign['offers'], $wpdb->insert_id);
                }
            } else {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    __('Failed to insert campaign: %s', 'seo-campaign-hub'),
                    $campaign['title']
                );
            }
        }
    }

    /**
     * Import campaign offers
     *
     * @param array $offers Offers to import
     * @param int   $campaign_id Campaign ID
     * @return void
     */
    private function import_campaign_offers($offers, $campaign_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_campaign_offers';

        foreach ($offers as $offer) {
            $offer['campaign_id'] = $campaign_id;
            unset($offer['id']);
            unset($offer['created_at']);
            unset($offer['updated_at']);

            $wpdb->insert($table, $offer);
        }
    }

    /**
     * Import offers
     *
     * @param array $offers Offers to import
     * @param array $args Import arguments
     * @param array $results Results reference
     * @return void
     */
    private function import_offers($offers, $args, &$results) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_offers';
        $post_type = 'sch_offer';

        foreach ($offers as $offer) {
            // Check if offer already exists by key
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE offer_key = %s",
                    $offer['offer_key']
                )
            );

            if ($exists) {
                // Update existing offer
                $offer_data = $offer;
                unset($offer_data['id']);
                unset($offer_data['created_at']);
                unset($offer_data['updated_at']);
                
                $result = $wpdb->update($table, $offer_data, ['id' => $exists]);
                if ($result !== false) {
                    $results['imported']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = sprintf(
                        __('Failed to update offer: %s', 'seo-campaign-hub'),
                        $offer['title']
                    );
                }
                continue;
            }

            // Create WordPress post
            $post_data = [
                'post_title' => $offer['title'],
                'post_content' => $offer['description'] ?? '',
                'post_excerpt' => $offer['short_description'] ?? '',
                'post_status' => $offer['status'] ?? 'draft',
                'post_type' => $post_type
            ];

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    __('Failed to create post for offer: %s', 'seo-campaign-hub'),
                    $offer['title']
                );
                continue;
            }

            // Insert offer data
            $offer_data = $offer;
            $offer_data['post_id'] = $post_id;
            unset($offer_data['id']);
            unset($offer_data['created_at']);
            unset($offer_data['updated_at']);

            $result = $wpdb->insert($table, $offer_data);

            if ($result) {
                $results['imported']++;
            } else {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    __('Failed to insert offer: %s', 'seo-campaign-hub'),
                    $offer['title']
                );
            }
        }
    }

    /**
     * Import links
     *
     * @param array $links Links to import
     * @param array $args Import arguments
     * @param array $results Results reference
     * @return void
     */
    private function import_links($links, $args, &$results) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_links';

        foreach ($links as $link) {
            // Check if link already exists by key
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE link_key = %s",
                    $link['link_key']
                )
            );

            if ($exists) {
                // Update existing link
                $link_data = $link;
                unset($link_data['id']);
                unset($link_data['created_at']);
                unset($link_data['updated_at']);
                
                $result = $wpdb->update($table, $link_data, ['id' => $exists]);
                if ($result !== false) {
                    $results['imported']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = sprintf(
                        __('Failed to update link: %s', 'seo-campaign-hub'),
                        $link['slug']
                    );
                }
                continue;
            }

            // Insert link data
            $link_data = $link;
            unset($link_data['id']);
            unset($link_data['created_at']);
            unset($link_data['updated_at']);

            $result = $wpdb->insert($table, $link_data);

            if ($result) {
                $results['imported']++;
            } else {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    __('Failed to insert link: %s', 'seo-campaign-hub'),
                    $link['slug']
                );
            }
        }
    }

    /**
     * Import settings
     *
     * @param array $settings Settings to import
     * @param array $results Results reference
     * @return void
     */
    private function import_settings($settings, &$results) {
        foreach ($settings as $key => $value) {
            if (strpos($key, 'seo_campaign_hub_') === 0) {
                update_option($key, $value);
            }
        }
        $results['imported']++;
    }

    /**
     * Generate export file
     *
     * @param string $data Data to export
     * @param string $format File format (json, csv)
     * @param string $filename Custom filename
     * @return string|false
     */
    public function generate_export_file($data, $format = 'json', $filename = '') {
        $export_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'uploads/exports/';
        
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        if (empty($filename)) {
            $filename = 'export_' . date('Y-m-d_H-i-s');
        }

        $filepath = $export_dir . $filename . '.' . $format;

        switch ($format) {
            case 'json':
                file_put_contents($filepath, $data);
                break;
            
            case 'csv':
                $this->convert_to_csv($data, $filepath);
                break;
            
            default:
                return false;
        }

        return $filepath;
    }

    /**
     * Convert data to CSV
     *
     * @param string $data JSON data
     * @param string $filepath Output file path
     * @return void
     */
    private function convert_to_csv($data, $filepath) {
        $data = json_decode($data, true);
        
        if (!is_array($data)) {
            return;
        }

        $handle = fopen($filepath, 'w');

        // Flatten nested arrays
        $flat_data = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $flat_data[] = array_merge(['type' => $key], $item);
                    }
                }
            } else {
                $flat_data[] = ['type' => $key, 'value' => $value];
            }
        }

        if (!empty($flat_data)) {
            // Write headers
            $headers = array_keys($flat_data[0]);
            fputcsv($handle, $headers);

            // Write data
            foreach ($flat_data as $row) {
                fputcsv($handle, $row);
            }
        }

        fclose($handle);
    }

    /**
     * Get import/export logs
     *
     * @param int $limit Number of logs
     * @return array
     */
    public function get_logs($limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_export_logs';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Log import/export activity
     *
     * @param string $type Log type (import, export)
     * @param string $format File format
     * @param int    $records Number of records
     * @param string $status Status (success, failed)
     * @param array  $meta Additional metadata
     * @return int|false
     */
    public function log_activity($type, $format, $records, $status = 'success', $meta = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_export_logs';

        $data = [
            'log_key' => 'log_' . uniqid(),
            'export_type' => $type,
            'format' => $format,
            'total_records' => $records,
            'exported_records' => $records,
            'status' => $status,
            'meta_data' => wp_json_encode($meta),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];

        if ($status === 'completed') {
            $data['completed_at'] = current_time('mysql');
        }

        return $wpdb->insert($table, $data);
    }
}
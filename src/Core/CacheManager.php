<?php
/**
 * Cache Manager
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CacheManager
 *
 * Handles caching operations for the plugin
 */
class CacheManager {
    /**
     * Cache enabled status
     *
     * @var bool
     */
    private $enabled = true;

    /**
     * Cache group
     *
     * @var string
     */
    private $cache_group = 'seo_campaign_hub';

    /**
     * Initialize cache features
     *
     * @return void
     */
    public function init() {
        $this->enabled = apply_filters('seo_campaign_hub_cache_enabled', $this->enabled);
        
        if ($this->enabled) {
            add_action('seo_campaign_hub_clear_cache', [$this, 'clear_all']);
            add_action('seo_campaign_hub_after_campaign_save', [$this, 'clear_campaign_cache']);
            add_action('seo_campaign_hub_after_offer_save', [$this, 'clear_offer_cache']);
        }
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @param string $group Cache group
     * @return mixed
     */
    public function get($key, $group = '') {
        if (!$this->enabled) {
            return false;
        }

        $group = $group ?: $this->cache_group;
        return wp_cache_get($key, $group);
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed  $data Data to cache
     * @param string $group Cache group
     * @param int    $expiration Cache expiration in seconds
     * @return bool
     */
    public function set($key, $data, $group = '', $expiration = 3600) {
        if (!$this->enabled) {
            return false;
        }

        $group = $group ?: $this->cache_group;
        return wp_cache_set($key, $data, $group, $expiration);
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @param string $group Cache group
     * @return bool
     */
    public function delete($key, $group = '') {
        $group = $group ?: $this->cache_group;
        return wp_cache_delete($key, $group);
    }

    /**
     * Clear cache group
     *
     * @param string $group Cache group
     * @return bool
     */
    public function clear($group = '') {
        $group = $group ?: $this->cache_group;
        return wp_cache_flush_group($group);
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public function clear_all() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_seo_campaign_hub_%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_seo_campaign_hub_%'
            )
        );
        
        // Clear object cache
        wp_cache_flush_group($this->cache_group);
    }

    /**
     * Clear campaign cache
     *
     * @param int $campaign_id Campaign ID
     * @return void
     */
    public function clear_campaign_cache($campaign_id) {
        $this->delete('campaign_' . $campaign_id);
        $this->delete('campaign_list');
        wp_cache_flush_group('campaigns');
    }

    /**
     * Clear offer cache
     *
     * @param int $offer_id Offer ID
     * @return void
     */
    public function clear_offer_cache($offer_id) {
        $this->delete('offer_' . $offer_id);
        $this->delete('offer_list');
        wp_cache_flush_group('offers');
    }

    /**
     * Check if cache is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        return $this->enabled;
    }

    /**
     * Disable cache
     *
     * @return void
     */
    public function disable() {
        $this->enabled = false;
    }

    /**
     * Enable cache
     *
     * @return void
     */
    public function enable() {
        $this->enabled = true;
    }

    /**
     * Get cache group
     *
     * @return string
     */
    public function get_cache_group() {
        return $this->cache_group;
    }

    /**
     * Set cache group
     *
     * @param string $group Cache group
     * @return void
     */
    public function set_cache_group($group) {
        $this->cache_group = $group;
    }

    /**
     * Cache a callback result
     *
     * @param callable $callback Callback function
     * @param array    $args Callback arguments
     * @param string   $key Cache key
     * @param int      $expiration Cache expiration
     * @return mixed
     */
    public function cache_callback($callback, $args, $key, $expiration = 3600) {
        $cached = $this->get($key);
        
        if ($cached !== false) {
            return $cached;
        }

        $result = call_user_func_array($callback, $args);
        $this->set($key, $result, '', $expiration);
        return $result;
    }

    /**
     * Get transient cache
     *
     * @param string $key Cache key
     * @return mixed
     */
    public function get_transient($key) {
        return get_transient('seo_campaign_hub_' . $key);
    }

    /**
     * Set transient cache
     *
     * @param string $key Cache key
     * @param mixed  $data Data to cache
     * @param int    $expiration Cache expiration in seconds
     * @return bool
     */
    public function set_transient($key, $data, $expiration = 3600) {
        return set_transient('seo_campaign_hub_' . $key, $data, $expiration);
    }

    /**
     * Delete transient cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete_transient($key) {
        return delete_transient('seo_campaign_hub_' . $key);
    }

    /**
     * Clear all transients
     *
     * @return void
     */
    public function clear_transients() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_seo_campaign_hub_%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_seo_campaign_hub_%'
            )
        );
    }
}
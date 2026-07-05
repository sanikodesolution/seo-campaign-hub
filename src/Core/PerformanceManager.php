<?php
/**
 * Performance Manager
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PerformanceManager
 *
 * Handles performance optimization for the plugin
 */
class PerformanceManager {
    /**
     * Start time for performance tracking
     *
     * @var float
     */
    private $start_time;

    /**
     * Start memory for performance tracking
     *
     * @var int
     */
    private $start_memory;

    /**
     * Initialize performance features
     *
     * @return void
     */
    public function init() {
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage();

        add_action('shutdown', [$this, 'log_performance']);
        add_filter('seo_campaign_hub_should_cache', [$this, 'should_cache']);
        add_action('seo_campaign_hub_performance_check', [$this, 'check_performance']);
    }

    /**
     * Log performance metrics
     *
     * @return void
     */
    public function log_performance() {
        $time = microtime(true) - $this->start_time;
        $memory = memory_get_usage() - $this->start_memory;

        if ($time > 5) {
            $this->log_slow_query($time, $memory);
        }
    }

    /**
     * Log slow queries
     *
     * @param float $time Execution time
     * @param int   $memory Memory usage
     * @return void
     */
    private function log_slow_query($time, $memory) {
        $log = sprintf(
            'Slow operation detected: %s seconds, %s bytes memory used',
            round($time, 3),
            number_format($memory)
        );
        error_log('SEO Campaign Hub: ' . $log);
    }

    /**
     * Check if caching should be enabled
     *
     * @return bool
     */
    public function should_cache() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return false;
        }
        return true;
    }

    /**
     * Check performance of queries
     *
     * FIX: $wpdb->queries is only populated when SAVEQUERIES is defined
     * and true — on a normal production site (where SAVEQUERIES is off
     * by default) this previously ran array_filter() over an always-empty
     * array, silently doing nothing while still being called on every
     * 'seo_campaign_hub_performance_check' hook. Added an explicit guard
     * so this is a deliberate no-op rather than a silent dead path.
     *
     * @return void
     */
    public function check_performance() {
        if (!defined('SAVEQUERIES') || !SAVEQUERIES) {
            return;
        }

        global $wpdb;
        $queries = $wpdb->queries;
        $slow_queries = array_filter($queries, function($query) {
            return $query[1] > 0.5;
        });

        if (!empty($slow_queries)) {
            foreach ($slow_queries as $query) {
                error_log(sprintf(
                    'SEO Campaign Hub Slow Query: %s (%s seconds)',
                    $query[0],
                    round($query[1], 3)
                ));
            }
        }
    }

    /**
     * Get execution time
     *
     * @return float
     */
    public function get_execution_time() {
        return microtime(true) - $this->start_time;
    }

    /**
     * Get memory usage
     *
     * @return int
     */
    public function get_memory_usage() {
        return memory_get_usage() - $this->start_memory;
    }

    /**
     * Optimize image
     *
     * @param string $image_path Image path
     * @param int    $quality Quality (1-100)
     * @return string
     */
    public function optimize_image($image_path, $quality = 80) {
        if (!function_exists('wp_get_image_editor')) {
            return $image_path;
        }

        $editor = wp_get_image_editor($image_path);
        if (!is_wp_error($editor)) {
            $editor->set_quality($quality);
            $editor->save($image_path);
        }

        return $image_path;
    }

    /**
     * Minify CSS
     *
     * @param string $css CSS content
     * @return string
     */
    public function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        // Remove unnecessary semicolons
        $css = preg_replace('/;}/', '}', $css);
        return trim($css);
    }

    /**
     * Minify JavaScript
     *
     * NOTE: This is a naive regex-based minifier. It will incorrectly
     * strip "//" sequences that appear inside strings or URLs (e.g.
     * "https://example.com") and can break JS relying on automatic
     * semicolon insertion. Safe only for trivial inline snippets — if
     * your build process already minifies bundled assets, prefer that
     * and avoid running this on real-world JS files.
     *
     * @param string $js JavaScript content
     * @return string
     */
    public function minify_js($js) {
        // Remove comments
        $js = preg_replace('/\/\/.*?$/m', '', $js);
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        return trim($js);
    }

    /**
     * Defer JavaScript
     *
     * @param string $src Script source
     * @return string
     */
    public function defer_script($src) {
        if (strpos($src, 'seo-campaign-hub') !== false) {
            return str_replace(' src', ' defer src', $src);
        }
        return $src;
    }

    /**
     * Lazy load images
     *
     * @param string $content Content to process
     * @return string
     */
    public function lazy_load_images($content) {
        if (strpos($content, '<img') === false) {
            return $content;
        }

        $content = preg_replace(
            '/<img\s+([^>]*?)src=(["\'])([^"\']+)\\2([^>]*)>/i',
            '<img $1src="$3" loading="lazy" $4>',
            $content
        );

        return $content;
    }

    /**
     * Cache database query results
     *
     * @param string $query SQL query
     * @param array  $results Query results
     * @param int    $expiration Cache expiration time
     * @return void
     */
    public function cache_query_results($query, $results, $expiration = 3600) {
        $key = 'seo_campaign_hub_query_' . md5($query);
        wp_cache_set($key, $results, 'seo_campaign_hub', $expiration);
    }

    /**
     * Get cached query results
     *
     * @param string $query SQL query
     * @return mixed
     */
    public function get_cached_query_results($query) {
        $key = 'seo_campaign_hub_query_' . md5($query);
        return wp_cache_get($key, 'seo_campaign_hub');
    }

    /**
     * Clear cached query results
     *
     * @param string $query SQL query
     * @return void
     */
    public function clear_cached_query_results($query) {
        $key = 'seo_campaign_hub_query_' . md5($query);
        wp_cache_delete($key, 'seo_campaign_hub');
    }

    /**
     * Enable object caching for WordPress
     *
     * @return void
     */
    public function enable_object_caching() {
        if (!defined('WP_CACHE')) {
            define('WP_CACHE', true);
        }
    }
}

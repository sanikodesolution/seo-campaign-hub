<?php
/**
 * Security Manager
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SecurityManager
 *
 * Handles security features for the plugin
 */
class SecurityManager {
    /**
     * Initialize security features
     *
     * @return void
     */
    public function init() {
        add_action('init', [$this, 'check_rate_limit']);
        add_filter('http_request_args', [$this, 'block_unauthorized_requests'], 10, 2);
        add_action('wp_login_failed', [$this, 'log_failed_login']);
    }

    /**
     * Check rate limiting
     *
     * @return void
     */
    public function check_rate_limit() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = 'seo_campaign_hub_rate_limit_' . md5($ip);
        $attempts = get_transient($key) ?: 0;

        if ($attempts > 100) {
            wp_die(
                esc_html__('Rate limit exceeded. Please try again later.', 'seo-campaign-hub'),
                esc_html__('Rate Limit Exceeded', 'seo-campaign-hub'),
                ['response' => 429]
            );
        }

        set_transient($key, $attempts + 1, 3600);
    }

    /**
     * Block unauthorized requests
     *
     * @param array  $args Request arguments
     * @param string $url Request URL
     * @return array
     */
    public function block_unauthorized_requests($args, $url) {
        if (strpos($url, 'seo-campaign-hub.com') !== false) {
            $args['timeout'] = 30;
            $args['sslverify'] = true;
        }
        return $args;
    }

    /**
     * Log failed login attempts
     *
     * @param string $username Username
     * @return void
     */
    public function log_failed_login($username) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        error_log(sprintf(
            'SEO Campaign Hub: Failed login attempt - Username: %s, IP: %s, Time: %s',
            $username,
            $ip,
            current_time('mysql')
        ));
    }

    /**
     * Verify nonce
     *
     * @param string $nonce Nonce value
     * @param string $action Action name
     * @return bool
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Verify user capability
     *
     * @param string $capability Capability name
     * @return bool
     */
    public function verify_capability($capability = 'manage_options') {
        return current_user_can($capability);
    }

    /**
     * Sanitize input data
     *
     * @param mixed $input Input data
     * @return mixed
     */
    public function sanitize_input($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize_input'], $input);
        }
        return sanitize_text_field($input);
    }

    /**
     * Sanitize HTML input
     *
     * @param string $input HTML input
     * @return string
     */
    public function sanitize_html($input) {
        return wp_kses_post($input);
    }

    /**
     * Sanitize URL
     *
     * @param string $url URL to sanitize
     * @return string
     */
    public function sanitize_url($url) {
        return esc_url_raw($url);
    }

    /**
     * Escape output
     *
     * @param string $output Output to escape
     * @return string
     */
    public function escape_output($output) {
        return esc_html($output);
    }

    /**
     * Escape attribute
     *
     * @param string $attr Attribute to escape
     * @return string
     */
    public function escape_attr($attr) {
        return esc_attr($attr);
    }

    /**
     * Escape URL
     *
     * @param string $url URL to escape
     * @return string
     */
    public function escape_url($url) {
        return esc_url($url);
    }

    /**
     * Escape JavaScript
     *
     * @param string $js JavaScript to escape
     * @return string
     */
    public function escape_js($js) {
        return esc_js($js);
    }

    /**
     * Generate secure random string
     *
     * @param int $length Length of string
     * @return string
     */
    public function generate_secure_string($length = 32) {
        return wp_generate_password($length, true, true);
    }

    /**
     * Hash data
     *
     * @param string $data Data to hash
     * @return string
     */
    public function hash_data($data) {
        return wp_hash($data);
    }

    /**
     * Check if request is from admin
     *
     * @return bool
     */
    public function is_admin_request() {
        return is_admin();
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public function is_ajax_request() {
        return wp_doing_ajax();
    }

    /**
     * Check if request is REST API
     *
     * @return bool
     */
    public function is_rest_request() {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Validate IP address
     *
     * @param string $ip IP address
     * @return bool
     */
    public function validate_ip($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return $this->validate_ip($ip) ? $ip : '';
    }

    /**
     * Generate nonce
     *
     * @param string $action Action name
     * @return string
     */
    public function create_nonce($action = '-1') {
        return wp_create_nonce($action);
    }
}
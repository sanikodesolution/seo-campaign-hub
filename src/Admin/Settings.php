<?php
/**
 * Settings Manager
 *
 * @package SEO_Campaign_Hub\Admin
 */

namespace SEO_Campaign_Hub\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings
 *
 * Handles plugin settings
 */
class Settings {
    /**
     * Options array
     *
     * @var array
     */
    private $options = [];

    /**
     * Settings sections
     *
     * @var array
     */
    private $sections = [];

    /**
     * Settings fields
     *
     * @var array
     */
    private $fields = [];

    /**
     * Initialize settings
     *
     * @return void
     */
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
        $this->load_options();
        $this->define_sections();
        $this->define_fields();
    }

    /**
     * Load options
     *
     * @return void
     */
    public function load_options() {
        $this->options = get_option('seo_campaign_hub_options', []);
    }

    /**
     * Define settings sections
     *
     * @return void
     */
    private function define_sections() {
        $this->sections = [
            'general' => [
                'title' => __('General Settings', 'seo-campaign-hub'),
                'description' => __('Configure general plugin settings.', 'seo-campaign-hub')
            ],
            'seo' => [
                'title' => __('SEO Settings', 'seo-campaign-hub'),
                'description' => __('Configure SEO optimization settings.', 'seo-campaign-hub')
            ],
            'analytics' => [
                'title' => __('Analytics Settings', 'seo-campaign-hub'),
                'description' => __('Configure analytics tracking.', 'seo-campaign-hub')
            ],
            'url_shortener' => [
                'title' => __('URL Shortener Settings', 'seo-campaign-hub'),
                'description' => __('Configure URL shortening features.', 'seo-campaign-hub')
            ],
            'qr_codes' => [
                'title' => __('QR Code Settings', 'seo-campaign-hub'),
                'description' => __('Configure QR code generation.', 'seo-campaign-hub')
            ],
            'advanced' => [
                'title' => __('Advanced Settings', 'seo-campaign-hub'),
                'description' => __('Advanced plugin settings.', 'seo-campaign-hub')
            ]
        ];
    }

    /**
     * Define settings fields
     *
     * @return void
     */
    private function define_fields() {
        $this->fields = [
            // General Settings
            'enable_plugin' => [
                'section' => 'general',
                'type' => 'checkbox',
                'title' => __('Enable Plugin', 'seo-campaign-hub'),
                'description' => __('Enable or disable the plugin functionality.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'default_redirect_type' => [
                'section' => 'general',
                'type' => 'select',
                'title' => __('Default Redirect Type', 'seo-campaign-hub'),
                'description' => __('Select the default redirect type for links.', 'seo-campaign-hub'),
                'options' => [
                    '301' => '301 - Permanent',
                    '302' => '302 - Temporary',
                    '307' => '307 - Temporary (same method)'
                ],
                'default' => '301'
            ],

            // SEO Settings
            'enable_schema' => [
                'section' => 'seo',
                'type' => 'checkbox',
                'title' => __('Enable Schema Markup', 'seo-campaign-hub'),
                'description' => __('Automatically add schema markup to campaign pages.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'enable_meta_tags' => [
                'section' => 'seo',
                'type' => 'checkbox',
                'title' => __('Enable Meta Tags', 'seo-campaign-hub'),
                'description' => __('Automatically add meta tags to campaign pages.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'enable_open_graph' => [
                'section' => 'seo',
                'type' => 'checkbox',
                'title' => __('Enable Open Graph', 'seo-campaign-hub'),
                'description' => __('Add Open Graph tags for social sharing.', 'seo-campaign-hub'),
                'default' => '1'
            ],

            // Analytics Settings
            'enable_analytics' => [
                'section' => 'analytics',
                'type' => 'checkbox',
                'title' => __('Enable Analytics', 'seo-campaign-hub'),
                'description' => __('Enable analytics tracking.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'anonymize_ip' => [
                'section' => 'analytics',
                'type' => 'checkbox',
                'title' => __('Anonymize IP Addresses', 'seo-campaign-hub'),
                'description' => __('Anonymize IP addresses for GDPR compliance.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'ignore_bots' => [
                'section' => 'analytics',
                'type' => 'checkbox',
                'title' => __('Ignore Bots', 'seo-campaign-hub'),
                'description' => __('Do not track traffic from bots and crawlers.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'track_page_views' => [
                'section' => 'analytics',
                'type' => 'checkbox',
                'title' => __('Track Page Views', 'seo-campaign-hub'),
                'description' => __('Track page views for campaigns.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'track_clicks' => [
                'section' => 'analytics',
                'type' => 'checkbox',
                'title' => __('Track Clicks', 'seo-campaign-hub'),
                'description' => __('Track clicks on offers and links.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'track_conversions' => [
                'section' => 'analytics',
                'type' => 'checkbox',
                'title' => __('Track Conversions', 'seo-campaign-hub'),
                'description' => __('Track conversion events.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'analytics_retention_days' => [
                'section' => 'analytics',
                'type' => 'number',
                'title' => __('Data Retention (Days)', 'seo-campaign-hub'),
                'description' => __('Number of days to keep analytics data.', 'seo-campaign-hub'),
                'default' => '90',
                'min' => '30',
                'max' => '365'
            ],

            // URL Shortener Settings
            'enable_shortener' => [
                'section' => 'url_shortener',
                'type' => 'checkbox',
                'title' => __('Enable URL Shortener', 'seo-campaign-hub'),
                'description' => __('Enable URL shortening functionality.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'shortener_slug_length' => [
                'section' => 'url_shortener',
                'type' => 'number',
                'title' => __('Slug Length', 'seo-campaign-hub'),
                'description' => __('Length of the short URL slug.', 'seo-campaign-hub'),
                'default' => '6',
                'min' => '4',
                'max' => '12'
            ],
            'shortener_prefix' => [
                'section' => 'url_shortener',
                'type' => 'text',
                'title' => __('URL Prefix', 'seo-campaign-hub'),
                'description' => __('Prefix for short URLs (e.g., go, link).', 'seo-campaign-hub'),
                'default' => 'go'
            ],

            // QR Code Settings
            'enable_qr_codes' => [
                'section' => 'qr_codes',
                'type' => 'checkbox',
                'title' => __('Enable QR Codes', 'seo-campaign-hub'),
                'description' => __('Enable QR code generation.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'qr_code_size' => [
                'section' => 'qr_codes',
                'type' => 'number',
                'title' => __('Default QR Size', 'seo-campaign-hub'),
                'description' => __('Default QR code size in pixels.', 'seo-campaign-hub'),
                'default' => '300',
                'min' => '100',
                'max' => '1000'
            ],
            'qr_code_format' => [
                'section' => 'qr_codes',
                'type' => 'select',
                'title' => __('Default Format', 'seo-campaign-hub'),
                'description' => __('Default QR code format.', 'seo-campaign-hub'),
                'options' => [
                    'png' => 'PNG',
                    'svg' => 'SVG',
                    'pdf' => 'PDF'
                ],
                'default' => 'png'
            ],
            'qr_code_color' => [
                'section' => 'qr_codes',
                'type' => 'color',
                'title' => __('Default Color', 'seo-campaign-hub'),
                'description' => __('Default QR code color.', 'seo-campaign-hub'),
                'default' => '#000000'
            ],
            'qr_code_bg_color' => [
                'section' => 'qr_codes',
                'type' => 'color',
                'title' => __('Default Background Color', 'seo-campaign-hub'),
                'description' => __('Default QR code background color.', 'seo-campaign-hub'),
                'default' => '#FFFFFF'
            ],

            // Advanced Settings
            'cache_enabled' => [
                'section' => 'advanced',
                'type' => 'checkbox',
                'title' => __('Enable Caching', 'seo-campaign-hub'),
                'description' => __('Enable caching for improved performance.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'cache_expiration' => [
                'section' => 'advanced',
                'type' => 'number',
                'title' => __('Cache Expiration (seconds)', 'seo-campaign-hub'),
                'description' => __('How long to cache data in seconds.', 'seo-campaign-hub'),
                'default' => '3600',
                'min' => '60',
                'max' => '86400'
            ],
            'minify_assets' => [
                'section' => 'advanced',
                'type' => 'checkbox',
                'title' => __('Minify Assets', 'seo-campaign-hub'),
                'description' => __('Minify CSS and JavaScript files.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'defer_scripts' => [
                'section' => 'advanced',
                'type' => 'checkbox',
                'title' => __('Defer JavaScript', 'seo-campaign-hub'),
                'description' => __('Defer JavaScript loading for better performance.', 'seo-campaign-hub'),
                'default' => '1'
            ]
        ];
    }

    /**
     * Register settings with WordPress
     *
     * @return void
     */
    public function register_settings() {
        // Register settings group
        register_setting(
            'seo_campaign_hub_settings',
            'seo_campaign_hub_options',
            [$this, 'sanitize_options']
        );

        // Register sections
        foreach ($this->sections as $section_id => $section) {
            add_settings_section(
                'seo_campaign_hub_' . $section_id,
                $section['title'],
                function() use ($section) {
                    echo '<p>' . esc_html($section['description']) . '</p>';
                },
                'seo_campaign_hub_settings'
            );

            // Register fields for this section
            $section_fields = array_filter($this->fields, function($field) use ($section_id) {
                return $field['section'] === $section_id;
            });

            foreach ($section_fields as $field_id => $field) {
                $this->register_field($field_id, $field);
            }
        }
    }

    /**
     * Register a settings field
     *
     * @param string $field_id Field ID
     * @param array  $field Field data
     * @return void
     */
    private function register_field($field_id, $field) {
        add_settings_field(
            $field_id,
            $field['title'],
            [$this, 'render_field'],
            'seo_campaign_hub_settings',
            'seo_campaign_hub_' . $field['section'],
            [
                'field_id' => $field_id,
                'field' => $field
            ]
        );
    }

    /**
     * Render a settings field
     *
     * @param array $args Field arguments
     * @return void
     */
    public function render_field($args) {
        $field_id = $args['field_id'];
        $field = $args['field'];
        $value = $this->get_option($field_id, $field['default'] ?? '');

        $name = 'seo_campaign_hub_options[' . $field_id . ']';

        switch ($field['type']) {
            case 'checkbox':
                ?>
                <input type="checkbox"
                       id="<?php echo esc_attr($field_id); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="1"
                       <?php checked($value, '1'); ?> />
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif;
                break;

            case 'text':
                ?>
                <input type="text"
                       id="<?php echo esc_attr($field_id); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr($value); ?>"
                       class="regular-text" />
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif;
                break;

            case 'number':
                ?>
                <input type="number"
                       id="<?php echo esc_attr($field_id); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr($value); ?>"
                       min="<?php echo esc_attr($field['min'] ?? ''); ?>"
                       max="<?php echo esc_attr($field['max'] ?? ''); ?>"
                       class="small-text" />
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif;
                break;

            case 'select':
                ?>
                <select id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($name); ?>">
                    <?php foreach ($field['options'] as $option_value => $option_label): ?>
                        <option value="<?php echo esc_attr($option_value); ?>"
                                <?php selected($value, $option_value); ?>>
                            <?php echo esc_html($option_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif;
                break;

            case 'color':
                ?>
                <input type="color"
                       id="<?php echo esc_attr($field_id); ?>"
                       name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr($value); ?>"
                       class="color-picker" />
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif;
                break;

            case 'textarea':
                ?>
                <textarea id="<?php echo esc_attr($field_id); ?>"
                          name="<?php echo esc_attr($name); ?>"
                          rows="5"
                          class="large-text"><?php echo esc_textarea($value); ?></textarea>
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif;
                break;

            default:
                echo '<p>' . esc_html__('Unknown field type.', 'seo-campaign-hub') . '</p>';
        }
    }

    /**
     * Sanitize options
     *
     * @param array $input Input data
     * @return array
     */
    public function sanitize_options($input) {
        $sanitized = [];

        foreach ($this->fields as $field_id => $field) {
            if (!isset($input[$field_id])) {
                continue;
            }

            $value = $input[$field_id];

            switch ($field['type']) {
                case 'checkbox':
                    $sanitized[$field_id] = $value === '1' ? '1' : '0';
                    break;

                case 'number':
                    $sanitized[$field_id] = intval($value);
                    break;

                case 'text':
                case 'color':
                    $sanitized[$field_id] = sanitize_text_field($value);
                    break;

                case 'textarea':
                    $sanitized[$field_id] = sanitize_textarea_field($value);
                    break;

                case 'select':
                    $allowed = array_keys($field['options']);
                    $sanitized[$field_id] = in_array($value, $allowed) ? $value : $field['default'];
                    break;

                default:
                    $sanitized[$field_id] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Get an option value
     *
     * @param string $key Option key
     * @param mixed  $default Default value
     * @return mixed
     */
    public function get_option($key, $default = null) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Update an option value
     *
     * @param string $key Option key
     * @param mixed  $value Option value
     * @return void
     */
    public function update_option($key, $value) {
        $this->options[$key] = $value;
        update_option('seo_campaign_hub_options', $this->options);
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function get_all_options() {
        return $this->options;
    }

    /**
     * Reset all options to defaults
     *
     * @return void
     */
    public function reset_options() {
        $defaults = [];
        foreach ($this->fields as $field_id => $field) {
            $defaults[$field_id] = $field['default'] ?? '';
        }
        $this->options = $defaults;
        update_option('seo_campaign_hub_options', $defaults);
    }

    /**
     * Get sections
     *
     * @return array
     */
    public function get_sections() {
        return $this->sections;
    }

    /**
     * Get fields
     *
     * @return array
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Get fields for a specific section
     *
     * @param string $section Section ID
     * @return array
     */
    public function get_section_fields($section) {
        return array_filter($this->fields, function($field) use ($section) {
            return $field['section'] === $section;
        });
    }
}
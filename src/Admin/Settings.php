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
        $this->load_options();
        $this->define_sections();
        $this->define_fields();

        // Called from Plugin::on_admin_init(), so register immediately
        // instead of re-hooking admin_init (which would miss this request).
        $this->register_settings();
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
            'localization' => [
                'title' => __('Localization', 'seo-campaign-hub'),
                'description' => __('Smart language and country redirects for short links (Europe, Americas, Middle East defaults).', 'seo-campaign-hub')
            ],
            'google_analytics' => [
                'title' => __('Google Analytics', 'seo-campaign-hub'),
                'description' => __('Connect Google Analytics (GA4) to track visitor behaviour alongside the built-in analytics.', 'seo-campaign-hub')
            ],
            'header_footer_scripts' => [
                'title' => __('Header & Footer Scripts', 'seo-campaign-hub'),
                'description' => __('Add custom code to the site-wide <head> and before </body>. Use this for Google AdSense verification, ad scripts, Meta Pixel, or any third-party snippet.', 'seo-campaign-hub')
            ],
            'ads_txt' => [
                'title' => __('Ads.txt (Google AdSense)', 'seo-campaign-hub'),
                'description' => __('Manage your ads.txt file for Google AdSense and other ad networks. This file is served at yoursite.com/ads.txt.', 'seo-campaign-hub')
            ],
            'tools' => [
                'title' => __('Public Tools', 'seo-campaign-hub'),
                'description' => __('Front-end utilities that visitors can use without logging into WordPress.', 'seo-campaign-hub')
            ],
            'social_share' => [
                'title' => __('Social Share', 'seo-campaign-hub'),
                'description' => __('Browser-based share icons on Posts → All Posts (no App ID or connected accounts).', 'seo-campaign-hub')
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

            // Localization
            'enable_smart_redirects' => [
                'section' => 'localization',
                'type' => 'checkbox',
                'title' => __('Enable Smart Redirects', 'seo-campaign-hub'),
                'description' => __('Redirect short links by visitor language and/or country when rules are set.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'default_redirect_priority' => [
                'section' => 'localization',
                'type' => 'select',
                'title' => __('Default Redirect Priority', 'seo-campaign-hub'),
                'description' => __('When both language and country rules exist, which match type is tried first (can be overridden per link).', 'seo-campaign-hub'),
                'options' => [
                    'language' => __('Language first', 'seo-campaign-hub'),
                    'country' => __('Country first', 'seo-campaign-hub'),
                ],
                'default' => 'language'
            ],
            'enabled_languages' => [
                'section' => 'localization',
                'type' => 'checkbox_group',
                'title' => __('Enabled Languages', 'seo-campaign-hub'),
                'description' => __('Languages available when creating short-link targeting rules.', 'seo-campaign-hub'),
                'options' => [
                    'en' => 'English (en)',
                    'es' => 'Spanish (es)',
                    'pt' => 'Portuguese (pt)',
                    'fr' => 'French (fr)',
                    'de' => 'German (de)',
                    'it' => 'Italian (it)',
                    'nl' => 'Dutch (nl)',
                    'pl' => 'Polish (pl)',
                    'ru' => 'Russian (ru)',
                    'ar' => 'Arabic (ar)',
                    'he' => 'Hebrew (he)',
                    'tr' => 'Turkish (tr)',
                    'fa' => 'Persian (fa)',
                ],
                'default' => ['en', 'es', 'pt', 'fr', 'de', 'it', 'nl', 'pl', 'ru', 'ar', 'he', 'tr', 'fa']
            ],
            'default_fallback_language' => [
                'section' => 'localization',
                'type' => 'select',
                'title' => __('Default Fallback Language', 'seo-campaign-hub'),
                'description' => __('Preferred language code for documentation and defaults (fallback URL is still the link destination).', 'seo-campaign-hub'),
                'options' => [
                    'en' => 'English (en)',
                    'es' => 'Spanish (es)',
                    'pt' => 'Portuguese (pt)',
                    'fr' => 'French (fr)',
                    'de' => 'German (de)',
                    'it' => 'Italian (it)',
                    'nl' => 'Dutch (nl)',
                    'pl' => 'Polish (pl)',
                    'ru' => 'Russian (ru)',
                    'ar' => 'Arabic (ar)',
                    'he' => 'Hebrew (he)',
                    'tr' => 'Turkish (tr)',
                    'fa' => 'Persian (fa)',
                ],
                'default' => 'en'
            ],

            // Header & Footer Scripts
            'header_scripts' => [
                'section' => 'header_footer_scripts',
                'type' => 'code',
                'title' => __('Header Scripts', 'seo-campaign-hub'),
                'description' => __('Code added here is output inside <head></head> on every front-end page. Paste Google AdSense verification, ad auto scripts, Meta Pixel base code, etc.', 'seo-campaign-hub'),
                'default' => '',
                'rows' => 10,
                'placeholder' => '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>'
            ],
            'footer_scripts' => [
                'section' => 'header_footer_scripts',
                'type' => 'code',
                'title' => __('Footer Scripts', 'seo-campaign-hub'),
                'description' => __('Code added here is output just before </body> on every front-end page. Useful for analytics, chat widgets, or scripts that should load last.', 'seo-campaign-hub'),
                'default' => '',
                'rows' => 10,
                'placeholder' => '<!-- Footer scripts here -->'
            ],
            'header_footer_admin_only' => [
                'section' => 'header_footer_scripts',
                'type' => 'checkbox',
                'title' => __('Restrict to Admins', 'seo-campaign-hub'),
                'description' => __('Only administrators can edit these scripts (already enforced by Settings page capability). Shown here as a reminder.', 'seo-campaign-hub'),
                'default' => '1'
            ],

            // Ads.txt Settings
            'enable_ads_txt' => [
                'section' => 'ads_txt',
                'type' => 'checkbox',
                'title' => __('Enable ads.txt', 'seo-campaign-hub'),
                'description' => __('Serve an ads.txt file at yoursite.com/ads.txt for Google AdSense verification.', 'seo-campaign-hub'),
                'default' => '0'
            ],
            'ads_txt_content' => [
                'section' => 'ads_txt',
                'type' => 'textarea',
                'title' => __('ads.txt Content', 'seo-campaign-hub'),
                'description' => __('Paste your Google AdSense ads.txt content here. Get it from your AdSense account → Sites → Ads.txt.', 'seo-campaign-hub'),
                'default' => '',
                'rows' => 10
            ],

            // Public tools
            'enable_image_to_svg' => [
                'section' => 'tools',
                'type' => 'checkbox',
                'title' => __('Enable Image → SVG tool', 'seo-campaign-hub'),
                'description' => __('Public page at /tools/image-to-svg/ (no login). Visitors convert PNG/JPG to SVG in the browser. Also available via shortcode [sch_image_to_svg]. After enabling, save Settings → Permalinks once if the page 404s.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'enable_public_url_shortener' => [
                'section' => 'tools',
                'type' => 'checkbox',
                'title' => __('Enable public URL shortener', 'seo-campaign-hub'),
                'description' => __('Public page at /tools/url-shortener/ (no login). Visitors create short links with auto-generated slugs. Shortcode [sch_url_shortener]. Requires the core URL Shortener to be enabled. Flush Permalinks once if the page 404s.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'public_shortener_same_site_only' => [
                'section' => 'tools',
                'type' => 'checkbox',
                'title' => __('Public shortener: same-site only', 'seo-campaign-hub'),
                'description' => __('When enabled, guest short links may only point to URLs on this website’s host.', 'seo-campaign-hub'),
                'default' => '0'
            ],
			'public_shortener_rate_limit' => [
                'section' => 'tools',
                'type' => 'number',
                'title' => __('Public shortener rate limit', 'seo-campaign-hub'),
                'description' => __('Max short links a visitor IP can create per hour (1–100). Default: 10.', 'seo-campaign-hub'),
                'default' => '10',
                'min' => 1,
                'max' => 100
            ],
            'tools_ad_above' => [
                'section' => 'tools',
                'type' => 'code',
                'title' => __('Public tools ad — above', 'seo-campaign-hub'),
                'description' => __('Shared HTML shown above the tool on /tools/image-to-svg/ and /tools/url-shortener/ only (not shortcodes). Paste AdSense units, affiliate banners, or company ad markup. Put the AdSense loader in Header Scripts if needed.', 'seo-campaign-hub'),
                'default' => '',
                'rows' => 8,
                'placeholder' => '<!-- AdSense / affiliate / company ad above the tool -->'
            ],
            'tools_ad_below' => [
                'section' => 'tools',
                'type' => 'code',
                'title' => __('Public tools ad — below', 'seo-campaign-hub'),
                'description' => __('Shared HTML shown below the tool on both standalone public tool pages (not shortcodes).', 'seo-campaign-hub'),
                'default' => '',
                'rows' => 8,
                'placeholder' => '<!-- AdSense / affiliate / company ad below the tool -->'
            ],

            // Social Share (admin Posts list)
            'enable_admin_social_share' => [
                'section' => 'social_share',
                'type' => 'checkbox',
                'title' => __('Enable admin post share actions', 'seo-campaign-hub'),
                'description' => __('Show social share icons on Posts → All Posts (Share column and row actions). Opens Facebook, X, LinkedIn, Pinterest, WhatsApp, Blogger, Telegram, Quora, Reddit, Email, and Copy link in the browser — no App ID required.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'social_share_networks' => [
                'section' => 'social_share',
                'type' => 'checkbox_group',
                'title' => __('Networks', 'seo-campaign-hub'),
                'description' => __('Choose which share actions appear in the Posts list row actions.', 'seo-campaign-hub'),
                'options' => [
                    'facebook'  => 'Facebook',
                    'x'         => 'X (Twitter)',
                    'linkedin'  => 'LinkedIn',
                    'pinterest' => 'Pinterest',
                    'whatsapp'  => 'WhatsApp',
                    'blogger'   => 'Blogger',
                    'telegram'  => 'Telegram',
                    'quora'     => 'Quora',
                    'reddit'    => 'Reddit',
                    'email'     => 'Email',
                    'copy'      => 'Copy link',
                ],
                'default' => [ 'facebook', 'x', 'linkedin', 'pinterest', 'whatsapp', 'blogger', 'telegram', 'quora', 'reddit', 'email', 'copy' ]
            ],

            // Google Analytics
            'enable_google_analytics' => [
                'section' => 'google_analytics',
                'type' => 'checkbox',
                'title' => __('Enable Google Analytics', 'seo-campaign-hub'),
                'description' => __('Inject the Google Analytics (GA4) tracking script on the front end.', 'seo-campaign-hub'),
                'default' => '0'
            ],
            'ga_measurement_id' => [
                'section' => 'google_analytics',
                'type' => 'text',
                'title' => __('Measurement ID', 'seo-campaign-hub'),
                'description' => __('Your GA4 Measurement ID (e.g. G-XXXXXXXXXX). Find it in Google Analytics → Admin → Data Streams.', 'seo-campaign-hub'),
                'default' => ''
            ],
            'ga_track_campaigns' => [
                'section' => 'google_analytics',
                'type' => 'checkbox',
                'title' => __('Track Campaign Views', 'seo-campaign-hub'),
                'description' => __('Send a custom event to GA4 when a campaign page is viewed.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'ga_track_offer_clicks' => [
                'section' => 'google_analytics',
                'type' => 'checkbox',
                'title' => __('Track Offer Clicks', 'seo-campaign-hub'),
                'description' => __('Send a custom event to GA4 when an offer link is clicked.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'ga_track_short_links' => [
                'section' => 'google_analytics',
                'type' => 'checkbox',
                'title' => __('Track Short Link Redirects', 'seo-campaign-hub'),
                'description' => __('Send a custom event to GA4 when a short link redirect occurs.', 'seo-campaign-hub'),
                'default' => '1'
            ],
            'ga_custom_dimensions' => [
                'section' => 'google_analytics',
                'type' => 'textarea',
                'title' => __('Custom Dimensions / Config', 'seo-campaign-hub'),
                'description' => __('Optional JSON object merged into the gtag config call. Example: {"cookie_flags":"SameSite=None;Secure","send_page_view":false}', 'seo-campaign-hub'),
                'default' => '',
                'rows' => 4
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
                          rows="<?php echo esc_attr( (string) ( $field['rows'] ?? 5 ) ); ?>"
                          class="large-text code"><?php echo esc_textarea($value); ?></textarea>
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif;
                break;

            case 'code':
                ?>
                <textarea id="<?php echo esc_attr($field_id); ?>"
                          name="<?php echo esc_attr($name); ?>"
                          rows="<?php echo esc_attr( (string) ( $field['rows'] ?? 8 ) ); ?>"
                          class="large-text code"
                          placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
                          style="font-family:monospace;font-size:13px;tab-size:4;"><?php echo esc_textarea($value); ?></textarea>
                <?php if (!empty($field['description'])): ?>
                    <p class="description"><?php echo esc_html($field['description']); ?></p>
                <?php endif;
                break;

            case 'checkbox_group':
                $selected = is_array($value) ? $value : (array) ($field['default'] ?? []);
                $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : [];
                echo '<fieldset><legend class="screen-reader-text"><span>' . esc_html($field['title']) . '</span></legend>';
                foreach ($options as $option_value => $option_label) {
                    $input_id = $field_id . '_' . $option_value;
                    ?>
                    <label for="<?php echo esc_attr($input_id); ?>" style="display:inline-block;min-width:180px;margin:0 12px 8px 0;">
                        <input type="checkbox"
                               id="<?php echo esc_attr($input_id); ?>"
                               name="<?php echo esc_attr($name); ?>[]"
                               value="<?php echo esc_attr($option_value); ?>"
                               <?php checked(in_array($option_value, $selected, true)); ?> />
                        <?php echo esc_html($option_label); ?>
                    </label>
                    <?php
                }
                echo '</fieldset>';
                if (!empty($field['description'])) {
                    echo '<p class="description">' . esc_html($field['description']) . '</p>';
                }
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

                case 'code':
                    // Allow raw HTML/JS (script tags, meta tags, etc.) — only admins can save settings.
                    $sanitized[$field_id] = wp_unslash($value);
                    break;

                case 'select':
                    $allowed = array_keys($field['options']);
                    $sanitized[$field_id] = in_array($value, $allowed) ? $value : $field['default'];
                    break;

                case 'checkbox_group':
                    $allowed = array_keys($field['options'] ?? []);
                    $values = is_array($value) ? $value : [];
                    $clean = [];
                    foreach ($values as $item) {
                        $item = sanitize_text_field((string) $item);
                        if (in_array($item, $allowed, true)) {
                            $clean[] = $item;
                        }
                    }
                    $sanitized[$field_id] = !empty($clean) ? array_values(array_unique($clean)) : ($field['default'] ?? []);
                    break;

                default:
                    $sanitized[$field_id] = sanitize_text_field($value);
            }
        }

        // Unchecked checkbox groups submit nothing — restore empty only when field missing and was submitted form.
        foreach ($this->fields as $field_id => $field) {
            if (($field['type'] ?? '') === 'checkbox_group' && !isset($input[$field_id])) {
                $sanitized[$field_id] = [];
            }
            if (($field['type'] ?? '') === 'checkbox' && !isset($input[$field_id])) {
                $sanitized[$field_id] = '0';
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
<?php
/**
 * Schema Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SchemaService
 *
 * Handles schema markup generation operations
 */
class SchemaService {
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
     * Initialize schema service
     *
     * @return void
     */
    public function init() {
        add_action('wp_head', [$this, 'output_schema']);
        add_action('seo_campaign_hub_campaign_saved', [$this, 'generate_default_schema']);
    }

    /**
     * Output schema markup
     *
     * @return void
     */
    public function output_schema() {
        if (!is_singular(['sch_campaign', 'sch_offer', 'post', 'page'])) {
            return;
        }

        $enabled = get_option('seo_campaign_hub_enable_schema', true);
        if (!$enabled) {
            return;
        }

        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);

        // Get schema data
        $schema = $this->get_schema_for_post($post_id, $post_type);

        if (!$schema) {
            return;
        }

        // Output schema
        echo '<!-- SEO Campaign Hub Schema -->' . "\n";
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }

    /**
     * Get schema for post
     *
     * @param int    $post_id Post ID
     * @param string $post_type Post type
     * @return array|null
     */
    public function get_schema_for_post($post_id, $post_type = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_schemas';

        // Try to get saved schema
        $schema_record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                WHERE post_id = %d AND is_active = 1 
                ORDER BY is_default DESC, id DESC LIMIT 1",
                $post_id
            )
        );

        if ($schema_record && !empty($schema_record->schema_data)) {
            $schema = json_decode($schema_record->schema_data, true);
            if ($schema) {
                return $schema;
            }
        }

        // Generate default schema
        $schema_type = $this->get_default_schema_type($post_type);
        return $this->generate_schema($post_id, $schema_type);
    }

    /**
     * Get default schema type
     *
     * @param string $post_type Post type
     * @return string
     */
    private function get_default_schema_type($post_type) {
        switch ($post_type) {
            case 'sch_campaign':
                return 'Article';
            case 'sch_offer':
                return 'Product';
            default:
                return 'WebPage';
        }
    }

    /**
     * Generate schema for post
     *
     * @param int    $post_id Post ID
     * @param string $type Schema type
     * @return array
     */
    public function generate_schema($post_id, $type = 'Article') {
        $post = get_post($post_id);
        $author = get_userdata($post->post_author);
        $thumbnail_id = get_post_thumbnail_id($post_id);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'headline' => get_the_title($post_id),
            'description' => wp_trim_words(get_the_excerpt($post_id), 30, '...'),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'author' => [
                '@type' => 'Person',
                'name' => $author ? $author->display_name : '',
                'url' => $author ? get_author_posts_url($author->ID) : ''
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id)
            ]
        ];

        // Add image if available
        if ($thumbnail_id) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => wp_get_attachment_url($thumbnail_id, 'large')
            ];
        }

        // Add content if available
        if (!empty($post->post_content)) {
            $schema['articleBody'] = wp_trim_words(strip_tags($post->post_content), 50, '...');
        }

        // Add FAQ schema if enabled
        if ($type === 'Article') {
            $faq_items = $this->get_faq_items($post_id);
            if (!empty($faq_items)) {
                $schema['mainEntity'] = $faq_items;
            }
        }

        return $schema;
    }

    /**
     * Get FAQ items from post
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_faq_items($post_id) {
        $faq_items = [];

        // Check for custom FAQ meta
        $faq_data = get_post_meta($post_id, '_seo_campaign_hub_faq', true);
        if ($faq_data) {
            $faq_data = json_decode($faq_data, true);
            if (is_array($faq_data)) {
                foreach ($faq_data as $item) {
                    if (!empty($item['question']) && !empty($item['answer'])) {
                        $faq_items[] = [
                            '@type' => 'Question',
                            'name' => $item['question'],
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $item['answer']
                            ]
                        ];
                    }
                }
            }
        }

        // If no FAQ data, try to extract from content
        if (empty($faq_items)) {
            $content = get_post_field('post_content', $post_id);
            $faq_items = $this->extract_faq_from_content($content);
        }

        return $faq_items;
    }

    /**
     * Extract FAQ from content
     *
     * @param string $content Post content
     * @return array
     */
    private function extract_faq_from_content($content) {
        $faq_items = [];

        // Look for Q&A patterns
        preg_match_all('/<h[2-3][^>]*>(?:Q|Question):?\s*(.*?)<\/h[2-3]>\s*(?:<p[^>]*>|)(.*?)(?:<\/p>|$)/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (count($match) >= 3) {
                $question = strip_tags($match[1]);
                $answer = strip_tags($match[2]);

                if (!empty($question) && !empty($answer)) {
                    $faq_items[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $answer
                        ]
                    ];
                }
            }
        }

        return $faq_items;
    }

    /**
     * Generate default schema on campaign save
     *
     * @param int $campaign_id Campaign ID
     * @return void
     */
    public function generate_default_schema($campaign_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sch_schemas';

        // Check if schema already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE campaign_id = %d AND is_default = 1",
                $campaign_id
            )
        );

        if ($exists) {
            return;
        }

        // Generate schema
        $schema = $this->generate_schema($campaign_id, 'Article');

        // Save to database
        $data = [
            'schema_key' => 'default_' . uniqid(),
            'campaign_id' => $campaign_id,
            'post_id' => $campaign_id,
            'schema_type' => 'Article',
            'schema_title' => get_the_title($campaign_id),
            'schema_description' => wp_trim_words(get_the_excerpt($campaign_id), 30, '...'),
            'schema_data' => wp_json_encode($schema),
            'is_active' => 1,
            'is_default' => 1,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];

        $wpdb->insert($table, $data);
    }

    /**
     * Create a custom schema
     *
     * @param array $data Schema data
     * @return int|false
     */
    public function create_schema($data) {
        // Validate data
        if (empty($data['schema_type']) || empty($data['schema_data'])) {
            return false;
        }

        // Generate schema key
        $data['schema_key'] = 'schema_' . uniqid();

        // Set defaults
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        if (!isset($data['is_default'])) {
            $data['is_default'] = 0;
        }

        // Set created by
        $data['created_by'] = get_current_user_id();

        // Insert into database
        $result = $this->db->insert('schemas', $data);

        if ($result) {
            $this->cache->clear('schemas');
            do_action('seo_campaign_hub_schema_created', $result, $data);
        }

        return $result;
    }

    /**
     * Get schema by ID
     *
     * @param int $id Schema ID
     * @return object|null
     */
    public function get_schema($id) {
        $cache_key = 'schema_' . $id;
        $schema = $this->cache->get($cache_key);

        if ($schema === false) {
            $schema = $this->db->get_row(
                $this->db->prepare(
                    "SELECT * FROM {$this->db->get_table('schemas')} WHERE id = %d",
                    $id
                )
            );
            $this->cache->set($cache_key, $schema, 'schemas', 3600);
        }

        return $schema;
    }

    /**
     * Get all schemas
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_schemas($args = []) {
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $where = [];

        if (!empty($args['schema_type'])) {
            $where[] = $this->db->prepare(
                "schema_type = %s",
                $args['schema_type']
            );
        }

        if (!empty($args['campaign_id'])) {
            $where[] = $this->db->prepare(
                "campaign_id = %d",
                $args['campaign_id']
            );
        }

        if (!empty($args['is_active'])) {
            $where[] = $this->db->prepare(
                "is_active = %d",
                intval($args['is_active'])
            );
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "
            SELECT * FROM {$this->db->get_table('schemas')}
            {$where_clause}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d
        ";

        $query = $this->db->prepare($query, $args['limit'], $args['offset']);
        return $this->db->get_results($query);
    }

    /**
     * Update schema
     *
     * @param int   $id Schema ID
     * @param array $data Schema data
     * @return int|false
     */
    public function update_schema($id, $data) {
        $schema = $this->get_schema($id);

        if (!$schema) {
            return false;
        }

        // Set modified by
        $data['modified_by'] = get_current_user_id();

        // Update
        $result = $this->db->update('schemas', $data, ['id' => $id]);

        if ($result !== false) {
            $this->cache->delete('schema_' . $id);
            $this->cache->clear('schemas');
            do_action('seo_campaign_hub_schema_updated', $id, $data);
        }

        return $result;
    }

    /**
     * Delete schema
     *
     * @param int $id Schema ID
     * @return bool
     */
    public function delete_schema($id) {
        $schema = $this->get_schema($id);

        if (!$schema) {
            return false;
        }

        $result = $this->db->delete('schemas', ['id' => $id]);

        if ($result) {
            $this->cache->delete('schema_' . $id);
            $this->cache->clear('schemas');
            do_action('seo_campaign_hub_schema_deleted', $id);
        }

        return $result;
    }

    /**
     * Get schema types
     *
     * @return array
     */
    public function get_schema_types() {
        return [
            'Article' => __('Article', 'seo-campaign-hub'),
            'FAQ' => __('FAQ', 'seo-campaign-hub'),
            'Breadcrumb' => __('Breadcrumb', 'seo-campaign-hub'),
            'Product' => __('Product', 'seo-campaign-hub'),
            'Review' => __('Review', 'seo-campaign-hub'),
            'Recipe' => __('Recipe', 'seo-campaign-hub'),
            'Event' => __('Event', 'seo-campaign-hub'),
            'HowTo' => __('How To', 'seo-campaign-hub'),
            'Video' => __('Video', 'seo-campaign-hub'),
            'Course' => __('Course', 'seo-campaign-hub'),
            'WebPage' => __('Web Page', 'seo-campaign-hub'),
            'BlogPosting' => __('Blog Posting', 'seo-campaign-hub')
        ];
    }

    /**
     * Get schema templates
     *
     * @return array
     */
    public function get_schema_templates() {
        return [
            'article' => [
                'label' => __('Article', 'seo-campaign-hub'),
                'template' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => '{post_title}',
                    'description' => '{post_excerpt}',
                    'image' => '{featured_image}',
                    'author' => [
                        '@type' => 'Person',
                        'name' => '{author_name}'
                    ],
                    'datePublished' => '{post_date}',
                    'dateModified' => '{modified_date}'
                ]
            ],
            'faq' => [
                'label' => __('FAQ', 'seo-campaign-hub'),
                'template' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => '{question_1}',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => '{answer_1}'
                            ]
                        ]
                    ]
                ]
            ],
            'product' => [
                'label' => __('Product', 'seo-campaign-hub'),
                'template' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => '{post_title}',
                    'description' => '{post_excerpt}',
                    'image' => '{featured_image}',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '{price}',
                        'priceCurrency' => '{currency}'
                    ]
                ]
            ]
        ];
    }
}
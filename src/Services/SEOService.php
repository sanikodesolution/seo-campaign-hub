<?php
/**
 * SEO Service
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SEOService
 *
 * Handles SEO optimization operations
 */
class SEOService {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_head', [$this, 'add_seo_meta_tags']);
        add_filter('document_title_parts', [$this, 'modify_title_parts']);
        add_action('seo_campaign_hub_campaign_saved', [$this, 'update_seo_scores']);
    }

    /**
     * Add SEO meta tags
     *
     * @return void
     */
    public function add_seo_meta_tags() {
        if (!is_singular(['sch_campaign', 'sch_offer', 'post', 'page'])) {
            return;
        }

        $post_id = get_the_ID();
        $meta_title = get_post_meta($post_id, '_seo_campaign_hub_meta_title', true);
        $meta_description = get_post_meta($post_id, '_seo_campaign_hub_meta_description', true);
        $meta_keywords = get_post_meta($post_id, '_seo_campaign_hub_meta_keywords', true);
        $canonical_url = get_post_meta($post_id, '_seo_campaign_hub_canonical_url', true);
        $noindex = get_post_meta($post_id, '_seo_campaign_hub_noindex', true);
        $nofollow = get_post_meta($post_id, '_seo_campaign_hub_nofollow', true);

        // Meta title
        if (!empty($meta_title)) {
            echo '<meta name="title" content="' . esc_attr($meta_title) . '" />' . "\n";
        }

        // Meta description
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
        }

        // Meta keywords
        if (!empty($meta_keywords)) {
            echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '" />' . "\n";
        }

        // Canonical URL
        if (!empty($canonical_url)) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        } else {
            echo '<link rel="canonical" href="' . esc_url(get_permalink($post_id)) . '" />' . "\n";
        }

        // Robots meta
        if ($noindex || $nofollow) {
            $robots = [];
            if ($noindex) $robots[] = 'noindex';
            if ($nofollow) $robots[] = 'nofollow';
            echo '<meta name="robots" content="' . esc_attr(implode(', ', $robots)) . '" />' . "\n";
        }
    }

    /**
     * Modify title parts
     *
     * @param array $title Title parts
     * @return array
     */
    public function modify_title_parts($title) {
        if (!is_singular(['sch_campaign', 'sch_offer', 'post', 'page'])) {
            return $title;
        }

        $post_id = get_the_ID();
        $meta_title = get_post_meta($post_id, '_seo_campaign_hub_meta_title', true);

        if (!empty($meta_title)) {
            $title['title'] = $meta_title;
        }

        return $title;
    }

    /**
     * Update SEO scores for a post
     *
     * @param int $post_id Post ID
     * @return void
     */
    public function update_seo_scores($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $content = $post->post_content;
        $title = $post->post_title;

        // Calculate SEO score
        $seo_score = $this->calculate_seo_score($content, $title);
        $readability_score = $this->calculate_readability_score($content);

        // Save scores
        update_post_meta($post_id, '_seo_campaign_hub_seo_score', $seo_score);
        update_post_meta($post_id, '_seo_campaign_hub_readability_score', $readability_score);

        // Update in custom table if exists
        global $wpdb;
        $table = $wpdb->prefix . 'sch_campaigns';
        $campaign = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE post_id = %d",
                $post_id
            )
        );

        if ($campaign) {
            $wpdb->update(
                $table,
                [
                    'seo_score' => $seo_score,
                    'readability_score' => $readability_score
                ],
                ['id' => $campaign->id]
            );
        }
    }

    /**
     * Calculate SEO score
     *
     * @param string $content Post content
     * @param string $title Post title
     * @return int
     */
    private function calculate_seo_score($content, $title) {
        $score = 0;
        $max_score = 100;

        // Check title length
        $title_length = strlen($title);
        if ($title_length >= 30 && $title_length <= 60) {
            $score += 20;
        } elseif ($title_length >= 20 && $title_length <= 70) {
            $score += 10;
        }

        // Check content length
        $content_length = str_word_count(strip_tags($content));
        if ($content_length >= 300) {
            $score += 20;
        } elseif ($content_length >= 150) {
            $score += 10;
        }

        // Check keyword density
        $keyword = get_post_meta(get_the_ID(), '_seo_campaign_hub_focus_keyword', true);
        if (!empty($keyword)) {
            $density = $this->calculate_keyword_density($content, $keyword);
            if ($density >= 1 && $density <= 3) {
                $score += 15;
            } elseif ($density >= 0.5 && $density <= 4) {
                $score += 8;
            }
        }

        // Check heading structure
        if (preg_match('/<h1/i', $content)) {
            $score += 10;
        }
        if (preg_match('/<h2/i', $content)) {
            $score += 10;
        }
        if (preg_match('/<h3/i', $content)) {
            $score += 5;
        }

        // Check images with alt text
        preg_match_all('/<img[^>]+>/i', $content, $images);
        if (!empty($images[0])) {
            $has_alt = 0;
            foreach ($images[0] as $img) {
                if (strpos($img, 'alt=') !== false) {
                    $has_alt++;
                }
            }
            $alt_ratio = $has_alt / count($images[0]);
            if ($alt_ratio >= 0.8) {
                $score += 10;
            } elseif ($alt_ratio >= 0.5) {
                $score += 5;
            }
        }

        // Check internal links
        preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>/i', $content, $links);
        if (!empty($links[1])) {
            $internal_links = 0;
            $site_url = home_url();
            foreach ($links[1] as $link) {
                if (strpos($link, $site_url) !== false) {
                    $internal_links++;
                }
            }
            if ($internal_links >= 3) {
                $score += 10;
            } elseif ($internal_links >= 1) {
                $score += 5;
            }
        }

        return min($score, $max_score);
    }

    /**
     * Calculate readability score
     *
     * @param string $content Post content
     * @return int
     */
    private function calculate_readability_score($content) {
        $score = 0;
        $max_score = 100;

        // Remove HTML tags
        $text = strip_tags($content);

        // Get sentences and words
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($text, 1);

        // Average sentence length
        $sentence_count = count($sentences);
        $word_count = count($words);

        if ($sentence_count > 0 && $word_count > 0) {
            $avg_sentence_length = $word_count / $sentence_count;
            
            if ($avg_sentence_length >= 15 && $avg_sentence_length <= 20) {
                $score += 30;
            } elseif ($avg_sentence_length >= 12 && $avg_sentence_length <= 25) {
                $score += 20;
            } elseif ($avg_sentence_length >= 8 && $avg_sentence_length <= 30) {
                $score += 10;
            }
        }

        // Average word length
        if ($word_count > 0) {
            $total_chars = array_sum(array_map('strlen', $words));
            $avg_word_length = $total_chars / $word_count;
            
            if ($avg_word_length >= 4 && $avg_word_length <= 6) {
                $score += 30;
            } elseif ($avg_word_length >= 3 && $avg_word_length <= 7) {
                $score += 20;
            } elseif ($avg_word_length >= 2 && $avg_word_length <= 8) {
                $score += 10;
            }
        }

        // Paragraph count
        $paragraphs = preg_split('/\n\s*\n/', $text);
        if (count($paragraphs) >= 3) {
            $score += 20;
        } elseif (count($paragraphs) >= 1) {
            $score += 10;
        }

        // Use of headings
        if (preg_match('/<h[2-6]/i', $content)) {
            $score += 20;
        }

        return min($score, $max_score);
    }

    /**
     * Calculate keyword density
     *
     * @param string $content Content
     * @param string $keyword Keyword
     * @return float
     */
    private function calculate_keyword_density($content, $keyword) {
        $text = strip_tags($content);
        $words = str_word_count($text, 1);
        $word_count = count($words);
        
        if ($word_count === 0) {
            return 0;
        }

        $keyword_count = 0;
        $keyword_lower = strtolower($keyword);
        
        foreach ($words as $word) {
            if (strtolower($word) === $keyword_lower) {
                $keyword_count++;
            }
        }

        return ($keyword_count / $word_count) * 100;
    }

    /**
     * Generate SEO meta title
     *
     * @param string $title Post title
     * @return string
     */
    public function generate_meta_title($title) {
        $title = strip_tags($title);
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
        return $title;
    }

    /**
     * Generate SEO meta description
     *
     * @param string $content Post content
     * @param int    $length Description length
     * @return string
     */
    public function generate_meta_description($content, $length = 160) {
        $description = strip_tags($content);
        $description = wp_trim_words($description, $length / 4, '...');
        
        if (strlen($description) > $length) {
            $description = substr($description, 0, $length - 3) . '...';
        }
        
        return $description;
    }

    /**
     * Generate SEO slug
     *
     * @param string $title Post title
     * @param int    $post_id Post ID
     * @return string
     */
    public function generate_slug($title, $post_id = 0) {
        $slug = sanitize_title($title);
        $slug = str_replace(['-', '_'], '-', $slug);
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Check uniqueness
        if ($post_id > 0) {
            global $wpdb;
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                    WHERE post_name = %s AND post_type = 'sch_campaign' AND ID != %d",
                    $slug,
                    $post_id
                )
            );
            
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }
        }

        return $slug;
    }

    /**
     * Get reading time
     *
     * @param string $content Post content
     * @return int
     */
    public function get_reading_time($content) {
        $words = str_word_count(strip_tags($content));
        $minutes = ceil($words / 200);
        return max(1, $minutes);
    }

    /**
     * Get SEO recommendations
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function get_seo_recommendations($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $recommendations = [];
        $content = $post->post_content;
        $title = $post->post_title;
        $seo_score = get_post_meta($post_id, '_seo_campaign_hub_seo_score', true);
        $readability_score = get_post_meta($post_id, '_seo_campaign_hub_readability_score', true);

        // Title recommendations
        $title_length = strlen($title);
        if ($title_length < 30) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => __('Your title is too short. Aim for 30-60 characters.', 'seo-campaign-hub')
            ];
        } elseif ($title_length > 60) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => __('Your title is too long. Aim for 30-60 characters.', 'seo-campaign-hub')
            ];
        }

        // Content recommendations
        $content_length = str_word_count(strip_tags($content));
        if ($content_length < 300) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => __('Your content is short. Aim for at least 300 words.', 'seo-campaign-hub')
            ];
        }

        // Keyword recommendations
        $keyword = get_post_meta($post_id, '_seo_campaign_hub_focus_keyword', true);
        if (empty($keyword)) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Add a focus keyword to improve SEO.', 'seo-campaign-hub')
            ];
        }

        // Heading recommendations
        if (!preg_match('/<h1/i', $content)) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Add an H1 heading to your content.', 'seo-campaign-hub')
            ];
        }

        // Image recommendations
        preg_match_all('/<img[^>]+>/i', $content, $images);
        if (!empty($images[0])) {
            $has_alt = 0;
            foreach ($images[0] as $img) {
                if (strpos($img, 'alt=') !== false) {
                    $has_alt++;
                }
            }
            if ($has_alt < count($images[0])) {
                $recommendations[] = [
                    'type' => 'info',
                    'message' => __('Add alt text to your images.', 'seo-campaign-hub')
                ];
            }
        }

        // Internal links recommendations
        preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>/i', $content, $links);
        if (!empty($links[1])) {
            $internal_links = 0;
            $site_url = home_url();
            foreach ($links[1] as $link) {
                if (strpos($link, $site_url) !== false) {
                    $internal_links++;
                }
            }
            if ($internal_links < 2) {
                $recommendations[] = [
                    'type' => 'info',
                    'message' => __('Add more internal links to your content.', 'seo-campaign-hub')
                ];
            }
        }

        // Meta description recommendations
        $meta_description = get_post_meta($post_id, '_seo_campaign_hub_meta_description', true);
        if (empty($meta_description)) {
            $recommendations[] = [
                'type' => 'info',
                'message' => __('Add a meta description to improve click-through rates.', 'seo-campaign-hub')
            ];
        }

        // Score summary
        $recommendations[] = [
            'type' => 'summary',
            'message' => sprintf(
                __('SEO Score: %d/100 | Readability: %d/100', 'seo-campaign-hub'),
                $seo_score ?: 0,
                $readability_score ?: 0
            )
        ];

        return $recommendations;
    }
}
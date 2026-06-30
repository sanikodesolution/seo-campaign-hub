<?php
/**
 * Dashboard View
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;
$campaign_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sch_campaigns");
$offer_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sch_offers");
$link_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sch_links");
$analytics_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sch_analytics");

// Get recent campaigns
$recent_campaigns = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}sch_campaigns 
     ORDER BY created_at DESC 
     LIMIT 5"
);

// Get recent analytics
$recent_analytics = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}sch_analytics 
    
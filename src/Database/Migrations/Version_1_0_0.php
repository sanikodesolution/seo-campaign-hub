<?php
/**
 * Database Migration - Version 1.0.0
 *
 * @package SEO_Campaign_Hub\Database\Migrations
 */

namespace SEO_Campaign_Hub\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Version_1_0_0
 *
 * Initial database schema
 */
class Version_1_0_0 implements MigrationInterface {
    /**
     * Run the migration
     *
     * @return void
     */
    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Note: All tables are created in the Activator class
        // This migration is for future updates
        
        if ($wpdb->last_error) {
            error_log('SEO Campaign Hub - Migration 1.0.0 error: ' . $wpdb->last_error);
        }
    }

    /**
     * Rollback the migration
     *
     * @return void
     */
    public function down() {
        global $wpdb;
        // Rollback logic for future updates
    }
}
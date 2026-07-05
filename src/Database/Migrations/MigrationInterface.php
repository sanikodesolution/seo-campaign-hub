<?php
/**
 * Migration Interface
 *
 * @package SEO_Campaign_Hub\Database\Migrations
 */

namespace SEO_Campaign_Hub\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface MigrationInterface
 *
 * Defines the contract for database migrations
 */
interface MigrationInterface {
    /**
     * Run the migration
     *
     * @return void
     */
    public function up();

    /**
     * Rollback the migration
     *
     * @return void
     */
    public function down();
}
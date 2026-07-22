<?php
/**
 * Database Migration - Version 1.1.0
 *
 * Smart language/country redirect columns.
 *
 * @package SEO_Campaign_Hub\Database\Migrations
 */

namespace SEO_Campaign_Hub\Database\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Version_1_1_0
 */
class Version_1_1_0 implements MigrationInterface {

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public function up() {
		global $wpdb;

		$links     = $wpdb->prefix . 'sch_links';
		$analytics = $wpdb->prefix . 'sch_analytics';

		if ( $this->table_exists( $links ) ) {
			if ( ! $this->column_exists( $links, 'redirect_priority' ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefixed and trusted.
				$wpdb->query(
					"ALTER TABLE {$links} ADD COLUMN redirect_priority ENUM('language','country') NOT NULL DEFAULT 'language' AFTER redirect_type"
				);
			}
			if ( ! $this->column_exists( $links, 'targeting_rules' ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE {$links} ADD COLUMN targeting_rules JSON DEFAULT NULL AFTER redirect_priority"
				);
			}
		}

		if ( $this->table_exists( $analytics ) ) {
			if ( ! $this->column_exists( $analytics, 'language' ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE {$analytics} ADD COLUMN language CHAR(2) DEFAULT NULL AFTER country"
				);
			}
			if ( $this->column_exists( $analytics, 'language' ) && ! $this->index_exists( $analytics, 'language' ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$analytics} ADD KEY language (language)" );
			}
		}

		if ( $wpdb->last_error && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'SEO Campaign Hub - Migration 1.1.0 error: ' . $wpdb->last_error );
		}
	}

	/**
	 * Rollback the migration.
	 *
	 * @return void
	 */
	public function down() {
		global $wpdb;

		$links     = $wpdb->prefix . 'sch_links';
		$analytics = $wpdb->prefix . 'sch_analytics';

		if ( $this->table_exists( $links ) ) {
			if ( $this->column_exists( $links, 'targeting_rules' ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$links} DROP COLUMN targeting_rules" );
			}
			if ( $this->column_exists( $links, 'redirect_priority' ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$links} DROP COLUMN redirect_priority" );
			}
		}

		if ( $this->table_exists( $analytics ) ) {
			if ( $this->index_exists( $analytics, 'language' ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$analytics} DROP INDEX language" );
			}
			if ( $this->column_exists( $analytics, 'language' ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$analytics} DROP COLUMN language" );
			}
		}
	}

	/**
	 * @param string $table Full table name.
	 * @return bool
	 */
	private function table_exists( $table ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);
	}

	/**
	 * @param string $table  Full table name.
	 * @param string $column Column name.
	 * @return bool
	 */
	private function column_exists( $table, $column ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) );
	}

	/**
	 * @param string $table Full table name.
	 * @param string $index Index name.
	 * @return bool
	 */
	private function index_exists( $table, $index ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A );
		if ( ! is_array( $indexes ) ) {
			return false;
		}
		foreach ( $indexes as $row ) {
			if ( isset( $row['Key_name'] ) && $row['Key_name'] === $index ) {
				return true;
			}
		}
		return false;
	}
}

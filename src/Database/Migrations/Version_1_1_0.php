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
			// VARCHAR/LONGTEXT for broad MySQL/MariaDB compatibility (avoid JSON type / AFTER).
			if ( ! $this->column_exists( $links, 'redirect_priority' ) ) {
				$this->safe_query(
					"ALTER TABLE {$links} ADD COLUMN redirect_priority VARCHAR(20) NOT NULL DEFAULT 'language'"
				);
			}
			if ( ! $this->column_exists( $links, 'targeting_rules' ) ) {
				$this->safe_query(
					"ALTER TABLE {$links} ADD COLUMN targeting_rules LONGTEXT NULL"
				);
			}
		}

		if ( $this->table_exists( $analytics ) ) {
			if ( ! $this->column_exists( $analytics, 'language' ) ) {
				$this->safe_query(
					"ALTER TABLE {$analytics} ADD COLUMN language CHAR(2) DEFAULT NULL"
				);
			}
			if ( $this->column_exists( $analytics, 'language' ) && ! $this->index_exists( $analytics, 'language' ) ) {
				$this->safe_query( "ALTER TABLE {$analytics} ADD KEY language (language)" );
			}
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
				$this->safe_query( "ALTER TABLE {$links} DROP COLUMN targeting_rules" );
			}
			if ( $this->column_exists( $links, 'redirect_priority' ) ) {
				$this->safe_query( "ALTER TABLE {$links} DROP COLUMN redirect_priority" );
			}
		}

		if ( $this->table_exists( $analytics ) ) {
			if ( $this->index_exists( $analytics, 'language' ) ) {
				$this->safe_query( "ALTER TABLE {$analytics} DROP INDEX language" );
			}
			if ( $this->column_exists( $analytics, 'language' ) ) {
				$this->safe_query( "ALTER TABLE {$analytics} DROP COLUMN language" );
			}
		}
	}

	/**
	 * Run a query without letting SQL failures crash the site.
	 *
	 * @param string $sql SQL statement.
	 * @return void
	 */
	private function safe_query( $sql ) {
		global $wpdb;

		try {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- DDL with trusted prefixed table names.
			$wpdb->query( $sql );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'SEO Campaign Hub migration 1.1.0: ' . $e->getMessage() );
			}
		}

		if ( ! empty( $wpdb->last_error ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'SEO Campaign Hub migration 1.1.0 SQL: ' . $wpdb->last_error );
			$wpdb->last_error = '';
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
		return (bool) $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$table}` LIKE %s", $column ) );
	}

	/**
	 * @param string $table Full table name.
	 * @param string $index Index name.
	 * @return bool
	 */
	private function index_exists( $table, $index ) {
		global $wpdb;
		try {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}`", ARRAY_A );
		} catch ( \Throwable $e ) {
			return false;
		}
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

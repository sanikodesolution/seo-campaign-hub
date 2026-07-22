<?php
/**
 * Database Manager
 *
 * @package SEO_Campaign_Hub\Database
 */

namespace SEO_Campaign_Hub\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Database
 *
 * Main database manager class
 */
class Database {
    /**
     * Database version
     *
     * @var string
     */
    private $db_version = '1.1.1';

    /**
     * Table prefix
     *
     * @var string
     */
    private $prefix;

    /**
     * WordPress database object
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * List of tables
     *
     * @var array
     */
    private $tables = [];

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'sch_';
        $this->define_tables();
    }

    /**
     * Initialize the database
     *
     * @return void
     */
    public function init() {
        try {
            $installed_version = get_option('seo_campaign_hub_db_version', '0.0.0');

            if (version_compare((string) $installed_version, $this->db_version, '<')) {
                $this->upgrade($installed_version);
            }

            // If a previous failed upgrade marked the schema done too early, retry.
            if (!$this->has_localization_columns()) {
                delete_option('seo_campaign_hub_schema_1_1_0');
            }

            // Guarantee 1.1.0 columns after zip upload without relying only on activate.
            if (get_option('seo_campaign_hub_schema_1_1_0') !== 'yes') {
                require_once SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Database/Migrations/MigrationInterface.php';
                require_once SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Database/Migrations/Version_1_1_0.php';
                (new \SEO_Campaign_Hub\Database\Migrations\Version_1_1_0())->up();
                if ($this->has_localization_columns()) {
                    update_option('seo_campaign_hub_schema_1_1_0', 'yes');
                    update_option('seo_campaign_hub_db_version', $this->db_version);
                    set_transient('seo_campaign_hub_show_setup_notice', '1.1.0', WEEK_IN_SECONDS);
                }
            }
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('SEO Campaign Hub database init failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Whether localization columns exist on live tables.
     *
     * @return bool
     */
    private function has_localization_columns() {
        global $wpdb;

        $links = $wpdb->prefix . 'sch_links';
        $analytics = $wpdb->prefix . 'sch_analytics';

        if (!(bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $links))) {
            // Fresh / missing tables — activator handles creation; don't block boot.
            return true;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $has_priority = (bool) $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$links}` LIKE %s", 'redirect_priority'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $has_rules = (bool) $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$links}` LIKE %s", 'targeting_rules'));

        $has_language = true;
        if ((bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $analytics))) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $has_language = (bool) $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$analytics}` LIKE %s", 'language'));
        }

        return $has_priority && $has_rules && $has_language;
    }

    /**
     * Define table names
     *
     * @return void
     */
    private function define_tables() {
        $this->tables = [
            'campaigns'       => $this->prefix . 'campaigns',
            'offers'          => $this->prefix . 'offers',
            'links'           => $this->prefix . 'links',
            'analytics'       => $this->prefix . 'analytics',
            'qr_codes'        => $this->prefix . 'qr_codes',
            'redirects'       => $this->prefix . 'redirects',
            'schemas'         => $this->prefix . 'schemas',
            'internal_links'  => $this->prefix . 'internal_links',
            'campaign_offers' => $this->prefix . 'campaign_offers',
            'ab_tests'        => $this->prefix . 'ab_tests',
            'export_logs'     => $this->prefix . 'export_logs',
            'system_logs'     => $this->prefix . 'system_logs'
        ];
    }

    /**
     * Get table name
     *
     * @param string $table Table key
     * @return string
     * @throws \Exception
     */
    public function get_table($table) {
        if (!isset($this->tables[$table])) {
            throw new \Exception("Table '{$table}' not defined");
        }
        return $this->tables[$table];
    }

    /**
     * Get all table names
     *
     * @return array
     */
    public function get_all_tables() {
        return $this->tables;
    }

    /**
     * Get the prefix
     *
     * @return string
     */
    public function get_prefix() {
        return $this->prefix;
    }

    /**
     * Upgrade database
     *
     * @param string $from_version Current version
     * @return void
     */
    public function upgrade($from_version) {
        try {
            $migration_manager = new MigrationManager();
            $migration_manager->upgrade($from_version, $this->db_version);
            update_option('seo_campaign_hub_db_version', $this->db_version);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('SEO Campaign Hub upgrade failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if a table exists
     *
     * @param string $table Table key
     * @return bool
     */
    public function table_exists($table) {
        try {
            $table_name = $this->get_table($table);
            return $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SHOW TABLES LIKE %s",
                    $table_name
                )
            ) === $table_name;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get table columns
     *
     * @param string $table Table key
     * @return array
     */
    public function get_table_columns($table) {
        try {
            $table_name = $this->get_table($table);
            return $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SHOW COLUMNS FROM {$table_name}"
                ),
                ARRAY_A
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get table row count
     *
     * @param string $table Table key
     * @return int
     */
    public function get_table_row_count($table) {
        try {
            $table_name = $this->get_table($table);
            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name}"
                )
            );
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Begin transaction
     *
     * @return void
     */
    public function begin_transaction() {
        $this->wpdb->query('START TRANSACTION');
    }

    /**
     * Commit transaction
     *
     * @return void
     */
    public function commit() {
        $this->wpdb->query('COMMIT');
    }

    /**
     * Rollback transaction
     *
     * @return void
     */
    public function rollback() {
        $this->wpdb->query('ROLLBACK');
    }

    /**
     * Get last insert ID
     *
     * @return int
     */
    public function last_insert_id() {
        return $this->wpdb->insert_id;
    }

    /**
     * Get rows affected
     *
     * @return int
     */
    public function rows_affected() {
        return $this->wpdb->rows_affected;
    }

    /**
     * Get last error
     *
     * @return string
     */
    public function last_error() {
        return $this->wpdb->last_error;
    }

    /**
     * Prepare SQL query
     *
     * @param string $query SQL query with placeholders
     * @param mixed  ...$args Arguments
     * @return string
     */
    public function prepare($query, ...$args) {
        return $this->wpdb->prepare($query, ...$args);
    }

    /**
     * Execute query
     *
     * @param string $query SQL query
     * @return int|bool
     */
    public function query($query) {
        return $this->wpdb->query($query);
    }

    /**
     * Get single variable
     *
     * @param string $query SQL query
     * @param int    $x     Column offset
     * @param int    $y     Row offset
     * @return string|null
     */
    public function get_var($query, $x = 0, $y = 0) {
        return $this->wpdb->get_var($query, $x, $y);
    }

    /**
     * Get single row
     *
     * @param string $query SQL query
     * @param string $output Output type
     * @param int    $y Row offset
     * @return object|array|null
     */
    public function get_row($query, $output = OBJECT, $y = 0) {
        return $this->wpdb->get_row($query, $output, $y);
    }

    /**
     * Get multiple rows
     *
     * @param string $query SQL query
     * @param string $output Output type
     * @return array
     */
    public function get_results($query, $output = OBJECT) {
        return $this->wpdb->get_results($query, $output);
    }

    /**
     * Get column
     *
     * @param string $query SQL query
     * @param int    $x Column offset
     * @return array
     */
    public function get_col($query, $x = 0) {
        return $this->wpdb->get_col($query, $x);
    }

    /**
     * Insert data
     *
     * @param string $table Table key
     * @param array  $data Data to insert
     * @param array  $format Data format
     * @return int|false
     */
    public function insert($table, $data, $format = null) {
        try {
            $table_name = $this->get_table($table);
            $result = $this->wpdb->insert($table_name, $data, $format);
            
            if ($result === false) {
                return false;
            }
            
            return $this->wpdb->insert_id;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update data
     *
     * @param string $table Table key
     * @param array  $data Data to update
     * @param array  $where Where conditions
     * @param array  $format Data format
     * @param array  $where_format Where format
     * @return int|false
     */
    public function update($table, $data, $where, $format = null, $where_format = null) {
        try {
            $table_name = $this->get_table($table);
            $result = $this->wpdb->update(
                $table_name,
                $data,
                $where,
                $format,
                $where_format
            );
            
            if ($result === false) {
                return false;
            }
            
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete data
     *
     * @param string $table Table key
     * @param array  $where Where conditions
     * @param array  $where_format Where format
     * @return int|false
     */
    public function delete($table, $where, $where_format = null) {
        try {
            $table_name = $this->get_table($table);
            $result = $this->wpdb->delete(
                $table_name,
                $where,
                $where_format
            );
            
            if ($result === false) {
                return false;
            }
            
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Escape data
     *
     * @param mixed $data Data to escape
     * @return string
     */
    public function escape($data) {
        return $this->wpdb->_real_escape($data);
    }

    /**
     * Get charset collation
     *
     * @return string
     */
    public function get_charset_collate() {
        return $this->wpdb->get_charset_collate();
    }
}
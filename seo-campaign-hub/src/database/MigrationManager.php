<?php
/**
 * Migration Manager
 *
 * @package SEO_Campaign_Hub\Database
 */

namespace SEO_Campaign_Hub\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MigrationManager
 *
 * Manages database migrations
 */
class MigrationManager {
    /**
     * WordPress database object
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Table prefix
     *
     * @var string
     */
    private $prefix;

    /**
     * Migrations directory
     *
     * @var string
     */
    private $migrations_dir;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'sch_';
        $this->migrations_dir = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Database/Migrations/';
    }

    /**
     * Upgrade database
     *
     * @param string $from_version Current version
     * @param string $to_version Target version
     * @return void
     */
    public function upgrade($from_version, $to_version) {
        $migrations = $this->get_migrations($from_version, $to_version);
        
        if (empty($migrations)) {
            return;
        }

        $this->wpdb->query('START TRANSACTION');

        try {
            foreach ($migrations as $migration) {
                $this->run_migration($migration);
            }
            $this->wpdb->query('COMMIT');
        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Get migrations between versions
     *
     * @param string $from_version Current version
     * @param string $to_version Target version
     * @return array
     */
    private function get_migrations($from_version, $to_version) {
        $migrations = [];
        $files = glob($this->migrations_dir . '*.php');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (preg_match('/^Version_([0-9_]+)$/', $filename, $matches)) {
                $version = str_replace('_', '.', $matches[1]);
                if (version_compare($version, $from_version, '>') && 
                    version_compare($version, $to_version, '<=')) {
                    $migrations[$version] = $file;
                }
            }
        }
        
        ksort($migrations);
        return $migrations;
    }

    /**
     * Run a migration
     *
     * @param string $file Migration file path
     * @return void
     */
    private function run_migration($file) {
        require_once $file;
        
        $class_name = 'SEO_Campaign_Hub\\Database\\Migrations\\' . 
                     basename($file, '.php');
        
        if (!class_exists($class_name)) {
            throw new \Exception("Migration class '{$class_name}' not found");
        }
        
        $migration = new $class_name();
        
        if (!$migration instanceof MigrationInterface) {
            throw new \Exception("Migration must implement MigrationInterface");
        }
        
        $migration->up();
    }

    /**
     * Get migration status
     *
     * @return array
     */
    public function get_migration_status() {
        $status = [];
        $files = glob($this->migrations_dir . '*.php');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (preg_match('/^Version_([0-9_]+)$/', $filename, $matches)) {
                $version = str_replace('_', '.', $matches[1]);
                $status[$version] = [
                    'file' => $file,
                    'applied' => version_compare(
                        $version,
                        get_option('seo_campaign_hub_db_version', '0.0.0'),
                        '<='
                    )
                ];
            }
        }
        
        ksort($status);
        return $status;
    }

    /**
     * Create a new migration
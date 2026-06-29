<?php
/**
 * Base Model
 *
 * @package SEO_Campaign_Hub\Database\Models
 */

namespace SEO_Campaign_Hub\Database\Models;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BaseModel
 *
 * Abstract base class for all models
 */
abstract class BaseModel implements ModelInterface {
    /**
     * Database instance
     *
     * @var \SEO_Campaign_Hub\Database\Database
     */
    protected $db;

    /**
     * Table name
     *
     * @var string
     */
    protected $table;

    /**
     * Primary key
     *
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * Cache key
     *
     * @var string
     */
    protected $cache_key = '';

    /**
     * Cache expiration
     *
     * @var int
     */
    protected $cache_expiration = 3600;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new \SEO_Campaign_Hub\Database\Database();
        $this->table = $this->get_table();
        $this->cache_key = $this->get_cache_key();
    }

    /**
     * Get table name
     *
     * @return string
     */
    abstract public function get_table();

    /**
     * Get cache key
     *
     * @return string
     */
    protected function get_cache_key() {
        return 'seo_campaign_hub_' . str_replace('sch_', '', $this->table);
    }

    /**
     * Get primary key
     *
     * @return string
     */
    public function get_primary_key() {
        return $this->primary_key;
    }

    /**
     * Get all records
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_all($args = []) {
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'orderby' => $this->primary_key,
            'order' => 'DESC',
            'where' => []
        ];

        $args = wp_parse_args($args, $defaults);

        // Build query
        $query = "SELECT * FROM {$this->table}";

        // Where clause
        if (!empty($args['where'])) {
            $where_clauses = [];
            foreach ($args['where'] as $key => $value) {
                $where_clauses[] = $this->db->prepare(
                    "{$key} = %s",
                    $value
                );
            }
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // Order by
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Limit
        if ($args['limit'] > 0) {
            $query .= $this->db->prepare(
                " LIMIT %d OFFSET %d",
                $args['limit'],
                $args['offset']
            );
        }

        // Check cache
        $cache_key = md5($query);
        $results = wp_cache_get($cache_key, $this->cache_key);

        if ($results === false) {
            $results = $this->db->get_results($query);
            wp_cache_set($cache_key, $results, $this->cache_key, $this->cache_expiration);
        }

        return $results;
    }

    /**
     * Get a single record by ID
     *
     * @param int $id Record ID
     * @return object|null
     */
    public function get_by_id($id) {
        $cache_key = "id_{$id}";
        $result = wp_cache_get($cache_key, $this->cache_key);

        if ($result === false) {
            $query = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE {$this->primary_key} = %d",
                $id
            );
            $result = $this->db->get_row($query);
            wp_cache_set($cache_key, $result, $this->cache_key, $this->cache_expiration);
        }

        return $result;
    }

    /**
     * Get a single record by key
     *
     * @param string $key Record key
     * @return object|null
     */
    public function get_by_key($key) {
        $cache_key = "key_{$key}";
        $result = wp_cache_get($cache_key, $this->cache_key);

        if ($result === false) {
            $query = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE %s = %s",
                $this->get_key_column(),
                $key
            );
            $result = $this->db->get_row($query);
            wp_cache_set($cache_key, $result, $this->cache_key, $this->cache_expiration);
        }

        return $result;
    }

    /**
     * Get key column name
     *
     * @return string
     */
    protected function get_key_column() {
        return str_replace('sch_', '', $this->table) . '_key';
    }

    /**
     * Insert a new record
     *
     * @param array $data Record data
     * @return int|false
     */
    public function insert($data) {
        // Add timestamps
        if (!isset($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        $result = $this->db->insert($this->table, $data);

        if ($result) {
            $this->clear_cache();
        }

        return $result;
    }

    /**
     * Update a record
     *
     * @param int   $id Record ID
     * @param array $data Record data
     * @return int|false
     */
    public function update($id, $data) {
        // Update timestamp
        $data['updated_at'] = current_time('mysql');

        $result = $this->db->update(
            $this->table,
            $data,
            [$this->primary_key => $id]
        );

        if ($result !== false) {
            $this->clear_cache();
        }

        return $result;
    }

    /**
     * Delete a record
     *
     * @param int $id Record ID
     * @return bool
     */
    public function delete($id) {
        $result = $this->db->delete(
            $this->table,
            [$this->primary_key => $id]
        );

        if ($result !== false) {
            $this->clear_cache();
            return true;
        }

        return false;
    }

    /**
     * Get count of records
     *
     * @param array $args Query arguments
     * @return int
     */
    public function count($args = []) {
        $query = "SELECT COUNT(*) FROM {$this->table}";

        // Where clause
        if (!empty($args['where'])) {
            $where_clauses = [];
            foreach ($args['where'] as $key => $value) {
                $where_clauses[] = $this->db->prepare(
                    "{$key} = %s",
                    $value
                );
            }
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        return (int) $this->db->get_var($query);
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clear_cache() {
        wp_cache_delete($this->cache_key);
    }

    /**
     * Sanitize data before insert/update
     *
     * @param array $data Data to sanitize
     * @return array
     */
    protected function sanitize_data($data) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = sanitize_text_field($value);
            }
        }
        return $data;
    }

    /**
     * Get table schema
     *
     * @return array
     */
    public function get_schema() {
        return $this->db->get_table_columns($this->table);
    }

    /**
     * Check if table exists
     *
     * @return bool
     */
    public function table_exists() {
        return $this->db->table_exists($this->table);
    }

    /**
     * Get table row count
     *
     * @return int
     */
    public function get_row_count() {
        return $this->db->get_table_row_count($this->table);
    }

    /**
     * Begin transaction
     *
     * @return void
     */
    public function begin_transaction() {
        $this->db->begin_transaction();
    }

    /**
     * Commit transaction
     *
     * @return void
     */
    public function commit() {
        $this->db->commit();
    }

    /**
     * Rollback transaction
     *
     * @return void
     */
    public function rollback() {
        $this->db->rollback();
    }

    /**
     * Get last insert ID
     *
     * @return int
     */
    public function last_insert_id() {
        return $this->db->last_insert_id();
    }

    /**
     * Get rows affected
     *
     * @return int
     */
    public function rows_affected() {
        return $this->db->rows_affected();
    }

    /**
     * Get last error
     *
     * @return string
     */
    public function last_error() {
        return $this->db->last_error();
    }
}
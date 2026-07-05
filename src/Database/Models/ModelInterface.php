<?php
/**
 * Model Interface
 *
 * @package SEO_Campaign_Hub\Database\Models
 */

namespace SEO_Campaign_Hub\Database\Models;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface ModelInterface
 *
 * Defines the contract for all models
 */
interface ModelInterface {
    /**
     * Get the table name
     *
     * @return string
     */
    public function get_table();

    /**
     * Get the primary key
     *
     * @return string
     */
    public function get_primary_key();

    /**
     * Get all records
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_all($args = []);

    /**
     * Get a single record by ID
     *
     * @param int $id Record ID
     * @return object|null
     */
    public function get_by_id($id);

    /**
     * Get a single record by key
     *
     * @param string $key Record key
     * @return object|null
     */
    public function get_by_key($key);

    /**
     * Insert a new record
     *
     * @param array $data Record data
     * @return int|false
     */
    public function insert($data);

    /**
     * Update a record
     *
     * @param int   $id Record ID
     * @param array $data Record data
     * @return int|false
     */
    public function update($id, $data);

    /**
     * Delete a record
     *
     * @param int $id Record ID
     * @return bool
     */
    public function delete($id);

    /**
     * Get count of records
     *
     * @param array $args Query arguments
     * @return int
     */
    public function count($args = []);
}
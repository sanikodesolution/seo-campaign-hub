<?php
/**
 * Repository Interface
 *
 * @package SEO_Campaign_Hub\Database\Repositories
 */

namespace SEO_Campaign_Hub\Database\Repositories;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface RepositoryInterface
 *
 * Defines the contract for all repositories
 */
interface RepositoryInterface {
    /**
     * Get the model instance
     *
     * @return \SEO_Campaign_Hub\Database\Models\ModelInterface
     */
    public function get_model();

    /**
     * Find by ID
     *
     * @param int $id Record ID
     * @return object|null
     */
    public function find($id);

    /**
     * Find by key
     *
     * @param string $key Record key
     * @return object|null
     */
    public function find_by_key($key);

    /**
     * Get all records
     *
     * @param array $args Query arguments
     * @return array
     */
    public function all($args = []);

    /**
     * Create a new record
     *
     * @param array $data Record data
     * @return int|false
     */
    public function create($data);

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
     * Count records
     *
     * @param array $args Query arguments
     * @return int
     */
    public function count($args = []);
}
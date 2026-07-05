<?php
/**
 * Base Repository
 *
 * @package SEO_Campaign_Hub\Database\Repositories
 */

namespace SEO_Campaign_Hub\Database\Repositories;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BaseRepository
 *
 * Abstract base class for all repositories
 */
abstract class BaseRepository implements RepositoryInterface {
    /**
     * Model instance
     *
     * @var \SEO_Campaign_Hub\Database\Models\ModelInterface
     */
    protected $model;

    /**
     * Constructor
     *
     * @param \SEO_Campaign_Hub\Database\Models\ModelInterface $model Model instance
     */
    public function __construct($model) {
        $this->model = $model;
    }

    /**
     * Get the model instance
     *
     * @return \SEO_Campaign_Hub\Database\Models\ModelInterface
     */
    public function get_model() {
        return $this->model;
    }

    /**
     * Find by ID
     *
     * @param int $id Record ID
     * @return object|null
     */
    public function find($id) {
        return $this->model->get_by_id($id);
    }

    /**
     * Find by key
     *
     * @param string $key Record key
     * @return object|null
     */
    public function find_by_key($key) {
        return $this->model->get_by_key($key);
    }

    /**
     * Get all records
     *
     * @param array $args Query arguments
     * @return array
     */
    public function all($args = []) {
        return $this->model->get_all($args);
    }

    /**
     * Create a new record
     *
     * @param array $data Record data
     * @return int|false
     */
    public function create($data) {
        return $this->model->insert($data);
    }

    /**
     * Update a record
     *
     * @param int   $id Record ID
     * @param array $data Record data
     * @return int|false
     */
    public function update($id, $data) {
        return $this->model->update($id, $data);
    }

    /**
     * Delete a record
     *
     * @param int $id Record ID
     * @return bool
     */
    public function delete($id) {
        return $this->model->delete($id);
    }

    /**
     * Count records
     *
     * @param array $args Query arguments
     * @return int
     */
    public function count($args = []) {
        return $this->model->count($args);
    }

    /**
     * Find by multiple conditions
     *
     * @param array $where Where conditions
     * @param array $args Additional query arguments
     * @return array
     */
    public function find_by($where, $args = []) {
        $args['where'] = $where;
        return $this->model->get_all($args);
    }

    /**
     * Find one by conditions
     *
     * @param array $where Where conditions
     * @return object|null
     */
    public function find_one_by($where) {
        $results = $this->find_by($where, ['limit' => 1]);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Paginate results
     *
     * @param array $args Query arguments
     * @return array
     */
    public function paginate($args = []) {
        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'orderby' => 'id',
            'order' => 'DESC',
            'where' => []
        ];

        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $query_args = [
            'limit' => $args['per_page'],
            'offset' => $offset,
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'where' => $args['where']
        ];

        $results = $this->model->get_all($query_args);
        $total = $this->model->count(['where' => $args['where']]);

        return [
            'data' => $results,
            'total' => $total,
            'page' => $args['page'],
            'per_page' => $args['per_page'],
            'total_pages' => ceil($total / $args['per_page'])
        ];
    }

    /**
     * Bulk insert records
     *
     * @param array $records Array of record data
     * @return bool
     */
    public function bulk_insert($records) {
        $this->model->begin_transaction();

        try {
            foreach ($records as $record) {
                $result = $this->model->insert($record);
                if ($result === false) {
                    throw new \Exception('Bulk insert failed');
                }
            }
            $this->model->commit();
            return true;
        } catch (\Exception $e) {
            $this->model->rollback();
            return false;
        }
    }

    /**
     * Bulk update records
     *
     * @param array $where Where conditions
     * @param array $data Data to update
     * @return int|false
     */
    public function bulk_update($where, $data) {
        $records = $this->find_by($where);

        if (empty($records)) {
            return 0;
        }

        $this->model->begin_transaction();
        $updated = 0;

        try {
            foreach ($records as $record) {
                $result = $this->model->update($record->id, $data);
                if ($result !== false) {
                    $updated++;
                }
            }
            $this->model->commit();
            return $updated;
        } catch (\Exception $e) {
            $this->model->rollback();
            return false;
        }
    }

    /**
     * Bulk delete records
     *
     * @param array $where Where conditions
     * @return int|false
     */
    public function bulk_delete($where) {
        $records = $this->find_by($where);

        if (empty($records)) {
            return 0;
        }

        $this->model->begin_transaction();
        $deleted = 0;

        try {
            foreach ($records as $record) {
                if ($this->model->delete($record->id)) {
                    $deleted++;
                }
            }
            $this->model->commit();
            return $deleted;
        } catch (\Exception $e) {
            $this->model->rollback();
            return false;
        }
    }
}
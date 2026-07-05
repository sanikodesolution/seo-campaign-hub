<?php
/**
 * Dependency Injection Container
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Container
 *
 * Simple dependency injection container with singleton support
 */
class Container {
    /**
     * Registered services
     *
     * @var array
     */
    private $services = [];

    /**
     * Resolved singleton instances
     *
     * @var array
     */
    private $singletons = [];

    /**
     * Service definitions
     *
     * @var array
     */
    private $definitions = [];

    /**
     * Register a service
     *
     * @param string   $name     Service name
     * @param callable $resolver Resolver function
     * @param bool     $singleton Whether to register as singleton
     * @return self
     */
    public function register($name, callable $resolver, $singleton = false) {
        $this->services[$name] = $resolver;
        $this->definitions[$name] = [
            'singleton' => $singleton,
            'resolved' => false
        ];
        return $this;
    }

    /**
     * Register a singleton service
     *
     * @param string   $name     Service name
     * @param callable $resolver Resolver function
     * @return self
     */
    public function singleton($name, callable $resolver) {
        return $this->register($name, $resolver, true);
    }

    /**
     * Get a service instance
     *
     * @param string $name Service name
     * @return mixed
     * @throws \Exception
     */
    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new \Exception(
                sprintf('Service "%s" not found in container', $name)
            );
        }

        // Check if this is a singleton and already resolved
        if ($this->is_singleton($name) && isset($this->singletons[$name])) {
            return $this->singletons[$name];
        }

        // Resolve the service
        $service = call_user_func($this->services[$name]);

        // Store if it's a singleton
        if ($this->is_singleton($name)) {
            $this->singletons[$name] = $service;
        }

        return $service;
    }

    /**
     * Check if a service is a singleton
     *
     * @param string $name Service name
     * @return bool
     */
    private function is_singleton($name) {
        return isset($this->definitions[$name]) && 
               $this->definitions[$name]['singleton'] === true;
    }

    /**
     * Check if a service exists
     *
     * @param string $name Service name
     * @return bool
     */
    public function has($name) {
        return isset($this->services[$name]);
    }

    /**
     * Remove a service from the container
     *
     * @param string $name Service name
     * @return self
     */
    public function remove($name) {
        unset($this->services[$name]);
        unset($this->singletons[$name]);
        unset($this->definitions[$name]);
        return $this;
    }

    /**
     * Get all registered service names
     *
     * @return array
     */
    public function get_service_names() {
        return array_keys($this->services);
    }

    /**
     * Clear all services
     *
     * @return self
     */
    public function clear() {
        $this->services = [];
        $this->singletons = [];
        $this->definitions = [];
        return $this;
    }

    /**
     * Magic method to get a service
     *
     * @param string $name Service name
     * @return mixed
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * Magic method to check if a service exists
     *
     * @param string $name Service name
     * @return bool
     */
    public function __isset($name) {
        return $this->has($name);
    }

    /**
     * Magic method to unset a service
     *
     * @param string $name Service name
     * @return void
     */
    public function __unset($name) {
        $this->remove($name);
    }
}
<?php
/**
 * Dependency Injection Container
 *
 * @package SEO_Campaign_Hub\Core
 */

// Prevent direct access — MUST be before namespace
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

namespace SEO_Campaign_Hub\Core;

/**
 * Class Container
 *
 * Simple dependency injection container with singleton support.
 */
class Container {

    /**
     * Registered service resolver closures.
     *
     * @var array<string, callable>
     */
    private array $services = [];

    /**
     * Resolved singleton instances.
     *
     * @var array<string, mixed>
     */
    private array $singletons = [];

    /**
     * Service metadata (singleton flag, resolved flag).
     *
     * @var array<string, array{singleton: bool, resolved: bool}>
     */
    private array $definitions = [];

    // =========================================================
    // REGISTRATION
    // =========================================================

    /**
     * Register a service with a resolver callable.
     *
     * @param string   $name      Service name/key.
     * @param callable $resolver  Callable that returns the service instance.
     * @param bool     $singleton Whether to cache the resolved instance.
     * @return static
     */
    public function register( string $name, callable $resolver, bool $singleton = false ): static {
        $this->services[ $name ]   = $resolver;
        $this->definitions[ $name ] = [
            'singleton' => $singleton,
            'resolved'  => false,
        ];
        return $this;
    }

    /**
     * Register a singleton service — resolved only once, then cached.
     *
     * @param string   $name     Service name/key.
     * @param callable $resolver Callable that returns the service instance.
     * @return static
     */
    public function singleton( string $name, callable $resolver ): static {
        return $this->register( $name, $resolver, true );
    }

    // =========================================================
    // RESOLUTION
    // =========================================================

    /**
     * Resolve and return a service instance.
     *
     * @param string $name Service name/key.
     * @return mixed
     * @throws \InvalidArgumentException When the service is not registered.
     * @throws \RuntimeException         When the resolver returns a non-object for a singleton.
     */
    public function get( string $name ): mixed {
        if ( ! isset( $this->services[ $name ] ) ) {
            throw new \InvalidArgumentException(
                sprintf( 'SEO Campaign Hub Container: service "%s" is not registered.', esc_html( $name ) )
            );
        }

        // Return cached singleton if already resolved
        if ( $this->is_singleton( $name ) && isset( $this->singletons[ $name ] ) ) {
            return $this->singletons[ $name ];
        }

        // Resolve the service
        $instance = ( $this->services[ $name ] )();

        // Cache singleton instances
        if ( $this->is_singleton( $name ) ) {
            $this->singletons[ $name ]              = $instance;
            $this->definitions[ $name ]['resolved'] = true;
        }

        return $instance;
    }

    /**
     * Check whether a service is registered.
     *
     * @param string $name Service name/key.
     * @return bool
     */
    public function has( string $name ): bool {
        return isset( $this->services[ $name ] );
    }

    /**
     * Check whether a singleton service has already been resolved.
     *
     * @param string $name Service name/key.
     * @return bool
     */
    public function is_resolved( string $name ): bool {
        return isset( $this->singletons[ $name ] );
    }

    // =========================================================
    // MANAGEMENT
    // =========================================================

    /**
     * Remove a service from the container.
     *
     * @param string $name Service name/key.
     * @return static
     */
    public function remove( string $name ): static {
        unset(
            $this->services[ $name ],
            $this->singletons[ $name ],
            $this->definitions[ $name ]
        );
        return $this;
    }

    /**
     * Return all registered service names.
     *
     * @return string[]
     */
    public function get_service_names(): array {
        return array_keys( $this->services );
    }

    /**
     * Clear all registered services and resolved instances.
     *
     * @return static
     */
    public function clear(): static {
        $this->services    = [];
        $this->singletons  = [];
        $this->definitions = [];
        return $this;
    }

    // =========================================================
    // MAGIC ACCESSORS
    // =========================================================

    /**
     * Allow $container->serviceName as a shorthand for $container->get('serviceName').
     *
     * @param string $name Service name.
     * @return mixed
     */
    public function __get( string $name ): mixed {
        return $this->get( $name );
    }

    /**
     * Allow isset($container->serviceName).
     *
     * @param string $name Service name.
     * @return bool
     */
    public function __isset( string $name ): bool {
        return $this->has( $name );
    }

    /**
     * Allow unset($container->serviceName).
     *
     * @param string $name Service name.
     * @return void
     */
    public function __unset( string $name ): void {
        $this->remove( $name );
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Check whether a service is registered as a singleton.
     *
     * @param string $name Service name.
     * @return bool
     */
    private function is_singleton( string $name ): bool {
        return isset( $this->definitions[ $name ] ) &&
               $this->definitions[ $name ]['singleton'] === true;
    }
}

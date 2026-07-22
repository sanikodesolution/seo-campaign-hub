<?php
/**
 * PSR-4 Autoloader for SEO Campaign Hub
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Autoloader
 *
 * Handles PSR-4 autoloading for the plugin with WordPress compatibility.
 */
class Autoloader {

    /**
     * Registered namespaces and their base directories.
     *
     * @var array<string, string>
     */
    private static array $namespaces = [];

    /**
     * Register the autoloader with PHP and add the plugin namespace.
     *
     * @return void
     */
    public static function register(): void {
        spl_autoload_register( [ __CLASS__, 'load' ] );

        // Map the root plugin namespace → src/
        // e.g. SEO_Campaign_Hub\Core\Plugin → src/Core/Plugin.php
        self::add_namespace(
            'SEO_Campaign_Hub\\',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR
        );

        // Load Composer autoloader if present (third-party libraries)
        self::register_composer();
    }

    /**
     * Load Composer's autoloader if vendor/autoload.php exists.
     *
     * @return void
     */
    private static function register_composer(): void {
        $composer_autoload = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        if ( file_exists( $composer_autoload ) ) {
            require_once $composer_autoload;
        }
    }

    /**
     * Add a namespace → base directory mapping.
     *
     * @param string $namespace Namespace prefix (with trailing backslash).
     * @param string $base_dir  Absolute path to the base directory (with trailing slash).
     * @return void
     */
    public static function add_namespace( string $namespace, string $base_dir ): void {
        self::$namespaces[ $namespace ] = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;
    }

    /**
     * Return all registered namespace → directory mappings.
     *
     * @return array<string, string>
     */
    public static function get_namespaces(): array {
        return self::$namespaces;
    }

    /**
     * PSR-4 class loader — called by spl_autoload_register.
     *
     * @param string $class Fully qualified class name.
     * @return bool True if the file was loaded, false otherwise.
     */
    public static function load( string $class ): bool {
        foreach ( self::$namespaces as $namespace => $base_dir ) {
            if ( strpos( $class, $namespace ) !== 0 ) {
                continue;
            }

            // Strip the namespace prefix, convert to file path
            $relative = substr( $class, strlen( $namespace ) );
            $file     = $base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';

            $resolved = self::resolve_file( $file );
            if ( null !== $resolved ) {
                require_once $resolved;
                return true;
            }

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( sprintf( 'SEO Campaign Hub Autoloader: file not found for class %s → %s', $class, $file ) );
            }
        }

        return false;
    }

    /**
     * Resolve a class file path, including lowercase legacy filenames.
     *
     * @param string $file Expected absolute file path.
     * @return string|null
     */
    private static function resolve_file( string $file ): ?string {
        if ( is_readable( $file ) ) {
            return $file;
        }

        $directory = dirname( $file );
        $basename  = basename( $file );

        // Fast path: common WordPress legacy lowercase class files (no glob needed).
        $lowercase = $directory . DIRECTORY_SEPARATOR . strtolower( $basename );
        if ( $lowercase !== $file && is_readable( $lowercase ) ) {
            return $lowercase;
        }

        return self::find_case_insensitive_file( $file );
    }

    /**
     * Find a file in the same directory ignoring filename case.
     *
     * @param string $file Expected absolute file path.
     * @return string|null Matched absolute path or null if not found.
     */
    private static function find_case_insensitive_file( string $file ): ?string {
        $directory = dirname( $file );
        if ( ! is_dir( $directory ) ) {
            return null;
        }

        $expected = strtolower( basename( $file ) );

        // Prefer scandir — available even when glob() is disabled on some hosts.
        $entries = @scandir( $directory ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if ( is_array( $entries ) ) {
            foreach ( $entries as $entry ) {
                if ( substr( $entry, -4 ) !== '.php' ) {
                    continue;
                }
                if ( strtolower( $entry ) === $expected ) {
                    return $directory . DIRECTORY_SEPARATOR . $entry;
                }
            }
        }

        if ( function_exists( 'glob' ) ) {
            $matches = glob( $directory . DIRECTORY_SEPARATOR . '*.php' );
            if ( is_array( $matches ) ) {
                foreach ( $matches as $candidate ) {
                    if ( strtolower( basename( $candidate ) ) === $expected ) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }
}

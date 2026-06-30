<?php
/**
 * PSR-4 Autoloader for SEO Campaign Hub
 *
 * @package SEO_Campaign_Hub\Core
 */

namespace SEO_Campaign_Hub\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Autoloader
 *
 * Handles PSR-4 autoloading for the plugin with WordPress compatibility
 */
class Autoloader {
    /**
     * Registered namespaces and their paths
     *
     * @var array
     */
    private static $namespaces = [];

    /**
     * Register the autoloader
     *
     * @return void
     */
    public static function register() {
        // Register the autoloader with spl_autoload
        spl_autoload_register([__CLASS__, 'load']);
        
        // Register plugin namespace
        self::add_namespace(
            'SEO_Campaign_Hub\\',
            SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/'
        );
        
        // Register Composer autoloader if it exists
        self::register_composer();
    }

    /**
     * Register Composer autoloader
     *
     * @return void
     */
    private static function register_composer() {
        $composer_autoload = SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'vendor/autoload.php';
        
        if (file_exists($composer_autoload)) {
            require_once $composer_autoload;
        }
    }

    /**
     * Add a namespace with its base directory
     *
     * @param string $namespace The namespace
     * @param string $base_dir The base directory
     * @return void
     */
    public static function add_namespace($namespace, $base_dir) {
        self::$namespaces[$namespace] = trailingslashit($base_dir);
    }

    /**
     * Get all registered namespaces
     *
     * @return array
     */
    public static function get_namespaces() {
        return self::$namespaces;
    }

    /**
     * Load a class using PSR-4
     *
     * @param string $class The fully qualified class name
     * @return bool True if loaded, false otherwise
     */
    public static function load($class) {
        // Check if the class belongs to our namespace
        foreach (self::$namespaces as $namespace => $base_dir) {
            if (strpos($class, $namespace) === 0) {
                $relative_class = substr($class, strlen($namespace));
                $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
                
                // Normalize file path
                $file = self::normalize_file_path($file);
                
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Normalize file path for Windows compatibility
     *
     * @param string $path The file path
     * @return string
     */
    private static function normalize_file_path($path) {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }
}

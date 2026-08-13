<?php
/**
 * PHPUnit bootstrap (no WordPress).
 *
 * @package SEO_Campaign_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/sch-wp/' );
}

$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( is_file( $autoload ) ) {
	require $autoload;
} else {
	require dirname( __DIR__ ) . '/src/Services/SeoMetaResolver.php';
}

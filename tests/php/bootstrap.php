<?php
/**
 * PHPUnit bootstrap file
 *
 * @package SEOGenerator
 */

// Composer autoloader must be loaded before WordPress test suite.
require_once dirname( dirname( __DIR__ ) ) . '/vendor/autoload.php';

// Get WordPress test suite location from environment or use default.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills to WordPress bootstrap.
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php\n";
	echo "Please set WP_TESTS_DIR environment variable to point to your WordPress test suite.\n";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __DIR__ ) ) . '/content-generator.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

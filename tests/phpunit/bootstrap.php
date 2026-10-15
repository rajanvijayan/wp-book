<?php
/**
 * PHPUnit bootstrap: loads the WordPress test library, then BookPress.
 *
 * Set WP_TESTS_DIR to the wordpress-develop tests/phpunit folder (bin/install-wp-tests.sh does this).
 *
 * @package WPBook
 */

$wpbook_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $wpbook_tests_dir ) {
	$wpbook_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}
if ( ! file_exists( $wpbook_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Could not find the WordPress test library in $wpbook_tests_dir. Run bin/install-wp-tests.sh first.\n" );
	exit( 1 );
}

// The polyfills must be loaded before the test library.
require_once dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once $wpbook_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__, 2 ) . '/wp-book.php';
	}
);

require $wpbook_tests_dir . '/includes/bootstrap.php';
require __DIR__ . '/class-testcase.php';

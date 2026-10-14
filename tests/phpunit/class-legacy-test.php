<?php
/**
 * WP Book 1.x compatibility.
 *
 * @package WPBook
 */

use WPBook\Templates\Template_Registry;

/**
 * @covers \WPBook\Legacy\Legacy
 */
class Legacy_Test extends WPBook_TestCase {

	public function test_legacy_templates_are_converted() {
		add_filter(
			'wp_book_get_templates', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 1.x filter under test.
			static function ( $templates ) {
				$templates['Sepia'] = array(
					'body' => array( 'color' => '#5b4636' ),
					'h2'   => array( 'color' => '#8b5a2b; } body { display:none' ),
				);
				return $templates;
			}
		);
		$all = Template_Registry::all();
		$this->assertArrayHasKey( 'legacy-sepia', $all );
		$this->assertStringContainsString( 'body{color:#5b4636;}', $all['legacy-sepia']['css'] );
		$this->assertStringNotContainsString( '}', str_replace( array( 'body{color:#5b4636;}', 'h2{' ), '', rtrim( $all['legacy-sepia']['css'], '}' ) ), 'Values cannot break out of their rule.' );
	}
}

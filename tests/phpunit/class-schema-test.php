<?php
/**
 * Book settings schema.
 *
 * @package WPBook
 */

use WPBook\Books\Book_Schema;

/**
 * @covers \WPBook\Books\Book_Schema
 */
class Schema_Test extends WPBook_TestCase {

	public function test_defaults_have_every_section() {
		$defaults = Book_Schema::defaults();
		foreach ( array( 'schema_version', 'content', 'structure', 'cover', 'back_cover', 'interior', 'layout', 'output' ) as $section ) {
			$this->assertArrayHasKey( $section, $defaults );
		}
		$this->assertSame( Book_Schema::VERSION, $defaults['schema_version'] );
		$this->assertSame( array(), $defaults['content']['chapters'] );
	}

	public function test_invalid_values_fall_back_to_defaults_or_are_clamped() {
		$config = Book_Schema::normalize(
			array(
				'cover'    => array(
					'template' => 'does-not-exist',
					'primary'  => 'red; background:url(x)',
					'title'    => '<script>alert(1)</script>Hello',
				),
				'interior' => array(
					'font_size'   => 99,
					'line_height' => 'abc',
				),
				'layout'   => array(
					'format'  => 'NOPE',
					'columns' => 7,
				),
				'unknown'  => array( 'x' => 1 ),
			)
		);
		$defaults = Book_Schema::defaults();
		$this->assertSame( $defaults['cover']['template'], $config['cover']['template'] );
		$this->assertSame( $defaults['cover']['primary'], $config['cover']['primary'] );
		$this->assertSame( 'Hello', $config['cover']['title'] );
		$this->assertSame( 18.0, $config['interior']['font_size'] );
		$this->assertSame( $defaults['interior']['line_height'], $config['interior']['line_height'] );
		$this->assertSame( $defaults['layout']['format'], $config['layout']['format'] );
		$this->assertSame( 3, $config['layout']['columns'] );
		$this->assertArrayNotHasKey( 'unknown', $config );
	}

	public function test_chapters_are_sanitized() {
		$chapters = Book_Schema::sanitize_chapters(
			array(
				array(
					'type' => 'post',
					'id'   => '12',
				),
				array(
					'type' => 'post',
					'id'   => 0,
				),
				array(
					'type'  => 'part',
					'title' => '<b>Part one</b>',
				),
				'garbage',
			)
		);
		$this->assertCount( 2, $chapters );
		$this->assertSame( 12, $chapters[0]['id'] );
		$this->assertTrue( $chapters[0]['include']['featured_image'] );
		$this->assertSame( 'part', $chapters[1]['type'] );
		$this->assertSame( 'Part one', $chapters[1]['title'] );
	}

	public function test_merge_replaces_only_given_fields() {
		$base   = Book_Schema::defaults();
		$merged = Book_Schema::merge( $base, array( 'cover' => array( 'title' => 'New' ) ) );
		$this->assertSame( 'New', $merged['cover']['title'] );
		$this->assertSame( $base['cover']['template'], $merged['cover']['template'] );
		// Normalising stores numbers as floats, so compare values, not types.
		$this->assertEquals( $base['layout'], $merged['layout'] );
	}

	public function test_migrate_sets_current_version() {
		$config = Book_Schema::normalize( array( 'schema_version' => 0 ) );
		$this->assertSame( Book_Schema::VERSION, $config['schema_version'] );
	}
}

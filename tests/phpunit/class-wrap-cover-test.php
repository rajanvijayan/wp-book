<?php
/**
 * Print cover.
 *
 * @package WPBook
 */

use WPBook\Books\Book;
use WPBook\Books\Book_Schema;
use WPBook\Covers\Wrap_Cover;
use WPBook\Render\Mpdf_Engine;

/**
 * @covers \WPBook\Covers\Wrap_Cover
 */
class Wrap_Cover_Test extends WPBook_TestCase {

	public function test_spine_width_follows_pages_and_paper() {
		$this->assertSame( 22.88, Wrap_Cover::spine_width( 400, 'white' ) );
		$this->assertSame( 25.4, Wrap_Cover::spine_width( 400, 'cream' ) );
		$this->assertSame( 0.0, Wrap_Cover::spine_width( 0, 'white' ) );
	}

	public function test_sheet_size_includes_spine_and_bleed() {
		$book = new Book( 0, 'Wrap', Book_Schema::merge( Book_Schema::defaults(), array( 'layout' => array( 'format' => 'TRADE-6X9' ) ) ) );
		$path = Mpdf_Engine::temp_file( 'wrap' );
		$out  = Wrap_Cover::render( $book, 200, $path );
		$this->assertEqualsWithDelta( 2 * ( 152.4 + Wrap_Cover::BLEED ) + 11.44, $out['width'], 0.01 );
		$this->assertEqualsWithDelta( 228.6 + 2 * Wrap_Cover::BLEED, $out['height'], 0.01 );
		$this->assertSame( '%PDF-', file_get_contents( $path, false, null, 0, 5 ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test.
		wp_delete_file( $path );
	}
}

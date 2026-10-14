<?php
/**
 * Book rendering.
 *
 * @package WPBook
 */

use WPBook\Books\Book;
use WPBook\Books\Book_Schema;
use WPBook\Render\Book_Renderer;
use WPBook\Render\Mpdf_Engine;

/**
 * @covers \WPBook\Render\Book_Renderer
 * @covers \WPBook\Render\Mpdf_Engine
 */
class Renderer_Test extends WPBook_TestCase {

	/**
	 * Build an unsaved book.
	 *
	 * @param array $patch Settings patch.
	 * @return Book
	 */
	private function book( array $patch ) {
		return new Book( 0, 'Test Book', Book_Schema::merge( Book_Schema::defaults(), $patch ), '', get_current_user_id() );
	}

	public function test_sections_follow_book_order() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$ids      = $this->make_posts( 2 );
		$chapters = array_merge( array( array( 'type' => 'part', 'title' => 'One' ) ), $this->chapters( $ids ) );
		$book     = $this->book(
			array(
				'content'    => array( 'chapters' => $chapters ),
				'structure'  => array( 'dedication' => 'For you' ),
				'back_cover' => array( 'enabled' => true ),
			)
		);
		$types    = array();
		foreach ( ( new Book_Renderer( $book ) )->sections() as $section ) {
			$types[] = $section['type'];
		}
		$this->assertSame( array( 'cover', 'title-page', 'copyright', 'dedication', 'toc', 'part', 'chapter', 'chapter', 'back-cover' ), $types );
	}

	public function test_cover_mode_renders_only_covers() {
		$book  = $this->book( array( 'back_cover' => array( 'enabled' => true ) ) );
		$types = array();
		foreach ( ( new Book_Renderer( $book, Book_Renderer::MODE_COVER ) )->sections() as $section ) {
			$types[] = $section['type'];
		}
		$this->assertSame( array( 'cover', 'back-cover' ), $types );
	}

	public function test_unreadable_posts_are_skipped() {
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$private = self::factory()->post->create(
			array(
				'post_status' => 'private',
				'post_author' => $admin,
				'post_title'  => 'Private chapter',
			)
		);
		$public  = $this->make_posts( 1 );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		$book   = $this->book( array( 'content' => array( 'chapters' => $this->chapters( array( $private, $public[0] ) ) ) ) );
		$titles = array();
		foreach ( ( new Book_Renderer( $book ) )->sections() as $section ) {
			if ( 'chapter' === $section['type'] ) {
				$titles[] = $section['title'];
			}
		}
		$this->assertSame( array( 'Chapter post 1' ), $titles );
	}

	public function test_pdf_contains_toc_headers_and_chapters() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$ids  = $this->make_posts( 3, array( 'post_content' => '<h2>Inner section</h2>' . str_repeat( '<p>Long paragraph text for the chapter body that fills pages.</p>', 60 ) ) );
		$book = $this->book(
			array(
				'content' => array( 'chapters' => $this->chapters( $ids ) ),
				'layout'  => array( 'format' => 'A5' ),
				'output'  => array( 'keywords' => 'alpha, beta' ),
			)
		);
		$path = Mpdf_Engine::temp_file( 'test' );
		$out  = ( new Mpdf_Engine() )->render( new Book_Renderer( $book ), $path );
		$this->assertGreaterThan( 6, $out['pages'] );
		$this->assertSame( '%PDF-', file_get_contents( $path, false, null, 0, 5 ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test.
		$text = $this->pdf_text( $path );
		if ( '' !== $text ) {
			$this->assertStringContainsString( 'Contents', $text );
			$this->assertStringContainsString( 'Chapter 1: Chapter post 1', $text );
			$this->assertStringContainsString( 'Inner section', $text );
		}
		wp_delete_file( $path );
	}

	public function test_every_template_and_cover_renders() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$ids = $this->make_posts( 1 );
		foreach ( array_keys( \WPBook\Templates\Template_Registry::all() ) as $template ) {
			foreach ( array_keys( \WPBook\Covers\Cover_Registry::all() ) as $cover ) {
				$book = $this->book(
					array(
						'content'  => array( 'chapters' => $this->chapters( $ids ) ),
						'cover'    => array( 'template' => $cover ),
						'interior' => array( 'template' => $template ),
					)
				);
				$path = Mpdf_Engine::temp_file( 'test' );
				$out  = ( new Mpdf_Engine() )->render( new Book_Renderer( $book, Book_Renderer::MODE_SAMPLE ), $path );
				$this->assertGreaterThanOrEqual( 2, $out['pages'], "$template / $cover" );
				wp_delete_file( $path );
			}
		}
	}
}

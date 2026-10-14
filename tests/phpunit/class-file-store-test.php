<?php
/**
 * Generated file storage.
 *
 * @package WPBook
 */

use WPBook\Books\Book_Repository;
use WPBook\Files\File_Store;

/**
 * @covers \WPBook\Files\File_Store
 */
class File_Store_Test extends WPBook_TestCase {

	/**
	 * Write a fake PDF to a temp file.
	 *
	 * @return string
	 */
	private function fake_pdf() {
		$path = wp_tempnam( 'wpbook-test.pdf' );
		file_put_contents( $path, '%PDF-1.4 test' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
		return $path;
	}

	public function test_files_are_protected_and_retention_applies() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$book = Book_Repository::create( 'Files', array( 'output' => array( 'keep' => 2 ) ) );
		$ids  = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$ids[] = File_Store::add( $book, $this->fake_pdf(), array( 'pages' => $i + 1 ) )['id'];
		}
		$records = File_Store::records( $book->id );
		$this->assertCount( 2, $records );
		$this->assertSame( array( $ids[2], $ids[1] ), wp_list_pluck( $records, 'id' ) );
		$this->assertFileDoesNotExist( File_Store::path( $book->id, array( 'id' => $ids[0] ) ) );
		$this->assertFileExists( dirname( File_Store::base_dir() ) . '/.htaccess' );
		$this->assertFileExists( File_Store::base_dir() . '/index.php' );
		$this->assertMatchesRegularExpression( '/^[a-z0-9]{24}$/', $ids[2] );
	}

	public function test_latest_returns_newest_of_kind() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$book  = Book_Repository::create( 'Kinds' );
		$first = File_Store::add( $book, $this->fake_pdf(), array( 'kind' => 'book' ) );
		File_Store::add( $book, $this->fake_pdf(), array( 'kind' => 'print-cover' ) );
		$this->assertSame( $first['id'], File_Store::latest( $book->id )['id'] );
		$this->assertSame( 'print-cover', File_Store::latest( $book->id, 'print-cover' )['kind'] );
	}

	public function test_deleting_a_book_removes_its_files() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$book   = Book_Repository::create( 'Delete me' );
		$record = File_Store::add( $book, $this->fake_pdf(), array() );
		$path   = File_Store::path( $book->id, $record );
		$this->assertFileExists( $path );
		Book_Repository::delete( $book );
		$this->assertFileDoesNotExist( $path );
	}
}

<?php
/**
 * Background generation.
 *
 * @package WPBook
 */

use WPBook\Books\Book_Repository;
use WPBook\Files\File_Store;
use WPBook\Jobs\Generate_Book_Job;

/**
 * @covers \WPBook\Jobs\Generate_Book_Job
 */
class Job_Test extends WPBook_TestCase {

	public function test_job_generates_a_file() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$book = Book_Repository::create( 'Job book', array( 'content' => array( 'chapters' => $this->chapters( $this->make_posts( 2 ) ) ) ) );
		$job  = Generate_Book_Job::enqueue( $book );
		$this->assertSame( 'queued', $job['status'] );
		$this->assertSame( $job['id'], Generate_Book_Job::enqueue( $book )['id'], 'A queued job is reused.' );

		Generate_Book_Job::handle( $book->id, $job['id'], $admin );
		$done = Generate_Book_Job::get( $book->id );
		$this->assertSame( 'done', $done['status'], (string) $done['message'] );
		$this->assertSame( 2, $done['done'] );
		$record = File_Store::find( $book->id, $done['file'] );
		$this->assertNotNull( $record );
		$this->assertGreaterThan( 3, $record['pages'] );
		$this->assertFileExists( File_Store::path( $book->id, $record ) );

		// Running the same job again (e.g. the queue fires after the inline fallback) does nothing.
		Generate_Book_Job::handle( $book->id, $job['id'], $admin );
		$this->assertCount( 1, File_Store::records( $book->id ) );
	}

	public function test_job_fails_for_users_who_cannot_manage_books() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$book       = Book_Repository::create( 'Job book', array( 'content' => array( 'chapters' => $this->chapters( $this->make_posts( 1 ) ) ) ) );
		$job        = Generate_Book_Job::enqueue( $book );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Generate_Book_Job::handle( $book->id, $job['id'], $subscriber );
		$this->assertSame( 'failed', Generate_Book_Job::get( $book->id )['status'] );
		$this->assertCount( 0, File_Store::records( $book->id ) );
	}

	public function test_print_cover_is_created_when_enabled() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$book = Book_Repository::create(
			'Print book',
			array(
				'content' => array( 'chapters' => $this->chapters( $this->make_posts( 2 ) ) ),
				'layout'  => array( 'format' => 'TRADE-6X9' ),
				'output'  => array( 'print_cover' => true ),
			)
		);
		$job  = Generate_Book_Job::enqueue( $book );
		Generate_Book_Job::handle( $book->id, $job['id'], $admin );
		$wrap = File_Store::latest( $book->id, 'print-cover' );
		$this->assertNotNull( $wrap );
		$this->assertSame( 'Print-book-print-cover.pdf', $wrap['name'] );
	}
}

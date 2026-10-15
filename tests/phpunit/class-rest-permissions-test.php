<?php
/**
 * REST API permissions.
 *
 * @package WPBook
 */

use WPBook\Books\Book_Repository;

/**
 * @covers \WPBook\Rest\Rest_Controller
 * @covers \WPBook\Rest\Content_Controller
 * @covers \WPBook\Capabilities
 */
class Rest_Permissions_Test extends WPBook_TestCase {

	/**
	 * Dispatch a REST request.
	 *
	 * @param string $method Method.
	 * @param string $route  Route below /wp-book/v2.
	 * @param array  $params Params.
	 * @return WP_REST_Response
	 */
	private function request( $method, $route, array $params = array() ) {
		$request = new WP_REST_Request( $method, '/wp-book/v2' . $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_get_server()->dispatch( $request );
	}

	public function test_subscribers_and_contributors_are_refused() {
		$book = Book_Repository::create( 'Secret book' );
		foreach ( array( 'subscriber', 'contributor', 'author' ) as $role ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
			foreach ( array( array( 'GET', '/books' ), array( 'GET', '/content' ), array( 'GET', '/meta' ), array( 'GET', '/books/' . $book->id ), array( 'POST', '/books/' . $book->id . '/generate' ) ) as $call ) {
				$response = $this->request( $call[0], $call[1] );
				$this->assertContains( $response->get_status(), array( 401, 403 ), "$role {$call[0]} {$call[1]}" );
			}
		}
	}

	public function test_editors_can_manage_books() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$response = $this->request( 'POST', '/books', array( 'title' => 'Editor book' ) );
		$this->assertSame( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'Editor book', $data['title'] );
		$this->assertSame( 200, $this->request( 'GET', '/books/' . $data['id'] )->get_status() );
	}

	public function test_base_capability_filter() {
		add_filter(
			'wpbook_base_capability',
			static function () {
				return 'manage_options';
			}
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 403, $this->request( 'GET', '/books' )->get_status() );
	}

	public function test_content_hides_posts_the_user_cannot_read() {
		$other   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$private = self::factory()->post->create(
			array(
				'post_status' => 'private',
				'post_author' => $other,
				'post_title'  => 'Private of other',
			)
		);
		$public  = self::factory()->post->create( array( 'post_title' => 'Public post' ) );

		// Authors normally cannot manage books; allow them so the read_post filtering is what is tested.
		add_filter(
			'wpbook_base_capability',
			static function () {
				return 'edit_posts';
			}
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		$response = $this->request(
			'GET',
			'/content',
			array(
				'status'   => 'any',
				'per_page' => 100,
			)
		);
		$ids      = wp_list_pluck( $response->get_data(), 'id' );
		$this->assertContains( $public, $ids );
		$this->assertNotContains( $private, $ids );
	}

	public function test_download_requires_capability() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$book = Book_Repository::create( 'Book' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$response = $this->request( 'GET', '/books/' . $book->id . '/files/abcdefgh1234/download' );
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}
}

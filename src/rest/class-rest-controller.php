<?php
/**
 * Shared REST helpers.
 *
 * @package WPBook
 */

namespace WPBook\Rest;

use WP_Error;
use WPBook\Books\Book;
use WPBook\Books\Book_Repository;
use WPBook\Capabilities;

/**
 * Base class for the wp-book/v2 controllers.
 */
abstract class Rest_Controller {

	const NS = 'wp-book/v2';

	/**
	 * Register routes.
	 */
	abstract public function register_routes();

	/**
	 * Permission callback: the user must be able to manage books.
	 *
	 * @return bool|WP_Error
	 */
	public function can_manage() {
		if ( Capabilities::current_user_can_manage() ) {
			return true;
		}
		return new WP_Error( 'wpbook_forbidden', __( 'You are not allowed to manage books.', 'wp-book' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Load the book named in the route or return a 404 error.
	 *
	 * @param \WP_REST_Request $request Request with an id parameter.
	 * @return Book|WP_Error
	 */
	protected function book( $request ) {
		$book = Book_Repository::find( (int) $request['id'] );
		if ( ! $book ) {
			return new WP_Error( 'wpbook_not_found', __( 'Book not found.', 'wp-book' ), array( 'status' => 404 ) );
		}
		return $book;
	}

	/**
	 * Route argument for a numeric ID.
	 *
	 * @return array
	 */
	protected function id_arg() {
		return array(
			'id' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);
	}
}

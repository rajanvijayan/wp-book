<?php
/**
 * Generation job REST routes.
 *
 * @package WPBook
 */

namespace WPBook\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WPBook\Files\File_Store;
use WPBook\Jobs\Generate_Book_Job;

/**
 * POST /books/{id}/generate starts a job; GET /books/{id}/job reports progress.
 */
final class Jobs_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)/generate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $this->id_arg(),
			)
		);
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)/job',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'status' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $this->id_arg(),
			)
		);
	}

	/**
	 * Start generation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function generate( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		if ( ! $book->post_ids() ) {
			return new WP_Error( 'wpbook_empty', __( 'Add at least one chapter before generating the book.', 'wp-book' ), array( 'status' => 400 ) );
		}
		return self::response( $book->id, Generate_Book_Job::enqueue( $book ) );
	}

	/**
	 * Job progress.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function status( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		return self::response( $book->id, Generate_Book_Job::status( $book ) );
	}

	/**
	 * Job JSON for the API, with the finished files when done. Internal fields (user, action) stay private.
	 *
	 * @param int        $book_id Book ID.
	 * @param array|null $job     Job state.
	 * @return array
	 */
	public static function response( $book_id, $job ) {
		if ( ! $job ) {
			return array( 'status' => 'idle' );
		}
		$out = array(
			'id'      => $job['id'],
			'status'  => $job['status'],
			'done'    => (int) $job['done'],
			'total'   => (int) $job['total'],
			'message' => $job['message'],
		);
		if ( 'done' === $job['status'] && $job['file'] ) {
			$record      = File_Store::find( $book_id, $job['file'] );
			$out['file'] = $record ? Files_Controller::file_response( $book_id, $record ) : null;
			if ( ! empty( $job['print_cover'] ) ) {
				$wrap               = File_Store::find( $book_id, $job['print_cover'] );
				$out['print_cover'] = $wrap ? Files_Controller::file_response( $book_id, $wrap ) : null;
			}
		}
		return $out;
	}
}

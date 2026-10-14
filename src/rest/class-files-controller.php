<?php
/**
 * Generated file REST routes.
 *
 * @package WPBook
 */

namespace WPBook\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WPBook\Files\File_Store;

/**
 * Lists, downloads and deletes a book's generated PDFs.
 */
final class Files_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		$file_args = $this->id_arg() + array(
			'file' => array(
				'type'    => 'string',
				'pattern' => '^[a-z0-9]{8,64}$',
			),
		);
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)/files',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $this->id_arg(),
			)
		);
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)/files/(?P<file>[a-z0-9]+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'destroy' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $file_args,
			)
		);
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)/files/(?P<file>[a-z0-9]+)/download',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'download' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $file_args,
			)
		);
	}

	/**
	 * GET /books/{id}/files
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function index( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		return array_map(
			static function ( $record ) use ( $book ) {
				return self::file_response( $book->id, $record );
			},
			File_Store::records( $book->id )
		);
	}

	/**
	 * DELETE /books/{id}/files/{file}
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function destroy( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		if ( ! File_Store::delete( $book->id, $request['file'] ) ) {
			return new WP_Error( 'wpbook_file_not_found', __( 'File not found.', 'wp-book' ), array( 'status' => 404 ) );
		}
		return array( 'deleted' => true );
	}

	/**
	 * GET /books/{id}/files/{file}/download: streams the PDF (cookie auth via ?_wpnonce=).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_Error|void
	 */
	public function download( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		$record = File_Store::find( $book->id, $request['file'] );
		if ( ! $record ) {
			return new WP_Error( 'wpbook_file_not_found', __( 'File not found.', 'wp-book' ), array( 'status' => 404 ) );
		}
		File_Store::stream( $book->id, $record );
	}

	/**
	 * File JSON including an authenticated download URL.
	 *
	 * @param int   $book_id Book ID.
	 * @param array $record  File record.
	 * @return array
	 */
	public static function file_response( $book_id, array $record ) {
		return array(
			'id'       => $record['id'],
			'kind'     => $record['kind'],
			'name'     => $record['name'],
			'size'     => (int) $record['size'],
			'pages'    => (int) $record['pages'],
			'created'  => gmdate( 'c', (int) $record['created'] ),
			'download' => add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), rest_url( self::NS . '/books/' . (int) $book_id . '/files/' . $record['id'] . '/download' ) ),
		);
	}
}

<?php
/**
 * Books REST routes.
 *
 * @package WPBook
 */

namespace WPBook\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WPBook\Books\Book;
use WPBook\Books\Book_Repository;
use WPBook\Covers\Auto_Cover;
use WPBook\Files\File_Store;
use WPBook\Jobs\Generate_Book_Job;

/**
 * CRUD for books plus duplicate and automatic cover suggestions.
 */
final class Books_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/books',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'index' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
						'search'   => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'title'  => array(
							'type'     => 'string',
							'required' => true,
						),
						'config' => array(
							'type'    => 'object',
							'default' => array(),
						),
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->id_arg(),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->id_arg() + array(
						'title'  => array( 'type' => 'string' ),
						'config' => array( 'type' => 'object' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'destroy' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->id_arg(),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)/duplicate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'duplicate' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $this->id_arg(),
			)
		);
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)/auto-cover',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'auto_cover' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $this->id_arg(),
			)
		);
	}

	/**
	 * GET /books
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function index( WP_REST_Request $request ) {
		$result   = Book_Repository::query(
			array(
				'page'     => $request['page'],
				'per_page' => $request['per_page'],
				'search'   => $request['search'],
			)
		);
		$response = rest_ensure_response( array_map( array( $this, 'summary' ), $result['items'] ) );
		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) $result['pages'] );
		return $response;
	}

	/**
	 * POST /books
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		$title = trim( (string) $request['title'] );
		if ( '' === $title ) {
			return new WP_Error( 'wpbook_title', __( 'Please give the book a title.', 'wp-book' ), array( 'status' => 400 ) );
		}
		$book = Book_Repository::create( $title, (array) $request['config'] );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		$response = rest_ensure_response( $this->detail( $book ) );
		$response->set_status( 201 );
		return $response;
	}

	/**
	 * GET /books/{id}
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function show( WP_REST_Request $request ) {
		$book = $this->book( $request );
		return is_wp_error( $book ) ? $book : $this->detail( $book );
	}

	/**
	 * PUT/PATCH /books/{id}
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function update( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		$title = $request->has_param( 'title' ) ? (string) $request['title'] : null;
		if ( null !== $title && '' === trim( $title ) ) {
			return new WP_Error( 'wpbook_title', __( 'Please give the book a title.', 'wp-book' ), array( 'status' => 400 ) );
		}
		$config = $request->has_param( 'config' ) ? (array) $request['config'] : null;
		$book   = Book_Repository::update( $book, $title, $config );
		return is_wp_error( $book ) ? $book : $this->detail( $book );
	}

	/**
	 * DELETE /books/{id}
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function destroy( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		return array( 'deleted' => Book_Repository::delete( $book ) );
	}

	/**
	 * POST /books/{id}/duplicate
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function duplicate( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		$copy = Book_Repository::duplicate( $book );
		return is_wp_error( $copy ) ? $copy : $this->detail( $copy );
	}

	/**
	 * GET /books/{id}/auto-cover: cover settings suggested from the site and the book's content.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function auto_cover( WP_REST_Request $request ) {
		$book = $this->book( $request );
		return is_wp_error( $book ) ? $book : Auto_Cover::suggest( $book );
	}

	/**
	 * Short representation for the books list.
	 *
	 * @param Book $book Book.
	 * @return array
	 */
	public function summary( Book $book ) {
		$files = File_Store::records( $book->id );
		return array(
			'id'          => $book->id,
			'title'       => $book->title,
			'modified'    => mysql_to_rfc3339( $book->modified_gmt ),
			'chapters'    => count( $book->post_ids() ),
			'cover'       => array(
				'template' => $book->config['cover']['template'],
				'primary'  => $book->config['cover']['primary'],
				'accent'   => $book->config['cover']['accent'],
				'text'     => $book->config['cover']['text_color'],
				'title'    => $book->cover_title(),
				'author'   => $book->author_name(),
			),
			'latest_file' => $files ? Files_Controller::file_response( $book->id, $files[0] ) : null,
			'job'         => Generate_Book_Job::get( $book->id ),
		);
	}

	/**
	 * Full representation for the editor.
	 *
	 * @param Book $book Book.
	 * @return array
	 */
	public function detail( Book $book ) {
		$data           = $this->summary( $book );
		$data['config'] = $book->config;
		$data['posts']  = Content_Controller::describe_posts( $book->post_ids() );
		$data['files']  = array_map(
			static function ( $record ) use ( $book ) {
				return Files_Controller::file_response( $book->id, $record );
			},
			File_Store::records( $book->id )
		);
		return $data;
	}
}

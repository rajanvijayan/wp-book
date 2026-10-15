<?php
/**
 * Live preview of a book, including unsaved changes.
 *
 * @package WPBook
 */

namespace WPBook\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WPBook\Books\Book;
use WPBook\Books\Book_Schema;
use WPBook\Render\Book_Renderer;
use WPBook\Render\Mpdf_Engine;

/**
 * POST /books/{id}/preview streams a quick PDF: the cover, or the cover plus the first chapters.
 */
final class Preview_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/books/(?P<id>\d+)/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $this->id_arg() + array(
					'mode'   => array(
						'type'    => 'string',
						'default' => Book_Renderer::MODE_SAMPLE,
						'enum'    => array( Book_Renderer::MODE_COVER, Book_Renderer::MODE_SAMPLE ),
					),
					'config' => array(
						'type'    => 'object',
						'default' => array(),
					),
					'title'  => array( 'type' => 'string' ),
				),
			)
		);
	}

	/**
	 * Render and stream the preview.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_Error|void
	 */
	public function preview( WP_REST_Request $request ) {
		$book = $this->book( $request );
		if ( is_wp_error( $book ) ) {
			return $book;
		}
		// Apply unsaved editor changes without storing them.
		$config  = Book_Schema::merge( $book->config, (array) $request['config'] );
		$title   = $request->has_param( 'title' ) && '' !== trim( (string) $request['title'] ) ? sanitize_text_field( $request['title'] ) : $book->title;
		$preview = new Book( $book->id, $title, $config, $book->modified_gmt, $book->author );
		// Previews never need a password or watermark.
		$preview->config['output']['password'] = '';

		$path = Mpdf_Engine::temp_file( 'wpbook-preview' );
		try {
			( new Mpdf_Engine() )->render( new Book_Renderer( $preview, $request['mode'] ), $path );
		} catch ( \Throwable $e ) {
			wp_delete_file( $path );
			return new WP_Error( 'wpbook_preview_failed', $e->getMessage(), array( 'status' => 500 ) );
		}

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Disposition: inline; filename="preview.pdf"' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming the PDF.
		wp_delete_file( $path );
		exit;
	}
}

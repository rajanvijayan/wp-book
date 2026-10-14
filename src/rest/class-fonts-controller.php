<?php
/**
 * Font packs for non-Latin scripts.
 *
 * @package WPBook
 */

namespace WPBook\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WPBook\Render\Font_Packs;

/**
 * GET /fonts, POST /fonts/{pack} (download the next file), DELETE /fonts/{pack}.
 */
final class Fonts_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/fonts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
		register_rest_route(
			self::NS,
			'/fonts/(?P<pack>[a-z]+)',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'install' ),
					'permission_callback' => array( $this, 'can_install' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove' ),
					'permission_callback' => array( $this, 'can_install' ),
				),
			)
		);
	}

	/**
	 * Installing fonts writes files to the server, so it also needs manage_options.
	 *
	 * @return bool|WP_Error
	 */
	public function can_install() {
		$can = $this->can_manage();
		if ( true !== $can ) {
			return $can;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return new WP_Error( 'wpbook_forbidden', __( 'Only administrators can install fonts.', 'wp-book' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Status of all packs.
	 *
	 * @return array
	 */
	public function index() {
		return array(
			'packs'       => Font_Packs::all(),
			'can_install' => current_user_can( 'manage_options' ),
		);
	}

	/**
	 * Download the next file of a pack.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function install( WP_REST_Request $request ) {
		return Font_Packs::install_next( (string) $request['pack'] );
	}

	/**
	 * Remove a pack.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array|WP_Error
	 */
	public function remove( WP_REST_Request $request ) {
		return Font_Packs::remove( (string) $request['pack'] );
	}
}

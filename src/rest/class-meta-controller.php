<?php
/**
 * Options the editor needs: post types, sizes, templates, covers, fonts and defaults.
 *
 * @package WPBook
 */

namespace WPBook\Rest;

use WP_REST_Server;
use WPBook\Books\Book_Schema;
use WPBook\Books\Page_Sizes;
use WPBook\Covers\Auto_Cover;
use WPBook\Covers\Cover_Registry;
use WPBook\Render\Font_Manager;
use WPBook\Templates\Template_Registry;

/**
 * GET /meta
 */
final class Meta_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/meta',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'show' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Editor metadata.
	 *
	 * @return array
	 */
	public function show() {
		$post_types = array();
		foreach ( Content_Controller::post_types() as $slug ) {
			$object     = get_post_type_object( $slug );
			$taxonomies = array();
			foreach ( get_object_taxonomies( $slug, 'objects' ) as $tax ) {
				if ( $tax->public && $tax->show_ui ) {
					$taxonomies[] = array(
						'slug'      => $tax->name,
						'label'     => $tax->labels->name,
						'rest_base' => $tax->rest_base ? $tax->rest_base : $tax->name,
						'rest_ns'   => $tax->rest_namespace ? $tax->rest_namespace : 'wp/v2',
					);
				}
			}
			$post_types[] = array(
				'slug'       => $slug,
				'label'      => $object->labels->name,
				'taxonomies' => $taxonomies,
			);
		}

		$covers = array();
		foreach ( Cover_Registry::all() as $key => $cover ) {
			$covers[] = $cover + array(
				'key'   => $key,
				'thumb' => self::asset_url( 'covers/' . $key . '/thumb.png' ),
			);
		}
		$templates = array();
		foreach ( array_keys( Template_Registry::all() ) as $key ) {
			$templates[] = Template_Registry::get( $key ) + array(
				'key'   => $key,
				'thumb' => self::asset_url( 'interior/' . $key . '/thumb.png' ),
			);
		}
		$fonts = array();
		foreach ( Font_Manager::all() as $key => $font ) {
			$fonts[] = array(
				'key'      => $key,
				'name'     => $font['name'],
				'category' => $font['category'],
			);
		}
		$formats = array();
		foreach ( Page_Sizes::all() as $key => $size ) {
			$formats[] = $size + array( 'key' => $key );
		}
		$logo = (int) get_theme_mod( 'custom_logo' );

		return array(
			'post_types' => $post_types,
			'formats'    => $formats,
			'covers'     => $covers,
			'templates'  => $templates,
			'fonts'      => $fonts,
			'defaults'   => Book_Schema::defaults(),
			'site'       => array(
				'name'    => get_bloginfo( 'name' ),
				'tagline' => get_bloginfo( 'description' ),
				'logo'    => $logo ? wp_get_attachment_image_url( $logo, 'medium' ) : '',
				'palette' => Auto_Cover::theme_palette(),
			),
		);
	}

	/**
	 * URL of a template asset, honouring theme overrides.
	 *
	 * @param string $relative Path below templates/.
	 * @return string
	 */
	private static function asset_url( $relative ) {
		foreach ( array(
			get_stylesheet_directory() => get_stylesheet_directory_uri(),
			get_template_directory()   => get_template_directory_uri(),
		) as $dir => $uri ) {
			if ( file_exists( $dir . '/bookpress/' . $relative ) ) {
				return $uri . '/bookpress/' . $relative;
			}
		}
		return file_exists( WPBOOK_DIR . 'templates/' . $relative ) ? WPBOOK_URL . 'templates/' . $relative : '';
	}
}

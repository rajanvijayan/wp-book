<?php
/**
 * The private post type that stores books.
 *
 * @package WPBook
 */

namespace WPBook\Books;

use WPBook\Capabilities;

/**
 * Registers the wpbook_book post type. Books are edited only in the BookPress app.
 */
final class Book_Post_Type {

	const POST_TYPE = 'wpbook_book';

	/**
	 * Register the post type.
	 */
	public static function register() {
		$cap = Capabilities::MANAGE;
		register_post_type(
			self::POST_TYPE,
			array(
				'label'           => __( 'Books', 'wp-book' ),
				'public'          => false,
				'show_ui'         => false,
				'show_in_rest'    => false,
				'rewrite'         => false,
				'query_var'       => false,
				'supports'        => array( 'title', 'author' ),
				'map_meta_cap'    => false,
				'capability_type' => 'wpbook_book',
				'capabilities'    => array(
					'edit_post'          => $cap,
					'read_post'          => $cap,
					'delete_post'        => $cap,
					'edit_posts'         => $cap,
					'edit_others_posts'  => $cap,
					'delete_posts'       => $cap,
					'publish_posts'      => $cap,
					'read_private_posts' => $cap,
					'create_posts'       => $cap,
				),
			)
		);
	}
}

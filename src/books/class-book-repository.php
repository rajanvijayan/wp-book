<?php
/**
 * Loads and saves books.
 *
 * @package WPBook
 */

namespace WPBook\Books;

use WP_Error;

/**
 * Persists books as wpbook_book posts with their settings in one JSON meta field.
 */
final class Book_Repository {

	const META_CONFIG = '_wpbook_config';

	/**
	 * Load a book.
	 *
	 * @param int $id Post ID.
	 * @return Book|null
	 */
	public static function find( $id ) {
		$post = get_post( (int) $id );
		if ( ! $post || Book_Post_Type::POST_TYPE !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}
		return self::from_post( $post );
	}

	/**
	 * Build a Book from its post.
	 *
	 * @param \WP_Post $post Post.
	 * @return Book
	 */
	public static function from_post( \WP_Post $post ) {
		$raw    = get_post_meta( $post->ID, self::META_CONFIG, true );
		$config = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : array();
		return new Book( $post->ID, $post->post_title, Book_Schema::normalize( $config ), $post->post_modified_gmt, (int) $post->post_author );
	}

	/**
	 * List books, newest first.
	 *
	 * @param array $args { page, per_page, search }.
	 * @return array{items: Book[], total: int, pages: int}
	 */
	public static function query( array $args = array() ) {
		$args  = wp_parse_args(
			$args,
			array(
				'page'     => 1,
				'per_page' => 20,
				'search'   => '',
			)
		);
		$query = new \WP_Query(
			array(
				'post_type'      => Book_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => min( 100, max( 1, (int) $args['per_page'] ) ),
				'paged'          => max( 1, (int) $args['page'] ),
				's'              => (string) $args['search'],
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		return array(
			'items' => array_map( array( self::class, 'from_post' ), $query->posts ),
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
		);
	}

	/**
	 * Create a book.
	 *
	 * @param string $title  Title.
	 * @param array  $config Settings (partial is fine; defaults fill the rest).
	 * @return Book|WP_Error
	 */
	public static function create( $title, array $config = array() ) {
		$config = Book_Schema::merge( Book_Schema::defaults(), $config );
		$id     = wp_insert_post(
			array(
				'post_type'   => Book_Post_Type::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => sanitize_text_field( $title ),
				'post_author' => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		self::write_config( $id, $config );
		return self::find( $id );
	}

	/**
	 * Update the title and/or merge partial settings.
	 *
	 * @param Book        $book  Book.
	 * @param string|null $title New title, or null to keep it.
	 * @param array|null  $patch Partial settings, or null to keep them.
	 * @return Book|WP_Error
	 */
	public static function update( Book $book, $title = null, $patch = null ) {
		if ( null !== $title ) {
			$result = wp_update_post(
				array(
					'ID'         => $book->id,
					'post_title' => sanitize_text_field( $title ),
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		if ( is_array( $patch ) ) {
			self::write_config( $book->id, Book_Schema::merge( $book->config, $patch ) );
			if ( null === $title ) {
				// Touch the post so the list sorts by last edit.
				wp_update_post( array( 'ID' => $book->id ) );
			}
		}
		return self::find( $book->id );
	}

	/**
	 * Copy a book (settings only, not its generated files).
	 *
	 * @param Book $book Book.
	 * @return Book|WP_Error
	 */
	public static function duplicate( Book $book ) {
		/* translators: %s: book title */
		return self::create( sprintf( __( '%s (copy)', 'wp-book' ), $book->title ), $book->config );
	}

	/**
	 * Delete a book permanently. Its files are removed by File_Store::on_delete_post().
	 *
	 * @param Book $book Book.
	 * @return bool
	 */
	public static function delete( Book $book ) {
		return (bool) wp_delete_post( $book->id, true );
	}

	/**
	 * Store settings as JSON.
	 *
	 * @param int   $id     Post ID.
	 * @param array $config Normalized settings.
	 */
	private static function write_config( $id, array $config ) {
		update_post_meta( $id, self::META_CONFIG, wp_slash( wp_json_encode( $config ) ) );
	}
}

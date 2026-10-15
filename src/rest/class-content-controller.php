<?php
/**
 * Search the site's content to add to a book.
 *
 * @package WPBook
 */

namespace WPBook\Rest;

use WP_Query;
use WP_REST_Request;
use WP_REST_Server;

/**
 * GET /content lists posts the current user can read, with filters.
 */
final class Content_Controller extends Rest_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/content',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'post_type' => array(
						'type'    => 'string',
						'default' => 'post',
						'enum'    => self::post_types(),
					),
					'search'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'taxonomy'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'terms'     => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
					'author'    => array(
						'type'    => 'integer',
						'default' => 0,
					),
					'after'     => array(
						'type'   => 'string',
						'format' => 'date-time',
					),
					'before'    => array(
						'type'   => 'string',
						'format' => 'date-time',
					),
					'status'    => array(
						'type'    => 'string',
						'default' => 'publish',
						'enum'    => array( 'publish', 'private', 'draft', 'future', 'pending', 'any' ),
					),
					'orderby'   => array(
						'type'    => 'string',
						'default' => 'date',
						'enum'    => array( 'date', 'title', 'modified', 'menu_order', 'ID' ),
					),
					'order'     => array(
						'type'    => 'string',
						'default' => 'asc',
						'enum'    => array( 'asc', 'desc' ),
					),
					'page'      => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page'  => array(
						'type'    => 'integer',
						'default' => 50,
						'minimum' => 1,
						'maximum' => 100,
					),
				),
			)
		);
	}

	/**
	 * Post types that can be added to a book: public ones, minus attachments and books.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );
		return array_values( $types );
	}

	/**
	 * GET /content
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function index( WP_REST_Request $request ) {
		$status = 'any' === $request['status'] ? array( 'publish', 'private', 'draft', 'future', 'pending' ) : array( $request['status'] );
		$args   = array(
			'post_type'           => $request['post_type'],
			'post_status'         => $status,
			's'                   => $request['search'],
			'orderby'             => $request['orderby'],
			'order'               => strtoupper( $request['order'] ),
			'paged'               => $request['page'],
			'posts_per_page'      => $request['per_page'],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);
		if ( $request['author'] ) {
			$args['author'] = (int) $request['author'];
		}
		if ( $request['taxonomy'] && $request['terms'] && taxonomy_exists( $request['taxonomy'] ) && is_object_in_taxonomy( $request['post_type'], $request['taxonomy'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- user-selected filter.
				array(
					'taxonomy' => $request['taxonomy'],
					'terms'    => array_map( 'absint', $request['terms'] ),
				),
			);
		}
		$date = array();
		if ( $request['after'] ) {
			$date['after'] = $request['after'];
		}
		if ( $request['before'] ) {
			$date['before'] = $request['before'];
		}
		if ( $date ) {
			$date['inclusive']  = true;
			$args['date_query'] = array( $date );
		}

		$query = new WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post ) {
			if ( current_user_can( 'read_post', $post->ID ) ) {
				$items[] = self::post_summary( $post );
			}
		}
		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );
		return $response;
	}

	/**
	 * Describe the posts used by a book (for the chapter list), keyed by ID.
	 *
	 * @param int[] $ids Post IDs.
	 * @return array
	 */
	public static function describe_posts( array $ids ) {
		$out = array();
		if ( ! $ids ) {
			return $out;
		}
		_prime_post_caches( $ids, false, false );
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( ! $post ) {
				$out[ $id ] = array(
					'id'      => $id,
					'missing' => true,
				);
				continue;
			}
			$out[ $id ] = current_user_can( 'read_post', $id ) ? self::post_summary( $post ) : array(
				'id'        => $id,
				'forbidden' => true,
			);
		}
		return (object) $out;
	}

	/**
	 * Fields the editor needs about a post.
	 *
	 * @param \WP_Post $post Post.
	 * @return array
	 */
	public static function post_summary( \WP_Post $post ) {
		$type = get_post_type_object( $post->post_type );
		return array(
			'id'        => $post->ID,
			'title'     => html_entity_decode( get_the_title( $post ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'type'      => $post->post_type,
			'type_name' => $type ? $type->labels->singular_name : $post->post_type,
			'status'    => $post->post_status,
			'date'      => mysql_to_rfc3339( $post->post_date_gmt && '0000-00-00 00:00:00' !== $post->post_date_gmt ? $post->post_date_gmt : $post->post_date ),
			'author'    => get_the_author_meta( 'display_name', $post->post_author ),
			'words'     => str_word_count( wp_strip_all_tags( $post->post_content ) ),
			'thumbnail' => has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'thumbnail' ) : '',
			'edit_link' => get_edit_post_link( $post, 'raw' ),
		);
	}
}

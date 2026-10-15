<?php
/**
 * A saved book.
 *
 * @package WPBook
 */

namespace WPBook\Books;

/**
 * Value object for a book and its normalized settings.
 */
final class Book {

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Book title (also the default cover title).
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Normalized settings, see Book_Schema.
	 *
	 * @var array
	 */
	public $config;

	/**
	 * Last modified time, GMT, as a MySQL datetime.
	 *
	 * @var string
	 */
	public $modified_gmt;

	/**
	 * Author user ID.
	 *
	 * @var int
	 */
	public $author;

	/**
	 * Constructor.
	 *
	 * @param int    $id           Post ID.
	 * @param string $title        Title.
	 * @param array  $config       Normalized settings.
	 * @param string $modified_gmt Last modified (GMT).
	 * @param int    $author       Author user ID.
	 */
	public function __construct( $id, $title, array $config, $modified_gmt = '', $author = 0 ) {
		$this->id           = (int) $id;
		$this->title        = (string) $title;
		$this->config       = $config;
		$this->modified_gmt = (string) $modified_gmt;
		$this->author       = (int) $author;
	}

	/**
	 * Post IDs of the chapters, in order.
	 *
	 * @return int[]
	 */
	public function post_ids() {
		$ids = array();
		foreach ( $this->config['content']['chapters'] as $chapter ) {
			if ( 'post' === $chapter['type'] ) {
				$ids[] = $chapter['id'];
			}
		}
		return $ids;
	}

	/**
	 * Cover title: the cover setting, or the book title.
	 *
	 * @return string
	 */
	public function cover_title() {
		return '' !== $this->config['cover']['title'] ? $this->config['cover']['title'] : $this->title;
	}

	/**
	 * Author name for the cover and metadata: the cover setting, or the post author's display name.
	 *
	 * @return string
	 */
	public function author_name() {
		if ( '' !== $this->config['cover']['author'] ) {
			return $this->config['cover']['author'];
		}
		$user = $this->author ? get_userdata( $this->author ) : false;
		return $user ? $user->display_name : get_bloginfo( 'name' );
	}
}

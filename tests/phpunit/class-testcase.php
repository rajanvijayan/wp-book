<?php
/**
 * Shared helpers for BookPress tests.
 *
 * @package WPBook
 */

use WPBook\Books\Book_Post_Type;

/**
 * Base test case.
 */
abstract class WPBook_TestCase extends WP_UnitTestCase {

	/**
	 * Register the post type (init already ran before the plugin's hooks in some setups).
	 */
	public function set_up() {
		parent::set_up();
		if ( ! post_type_exists( Book_Post_Type::POST_TYPE ) ) {
			Book_Post_Type::register();
		}
	}

	/**
	 * Create published posts with simple content.
	 *
	 * @param int   $count Number of posts.
	 * @param array $args  Extra post args.
	 * @return int[]
	 */
	protected function make_posts( $count, array $args = array() ) {
		$ids = array();
		for ( $i = 1; $i <= $count; $i++ ) {
			$ids[] = self::factory()->post->create(
				array_merge(
					array(
						'post_title'   => "Chapter post $i",
						'post_content' => "<h2>Section $i</h2><p>Body text of post $i.</p>",
						'post_status'  => 'publish',
					),
					$args
				)
			);
		}
		return $ids;
	}

	/**
	 * Chapters config from post IDs.
	 *
	 * @param int[] $ids Post IDs.
	 * @return array
	 */
	protected function chapters( array $ids ) {
		return array_map(
			static function ( $id ) {
				return array(
					'type' => 'post',
					'id'   => $id,
				);
			},
			$ids
		);
	}

	/**
	 * Text of a PDF via pdftotext, or '' when the tool is missing.
	 *
	 * @param string $path PDF path.
	 * @return string
	 */
	protected function pdf_text( $path ) {
		$out = shell_exec( 'pdftotext -layout ' . escapeshellarg( $path ) . ' - 2>/dev/null' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec -- test helper.
		return (string) $out;
	}
}

<?php
/**
 * Background book generation.
 *
 * @package WPBook
 */

namespace WPBook\Jobs;

use WPBook\Books\Book;
use WPBook\Books\Book_Repository;
use WPBook\Covers\Wrap_Cover;
use WPBook\Files\File_Store;
use WPBook\Render\Book_Renderer;
use WPBook\Render\Mpdf_Engine;

/**
 * Queues generation with Action Scheduler and tracks its state in book meta.
 *
 * Job state (meta _wpbook_job): id, status (queued|running|done|failed), done, total, message, file, user,
 * created, updated, action.
 */
final class Generate_Book_Job {

	const HOOK     = 'wpbook_generate_book';
	const GROUP    = 'wp-book';
	const META_JOB = '_wpbook_job';

	/**
	 * A queued job is run inline by the status endpoint after this many seconds, for sites where
	 * WP-Cron and Action Scheduler's async runner cannot reach themselves.
	 */
	const INLINE_AFTER = 12;

	/**
	 * A running job with no progress for this long is treated as dead.
	 */
	const STALE_AFTER = 600;

	/**
	 * Queue a generation job, or return the one already running.
	 *
	 * @param Book $book Book.
	 * @return array Job state.
	 */
	public static function enqueue( Book $book ) {
		$job = self::get( $book->id );
		if ( $job && in_array( $job['status'], array( 'queued', 'running' ), true ) && time() - $job['updated'] < self::STALE_AFTER ) {
			return $job;
		}
		$job = array(
			'id'      => strtolower( wp_generate_password( 16, false, false ) ),
			'status'  => 'queued',
			'done'    => 0,
			'total'   => count( $book->post_ids() ),
			'message' => '',
			'file'    => '',
			'user'    => get_current_user_id(),
			'created' => time(),
			'updated' => time(),
			'action'  => 0,
		);
		self::save( $book->id, $job );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			$job['action'] = (int) as_enqueue_async_action( self::HOOK, array( $book->id, $job['id'], $job['user'] ), self::GROUP );
			self::save( $book->id, $job );
		}
		return $job;
	}

	/**
	 * Current job state of a book.
	 *
	 * @param int $book_id Book ID.
	 * @return array|null
	 */
	public static function get( $book_id ) {
		$job = get_post_meta( (int) $book_id, self::META_JOB, true );
		return is_array( $job ) && isset( $job['id'] ) ? $job : null;
	}

	/**
	 * Job state for the REST API; runs a stuck queued job inline.
	 *
	 * @param Book $book Book.
	 * @return array|null
	 */
	public static function status( Book $book ) {
		$job = self::get( $book->id );
		if ( $job && 'queued' === $job['status'] && time() - $job['created'] >= self::INLINE_AFTER ) {
			self::handle( $book->id, $job['id'], $job['user'] );
			$job = self::get( $book->id );
		}
		if ( $job && 'running' === $job['status'] && time() - $job['updated'] >= self::STALE_AFTER ) {
			$job['status']  = 'failed';
			$job['message'] = __( 'Generation stopped responding. Try again, or include fewer posts.', 'wp-book' );
			self::save( $book->id, $job );
		}
		return $job;
	}

	/**
	 * Action Scheduler callback: generate the PDF.
	 *
	 * @param int    $book_id Book ID.
	 * @param string $job_id  Job ID (ignored if it is no longer the book's current job).
	 * @param int    $user_id User who started the job; permissions are checked as this user.
	 *
	 * @throws \RuntimeException Caught inside; turned into a failed job state.
	 */
	public static function handle( $book_id, $job_id, $user_id ) {
		$job = self::get( $book_id );
		if ( ! $job || $job['id'] !== $job_id || 'queued' !== $job['status'] ) {
			return;
		}
		// Claim the job so Action Scheduler and the inline fallback never both run it.
		if ( ! add_post_meta( (int) $book_id, '_wpbook_claim_' . $job_id, time(), true ) ) {
			return;
		}
		$job['status']  = 'running';
		$job['updated'] = time();
		self::save( $book_id, $job );

		$previous_user = get_current_user_id();
		wp_set_current_user( (int) $user_id );

		try {
			$book = Book_Repository::find( $book_id );
			if ( ! $book || ! user_can( (int) $user_id, \WPBook\Capabilities::MANAGE ) ) {
				throw new \RuntimeException( __( 'The book no longer exists or you cannot edit it.', 'wp-book' ) );
			}
			self::raise_limits();

			$path   = Mpdf_Engine::temp_file( 'wpbook-' . $book_id );
			$result = ( new Mpdf_Engine() )->render(
				new Book_Renderer( $book ),
				$path,
				static function ( $done, $total ) use ( $book_id, &$job ) {
					// Write progress at most once a second to keep database writes low.
					if ( time() > $job['updated'] || $done === $total ) {
						$job['done']    = $done;
						$job['total']   = $total;
						$job['updated'] = time();
						self::save( $book_id, $job );
					}
				}
			);
			$record = File_Store::add(
				$book,
				$path,
				array(
					'kind'  => 'book',
					'pages' => $result['pages'],
				)
			);
			if ( ! $record ) {
				throw new \RuntimeException( __( 'The PDF was created but could not be saved to the uploads folder.', 'wp-book' ) );
			}
			$job['status'] = 'done';
			$job['file']   = $record['id'];
			$job['pages']  = $result['pages'];

			if ( $book->config['output']['print_cover'] ) {
				$wrap_path = Mpdf_Engine::temp_file( 'wpbook-wrap-' . $book_id );
				$wrap      = Wrap_Cover::render( $book, $result['pages'], $wrap_path );
				$wrap_rec  = File_Store::add(
					$book,
					$wrap_path,
					array(
						'kind'  => 'print-cover',
						'pages' => 1,
					)
				);
				if ( $wrap_rec ) {
					$job['print_cover'] = $wrap_rec['id'];
					$job['spine']       = $wrap['spine'];
				}
			}
		} catch ( \Throwable $e ) {
			$job['status']  = 'failed';
			$job['message'] = $e->getMessage();
			if ( isset( $path ) && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$job['updated'] = time();
		self::save( $book_id, $job );
		delete_post_meta( (int) $book_id, '_wpbook_claim_' . $job_id );
		wp_set_current_user( $previous_user );
	}

	/**
	 * Give long books room to finish.
	 */
	private static function raise_limits() {
		wp_raise_memory_limit( 'admin' );
		// Action Scheduler's helper raises the time limit only where the host allows it.
		if ( class_exists( 'ActionScheduler_Compatibility' ) ) {
			\ActionScheduler_Compatibility::raise_time_limit( 0 );
		}
	}

	/**
	 * Persist job state.
	 *
	 * @param int   $book_id Book ID.
	 * @param array $job     Job state.
	 */
	private static function save( $book_id, array $job ) {
		update_post_meta( (int) $book_id, self::META_JOB, $job );
	}
}

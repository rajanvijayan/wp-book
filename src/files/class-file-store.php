<?php
/**
 * Storage for generated PDFs.
 *
 * @package WPBook
 */

namespace WPBook\Files;

use WPBook\Books\Book;
use WPBook\Books\Book_Post_Type;

/**
 * Keeps each book's generated files in uploads/wp-book/books/{id}/ with random names, behind deny rules,
 * and records them in post meta. Files are downloaded only through the authenticated REST endpoint.
 */
final class File_Store {

	const META_FILES = '_wpbook_files';

	/**
	 * Base folder for all books.
	 *
	 * @return string
	 */
	public static function base_dir() {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . 'wp-book/books';
	}

	/**
	 * Create the base folder with deny rules. Safe to call repeatedly.
	 *
	 * @return string The folder.
	 */
	public static function ensure_base_dir() {
		$dir = self::base_dir();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		self::protect( dirname( $dir ) );
		self::protect( $dir );
		return $dir;
	}

	/**
	 * Add deny rules and an empty index to a folder.
	 *
	 * @param string $dir Folder.
	 */
	private static function protect( $dir ) {
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			// Apache 2.4 and 2.2. Nginx ignores this, which is why file names are also random.
			file_put_contents( $dir . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- tiny static file during setup.
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- tiny static file during setup.
		}
	}

	/**
	 * Folder of one book.
	 *
	 * @param int $book_id Book ID.
	 * @return string
	 */
	public static function book_dir( $book_id ) {
		return self::ensure_base_dir() . '/' . (int) $book_id;
	}

	/**
	 * Move a freshly generated file into the store and record it.
	 *
	 * @param Book   $book   Book.
	 * @param string $source Path of the generated PDF (it is moved).
	 * @param array  $meta   { kind: 'book'|'print-cover', pages: int }.
	 * @return array|null The file record, or null on failure.
	 */
	public static function add( Book $book, $source, array $meta ) {
		$dir = self::book_dir( $book->id );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$id   = strtolower( wp_generate_password( 24, false, false ) );
		$path = $dir . '/' . $id . '.pdf';
		// rename() fails across filesystems (system temp → uploads); fall back to copy + delete.
		$moved = @rename( $source, $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename -- see above.
		if ( ! $moved && copy( $source, $path ) ) {
			wp_delete_file( $source );
			$moved = true;
		}
		if ( ! $moved ) {
			return null;
		}

		$kind   = isset( $meta['kind'] ) ? $meta['kind'] : 'book';
		$record = array(
			'id'      => $id,
			'kind'    => $kind,
			'name'    => self::download_name( $book, $kind ),
			'size'    => (int) filesize( $path ),
			'pages'   => isset( $meta['pages'] ) ? (int) $meta['pages'] : 0,
			'created' => time(),
			'user'    => get_current_user_id(),
		);

		$files = self::records( $book->id );
		array_unshift( $files, $record );
		$keep = (int) $book->config['output']['keep'];
		foreach ( array_slice( $files, $keep ) as $old ) {
			self::unlink_record( $book->id, $old );
		}
		update_post_meta( $book->id, self::META_FILES, array_slice( $files, 0, $keep ) );
		return $record;
	}

	/**
	 * File records of a book, newest first.
	 *
	 * @param int $book_id Book ID.
	 * @return array[]
	 */
	public static function records( $book_id ) {
		$files = get_post_meta( (int) $book_id, self::META_FILES, true );
		return is_array( $files ) ? array_values( $files ) : array();
	}

	/**
	 * One file record.
	 *
	 * @param int    $book_id Book ID.
	 * @param string $file_id File ID.
	 * @return array|null
	 */
	public static function find( $book_id, $file_id ) {
		foreach ( self::records( $book_id ) as $record ) {
			if ( hash_equals( $record['id'], (string) $file_id ) ) {
				return $record;
			}
		}
		return null;
	}

	/**
	 * Absolute path of a recorded file.
	 *
	 * @param int   $book_id Book ID.
	 * @param array $record  File record.
	 * @return string
	 */
	public static function path( $book_id, array $record ) {
		return self::base_dir() . '/' . (int) $book_id . '/' . preg_replace( '/[^a-z0-9]/', '', $record['id'] ) . '.pdf';
	}

	/**
	 * Delete one file.
	 *
	 * @param int    $book_id Book ID.
	 * @param string $file_id File ID.
	 * @return bool
	 */
	public static function delete( $book_id, $file_id ) {
		$files = self::records( $book_id );
		foreach ( $files as $i => $record ) {
			if ( hash_equals( $record['id'], (string) $file_id ) ) {
				self::unlink_record( $book_id, $record );
				unset( $files[ $i ] );
				update_post_meta( (int) $book_id, self::META_FILES, array_values( $files ) );
				return true;
			}
		}
		return false;
	}

	/**
	 * Remove a book's folder when the book is deleted.
	 *
	 * @param int $post_id Post being deleted.
	 */
	public static function on_delete_post( $post_id ) {
		if ( Book_Post_Type::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}
		foreach ( self::records( $post_id ) as $record ) {
			self::unlink_record( $post_id, $record );
		}
		$dir = self::base_dir() . '/' . (int) $post_id;
		if ( is_dir( $dir ) ) {
			@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- folder is empty once files are gone.
		}
	}

	/**
	 * Send a file to the browser as a download and stop.
	 *
	 * @param int   $book_id Book ID.
	 * @param array $record  File record.
	 */
	public static function stream( $book_id, array $record ) {
		$path = self::path( $book_id, $record );
		if ( ! is_readable( $path ) ) {
			status_header( 404 );
			exit;
		}
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( "Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode( $record['name'] ) . '; filename="' . str_replace( '"', '', remove_accents( $record['name'] ) ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a large binary file.
		exit;
	}

	/**
	 * File name offered to the browser.
	 *
	 * @param Book   $book Book.
	 * @param string $kind book or print-cover.
	 * @return string
	 */
	private static function download_name( Book $book, $kind ) {
		$base = '' !== $book->config['output']['filename'] ? $book->config['output']['filename'] : $book->title;
		$base = sanitize_file_name( $base );
		$base = '' !== $base ? preg_replace( '/\.pdf$/i', '', $base ) : 'book';
		return $base . ( 'print-cover' === $kind ? '-print-cover' : '' ) . '.pdf';
	}

	/**
	 * Delete a recorded file from disk.
	 *
	 * @param int   $book_id Book ID.
	 * @param array $record  File record.
	 */
	private static function unlink_record( $book_id, array $record ) {
		$path = self::path( $book_id, $record );
		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}
}

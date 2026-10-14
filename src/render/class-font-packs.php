<?php
/**
 * Optional font packs for non-Latin scripts.
 *
 * @package WPBook
 */

namespace WPBook\Render;

use WP_Error;
use WPBook\Files\File_Store;

/**
 * The plugin ships with DejaVu only. Fonts for Chinese, Japanese, Korean and other scripts are mPDF's own
 * fonts, downloaded on request from the mPDF release that the plugin bundles, checked against the SHA-256
 * hashes in font-packs.php and stored in uploads/wp-book/fonts/.
 */
final class Font_Packs {

	/**
	 * Manifest cache.
	 *
	 * @var array|null
	 */
	private static $manifest = null;

	/**
	 * Generated manifest: mPDF version and files per pack.
	 *
	 * @return array
	 */
	public static function manifest() {
		if ( null === self::$manifest ) {
			self::$manifest = require __DIR__ . '/font-packs.php';
		}
		return self::$manifest;
	}

	/**
	 * Base URL of the font files: the ttfonts folder of the mPDF release the plugin bundles.
	 *
	 * @return string
	 */
	public static function source() {
		// Fonts are downloaded once, on an administrator's request, verified and stored locally; nothing is
		// loaded from this URL when pages or PDFs are viewed. Disclosed in readme.txt.
		return 'https://raw.githubusercontent.com/mpdf/mpdf/' . rawurlencode( self::manifest()['mpdf'] ) . '/ttfonts/'; // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- one-time admin download, see above.
	}

	/**
	 * Labels and descriptions of the packs.
	 *
	 * @return array[]
	 */
	public static function labels() {
		return array(
			'cjk'   => array(
				'name'        => __( 'Chinese, Japanese and Korean', 'wp-book' ),
				'description' => __( 'Fonts for Han characters, Hiragana, Katakana and Hangul.', 'wp-book' ),
			),
			'world' => array(
				'name'        => __( 'World scripts', 'wp-book' ),
				'description' => __( 'Arabic, Hebrew, Persian, Urdu, Indic scripts, Thai, Khmer, Lao, Myanmar, Tibetan, Ethiopic and more.', 'wp-book' ),
			),
		);
	}

	/**
	 * Folder that holds downloaded fonts.
	 *
	 * @return string
	 */
	public static function dir() {
		return dirname( File_Store::base_dir() ) . '/fonts';
	}

	/**
	 * Status of every pack.
	 *
	 * @return array[] key, name, description, size, files, installed (files present), complete.
	 */
	public static function all() {
		$out = array();
		foreach ( self::labels() as $key => $label ) {
			$files     = self::manifest()['packs'][ $key ];
			$installed = 0;
			foreach ( $files as $name => $file ) {
				if ( self::has_file( $name, $file['size'] ) ) {
					++$installed;
				}
			}
			$out[] = $label + array(
				'key'       => $key,
				'size'      => array_sum( array_column( $files, 'size' ) ),
				'files'     => count( $files ),
				'installed' => $installed,
				'complete'  => count( $files ) === $installed,
			);
		}
		return $out;
	}

	/**
	 * Whether a pack is fully installed.
	 *
	 * @param string $key Pack key.
	 * @return bool
	 */
	public static function is_installed( $key ) {
		foreach ( self::all() as $pack ) {
			if ( $key === $pack['key'] ) {
				return $pack['complete'];
			}
		}
		return false;
	}

	/**
	 * Download the next missing file of a pack. Call repeatedly until the pack is complete, so that no single
	 * request has to fetch the whole pack.
	 *
	 * @param string $key Pack key.
	 * @return array|WP_Error Pack status after the download.
	 */
	public static function install_next( $key ) {
		$packs = self::manifest()['packs'];
		if ( ! isset( $packs[ $key ] ) ) {
			return new WP_Error( 'wpbook_unknown_pack', __( 'Unknown font pack.', 'wp-book' ), array( 'status' => 404 ) );
		}
		foreach ( $packs[ $key ] as $name => $file ) {
			if ( self::has_file( $name, $file['size'] ) ) {
				continue;
			}
			$result = self::download( $name, $file );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			break;
		}
		return self::status( $key );
	}

	/**
	 * Delete the files of a pack.
	 *
	 * @param string $key Pack key.
	 * @return array|WP_Error Pack status.
	 */
	public static function remove( $key ) {
		$packs = self::manifest()['packs'];
		if ( ! isset( $packs[ $key ] ) ) {
			return new WP_Error( 'wpbook_unknown_pack', __( 'Unknown font pack.', 'wp-book' ), array( 'status' => 404 ) );
		}
		foreach ( array_keys( $packs[ $key ] ) as $name ) {
			if ( file_exists( self::dir() . '/' . $name ) ) {
				wp_delete_file( self::dir() . '/' . $name );
			}
		}
		return self::status( $key );
	}

	/**
	 * Packs that the given HTML needs but that are not installed.
	 *
	 * @param string $html Section HTML.
	 * @return string[] Pack keys.
	 */
	public static function missing_for( $html ) {
		$missing = array();
		$text    = wp_strip_all_tags( $html );
		if ( preg_match( '/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $text ) && ! self::is_installed( 'cjk' ) ) {
			$missing[] = 'cjk';
		}
		if ( preg_match( '/[\p{Arabic}\p{Hebrew}\p{Syriac}\p{Thaana}\p{Devanagari}\p{Bengali}\p{Gurmukhi}\p{Gujarati}\p{Oriya}\p{Tamil}\p{Telugu}\p{Kannada}\p{Malayalam}\p{Sinhala}\p{Thai}\p{Lao}\p{Tibetan}\p{Myanmar}\p{Khmer}\p{Ethiopic}]/u', $text ) && ! self::is_installed( 'world' ) ) {
			$missing[] = 'world';
		}
		return $missing;
	}

	/**
	 * Status of one pack.
	 *
	 * @param string $key Pack key.
	 * @return array
	 */
	private static function status( $key ) {
		foreach ( self::all() as $pack ) {
			if ( $key === $pack['key'] ) {
				return $pack;
			}
		}
		return array();
	}

	/**
	 * Whether a font file is present with the expected size.
	 *
	 * @param string $name File name.
	 * @param int    $size Expected size in bytes.
	 * @return bool
	 */
	private static function has_file( $name, $size ) {
		$path = self::dir() . '/' . $name;
		return file_exists( $path ) && filesize( $path ) === (int) $size;
	}

	/**
	 * Download one font file and verify its hash.
	 *
	 * @param string $name File name.
	 * @param array  $file { size, sha256 }.
	 * @return true|WP_Error
	 */
	private static function download( $name, array $file ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		File_Store::ensure_base_dir();
		if ( ! is_dir( self::dir() ) ) {
			wp_mkdir_p( self::dir() );
		}

		$tmp = download_url( self::source() . rawurlencode( $name ), 300 );
		if ( is_wp_error( $tmp ) ) {
			/* translators: 1: font file name, 2: error message */
			return new WP_Error( 'wpbook_font_download', sprintf( __( 'Could not download %1$s: %2$s', 'wp-book' ), $name, $tmp->get_error_message() ), array( 'status' => 502 ) );
		}
		if ( ! hash_equals( $file['sha256'], (string) hash_file( 'sha256', $tmp ) ) ) {
			wp_delete_file( $tmp );
			/* translators: %s: font file name */
			return new WP_Error( 'wpbook_font_hash', sprintf( __( 'The downloaded file %s did not match its checksum and was discarded.', 'wp-book' ), $name ), array( 'status' => 502 ) );
		}

		$path = self::dir() . '/' . $name;
		// rename() fails across filesystems (system temp → uploads); fall back to copy + delete.
		$moved = @rename( $tmp, $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename -- see above.
		if ( ! $moved && copy( $tmp, $path ) ) {
			$moved = true;
		}
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}
		if ( ! $moved ) {
			return new WP_Error( 'wpbook_font_write', __( 'The font could not be saved to the uploads folder.', 'wp-book' ), array( 'status' => 500 ) );
		}
		return true;
	}
}

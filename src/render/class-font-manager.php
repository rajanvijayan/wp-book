<?php
/**
 * Fonts available to covers and interiors.
 *
 * @package WPBook
 */

namespace WPBook\Render;

/**
 * Maps font keys to mPDF font definitions. Bundled OFL fonts live in fonts/{key}/; DejaVu ships with mPDF.
 */
final class Font_Manager {

	/**
	 * Font families keyed by the value stored in books.
	 *
	 * @return array<string, array{name: string, css: string, category: string, bundled: bool}>
	 */
	public static function all() {
		return array(
			'inter'        => array(
				'name'     => 'Inter',
				'css'      => 'wpbinter',
				'category' => 'sans-serif',
				'bundled'  => true,
			),
			'montserrat'   => array(
				'name'     => 'Montserrat',
				'css'      => 'wpbmontserrat',
				'category' => 'sans-serif',
				'bundled'  => true,
			),
			'lora'         => array(
				'name'     => 'Lora',
				'css'      => 'wpblora',
				'category' => 'serif',
				'bundled'  => true,
			),
			'playfair'     => array(
				'name'     => 'Playfair Display',
				'css'      => 'wpbplayfair',
				'category' => 'serif',
				'bundled'  => true,
			),
			'bebas'        => array(
				'name'     => 'Bebas Neue',
				'css'      => 'wpbbebas',
				'category' => 'display',
				'bundled'  => true,
			),
			'dejavu-serif' => array(
				'name'     => 'DejaVu Serif',
				'css'      => 'dejavuserif',
				'category' => 'serif',
				'bundled'  => false,
			),
			'dejavu-sans'  => array(
				'name'     => 'DejaVu Sans',
				'css'      => 'dejavusans',
				'category' => 'sans-serif',
				'bundled'  => false,
			),
			'mono'         => array(
				'name'     => 'DejaVu Sans Mono',
				'css'      => 'dejavusansmono',
				'category' => 'monospace',
				'bundled'  => false,
			),
		);
	}

	/**
	 * CSS font-family value for a key, with a fallback that mPDF always has.
	 *
	 * @param string $key Font key.
	 * @return string
	 */
	public static function css_family( $key ) {
		$families = self::all();
		if ( ! isset( $families[ $key ] ) ) {
			return 'dejavusans';
		}
		$fallback = 'serif' === $families[ $key ]['category'] ? 'dejavuserif' : 'dejavusans';
		return $families[ $key ]['css'] . ', ' . $fallback;
	}

	/**
	 * Directory of the bundled fonts.
	 *
	 * @return string
	 */
	public static function bundled_dir() {
		return WPBOOK_DIR . 'fonts';
	}

	/**
	 * Extra mPDF configuration that registers the bundled fonts.
	 *
	 * @param array $config mPDF configuration built so far.
	 * @return array
	 */
	public static function mpdf_config( array $config ) {
		$defaults  = ( new \WPBook\Vendor\Mpdf\Config\FontVariables() )->getDefaults();
		$dirs      = ( new \WPBook\Vendor\Mpdf\Config\ConfigVariables() )->getDefaults()['fontDir'];
		$font_data = $defaults['fontdata'];

		foreach ( self::all() as $key => $family ) {
			if ( ! $family['bundled'] ) {
				continue;
			}
			$entry = array( 'R' => $key . '/' . $key . '-Regular.ttf' );
			foreach ( array(
				'B'  => 'Bold',
				'I'  => 'Italic',
				'BI' => 'BoldItalic',
			) as $style => $suffix ) {
				$file = $key . '/' . $key . '-' . $suffix . '.ttf';
				if ( file_exists( self::bundled_dir() . '/' . $file ) ) {
					$entry[ $style ] = $file;
				}
			}
			$font_data[ $family['css'] ] = $entry;
		}

		// Without WordPress (tests/smoke-pdf.php) there is no uploads folder, so no downloaded packs.
		if ( function_exists( 'wp_upload_dir' ) ) {
			$dirs[] = Font_Packs::dir();
		}
		$dirs[] = self::bundled_dir();

		// The release ships mPDF with DejaVu only; drop families whose files are not installed so that
		// mPDF falls back to the default font instead of failing on a missing file.
		foreach ( $font_data as $family => $files ) {
			if ( ! self::font_file_exists( $dirs, $files['R'] ) ) {
				unset( $font_data[ $family ] );
			}
		}

		$config['fontDir']  = $dirs;
		$config['fontdata'] = $font_data;
		return $config;
	}

	/**
	 * Whether a font file is in one of the font folders.
	 *
	 * @param string[] $dirs Font folders.
	 * @param string   $file File name relative to a folder.
	 * @return bool
	 */
	private static function font_file_exists( array $dirs, $file ) {
		foreach ( $dirs as $dir ) {
			if ( file_exists( $dir . '/' . $file ) ) {
				return true;
			}
		}
		return false;
	}
}

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

		$config['fontDir']  = array_merge( $dirs, array( self::bundled_dir() ) );
		$config['fontdata'] = $font_data;
		return $config;
	}
}

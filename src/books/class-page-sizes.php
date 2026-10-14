<?php
/**
 * Paper sizes offered in the layout settings.
 *
 * @package WPBook
 */

namespace WPBook\Books;

/**
 * Page sizes in millimetres, keyed by the value stored in a book.
 */
final class Page_Sizes {

	/**
	 * All sizes. Book trade sizes come first because they are the most common for eBooks and print.
	 *
	 * @return array<string, array{label: string, width: float, height: float, group: string}>
	 */
	public static function all() {
		$sizes = array(
			'A4'          => array( 'A4', 210, 297, 'paper' ),
			'A5'          => array( 'A5', 148, 210, 'paper' ),
			'LETTER'      => array( 'US Letter', 215.9, 279.4, 'paper' ),
			'LEGAL'       => array( 'US Legal', 215.9, 355.6, 'paper' ),
			'TRADE-6X9'   => array( 'Trade paperback 6 × 9 in', 152.4, 228.6, 'book' ),
			'TRADE-55X85' => array( 'Digest 5.5 × 8.5 in', 139.7, 215.9, 'book' ),
			'TRADE-5X8'   => array( 'Novel 5 × 8 in', 127, 203.2, 'book' ),
			'TRADE-7X10'  => array( 'Textbook 7 × 10 in', 177.8, 254, 'book' ),
			'TRADE-85X11' => array( 'Workbook 8.5 × 11 in', 215.9, 279.4, 'book' ),
			'POCKET'      => array( 'Pocket 4.25 × 6.87 in', 108, 174.5, 'book' ),
			'ROYAL'       => array( 'Royal 153 × 234 mm', 153, 234, 'book' ),
			'DEMY'        => array( 'Demy 135 × 216 mm', 135, 216, 'book' ),
			'A3'          => array( 'A3', 297, 420, 'paper' ),
			'A6'          => array( 'A6', 105, 148, 'paper' ),
			'B5'          => array( 'B5', 176, 250, 'paper' ),
			'EXECUTIVE'   => array( 'Executive', 184.1, 266.7, 'paper' ),
			'TABLOID'     => array( 'Tabloid', 279.4, 431.8, 'paper' ),
			'SQUARE-8'    => array( 'Square 8 × 8 in', 203.2, 203.2, 'book' ),
		);
		$out   = array();
		foreach ( $sizes as $key => $size ) {
			$out[ $key ] = array(
				'label'  => $size[0],
				'width'  => (float) $size[1],
				'height' => (float) $size[2],
				'group'  => $size[3],
			);
		}
		return $out;
	}

	/**
	 * Page size of a book in mm, honouring an internal exact-size override (used for print covers with bleed).
	 *
	 * @param array $layout Layout settings.
	 * @return float[] [width, height]
	 */
	public static function for_layout( array $layout ) {
		if ( isset( $layout['_size'] ) && is_array( $layout['_size'] ) ) {
			return array( (float) $layout['_size'][0], (float) $layout['_size'][1] );
		}
		return self::dimensions( $layout['format'], $layout['orientation'] );
	}

	/**
	 * Width and height in mm for a key, in the given orientation.
	 *
	 * @param string $key         Size key.
	 * @param string $orientation P or L.
	 * @return float[] [width, height]
	 */
	public static function dimensions( $key, $orientation = 'P' ) {
		$all  = self::all();
		$size = isset( $all[ $key ] ) ? $all[ $key ] : $all['A4'];
		$w    = $size['width'];
		$h    = $size['height'];
		return 'L' === $orientation ? array( max( $w, $h ), min( $w, $h ) ) : array( min( $w, $h ), max( $w, $h ) );
	}
}

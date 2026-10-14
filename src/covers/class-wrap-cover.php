<?php
/**
 * Print cover: back + spine + front on one sheet with bleed.
 *
 * @package WPBook
 */

namespace WPBook\Covers;

use WPBook\Books\Book;
use WPBook\Books\Page_Sizes;
use WPBook\Render\Book_Renderer;
use WPBook\Render\Font_Manager;
use WPBook\Render\Mpdf_Engine;
use WPBook\Vendor\Mpdf\Mpdf;
use WPBook\Vendor\Mpdf\Output\Destination;

/**
 * Builds the single-sheet cover that print-on-demand services ask for.
 *
 * Layout, left to right: bleed, back cover, spine, front cover, bleed. The front and back are rendered
 * with the normal cover templates at trim size plus bleed and placed with mPDF's page import.
 */
final class Wrap_Cover {

	/**
	 * Bleed on every outer edge: 0.125 in.
	 */
	const BLEED = 3.175;

	/**
	 * Spine needs at least this width (mm) before text is printed on it.
	 */
	const MIN_SPINE_TEXT = 6.0;

	/**
	 * Paper thickness per page in mm (common print-on-demand values).
	 *
	 * @param string $paper white or cream.
	 * @return float
	 */
	public static function page_thickness( $paper ) {
		return 'cream' === $paper ? 0.0635 : 0.0572;
	}

	/**
	 * Spine width in mm for a page count.
	 *
	 * @param int    $pages Interior page count.
	 * @param string $paper white or cream.
	 * @return float
	 */
	public static function spine_width( $pages, $paper ) {
		return round( max( 0, (int) $pages ) * self::page_thickness( $paper ), 2 );
	}

	/**
	 * Render the wrap cover to a file.
	 *
	 * @param Book   $book  Book.
	 * @param int    $pages Page count of the interior PDF.
	 * @param string $path  Destination file.
	 * @return array{pages: int, spine: float, width: float, height: float}
	 */
	public static function render( Book $book, $pages, $path ) {
		list( $trim_w, $trim_h ) = Page_Sizes::for_layout( $book->config['layout'] );
		$spine                   = self::spine_width( $pages, $book->config['output']['paper'] );
		$panel_w                 = $trim_w + self::BLEED;
		$sheet_h                 = $trim_h + 2 * self::BLEED;
		$sheet_w                 = 2 * $panel_w + $spine;

		$front = self::render_panel( $book, 'front', $panel_w, $sheet_h );
		$back  = self::render_panel( $book, 'back', $panel_w, $sheet_h );

		$config = Font_Manager::mpdf_config(
			array(
				'mode'          => 'utf-8',
				'format'        => array( $sheet_w, $sheet_h ),
				'orientation'   => 'P',
				'tempDir'       => Mpdf_Engine::temp_dir(),
				'margin_left'   => 0,
				'margin_right'  => 0,
				'margin_top'    => 0,
				'margin_bottom' => 0,
				'margin_header' => 0,
				'margin_footer' => 0,
			)
		);
		$mpdf   = new Mpdf( $config );
		$mpdf->SetTitle( $book->cover_title() . ' – ' . __( 'print cover', 'wp-book' ) );
		$mpdf->SetCreator( 'BookPress' );
		$mpdf->AddPage();

		$mpdf->SetSourceFile( $back );
		$mpdf->UseTemplate( $mpdf->ImportPage( 1 ), 0, 0, $panel_w, $sheet_h );
		$mpdf->SetSourceFile( $front );
		$mpdf->UseTemplate( $mpdf->ImportPage( 1 ), $panel_w + $spine, 0, $panel_w, $sheet_h );

		if ( $spine > 0 ) {
			$mpdf->WriteHTML( self::spine_html( $book, $panel_w, $spine, $sheet_h ) );
		}
		$mpdf->Output( $path, Destination::FILE );
		wp_delete_file( $front );
		wp_delete_file( $back );

		return array(
			'pages'  => 1,
			'spine'  => $spine,
			'width'  => round( $sheet_w, 2 ),
			'height' => round( $sheet_h, 2 ),
		);
	}

	/**
	 * Render the front or back cover alone at panel size (trim + bleed).
	 *
	 * @param Book   $book   Book.
	 * @param string $side   front or back.
	 * @param float  $width  Panel width (mm).
	 * @param float  $height Panel height (mm).
	 * @return string Temporary PDF path.
	 */
	private static function render_panel( Book $book, $side, $width, $height ) {
		$config                          = $book->config;
		$config['layout']['_size']       = array( $width, $height );
		$config['cover']['enabled']      = 'front' === $side;
		$config['back_cover']['enabled'] = 'back' === $side;
		$config['output']['password']    = '';
		$config['output']['watermark']   = '';
		$config['content']['chapters']   = array();
		$panel                           = new Book( $book->id, $book->title, $config, $book->modified_gmt, $book->author );
		$path                            = Mpdf_Engine::temp_file( 'wpbook-' . $side );
		( new Mpdf_Engine() )->render( new Book_Renderer( $panel, Book_Renderer::MODE_COVER ), $path );
		return $path;
	}

	/**
	 * Spine: cover colour with the title and author running top to bottom.
	 *
	 * @param Book  $book   Book.
	 * @param float $left   Spine x position (mm).
	 * @param float $width  Spine width (mm).
	 * @param float $height Sheet height (mm).
	 * @return string
	 */
	private static function spine_html( Book $book, $left, $width, $height ) {
		$cover = $book->config['cover'];
		$html  = '<div style="position: absolute; left: ' . $left . 'mm; top: 0; width: ' . $width . 'mm; height: ' . $height . 'mm; background: ' . esc_attr( $cover['primary'] ) . ';"></div>';
		if ( $width < self::MIN_SPINE_TEXT ) {
			return $html;
		}
		$size = min( 14, round( $width * 0.42 * 2.83, 1 ) );
		$text = esc_html( $book->cover_title() ) . '<span style="color: ' . esc_attr( $cover['accent'] ) . ';">  ·  </span>' . esc_html( $book->author_name() );
		// A rotated table cell is mPDF's way to set vertical text; it reads top to bottom as on most spines.
		return $html . '<div style="position: absolute; left: ' . $left . 'mm; top: ' . ( self::BLEED + 8 ) . 'mm; width: ' . $width . 'mm; height: ' . ( $height - 2 * self::BLEED - 16 ) . 'mm;">'
			. '<table style="width: ' . $width . 'mm; height: ' . ( $height - 2 * self::BLEED - 16 ) . 'mm; border-collapse: collapse;"><tr>'
			. '<td text-rotate="-90" style="vertical-align: middle; text-align: center; padding: 0; border: 0; font-family: ' . Font_Manager::css_family( $cover['title_font'] ) . '; font-size: ' . $size . 'pt; color: ' . esc_attr( $cover['text_color'] ) . ';">' . $text . '</td>'
			. '</tr></table></div>';
	}
}

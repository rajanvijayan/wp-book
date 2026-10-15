<?php
/**
 * Generates a small PDF with the bundled (prefixed) mPDF, the plugin's fonts, a QR code and named-page
 * headers, then checks the output. Runs without WordPress.
 *
 * Usage: php tests/smoke-pdf.php
 *
 * @package WPBook
 */

define( 'WPBOOK_DIR', dirname( __DIR__ ) . '/' );
require WPBOOK_DIR . 'vendor/autoload.php';
require WPBOOK_DIR . 'vendor-prefixed/autoload.php';

use WPBook\Render\Font_Manager;
use WPBook\Vendor\Mpdf\HTMLParserMode;
use WPBook\Vendor\Mpdf\Mpdf;

$out  = sys_get_temp_dir() . '/wp-book-smoke.pdf';
$mpdf = new Mpdf(
	Font_Manager::mpdf_config(
		array(
			'format'           => array( 152.4, 228.6 ),
			'tempDir'          => sys_get_temp_dir() . '/wp-book',
			'autoScriptToLang' => true,
			'autoLangToFont'   => true,
		)
	)
);
$mpdf->WriteHTML(
	'h1 { font-family: ' . Font_Manager::css_family( 'playfair' ) . '; } p { font-family: ' . Font_Manager::css_family( 'lora' ) . '; }'
	. '@page wpbm1 { odd-header-name: html_h1; even-header-name: html_h1; } @page wpbm1 :first { header: html_blank; }',
	HTMLParserMode::HEADER_CSS
);
$mpdf->WriteHTML( '<htmlpageheader name="blank"><div></div></htmlpageheader><htmlpageheader name="h1"><div>Running header</div></htmlpageheader>' );
$mpdf->AddPageByArray( array( 'pageselector' => 'wpbm1' ) );
$mpdf->WriteHTML( '<h1>Chapter 1</h1><p>Hello <b>world</b> <i>in italics</i>: café, 日本語, العربية.</p>' . str_repeat( '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>', 40 ) );
$mpdf->WriteHTML( '<barcode code="https://wordpress.org/plugins/wp-book/" type="QR" size="0.8" />' );
$mpdf->Output( $out, 'F' );

$fonts = array();
preg_match_all( '#/BaseFont\s*/([A-Z]{6}\+)?([A-Za-z0-9-]+)#', (string) file_get_contents( $out ), $m );
foreach ( $m[2] as $font ) {
	$fonts[ $font ] = true;
}
$ok = file_get_contents( $out, false, null, 0, 5 ) === '%PDF-' && $mpdf->page >= 2
	&& preg_grep( '/^Playfair/i', array_keys( $fonts ) ) && preg_grep( '/^Lora/i', array_keys( $fonts ) );
if ( ! $ok ) {
	fwrite( STDERR, 'PDF smoke test failed. Fonts: ' . implode( ', ', array_keys( $fonts ) ) . "\n" );
	exit( 1 );
}
printf( "mPDF %s on PHP %s: %d pages, %d bytes, fonts: %s\n", Mpdf::VERSION, PHP_VERSION, $mpdf->page, filesize( $out ), implode( ', ', array_keys( $fonts ) ) );

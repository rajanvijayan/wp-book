<?php
/**
 * Dev script: render gallery thumbnails for every cover design and interior template.
 *
 * Creates temporary sample posts and a generated landscape image, renders each design with the real
 * engine, rasterises it with pdftoppm and writes templates/covers/{key}/thumb.png and
 * templates/interior/{key}/thumb.png. Everything temporary is deleted afterwards.
 *
 * Usage (needs pdftoppm and GD): wp eval-file bin/render-thumbs.php [covers|interior|all] [out-dir]
 *
 * @package WPBook
 */

use WPBook\Books\Book;
use WPBook\Books\Book_Schema;
use WPBook\Covers\Cover_Registry;
use WPBook\Render\Book_Renderer;
use WPBook\Render\Mpdf_Engine;
use WPBook\Templates\Template_Registry;

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DiscouragedPHPFunctions, WordPress.Security.EscapeOutput -- CLI dev script.

$wpbook_what = isset( $args[0] ) ? $args[0] : 'all';
$wpbook_out  = isset( $args[1] ) ? rtrim( $args[1], '/' ) : WPBOOK_DIR . 'templates';
wp_set_current_user( 1 );

require_once __DIR__ . '/render-thumbs-lib.php';

/**
 * Render a book and rasterise pages to PNG files.
 *
 * @param Book   $book  Book.
 * @param string $mode  Render mode.
 * @param int    $first First page.
 * @param int    $last  Last page.
 * @param int    $px    Width of each page in pixels.
 * @return string[] PNG paths.
 */
function wpbook_thumb_render( Book $book, $mode, $first, $last, $px ) {
	$pdf = Mpdf_Engine::temp_file( 'thumb' );
	( new Mpdf_Engine() )->render( new Book_Renderer( $book, $mode ), $pdf );
	$prefix = $pdf . '-p';
	exec( sprintf( 'pdftoppm -png -f %d -l %d -scale-to-x %d -scale-to-y -1 %s %s', $first, $last, $px, escapeshellarg( $pdf ), escapeshellarg( $prefix ) ) );
	$files = glob( $prefix . '*.png' );
	sort( $files );
	unlink( $pdf );
	return $files;
}

$wpbook_image = wpbook_thumb_photo();
$wpbook_posts = wpbook_thumb_posts( $wpbook_image );
$wpbook_base  = Book_Schema::defaults();
$wpbook_base['content']['chapters'] = array_map(
	static function ( $id ) {
		return array(
			'type'    => 'post',
			'id'      => $id,
			'include' => array( 'featured_image' => false ),
		);
	},
	$wpbook_posts
);

if ( in_array( $wpbook_what, array( 'all', 'covers' ), true ) ) {
	foreach ( Cover_Registry::all() as $key => $cover ) {
		$config = Book_Schema::merge(
			$wpbook_base,
			array(
				'cover'  => array_merge(
					$cover['defaults'],
					array(
						'template'     => $key,
						'subtitle'     => 'Walks, notes and small discoveries',
						'author'       => 'Alex Morgan',
						'badge'        => 'Volume One',
						'image_id'     => $cover['uses_image'] ? $wpbook_image : 0,
						'use_featured' => false,
					)
				),
				'layout' => array( 'format' => 'TRADE-6X9' ),
			)
		);
		$files = wpbook_thumb_render( new Book( 0, 'Field Notes', $config ), Book_Renderer::MODE_COVER, 1, 1, 300 );
		@mkdir( "$wpbook_out/covers/$key", 0755, true );
		rename( $files[0], "$wpbook_out/covers/$key/thumb.png" );
		WP_CLI::log( "cover $key" );
	}
}

if ( in_array( $wpbook_what, array( 'all', 'interior' ), true ) ) {
	foreach ( array_keys( Template_Registry::all() ) as $key ) {
		$template = Template_Registry::get( $key );
		$config   = Book_Schema::merge(
			$wpbook_base,
			array(
				'cover'     => array( 'enabled' => false ),
				'structure' => array(
					'title_page' => false,
					'copyright'  => false,
					'toc'        => false,
				),
				'interior'  => array(
					'template'  => $key,
					'drop_caps' => 'classic' === $key,
					'indent'    => 'classic' === $key,
				),
				'layout'    => array_merge( array( 'format' => 'TRADE-6X9' ), $template['layout_defaults'] ),
			)
		);
		$files = wpbook_thumb_render( new Book( 0, 'Field Notes', $config ), Book_Renderer::MODE_SAMPLE, 1, 2, 240 );
		// Compose a two-page spread on a light grey background.
		$pages  = array_map( 'imagecreatefrompng', $files );
		$pw     = imagesx( $pages[0] );
		$ph     = imagesy( $pages[0] );
		$pad    = 16;
		$spread = imagecreatetruecolor( $pw * 2 + $pad * 2 + 2, $ph + $pad * 2 );
		imagefill( $spread, 0, 0, imagecolorallocate( $spread, 233, 234, 236 ) );
		foreach ( $pages as $i => $page ) {
			imagecopy( $spread, $page, $pad + $i * ( $pw + 2 ), $pad, 0, 0, $pw, $ph );
			imagedestroy( $page );
		}
		@mkdir( "$wpbook_out/interior/$key", 0755, true );
		imagepng( $spread, "$wpbook_out/interior/$key/thumb.png", 9 );
		imagedestroy( $spread );
		array_map( 'unlink', $files );
		WP_CLI::log( "interior $key" );
	}
}

foreach ( $wpbook_posts as $wpbook_id ) {
	wp_delete_post( $wpbook_id, true );
}
wp_delete_attachment( $wpbook_image, true );
WP_CLI::success( 'Thumbnails written to ' . $wpbook_out );

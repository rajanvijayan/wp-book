<?php
/**
 * Sample content helpers for the dev scripts (thumbnails, demo data).
 *
 * @package WPBook
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DiscouragedPHPFunctions -- CLI dev script.

/**
 * Draw a synthetic landscape photo (sky, sun, mountains, lake) and add it to the media library.
 *
 * @return int Attachment ID.
 */
function wpbook_thumb_photo() {
	$w   = 1200;
	$h   = 1600;
	$img = imagecreatetruecolor( $w, $h );
	for ( $y = 0; $y < $h; $y++ ) {
		$t = $y / $h;
		$c = imagecolorallocate( $img, (int) ( 40 + 200 * $t ), (int) ( 70 + 120 * $t ), (int) ( 140 + 60 * ( 1 - $t ) ) );
		imageline( $img, 0, $y, $w, $y, $c );
	}
	imagefilledellipse( $img, 820, 620, 260, 260, imagecolorallocate( $img, 255, 214, 150 ) );
	$layers = array(
		array( 0.62, array( 70, 80, 120 ), 380 ),
		array( 0.70, array( 45, 55, 90 ), 300 ),
		array( 0.78, array( 25, 35, 60 ), 240 ),
	);
	foreach ( $layers as $i => $layer ) {
		list( $base, $rgb, $amp ) = $layer;
		$points = array( 0, $h );
		for ( $x = 0; $x <= $w; $x += 60 ) {
			$points[] = $x;
			$points[] = (int) ( $h * $base - abs( sin( ( $x + $i * 170 ) / 210 ) ) * $amp * 0.5 - ( $x % 120 ) * 0.2 );
		}
		$points[] = $w;
		$points[] = $h;
		imagefilledpolygon( $img, $points, imagecolorallocate( $img, $rgb[0], $rgb[1], $rgb[2] ) );
	}
	// A lake in the lower quarter reflecting the sky, so the horizon has no hard edge.
	$lake = (int) ( $h * 0.8 );
	for ( $y = $lake; $y < $h; $y++ ) {
		$t = ( $y - $lake ) / ( $h - $lake );
		$c = imagecolorallocate( $img, (int) ( 150 - 110 * $t ), (int) ( 140 - 100 * $t ), (int) ( 170 - 90 * $t ) );
		imageline( $img, 0, $y, $w, $y, $c );
	}
	imagefilledellipse( $img, 820, (int) ( $lake + 90 ), 200, 26, imagecolorallocate( $img, 230, 196, 150 ) );
	$uploads = wp_upload_dir();
	$file    = $uploads['path'] . '/wpbook-sample-landscape.jpg';
	imagejpeg( $img, $file, 88 );
	imagedestroy( $img );
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'BookPress sample landscape',
			'post_status'    => 'inherit',
		),
		$file
	);
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	return $id;
}

/**
 * Sample chapter body: paragraphs, headings, a quote, a list, code and a table.
 *
 * @return string
 */
function wpbook_thumb_body() {
	$para = 'The valley woke slowly that morning. Mist hung over the river, and the path climbed between birches whose leaves had only just begun to turn. She walked without hurry, counting the bridges, listening to the water and to her own steps on the wet stones. ';
	$body = '<p>' . str_repeat( $para, 2 ) . '</p>'
		. '<h2>Into the hills</h2><p>' . $para . '</p>'
		. '<blockquote><p>Every road is a story told one step at a time.</p></blockquote>'
		. '<p>' . str_repeat( $para, 2 ) . '</p>'
		. '<h3>What to carry</h3><ul><li>Water and a warm layer</li><li>A paper map</li><li>Something to write with</li></ul>'
		. '<pre><code>route = plan(start, "ridge", stops=3)</code></pre>'
		. '<table><tr><th>Stage</th><th>Distance</th></tr><tr><td>Valley</td><td>6 km</td></tr><tr><td>Ridge</td><td>9 km</td></tr></table>'
		. '<h2>The ridge</h2><p>' . str_repeat( $para, 3 ) . '</p>';
	return $body;
}

/**
 * Create sample chapter posts.
 *
 * @param int $image Attachment ID used as a featured image.
 * @return int[] Post IDs.
 */
function wpbook_thumb_posts( $image ) {
	$body = wpbook_thumb_body();
	$ids  = array();
	foreach ( array( 'Morning in the Valley', 'The Long Ridge', 'Coming Home' ) as $title ) {
		$id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $body,
				'post_status'  => 'publish',
				'post_excerpt' => 'A quiet walk from the river to the ridge.',
			)
		);
		set_post_thumbnail( $id, $image );
		$ids[] = $id;
	}
	return $ids;
}


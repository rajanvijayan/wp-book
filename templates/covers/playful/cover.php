<?php
/**
 * Playful cover: confetti dots and squiggles, seeded from the title so the layout is stable.
 *
 * @package WPBook
 * @var array  $cover    Cover settings.
 * @var string $title    Title.
 * @var string $subtitle Subtitle.
 * @var string $author   Author.
 * @var string $badge    Badge.
 * @var float  $width    Page width (mm).
 * @var float  $height   Page height (mm).
 */

defined( 'ABSPATH' ) || exit;

$wpbook_colors = array( $cover['accent'], '#ffbe0b', '#fb5607', '#8338ec', '#06d6a0' );
$wpbook_seed   = crc32( $title );
$wpbook_rand   = static function () use ( &$wpbook_seed ) {
	$wpbook_seed = ( $wpbook_seed * 1103515245 + 12345 ) & 0x7fffffff;
	return $wpbook_seed / 0x7fffffff;
};
$wpbook_shapes = '';
for ( $wpbook_i = 0; $wpbook_i < 34; $wpbook_i++ ) {
	$wpbook_x = $wpbook_rand() * $width;
	$wpbook_y = $wpbook_rand() * $height;
	// Keep the title area (30%–70% height, left two thirds) clear.
	if ( $wpbook_y > $height * 0.28 && $wpbook_y < $height * 0.72 && $wpbook_x < $width * 0.78 ) {
		continue;
	}
	$wpbook_c = esc_attr( $wpbook_colors[ $wpbook_i % count( $wpbook_colors ) ] );
	if ( 0 === $wpbook_i % 4 ) {
		$wpbook_shapes .= '<path d="M' . $wpbook_x . ',' . $wpbook_y . ' q2,-3 4,0 t4,0 t4,0" fill="none" stroke="' . $wpbook_c . '" stroke-width="1.1" stroke-linecap="round"/>';
	} else {
		$wpbook_shapes .= '<circle cx="' . $wpbook_x . '" cy="' . $wpbook_y . '" r="' . ( 0.8 + 2.2 * $wpbook_rand() ) . '" fill="' . $wpbook_c . '"/>';
	}
}
$wpbook_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . 'mm" height="' . $height . 'mm" viewBox="0 0 ' . $width . ' ' . $height . '">' . $wpbook_shapes . '</svg>';
?>
<div class="wpbc-play"></div>
<div class="wpbc-play-art"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_svg ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $height ); ?>mm;" /></div>
<?php if ( '' !== $badge ) : ?>
	<div class="wpbc-play-badge"><?php echo esc_html( $badge ); ?></div>
<?php endif; ?>
<div class="wpbc-play-text">
	<div class="wpbc-play-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-play-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-play-author"><?php echo esc_html( $author ); ?></div>

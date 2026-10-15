<?php
/**
 * Botanical cover: leaf sprays in two corners.
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

$wpbook_a = esc_attr( $cover['accent'] );

/**
 * One leaf spray: a curved stem with paired leaves, as SVG.
 *
 * @param float  $x     Origin x.
 * @param float  $y     Origin y.
 * @param float  $angle Rotation in degrees.
 * @param float  $scale Size multiplier.
 * @param string $color Fill colour.
 * @return string
 */
$wpbook_spray = static function ( $x, $y, $angle, $scale, $color ) {
	$leaf  = 'M0,0 C3,-4 9,-4 12,0 C9,4 3,4 0,0 Z';
	$out   = '<g transform="translate(' . $x . ',' . $y . ') rotate(' . $angle . ') scale(' . $scale . ')">';
	$out  .= '<path d="M0,0 C10,-2 22,-1 40,4" fill="none" stroke="' . $color . '" stroke-width="0.6"/>';
	$steps = array( array( 6, -1, -40 ), array( 12, -1.6, 35 ), array( 19, -1.4, -38 ), array( 26, -0.6, 32 ), array( 33, 1.2, -30 ) );
	foreach ( $steps as $i => $step ) {
		list( $sx, $sy, $rot ) = $step;
		$opacity               = 0.55 + 0.08 * $i;
		$out                  .= '<path d="' . $leaf . '" transform="translate(' . $sx . ',' . $sy . ') rotate(' . $rot . ')" fill="' . $color . '" fill-opacity="' . $opacity . '"/>';
	}
	return $out . '</g>';
};
$wpbook_svg   = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . 'mm" height="' . $height . 'mm" viewBox="0 0 ' . $width . ' ' . $height . '">'
	. $wpbook_spray( -4, $height * 0.08, 28, $width / 70, $wpbook_a )
	. $wpbook_spray( $width * 0.1, -3, 62, $width / 110, $wpbook_a )
	. $wpbook_spray( $width + 4, $height * 0.9, 208, $width / 70, $wpbook_a )
	. $wpbook_spray( $width * 0.88, $height + 3, 242, $width / 110, $wpbook_a )
	. '</svg>';
?>
<div class="wpbc-bot"></div>
<div class="wpbc-bot-art"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_svg ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $height ); ?>mm;" /></div>
<div class="wpbc-bot-text">
	<?php if ( '' !== $badge ) : ?>
		<div class="wpbc-bot-badge"><?php echo esc_html( $badge ); ?></div>
	<?php endif; ?>
	<div class="wpbc-bot-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-bot-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-bot-author"><?php echo esc_html( $author ); ?></div>

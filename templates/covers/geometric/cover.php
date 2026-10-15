<?php
/**
 * Geometric cover.
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

$wpbook_w   = $width;
$wpbook_h   = $height;
$wpbook_a   = esc_attr( $cover['accent'] );
$wpbook_t   = esc_attr( $cover['text_color'] );
$wpbook_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $wpbook_w . 'mm" height="' . $wpbook_h . 'mm" viewBox="0 0 ' . $wpbook_w . ' ' . $wpbook_h . '">'
	. '<circle cx="' . ( $wpbook_w * 0.86 ) . '" cy="' . ( $wpbook_h * 0.16 ) . '" r="' . ( $wpbook_w * 0.48 ) . '" fill="' . $wpbook_a . '"/>'
	. '<circle cx="' . ( $wpbook_w * 0.62 ) . '" cy="' . ( $wpbook_h * 0.42 ) . '" r="' . ( $wpbook_w * 0.2 ) . '" fill="' . $wpbook_t . '" fill-opacity="0.12"/>'
	. '<polygon points="0,' . ( $wpbook_h * 0.5 ) . ' ' . ( $wpbook_w * 0.36 ) . ',' . ( $wpbook_h * 0.74 ) . ' 0,' . ( $wpbook_h * 0.98 ) . '" fill="' . $wpbook_a . '" fill-opacity="0.55"/>'
	. '<circle cx="' . ( $wpbook_w * 0.2 ) . '" cy="' . ( $wpbook_h * 0.3 ) . '" r="' . ( $wpbook_w * 0.05 ) . '" fill="none" stroke="' . $wpbook_t . '" stroke-width="0.8" stroke-opacity="0.6"/>'
	. '<line x1="' . ( $wpbook_w * 0.1 ) . '" y1="' . ( $wpbook_h * 0.62 ) . '" x2="' . ( $wpbook_w * 0.32 ) . '" y2="' . ( $wpbook_h * 0.62 ) . '" stroke="' . $wpbook_a . '" stroke-width="1.6"/>'
	. '</svg>';
?>
<div class="wpbc-geo"></div>
<div class="wpbc-geo-art"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_svg ) ); ?>" style="width: <?php echo esc_attr( $wpbook_w ); ?>mm; height: <?php echo esc_attr( $wpbook_h ); ?>mm;" /></div>
<?php if ( '' !== $badge ) : ?>
	<div class="wpbc-geo-badge"><?php echo esc_html( $badge ); ?></div>
<?php endif; ?>
<div class="wpbc-geo-text">
	<div class="wpbc-geo-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-geo-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-geo-author"><?php echo esc_html( $author ); ?></div>

<?php
/**
 * Classic Novel cover.
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
$wpbook_m   = round( $wpbook_w * 0.07, 2 );
$wpbook_m2  = round( $wpbook_m + 2.2, 2 );
$wpbook_mid = round( $wpbook_h * 0.29, 2 );
$wpbook_cx  = round( $wpbook_w / 2, 2 );
$wpbook_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $wpbook_w . 'mm" height="' . $wpbook_h . 'mm" viewBox="0 0 ' . $wpbook_w . ' ' . $wpbook_h . '">'
	. '<rect x="' . $wpbook_m . '" y="' . $wpbook_m . '" width="' . ( $wpbook_w - 2 * $wpbook_m ) . '" height="' . ( $wpbook_h - 2 * $wpbook_m ) . '" fill="none" stroke="' . $wpbook_a . '" stroke-width="0.8"/>'
	. '<rect x="' . $wpbook_m2 . '" y="' . $wpbook_m2 . '" width="' . ( $wpbook_w - 2 * $wpbook_m2 ) . '" height="' . ( $wpbook_h - 2 * $wpbook_m2 ) . '" fill="none" stroke="' . $wpbook_a . '" stroke-width="0.3"/>'
	. '<line x1="' . ( $wpbook_cx - 22 ) . '" y1="' . $wpbook_mid . '" x2="' . ( $wpbook_cx - 4 ) . '" y2="' . $wpbook_mid . '" stroke="' . $wpbook_a . '" stroke-width="0.4"/>'
	. '<line x1="' . ( $wpbook_cx + 4 ) . '" y1="' . $wpbook_mid . '" x2="' . ( $wpbook_cx + 22 ) . '" y2="' . $wpbook_mid . '" stroke="' . $wpbook_a . '" stroke-width="0.4"/>'
	. '<polygon points="' . $wpbook_cx . ',' . ( $wpbook_mid - 2.2 ) . ' ' . ( $wpbook_cx + 2.2 ) . ',' . $wpbook_mid . ' ' . $wpbook_cx . ',' . ( $wpbook_mid + 2.2 ) . ' ' . ( $wpbook_cx - 2.2 ) . ',' . $wpbook_mid . '" fill="' . $wpbook_a . '"/>'
	. '<polygon points="' . $wpbook_cx . ',' . ( $wpbook_h * 0.79 - 1.6 ) . ' ' . ( $wpbook_cx + 1.6 ) . ',' . ( $wpbook_h * 0.79 ) . ' ' . $wpbook_cx . ',' . ( $wpbook_h * 0.79 + 1.6 ) . ' ' . ( $wpbook_cx - 1.6 ) . ',' . ( $wpbook_h * 0.79 ) . '" fill="' . $wpbook_a . '"/>'
	. '</svg>';
?>
<div class="wpbc-cn"></div>
<div class="wpbc-cn-frame"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_svg ) ); ?>" style="width: <?php echo esc_attr( $wpbook_w ); ?>mm; height: <?php echo esc_attr( $wpbook_h ); ?>mm;" /></div>
<?php if ( '' !== $badge ) : ?>
	<div class="wpbc-cn-badge"><?php echo esc_html( $badge ); ?></div>
<?php endif; ?>
<div class="wpbc-cn-text">
	<div class="wpbc-cn-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-cn-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-cn-author"><?php echo esc_html( $author ); ?></div>

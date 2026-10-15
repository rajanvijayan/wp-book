<?php
/**
 * Tech Guide cover.
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

$wpbook_t    = esc_attr( $cover['text_color'] );
$wpbook_a    = esc_attr( $cover['accent'] );
$wpbook_grid = '';
for ( $wpbook_x = 0; $wpbook_x <= $width; $wpbook_x += 8 ) {
	$wpbook_grid .= '<line x1="' . $wpbook_x . '" y1="0" x2="' . $wpbook_x . '" y2="' . $height . '" stroke="' . $wpbook_t . '" stroke-opacity="0.06" stroke-width="0.2"/>';
}
for ( $wpbook_y = 0; $wpbook_y <= $height; $wpbook_y += 8 ) {
	$wpbook_grid .= '<line x1="0" y1="' . $wpbook_y . '" x2="' . $width . '" y2="' . $wpbook_y . '" stroke="' . $wpbook_t . '" stroke-opacity="0.06" stroke-width="0.2"/>';
}
// Decorative "code" bars in the lower right.
$wpbook_bars = '';
$wpbook_lens = array( 30, 22, 36, 14, 26, 18 );
foreach ( $wpbook_lens as $wpbook_i => $wpbook_len ) {
	$wpbook_bars .= '<rect x="' . ( $width * 0.52 + ( $wpbook_i % 2 ) * 6 ) . '" y="' . ( $height * 0.7 + $wpbook_i * 3.6 ) . '" width="' . $wpbook_len . '" height="1.6" rx="0.8" fill="' . ( 1 === $wpbook_i % 3 ? $wpbook_a : $wpbook_t ) . '" fill-opacity="' . ( 1 === $wpbook_i % 3 ? '0.8' : '0.18' ) . '"/>';
}
$wpbook_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . 'mm" height="' . $height . 'mm" viewBox="0 0 ' . $width . ' ' . $height . '">' . $wpbook_grid . $wpbook_bars
	. '<rect x="' . ( $width * 0.1 ) . '" y="' . ( $height * 0.31 ) . '" width="4" height="1.4" fill="' . $wpbook_a . '"/></svg>';
?>
<div class="wpbc-tech"></div>
<div class="wpbc-tech-art"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_svg ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $height ); ?>mm;" /></div>
<?php if ( '' !== $badge ) : ?>
	<div class="wpbc-tech-badge"><?php echo esc_html( $badge ); ?></div>
<?php endif; ?>
<div class="wpbc-tech-text">
	<div class="wpbc-tech-prompt">$ open ./book</div>
	<div class="wpbc-tech-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-tech-subtitle">// <?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-tech-author">@<?php echo esc_html( $author ); ?></div>

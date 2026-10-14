<?php
/**
 * Aurora cover.
 *
 * @package WPBook
 * @var array  $cover    Cover settings.
 * @var string $title    Title.
 * @var string $subtitle Subtitle.
 * @var string $author   Author.
 * @var string $badge    Badge or series text.
 * @var float  $width    Page width (mm).
 * @var float  $height   Page height (mm).
 * @var string $logo     Local logo path or ''.
 */

defined( 'ABSPATH' ) || exit;

// Soft glowing circles drawn in SVG (mPDF renders SVG images; CSS blur is not supported).
$wpbook_svg = sprintf(
	'<svg xmlns="http://www.w3.org/2000/svg" width="%1$s" height="%2$s" viewBox="0 0 100 %3$s">'
	. '<defs><radialGradient id="g1"><stop offset="0" stop-color="#ffffff" stop-opacity="0.35"/><stop offset="1" stop-color="#ffffff" stop-opacity="0"/></radialGradient>'
	. '<radialGradient id="g2"><stop offset="0" stop-color="%4$s" stop-opacity="0.55"/><stop offset="1" stop-color="%4$s" stop-opacity="0"/></radialGradient></defs>'
	. '<circle cx="85" cy="18" r="38" fill="url(#g1)"/><circle cx="10" cy="%5$s" r="48" fill="url(#g2)"/>'
	. '<circle cx="70" cy="%6$s" r="1.2" fill="#ffffff" fill-opacity="0.7"/><circle cx="22" cy="20" r="0.8" fill="#ffffff" fill-opacity="0.6"/>'
	. '<circle cx="90" cy="%7$s" r="0.9" fill="#ffffff" fill-opacity="0.6"/></svg>',
	$width . 'mm',
	$height . 'mm',
	round( 100 * $height / $width, 2 ),
	$cover['accent'],
	round( 100 * $height / $width * 0.85, 2 ),
	round( 100 * $height / $width * 0.55, 2 ),
	round( 100 * $height / $width * 0.35, 2 )
);
?>
<div class="wpbc-aurora"></div>
<div class="wpbc-aurora-glow"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_svg ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $height ); ?>mm;" /></div>
<?php if ( '' !== $logo ) : ?>
	<div class="wpbc-aurora-logo"><img src="<?php echo esc_attr( $logo ); ?>" style="height: 14mm;" /></div>
<?php endif; ?>
<div class="wpbc-aurora-text">
	<?php if ( '' !== $badge ) : ?>
		<div class="wpbc-aurora-badge"><?php echo esc_html( $badge ); ?></div>
	<?php endif; ?>
	<div class="wpbc-aurora-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-aurora-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-aurora-author"><?php echo esc_html( $author ); ?></div>

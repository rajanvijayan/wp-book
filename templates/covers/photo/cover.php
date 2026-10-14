<?php
/**
 * Full-bleed Photo cover.
 *
 * @package WPBook
 * @var array  $cover    Cover settings.
 * @var string $title    Title.
 * @var string $subtitle Subtitle.
 * @var string $author   Author.
 * @var string $badge    Badge.
 * @var string $image    Local image path or ''.
 * @var string $logo     Local logo path or ''.
 * @var float  $width    Page width (mm).
 * @var float  $height   Page height (mm).
 */

defined( 'ABSPATH' ) || exit;

$wpbook_shade = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . 'mm" height="' . $height . 'mm" viewBox="0 0 100 100" preserveAspectRatio="none">'
	. '<defs><linearGradient id="s" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . esc_attr( $cover['primary'] ) . '" stop-opacity="0.35"/><stop offset="0.35" stop-color="' . esc_attr( $cover['primary'] ) . '" stop-opacity="0"/>'
	. '<stop offset="0.5" stop-color="' . esc_attr( $cover['primary'] ) . '" stop-opacity="0.05"/><stop offset="1" stop-color="' . esc_attr( $cover['primary'] ) . '" stop-opacity="0.9"/></linearGradient></defs>'
	. '<rect x="0" y="0" width="100" height="100" fill="url(#s)"/></svg>';
?>
<div class="wpbc-photo-bg"></div>
<?php if ( '' !== $image ) : ?>
	<div class="wpbc-photo-img"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::fit_image( $image, $width, $height ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $height ); ?>mm;" /></div>
<?php endif; ?>
<div class="wpbc-photo-shade"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_shade ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $height ); ?>mm;" /></div>
<?php if ( '' !== $badge ) : ?>
	<div class="wpbc-photo-badge"><?php echo esc_html( $badge ); ?></div>
<?php endif; ?>
<?php if ( '' !== $logo ) : ?>
	<div class="wpbc-photo-logo"><img src="<?php echo esc_attr( $logo ); ?>" style="height: 12mm;" /></div>
<?php endif; ?>
<div class="wpbc-photo-text">
	<div class="wpbc-photo-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-photo-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-photo-author"><?php echo esc_html( $author ); ?></div>

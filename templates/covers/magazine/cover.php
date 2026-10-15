<?php
/**
 * Magazine cover.
 *
 * @package WPBook
 * @var array  $cover    Cover settings.
 * @var string $title    Title.
 * @var string $subtitle Subtitle.
 * @var string $author   Author.
 * @var string $badge    Badge.
 * @var string $image    Local image path or ''.
 * @var float  $width    Page width (mm).
 * @var float  $height   Page height (mm).
 * @var string $site     Site name.
 */

defined( 'ABSPATH' ) || exit;

$wpbook_len   = max( 4, function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title ) );
$wpbook_size  = min( 92, $width * 0.88 / ( 0.4 * min( $wpbook_len, 14 ) ) * 2.83 );
$wpbook_badge = round( $width * 0.24, 2 );
$wpbook_shade = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . 'mm" height="' . $height . 'mm" viewBox="0 0 100 100" preserveAspectRatio="none">'
	. '<defs><linearGradient id="m" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#000000" stop-opacity="0.45"/><stop offset="0.3" stop-color="#000000" stop-opacity="0"/>'
	. '<stop offset="0.6" stop-color="#000000" stop-opacity="0"/><stop offset="1" stop-color="#000000" stop-opacity="0.65"/></linearGradient></defs>'
	. '<rect x="0" y="0" width="100" height="100" fill="url(#m)"/></svg>';
?>
<div class="wpbc-mag-bg"></div>
<?php if ( '' !== $image ) : ?>
	<div class="wpbc-mag-img"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::fit_image( $image, $width, $height ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $height ); ?>mm;" /></div>
<?php endif; ?>
<div class="wpbc-mag-shade"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_shade ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $height ); ?>mm;" /></div>
<div class="wpbc-mag-title" style="font-size: <?php echo esc_attr( round( $wpbook_size, 1 ) ); ?>pt;"><?php echo esc_html( $title ); ?></div>
<?php if ( '' !== $badge ) : ?>
	<?php
	$wpbook_by   = round( $height * 0.4, 2 );
	$wpbook_disc = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $wpbook_badge . 'mm" height="' . $wpbook_badge . 'mm" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="' . esc_attr( $cover['accent'] ) . '"/><circle cx="5" cy="5" r="4.3" fill="none" stroke="#ffffff" stroke-width="0.15" stroke-dasharray="0.4 0.3"/></svg>';
	?>
	<div style="position: absolute; right: 7%; top: <?php echo esc_attr( $wpbook_by ); ?>mm;"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::svg_uri( $wpbook_disc ) ); ?>" style="width: <?php echo esc_attr( $wpbook_badge ); ?>mm; height: <?php echo esc_attr( $wpbook_badge ); ?>mm;" /></div>
	<div class="wpbc-mag-badge" style="right: 7%; top: <?php echo esc_attr( $wpbook_by + $wpbook_badge * 0.36 ); ?>mm; width: <?php echo esc_attr( $wpbook_badge ); ?>mm; font-size: 10pt; line-height: 1.1;"><?php echo esc_html( $badge ); ?></div>
<?php endif; ?>
<div class="wpbc-mag-lines">
	<div class="wpbc-mag-kicker"><?php echo esc_html( $site ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-mag-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-mag-author"><?php echo esc_html( $author ); ?></div>

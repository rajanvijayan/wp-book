<?php
/**
 * Split cover.
 *
 * @package WPBook
 * @var string $title    Title.
 * @var string $subtitle Subtitle.
 * @var string $author   Author.
 * @var string $badge    Badge.
 * @var string $image    Local image path or ''.
 * @var float  $width    Page width (mm).
 * @var float  $height   Page height (mm).
 */

defined( 'ABSPATH' ) || exit;

$wpbook_top = round( $height * 0.56, 2 );
?>
<div class="wpbc-split-top" style="height: <?php echo esc_attr( $wpbook_top ); ?>mm;"></div>
<?php if ( '' !== $image ) : ?>
	<div class="wpbc-split-img"><img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::fit_image( $image, $width, $wpbook_top ) ); ?>" style="width: <?php echo esc_attr( $width ); ?>mm; height: <?php echo esc_attr( $wpbook_top ); ?>mm;" /></div>
<?php endif; ?>
<div class="wpbc-split-band" style="height: <?php echo esc_attr( $height - $wpbook_top ); ?>mm;"></div>
<div class="wpbc-split-stripe" style="top: <?php echo esc_attr( $wpbook_top - 1.1 ); ?>mm;"></div>
<div class="wpbc-split-text" style="top: <?php echo esc_attr( $wpbook_top + $height * 0.06 ); ?>mm;">
	<?php if ( '' !== $badge ) : ?>
		<div class="wpbc-split-badge"><?php echo esc_html( $badge ); ?></div>
	<?php endif; ?>
	<div class="wpbc-split-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-split-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-split-author"><?php echo esc_html( $author ); ?></div>

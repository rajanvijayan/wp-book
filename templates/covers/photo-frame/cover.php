<?php
/**
 * Photo Frame cover.
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

$wpbook_fw = round( $width * 0.7, 2 );
$wpbook_fh = round( $height * 0.44, 2 );
$wpbook_fx = round( ( $width - $wpbook_fw ) / 2 - 2.4, 2 );
$wpbook_fy = round( $height * 0.11, 2 );
?>
<div class="wpbc-pf"></div>
<div class="wpbc-pf-shadow" style="left: <?php echo esc_attr( $wpbook_fx + 1.6 ); ?>mm; top: <?php echo esc_attr( $wpbook_fy + 2 ); ?>mm; width: <?php echo esc_attr( $wpbook_fw + 4.8 ); ?>mm; height: <?php echo esc_attr( $wpbook_fh + 4.8 ); ?>mm; background: rgba(0,0,0,0.12);"></div>
<div class="wpbc-pf-frame" style="left: <?php echo esc_attr( $wpbook_fx ); ?>mm; top: <?php echo esc_attr( $wpbook_fy ); ?>mm; width: <?php echo esc_attr( $wpbook_fw ); ?>mm;">
	<?php if ( '' !== $image ) : ?>
		<img src="<?php echo esc_attr( \WPBook\Covers\Cover_Renderer::fit_image( $image, $wpbook_fw, $wpbook_fh, 1400 ) ); ?>" style="width: <?php echo esc_attr( $wpbook_fw ); ?>mm; height: <?php echo esc_attr( $wpbook_fh ); ?>mm;" />
	<?php else : ?>
		<div class="wpbc-pf-placeholder" style="width: <?php echo esc_attr( $wpbook_fw ); ?>mm; height: <?php echo esc_attr( $wpbook_fh ); ?>mm;"></div>
	<?php endif; ?>
</div>
<div class="wpbc-pf-text">
	<?php if ( '' !== $badge ) : ?>
		<div class="wpbc-pf-badge"><?php echo esc_html( $badge ); ?></div>
	<?php endif; ?>
	<div class="wpbc-pf-title"><?php echo esc_html( $title ); ?></div>
	<div class="wpbc-pf-rule"></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-pf-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-pf-author"><?php echo esc_html( $author ); ?></div>

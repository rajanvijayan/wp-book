<?php
/**
 * Minimal cover.
 *
 * @package WPBook
 * @var string $title    Title.
 * @var string $subtitle Subtitle.
 * @var string $author   Author.
 * @var string $badge    Badge.
 * @var string $logo     Local logo path or ''.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpbc-min"></div>
<?php if ( '' !== $badge ) : ?>
	<div class="wpbc-min-badge"><?php echo esc_html( $badge ); ?></div>
<?php endif; ?>
<?php if ( '' !== $logo ) : ?>
	<div class="wpbc-min-logo"><img src="<?php echo esc_attr( $logo ); ?>" style="height: 11mm;" /></div>
<?php endif; ?>
<div class="wpbc-min-rule"></div>
<div class="wpbc-min-text">
	<div class="wpbc-min-title"><?php echo esc_html( $title ); ?></div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-min-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-min-author"><?php echo esc_html( $author ); ?></div>

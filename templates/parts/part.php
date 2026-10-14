<?php
/**
 * Part divider.
 *
 * @package WPBook
 * @var array $part { title, subtitle }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpb-part">
	<p class="wpb-part-label"><?php esc_html_e( 'Part', 'wp-book' ); ?></p>
	<h1><?php echo esc_html( $part['title'] ); ?></h1>
	<?php if ( '' !== $part['subtitle'] ) : ?>
		<p class="wpb-part-subtitle"><?php echo esc_html( $part['subtitle'] ); ?></p>
	<?php endif; ?>
</div>

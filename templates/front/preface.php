<?php
/**
 * Preface page.
 *
 * @package WPBook
 * @var string $preface_html Filtered preface HTML.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpb-preface">
	<h1><?php esc_html_e( 'Preface', 'wp-book' ); ?></h1>
	<?php echo $preface_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized with wp_kses_post and Content_Filter. ?>
</div>

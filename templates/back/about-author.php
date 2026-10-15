<?php
/**
 * About the author page.
 *
 * @package WPBook
 * @var string $about_html Filtered HTML.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpb-about">
	<h1><?php esc_html_e( 'About the Author', 'wp-book' ); ?></h1>
	<?php echo $about_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized with wp_kses_post and Content_Filter. ?>
</div>

<?php
/**
 * Title page.
 *
 * @package WPBook
 * @var \WPBook\Books\Book $book
 * @var array              $config
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpb-title-page">
	<h1 class="wpb-tp-title"><?php echo esc_html( $book->cover_title() ); ?></h1>
	<?php if ( '' !== $config['cover']['subtitle'] ) : ?>
		<p class="wpb-tp-subtitle"><?php echo esc_html( $config['cover']['subtitle'] ); ?></p>
	<?php endif; ?>
	<p class="wpb-tp-author"><?php echo esc_html( $book->author_name() ); ?></p>
	<p class="wpb-tp-site"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
</div>

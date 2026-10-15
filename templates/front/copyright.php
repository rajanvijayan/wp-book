<?php
/**
 * Copyright page.
 *
 * @package WPBook
 * @var \WPBook\Books\Book $book
 * @var array              $config
 */

defined( 'ABSPATH' ) || exit;

$wpbook_text = $config['structure']['copyright_text'];
?>
<div class="wpb-copyright">
	<p><strong><?php echo esc_html( $book->cover_title() ); ?></strong></p>
	<?php if ( '' !== $wpbook_text ) : ?>
		<?php foreach ( preg_split( '/\R/', $wpbook_text ) as $wpbook_line ) : ?>
			<p><?php echo esc_html( $wpbook_line ); ?></p>
		<?php endforeach; ?>
	<?php else : ?>
		<?php /* translators: 1: year, 2: author name */ ?>
		<p><?php echo esc_html( sprintf( __( 'Copyright © %1$s %2$s. All rights reserved.', 'wp-book' ), wp_date( 'Y' ), $book->author_name() ) ); ?></p>
		<p><?php esc_html_e( 'No part of this book may be reproduced without written permission from the author.', 'wp-book' ); ?></p>
	<?php endif; ?>
	<p><?php echo esc_html( home_url( '/' ) ); ?></p>
</div>

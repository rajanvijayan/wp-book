<?php
/**
 * Dedication page.
 *
 * @package WPBook
 * @var array $config
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpb-dedication">
	<?php foreach ( preg_split( '/\R/', $config['structure']['dedication'] ) as $wpbook_line ) : ?>
		<p><?php echo esc_html( $wpbook_line ); ?></p>
	<?php endforeach; ?>
</div>

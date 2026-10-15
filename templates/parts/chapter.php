<?php
/**
 * Chapter opener and body.
 *
 * @package WPBook
 * @var \WP_Post $post      Post.
 * @var array    $chapter   Chapter settings { include: { featured_image, excerpt, author, date } }.
 * @var array    $config    Book settings.
 * @var int      $number    Chapter number.
 * @var string   $title     Chapter title.
 * @var string   $body_html Filtered content.
 */

defined( 'ABSPATH' ) || exit;

$wpbook_include = $chapter['include'];
$wpbook_meta    = array();
if ( $wpbook_include['author'] ) {
	$wpbook_meta[] = get_the_author_meta( 'display_name', $post->post_author );
}
if ( $wpbook_include['date'] ) {
	$wpbook_meta[] = get_the_date( '', $post );
}
$wpbook_image = '';
if ( $wpbook_include['featured_image'] && $config['interior']['images'] && has_post_thumbnail( $post ) ) {
	$wpbook_src   = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'large' );
	$wpbook_image = $wpbook_src ? \WPBook\Render\Content_Filter::local_image_path( $wpbook_src[0] ) : '';
}
?>
<div class="wpb-chapter">
	<div class="wpb-chapter-opener">
		<?php if ( $config['structure']['chapter_numbers'] ) : ?>
			<p class="wpb-chapter-label">
				<?php echo esc_html( ( '' !== $config['structure']['chapter_label'] ? $config['structure']['chapter_label'] : __( 'Chapter', 'wp-book' ) ) . ' ' . $number ); ?>
			</p>
		<?php endif; ?>
		<h1 class="wpb-chapter-title"><?php echo esc_html( wp_strip_all_tags( $title ) ); ?></h1>
		<?php if ( $wpbook_meta ) : ?>
			<p class="wpb-chapter-meta"><?php echo esc_html( implode( ' · ', $wpbook_meta ) ); ?></p>
		<?php endif; ?>
		<?php if ( $wpbook_include['excerpt'] && has_excerpt( $post ) ) : ?>
			<p class="wpb-chapter-excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $post ) ) ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $wpbook_image ) : ?>
			<div class="wpb-featured"><img src="<?php echo esc_attr( $wpbook_image ); ?>" alt="" /></div>
		<?php endif; ?>
	</div>
	<div class="wpb-chapter-body">
		<?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered by Content_Filter (scripts, embeds and remote images removed). ?>
	</div>
</div>

<?php
/**
 * Bold Type cover: one word per line, sized to the longest word.
 *
 * @package WPBook
 * @var string $title    Title.
 * @var string $subtitle Subtitle.
 * @var string $author   Author.
 * @var string $badge    Badge.
 * @var float  $width    Page width (mm).
 */

defined( 'ABSPATH' ) || exit;

$wpbook_words   = preg_split( '/\s+/u', trim( $title ) );
$wpbook_longest = 1;
foreach ( $wpbook_words as $wpbook_word ) {
	$wpbook_longest = max( $wpbook_longest, function_exists( 'mb_strlen' ) ? mb_strlen( $wpbook_word ) : strlen( $wpbook_word ) );
}
// Bebas Neue glyphs are about 0.4em wide; fill 84% of the page width, capped for very short words and long titles.
$wpbook_size = min( $width * 0.84 / ( 0.4 * max( 4, $wpbook_longest ) ) * 2.83, 120, $width * 4.4 / max( 1, count( $wpbook_words ) ) );
?>
<div class="wpbc-bold"></div>
<?php if ( '' !== $badge ) : ?>
	<div class="wpbc-bold-badge"><?php echo esc_html( $badge ); ?></div>
<?php endif; ?>
<div class="wpbc-bold-text">
	<div class="wpbc-bold-title" style="font-size: <?php echo esc_attr( round( $wpbook_size, 1 ) ); ?>pt;">
		<?php echo implode( '<br />', array_map( 'esc_html', $wpbook_words ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each word escaped. ?>
	</div>
	<?php if ( '' !== $subtitle ) : ?>
		<div class="wpbc-bold-subtitle"><?php echo esc_html( $subtitle ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-bold-bar"></div>
<div class="wpbc-bold-author"><?php echo esc_html( $author ); ?></div>

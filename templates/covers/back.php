<?php
/**
 * Back cover.
 *
 * @package WPBook
 * @var string $title  Title.
 * @var string $author Author.
 * @var string $blurb  Blurb text.
 * @var string $bio    Author bio text.
 * @var string $avatar Local avatar path or ''.
 * @var string $qr_url URL for the QR code or ''.
 * @var string $site   Site name.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpbc-back"></div>
<div class="wpbc-back-blurb">
	<?php foreach ( array_filter( preg_split( '/\R{2,}/', $blurb ) ) as $wpbook_para ) : ?>
		<p style="text-indent: 0; text-align: left;"><?php echo nl2br( esc_html( $wpbook_para ) ); ?></p>
	<?php endforeach; ?>
	<?php if ( '' !== trim( $bio ) ) : ?>
		<div class="wpbc-back-rule"></div>
		<?php if ( '' !== $avatar ) : ?>
			<div class="wpbc-back-avatar"><img src="<?php echo esc_attr( $avatar ); ?>" style="width: 18mm; height: 18mm; border-radius: 9mm;" /></div>
		<?php endif; ?>
		<div class="wpbc-back-bio"><?php echo nl2br( esc_html( $bio ) ); ?></div>
	<?php endif; ?>
</div>
<div class="wpbc-back-footer">
	<table width="100%"><tr>
		<td style="vertical-align: bottom; border: 0;">
			<div class="wpbc-back-title"><?php echo esc_html( $title ); ?></div>
			<div><?php echo esc_html( $author ); ?> · <?php echo esc_html( $site ); ?></div>
		</td>
		<?php if ( '' !== $qr_url ) : ?>
			<td style="text-align: right; vertical-align: bottom; border: 0;">
				<barcode code="<?php echo esc_attr( $qr_url ); ?>" type="QR" size="0.7" error="M" disableborder="1" />
			</td>
		<?php endif; ?>
	</tr></table>
</div>

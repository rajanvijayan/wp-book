<?php
/**
 * Suggests cover settings from the site's branding and the book's content.
 *
 * @package WPBook
 */

namespace WPBook\Covers;

use WPBook\Books\Book;

/**
 * Builds a one-click cover: theme palette colours, the site logo and the first featured image.
 */
final class Auto_Cover {

	/**
	 * Suggested settings patch, in the same shape as the book config.
	 *
	 * @param Book $book Book.
	 * @return array{cover: array, reason: string[]}
	 */
	public static function suggest( Book $book ) {
		$reasons  = array();
		$palette  = self::theme_palette();
		$featured = self::first_featured_image( $book );
		$logo     = (bool) ( get_theme_mod( 'custom_logo' ) || get_option( 'site_icon' ) );

		$primary = isset( $palette[0] ) ? $palette[0] : '#1f2a44';
		$accent  = isset( $palette[1] ) ? $palette[1] : '#f2b84b';
		if ( $palette ) {
			$reasons[] = __( 'Colours come from your theme palette.', 'wp-book' );
		}

		$template = $featured ? 'photo' : 'aurora';
		if ( $featured ) {
			$reasons[] = __( 'The photo is the first featured image in the book.', 'wp-book' );
		}
		if ( $logo ) {
			$reasons[] = __( 'Your site logo is shown on the cover.', 'wp-book' );
		}

		return array(
			'cover'  => array(
				'enabled'      => true,
				'template'     => $template,
				'title'        => '',
				'subtitle'     => get_bloginfo( 'description' ),
				'primary'      => $primary,
				'accent'       => $accent,
				'text_color'   => $featured ? '#ffffff' : self::readable_text( $primary ),
				'use_featured' => (bool) $featured,
				'image_id'     => 0,
				'logo'         => $logo,
			),
			'reason' => $reasons,
		);
	}

	/**
	 * Distinct, non-grey colours from the theme's palette (theme.json), darkest first.
	 *
	 * @return string[]
	 */
	public static function theme_palette() {
		$colors  = array();
		$palette = function_exists( 'wp_get_global_settings' ) ? wp_get_global_settings( array( 'color', 'palette' ) ) : array();
		foreach ( array( 'theme', 'custom' ) as $origin ) {
			if ( empty( $palette[ $origin ] ) || ! is_array( $palette[ $origin ] ) ) {
				continue;
			}
			foreach ( $palette[ $origin ] as $entry ) {
				$hex = isset( $entry['color'] ) ? sanitize_hex_color( $entry['color'] ) : '';
				if ( $hex && 7 === strlen( $hex ) && self::saturation( $hex ) > 0.18 ) {
					$colors[] = strtolower( $hex );
				}
			}
		}
		$colors = array_values( array_unique( $colors ) );
		usort(
			$colors,
			static function ( $a, $b ) {
				return self::luminance( $a ) <=> self::luminance( $b );
			}
		);
		return $colors;
	}

	/**
	 * White or near-black, whichever reads better on the background.
	 *
	 * @param string $background Hex colour.
	 * @return string
	 */
	public static function readable_text( $background ) {
		return self::luminance( $background ) > 0.45 ? '#1d1d1f' : '#ffffff';
	}

	/**
	 * Featured image of the first chapter that has one.
	 *
	 * @param Book $book Book.
	 * @return int Attachment ID or 0.
	 */
	private static function first_featured_image( Book $book ) {
		foreach ( $book->post_ids() as $id ) {
			if ( current_user_can( 'read_post', $id ) && has_post_thumbnail( $id ) ) {
				return (int) get_post_thumbnail_id( $id );
			}
		}
		return 0;
	}

	/**
	 * Relative luminance (0–1).
	 *
	 * @param string $hex Colour.
	 * @return float
	 */
	private static function luminance( $hex ) {
		list( $r, $g, $b ) = array_map(
			static function ( $c ) {
				$c = hexdec( $c ) / 255;
				return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
			},
			str_split( ltrim( $hex, '#' ), 2 )
		);
		return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
	}

	/**
	 * HSL saturation (0–1), used to skip greys.
	 *
	 * @param string $hex Colour.
	 * @return float
	 */
	private static function saturation( $hex ) {
		list( $r, $g, $b ) = array_map(
			static function ( $c ) {
				return hexdec( $c ) / 255;
			},
			str_split( ltrim( $hex, '#' ), 2 )
		);
		$max               = max( $r, $g, $b );
		$min               = min( $r, $g, $b );
		$l                 = ( $max + $min ) / 2;
		if ( $max === $min ) {
			return 0.0;
		}
		return $l > 0.5 ? ( $max - $min ) / ( 2 - $max - $min ) : ( $max - $min ) / ( $max + $min );
	}
}

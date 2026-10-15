<?php
/**
 * Renders front and back covers.
 *
 * @package WPBook
 */

namespace WPBook\Covers;

use WPBook\Books\Book;
use WPBook\Books\Page_Sizes;
use WPBook\Render\Content_Filter;
use WPBook\Render\Font_Manager;
use WPBook\Render\Template_Loader;

/**
 * Builds the variables every cover template receives and renders the chosen design.
 *
 * Templates get: $book, $cover (settings), $title, $subtitle, $author, $badge, $width/$height (mm),
 * $image (local path or ''), $logo (local path or ''), $title_font/$body_font (CSS families), $site.
 */
final class Cover_Renderer {

	/**
	 * Front cover HTML and CSS.
	 *
	 * @param Book $book Book.
	 * @return array{html: string, css: string}
	 */
	public static function front( Book $book ) {
		$vars = self::vars( $book );
		$key  = $vars['cover']['template'];
		if ( '' === Template_Loader::locate( 'covers/' . $key . '/cover.php' ) ) {
			$key = 'aurora';
		}
		return array(
			'html' => Template_Loader::render( 'covers/' . $key . '/cover.php', $vars ),
			'css'  => Template_Loader::contents( 'covers/' . $key . '/cover.css', self::css_vars( $vars ) ),
		);
	}

	/**
	 * Back cover HTML and CSS.
	 *
	 * @param Book $book Book.
	 * @return array{html: string, css: string}
	 */
	public static function back( Book $book ) {
		$vars = self::vars( $book );
		$back = $book->config['back_cover'];

		$avatar = '';
		$user   = $back['author_user'] ? $back['author_user'] : $book->author;
		if ( $back['show_avatar'] && $user ) {
			$avatar = self::avatar_path( $user );
		}
		$qr_url = '';
		if ( $back['qr'] ) {
			$qr_url = '' !== $back['qr_url'] ? $back['qr_url'] : home_url( '/' );
		}
		$vars += array(
			'back'   => $back,
			'blurb'  => $back['blurb'],
			'bio'    => $back['bio'],
			'avatar' => $avatar,
			'qr_url' => $qr_url,
		);
		return array(
			'html' => Template_Loader::render( 'covers/back.php', $vars ),
			'css'  => Template_Loader::contents( 'covers/back.css', self::css_vars( $vars ) ),
		);
	}

	/**
	 * Variables shared by every cover template.
	 *
	 * @param Book $book Book.
	 * @return array
	 */
	public static function vars( Book $book ) {
		$cover                  = $book->config['cover'];
		$layout                 = $book->config['layout'];
		list( $width, $height ) = Page_Sizes::for_layout( $layout );

		return array(
			'book'       => $book,
			'cover'      => $cover,
			'title'      => $book->cover_title(),
			'subtitle'   => $cover['subtitle'],
			'author'     => $book->author_name(),
			'badge'      => $cover['badge'],
			'width'      => $width,
			'height'     => $height,
			'image'      => self::image_path( $book ),
			'logo'       => $cover['logo'] ? self::logo_path() : '',
			'title_font' => Font_Manager::css_family( $cover['title_font'] ),
			'body_font'  => Font_Manager::css_family( $cover['body_font'] ),
			'site'       => get_bloginfo( 'name' ),
			'title_size' => self::title_size( $book->cover_title(), $width ),
		);
	}

	/**
	 * Placeholders available in cover.css.
	 *
	 * @param array $vars Template variables.
	 * @return array
	 */
	private static function css_vars( array $vars ) {
		$cover = $vars['cover'];
		return array(
			'primary'    => $cover['primary'],
			'accent'     => $cover['accent'],
			'text'       => $cover['text_color'],
			'title_font' => $vars['title_font'],
			'body_font'  => $vars['body_font'],
			'width'      => $vars['width'] . 'mm',
			'height'     => $vars['height'] . 'mm',
			'align'      => $cover['align'],
			// Title size scales with the page width and the title length so every size looks balanced.
			'title_size' => self::title_size( $vars['title'], $vars['width'] ) . 'pt',
		);
	}

	/**
	 * Local path of the cover image: the chosen attachment, or the first chapter's featured image.
	 *
	 * @param Book $book Book.
	 * @return string
	 */
	public static function image_path( Book $book ) {
		$cover = $book->config['cover'];
		$id    = $cover['image_id'];
		if ( ! $id && $cover['use_featured'] ) {
			foreach ( $book->post_ids() as $post_id ) {
				if ( current_user_can( 'read_post', $post_id ) && has_post_thumbnail( $post_id ) ) {
					$id = get_post_thumbnail_id( $post_id );
					break;
				}
			}
		}
		if ( ! $id || ! wp_attachment_is_image( $id ) ) {
			return '';
		}
		$src = wp_get_attachment_image_src( $id, 'full' );
		return $src ? Content_Filter::local_image_path( $src[0] ) : '';
	}

	/**
	 * Local path of the site logo (Customizer logo, else site icon).
	 *
	 * @return string
	 */
	public static function logo_path() {
		$id = (int) get_theme_mod( 'custom_logo' );
		if ( ! $id ) {
			$id = (int) get_option( 'site_icon' );
		}
		if ( ! $id ) {
			return '';
		}
		$src = wp_get_attachment_image_src( $id, 'full' );
		return $src ? Content_Filter::local_image_path( $src[0] ) : '';
	}

	/**
	 * Local path of a user's uploaded avatar, if a plugin stores one in uploads. Gravatar is remote, so it is skipped.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function avatar_path( $user_id ) {
		$url = get_avatar_url( $user_id, array( 'size' => 256 ) );
		return $url ? Content_Filter::local_image_path( $url ) : '';
	}

	/**
	 * Centre-crop an image to an aspect ratio (mPDF has no object-fit: cover). Results are cached.
	 *
	 * @param string $path   Local image path.
	 * @param float  $width  Box width (any unit).
	 * @param float  $height Box height (same unit).
	 * @param int    $max_px Longest edge of the result in pixels.
	 * @return string Path of the cropped image, or the original on failure.
	 */
	public static function fit_image( $path, $width, $height, $max_px = 2000 ) {
		if ( '' === $path || 0 === strpos( $path, 'data:' ) || ! is_readable( $path ) || $width <= 0 || $height <= 0 ) {
			return $path;
		}
		$dir    = \WPBook\Render\Mpdf_Engine::temp_dir() . '/covers';
		$target = $dir . '/' . md5( $path . filemtime( $path ) . round( $width / $height, 4 ) . $max_px ) . '.jpg';
		if ( file_exists( $target ) ) {
			return $target;
		}
		$editor = wp_get_image_editor( $path );
		if ( is_wp_error( $editor ) ) {
			return $path;
		}
		$size  = $editor->get_size();
		$ratio = $width / $height;
		$src_w = $size['width'];
		$src_h = $size['height'];
		if ( $src_w / $src_h > $ratio ) {
			$crop_w = (int) round( $src_h * $ratio );
			$crop_h = $src_h;
		} else {
			$crop_w = $src_w;
			$crop_h = (int) round( $src_w / $ratio );
		}
		$scale = min( 1, $max_px / max( $crop_w, $crop_h ) );
		$editor->crop( (int) ( ( $src_w - $crop_w ) / 2 ), (int) ( ( $src_h - $crop_h ) / 2 ), $crop_w, $crop_h, (int) round( $crop_w * $scale ), (int) round( $crop_h * $scale ) );
		$editor->set_quality( 86 );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$saved = $editor->save( $target, 'image/jpeg' );
		return is_wp_error( $saved ) ? $path : $saved['path'];
	}

	/**
	 * Title font size in points, scaled to the page width and shortened for long titles.
	 *
	 * @param string $title Title.
	 * @param float  $width Page width in mm.
	 * @param float  $scale Design-specific multiplier.
	 * @return float
	 */
	public static function title_size( $title, $width, $scale = 1.0 ) {
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );
		$factor = $length <= 12 ? 1 : ( $length <= 22 ? 0.84 : ( $length <= 36 ? 0.7 : 0.56 ) );
		return round( $width / 5.2 * $factor * $scale, 1 );
	}

	/**
	 * Inline SVG as a data URI for <img>, which mPDF renders reliably.
	 *
	 * @param string $svg SVG markup.
	 * @return string
	 */
	public static function svg_uri( $svg ) {
		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- data URI for an inline SVG image.
	}
}

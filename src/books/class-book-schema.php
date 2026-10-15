<?php
/**
 * Versioned book settings: defaults, sanitizing and migrations.
 *
 * @package WPBook
 */

namespace WPBook\Books;

/**
 * Describes every book setting. A field spec is [type, default, extra]:
 *
 * - bool, int (extra: [min, max]), float (extra: [min, max])
 * - enum (extra: allowed values, or a callable returning them)
 * - color (#rrggbb), text (one line), textarea (plain multi-line), html (limited HTML), url, id (attachment/user ID)
 */
final class Book_Schema {

	/**
	 * Current settings version. Bump it and add a step to migrate() when the shape changes.
	 */
	const VERSION = 1;

	/**
	 * Field specs per section (chapters are handled separately).
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'structure'  => array(
				'title_page'        => array( 'bool', true ),
				'copyright'         => array( 'bool', true ),
				'copyright_text'    => array( 'textarea', '' ),
				'dedication'        => array( 'textarea', '' ),
				'preface'           => array( 'html', '' ),
				'toc'               => array( 'bool', true ),
				'toc_title'         => array( 'text', '' ),
				'toc_depth'         => array( 'int', 2, array( 1, 3 ) ),
				'chapter_numbers'   => array( 'bool', true ),
				'chapter_label'     => array( 'text', '' ),
				'about_author'      => array( 'bool', false ),
				'about_author_text' => array( 'html', '' ),
			),
			'cover'      => array(
				'enabled'      => array( 'bool', true ),
				'template'     => array( 'enum', 'aurora', array( self::class, 'cover_keys' ) ),
				'title'        => array( 'text', '' ),
				'subtitle'     => array( 'text', '' ),
				'author'       => array( 'text', '' ),
				'badge'        => array( 'text', '' ),
				'primary'      => array( 'color', '#1f2a44' ),
				'accent'       => array( 'color', '#f2b84b' ),
				'text_color'   => array( 'color', '#ffffff' ),
				'title_font'   => array( 'enum', 'playfair', array( self::class, 'font_keys' ) ),
				'body_font'    => array( 'enum', 'inter', array( self::class, 'font_keys' ) ),
				'image_id'     => array( 'id', 0 ),
				'use_featured' => array( 'bool', false ),
				'logo'         => array( 'bool', false ),
				'align'        => array( 'enum', 'center', array( 'left', 'center', 'right' ) ),
			),
			'back_cover' => array(
				'enabled'     => array( 'bool', false ),
				'blurb'       => array( 'textarea', '' ),
				'bio'         => array( 'textarea', '' ),
				'author_user' => array( 'id', 0 ),
				'show_avatar' => array( 'bool', true ),
				'qr'          => array( 'bool', true ),
				'qr_url'      => array( 'url', '' ),
			),
			'interior'   => array(
				'template'    => array( 'enum', 'classic', array( self::class, 'template_keys' ) ),
				'font'        => array( 'enum', '', array( self::class, 'font_keys_or_default' ) ),
				'font_size'   => array( 'float', 11, array( 8, 18 ) ),
				'line_height' => array( 'float', 1.5, array( 1, 2.5 ) ),
				'justify'     => array( 'bool', true ),
				'indent'      => array( 'bool', false ),
				'drop_caps'   => array( 'bool', false ),
				'images'      => array( 'bool', true ),
			),
			'layout'     => array(
				'format'         => array( 'enum', 'A5', array( self::class, 'format_keys' ) ),
				'orientation'    => array( 'enum', 'P', array( 'P', 'L' ) ),
				'margin_top'     => array( 'float', 18, array( 5, 60 ) ),
				'margin_bottom'  => array( 'float', 20, array( 5, 60 ) ),
				'margin_inner'   => array( 'float', 18, array( 5, 60 ) ),
				'margin_outer'   => array( 'float', 15, array( 5, 60 ) ),
				'mirror'         => array( 'bool', false ),
				'chapter_recto'  => array( 'bool', false ),
				'columns'        => array( 'int', 1, array( 1, 3 ) ),
				'running_header' => array( 'bool', true ),
				'page_numbers'   => array( 'enum', 'bottom-center', array( 'none', 'bottom-center', 'bottom-outer', 'top-outer' ) ),
				'header_text'    => array( 'text', '' ),
				'footer_text'    => array( 'text', '' ),
			),
			'output'     => array(
				'filename'      => array( 'text', '' ),
				'subject'       => array( 'text', '' ),
				'keywords'      => array( 'text', '' ),
				'bookmarks'     => array( 'bool', true ),
				'watermark'     => array( 'text', '' ),
				'password'      => array( 'text', '' ),
				'image_quality' => array( 'int', 85, array( 30, 100 ) ),
				'keep'          => array( 'int', 5, array( 1, 50 ) ),
				'print_cover'   => array( 'bool', false ),
				'paper'         => array( 'enum', 'white', array( 'white', 'cream' ) ),
			),
		);
	}

	/**
	 * Default settings for a new book.
	 *
	 * @return array
	 */
	public static function defaults() {
		$config = array(
			'schema_version' => self::VERSION,
			'content'        => array( 'chapters' => array() ),
		);
		foreach ( self::fields() as $section => $fields ) {
			foreach ( $fields as $key => $spec ) {
				$config[ $section ][ $key ] = $spec[1];
			}
		}
		return $config;
	}

	/**
	 * Bring stored settings up to the current version, then sanitize them.
	 *
	 * @param mixed $config Stored or submitted settings.
	 * @return array
	 */
	public static function normalize( $config ) {
		return self::sanitize( self::migrate( is_array( $config ) ? $config : array() ) );
	}

	/**
	 * Upgrade older settings. Version 1 is the first, so nothing to do yet.
	 *
	 * @param array $config Settings.
	 * @return array
	 */
	public static function migrate( array $config ) {
		$config['schema_version'] = self::VERSION;
		return $config;
	}

	/**
	 * Merge a partial update into existing settings (sections and fields are replaced, chapters as a whole).
	 *
	 * @param array $current Current normalized settings.
	 * @param array $patch   Partial settings from the client.
	 * @return array Normalized result.
	 */
	public static function merge( array $current, array $patch ) {
		foreach ( $patch as $section => $values ) {
			if ( ! isset( $current[ $section ] ) || ! is_array( $values ) ) {
				continue;
			}
			$current[ $section ] = array_merge( $current[ $section ], $values );
		}
		return self::normalize( $current );
	}

	/**
	 * Sanitize settings against the field specs. Unknown keys are dropped, invalid values fall back to defaults.
	 *
	 * @param array $config Settings.
	 * @return array
	 */
	public static function sanitize( array $config ) {
		$out = array(
			'schema_version' => self::VERSION,
			'content'        => array(
				'chapters' => self::sanitize_chapters( isset( $config['content']['chapters'] ) ? $config['content']['chapters'] : array() ),
			),
		);
		foreach ( self::fields() as $section => $fields ) {
			$values = isset( $config[ $section ] ) && is_array( $config[ $section ] ) ? $config[ $section ] : array();
			foreach ( $fields as $key => $spec ) {
				$out[ $section ][ $key ] = array_key_exists( $key, $values ) ? self::sanitize_value( $values[ $key ], $spec ) : $spec[1];
			}
		}
		return $out;
	}

	/**
	 * Sanitize the chapter list: posts and part dividers.
	 *
	 * @param mixed $chapters Chapters.
	 * @return array
	 */
	public static function sanitize_chapters( $chapters ) {
		$out = array();
		if ( ! is_array( $chapters ) ) {
			return $out;
		}
		foreach ( array_slice( $chapters, 0, 2000 ) as $chapter ) {
			if ( ! is_array( $chapter ) ) {
				continue;
			}
			$type = isset( $chapter['type'] ) ? $chapter['type'] : 'post';
			if ( 'part' === $type ) {
				$out[] = array(
					'type'     => 'part',
					'title'    => sanitize_text_field( isset( $chapter['title'] ) ? $chapter['title'] : '' ),
					'subtitle' => sanitize_text_field( isset( $chapter['subtitle'] ) ? $chapter['subtitle'] : '' ),
				);
				continue;
			}
			$id = isset( $chapter['id'] ) ? absint( $chapter['id'] ) : 0;
			if ( ! $id ) {
				continue;
			}
			$include = isset( $chapter['include'] ) && is_array( $chapter['include'] ) ? $chapter['include'] : array();
			$out[]   = array(
				'type'    => 'post',
				'id'      => $id,
				'title'   => sanitize_text_field( isset( $chapter['title'] ) ? $chapter['title'] : '' ),
				'include' => array(
					'featured_image' => self::to_bool( isset( $include['featured_image'] ) ? $include['featured_image'] : true ),
					'excerpt'        => self::to_bool( isset( $include['excerpt'] ) ? $include['excerpt'] : false ),
					'author'         => self::to_bool( isset( $include['author'] ) ? $include['author'] : false ),
					'date'           => self::to_bool( isset( $include['date'] ) ? $include['date'] : false ),
				),
			);
		}
		return $out;
	}

	/**
	 * Sanitize one value by its spec.
	 *
	 * @param mixed $value Value.
	 * @param array $spec  [type, default, extra].
	 * @return mixed
	 */
	private static function sanitize_value( $value, array $spec ) {
		list( $type, $default ) = $spec;
		$extra                  = isset( $spec[2] ) ? $spec[2] : null;
		switch ( $type ) {
			case 'bool':
				return self::to_bool( $value );
			case 'int':
				return is_numeric( $value ) ? (int) min( max( (int) $value, $extra[0] ), $extra[1] ) : $default;
			case 'float':
				return is_numeric( $value ) ? round( (float) min( max( (float) $value, $extra[0] ), $extra[1] ), 2 ) : $default;
			case 'enum':
				$allowed = is_callable( $extra ) ? call_user_func( $extra ) : (array) $extra;
				return in_array( $value, $allowed, true ) ? $value : $default;
			case 'color':
				$color = sanitize_hex_color( is_string( $value ) ? $value : '' );
				return $color ? $color : $default;
			case 'text':
				return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $default;
			case 'textarea':
				return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : $default;
			case 'html':
				return is_scalar( $value ) ? wp_kses_post( (string) $value ) : $default;
			case 'url':
				return is_string( $value ) ? esc_url_raw( $value, array( 'http', 'https' ) ) : $default;
			case 'id':
				return absint( $value );
		}
		return $default;
	}

	/**
	 * Loose boolean conversion for values that arrive as strings from forms.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function to_bool( $value ) {
		return in_array( $value, array( true, 1, '1', 'true', 'on', 'yes' ), true );
	}

	/**
	 * Allowed cover template keys.
	 *
	 * @return string[]
	 */
	public static function cover_keys() {
		return array_keys( \WPBook\Covers\Cover_Registry::all() );
	}

	/**
	 * Allowed interior template keys.
	 *
	 * @return string[]
	 */
	public static function template_keys() {
		return array_keys( \WPBook\Templates\Template_Registry::all() );
	}

	/**
	 * Allowed font keys.
	 *
	 * @return string[]
	 */
	public static function font_keys() {
		return array_keys( \WPBook\Render\Font_Manager::all() );
	}

	/**
	 * Allowed font keys plus '' (use the template's font).
	 *
	 * @return string[]
	 */
	public static function font_keys_or_default() {
		return array_merge( array( '' ), self::font_keys() );
	}

	/**
	 * Allowed paper size keys.
	 *
	 * @return string[]
	 */
	public static function format_keys() {
		return array_keys( Page_Sizes::all() );
	}
}

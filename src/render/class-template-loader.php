<?php
/**
 * Locates and renders template files, letting themes override them.
 *
 * @package WPBook
 */

namespace WPBook\Render;

/**
 * Looks in {child theme}/bookpress/, then {theme}/bookpress/, then the plugin's templates/ folder.
 */
final class Template_Loader {

	/**
	 * Absolute path of a template file, or '' if none exists.
	 *
	 * @param string $relative Path below templates/, e.g. covers/aurora/cover.php.
	 * @return string
	 */
	public static function locate( $relative ) {
		$relative = ltrim( str_replace( array( '..', '\\' ), '', $relative ), '/' );
		$theme    = locate_template( 'bookpress/' . $relative );
		if ( $theme ) {
			return $theme;
		}
		$plugin = WPBOOK_DIR . 'templates/' . $relative;
		return file_exists( $plugin ) ? $plugin : '';
	}

	/**
	 * Render a PHP template with variables and return its output.
	 *
	 * @param string $relative Path below templates/.
	 * @param array  $vars     Variables extracted into the template scope.
	 * @return string
	 */
	public static function render( $relative, array $vars = array() ) {
		$file = self::locate( $relative );
		if ( '' === $file ) {
			return '';
		}
		ob_start();
		( static function ( $wpbook_file, $wpbook_vars ) {
			extract( $wpbook_vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- template variables.
			include $wpbook_file;
		} )( $file, $vars );
		return (string) ob_get_clean();
	}

	/**
	 * Read a static template file (CSS) and replace {{placeholders}}.
	 *
	 * @param string $relative Path below templates/.
	 * @param array  $replace  Placeholder => value.
	 * @return string
	 */
	public static function contents( $relative, array $replace = array() ) {
		$file = self::locate( $relative );
		if ( '' === $file ) {
			return '';
		}
		$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local template file.
		foreach ( $replace as $key => $value ) {
			$css = str_replace( '{{' . $key . '}}', (string) $value, $css );
		}
		return $css;
	}
}

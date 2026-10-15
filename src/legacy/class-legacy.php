<?php
/**
 * Compatibility with WP Book 1.x.
 *
 * @package WPBook
 */

namespace WPBook\Legacy;

use WPBook\Admin\Admin_App;
use WPBook\Capabilities;

/**
 * Redirects the old settings screen and turns 1.x custom templates into interior templates.
 */
final class Legacy {

	/**
	 * Old settings page slug (Settings → WP Book).
	 */
	const OLD_SLUG = 'wp-book';

	/**
	 * Hook everything.
	 */
	public static function register() {
		add_action( 'admin_menu', array( self::class, 'old_page' ), 99 );
		add_filter( 'wpbook_templates', array( self::class, 'legacy_templates' ), 5 );
	}

	/**
	 * Keep the old Settings → WP Book URL working by redirecting it to BookPress.
	 */
	public static function old_page() {
		$hook = add_submenu_page( '', __( 'BookPress', 'wp-book' ), '', Capabilities::MANAGE, self::OLD_SLUG, '__return_null' );
		if ( $hook ) {
			add_action(
				'load-' . $hook,
				static function () {
					wp_safe_redirect( Admin_App::url() );
					exit;
				}
			);
		}
	}

	/**
	 * Convert templates registered by 1.x code through wp_book_get_templates into interior templates.
	 *
	 * 1.x templates were arrays of CSS rules: [ 'name' => [ 'selector' => [ 'property' => 'value' ] ] ].
	 *
	 * @param array $templates Interior templates.
	 * @return array
	 */
	public static function legacy_templates( $templates ) {
		if ( ! has_filter( 'wp_book_get_templates' ) ) {
			return $templates;
		}
		// Public filter since 1.0; the name is kept for backward compatibility.
		$legacy = apply_filters( 'wp_book_get_templates', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		if ( ! is_array( $legacy ) ) {
			return $templates;
		}
		foreach ( $legacy as $name => $rules ) {
			if ( ! is_array( $rules ) ) {
				continue;
			}
			$css = '';
			foreach ( $rules as $selector => $declarations ) {
				if ( ! is_array( $declarations ) ) {
					continue;
				}
				$css .= wp_strip_all_tags( $selector ) . '{';
				foreach ( $declarations as $property => $value ) {
					$css .= preg_replace( '/[^a-z-]/', '', strtolower( $property ) ) . ':' . str_replace( array( '{', '}', ';' ), '', wp_strip_all_tags( (string) $value ) ) . ';';
				}
				$css .= '}';
			}
			$templates[ 'legacy-' . sanitize_title( $name ) ] = array(
				/* translators: %s: template name from WP Book 1.x */
				'name'         => sprintf( __( '%s (legacy)', 'wp-book' ), $name ),
				'description'  => __( 'Custom template registered for WP Book 1.x.', 'wp-book' ),
				'font'         => 'dejavu-serif',
				'heading_font' => 'dejavu-serif',
				'css'          => $css,
			);
		}
		return $templates;
	}
}

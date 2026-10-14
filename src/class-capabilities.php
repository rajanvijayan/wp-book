<?php
/**
 * Capability used to manage books.
 *
 * @package WPBook
 */

namespace WPBook;

/**
 * Grants the book capability to users who hold the base capability.
 */
final class Capabilities {

	/**
	 * Capability checked by every admin screen and REST route.
	 */
	const MANAGE = 'wpbook_manage_books';

	/**
	 * Hook the capability mapping.
	 */
	public static function register() {
		add_filter( 'user_has_cap', array( self::class, 'grant' ), 10, 1 );
	}

	/**
	 * The core capability that implies book management. Editors and admins by default.
	 *
	 * @return string
	 */
	public static function base_capability() {
		/**
		 * Filters the core capability that lets a user manage books.
		 *
		 * @param string $capability Default 'edit_others_posts'.
		 */
		return (string) apply_filters( 'wpbook_base_capability', 'edit_others_posts' );
	}

	/**
	 * Add the book capability to users who have the base capability.
	 *
	 * @param array $allcaps All capabilities of the user.
	 * @return array
	 */
	public static function grant( $allcaps ) {
		$base = self::base_capability();
		if ( ! empty( $allcaps[ $base ] ) ) {
			$allcaps[ self::MANAGE ] = true;
		}
		return $allcaps;
	}

	/**
	 * Whether the current user can manage books.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( self::MANAGE );
	}
}

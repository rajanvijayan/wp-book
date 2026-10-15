<?php
/**
 * The BookPress admin screen.
 *
 * @package WPBook
 */

namespace WPBook\Admin;

use WPBook\Capabilities;

/**
 * Adds the top-level menu and mounts the React app from build/.
 */
final class Admin_App {

	const SLUG = 'bookpress';

	/**
	 * Screen hook suffix.
	 *
	 * @var string
	 */
	private $hook = '';

	/**
	 * Register hooks.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WPBOOK_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Add the menu.
	 */
	public function menu() {
		$this->hook = (string) add_menu_page(
			__( 'BookPress', 'wp-book' ),
			__( 'BookPress', 'wp-book' ),
			Capabilities::MANAGE,
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-book-alt',
			26
		);
		add_submenu_page( self::SLUG, __( 'All Books', 'wp-book' ), __( 'All Books', 'wp-book' ), Capabilities::MANAGE, self::SLUG, array( $this, 'render' ) );
		add_submenu_page( self::SLUG, __( 'New Book', 'wp-book' ), __( 'New Book', 'wp-book' ), Capabilities::MANAGE, self::SLUG . '#/new', '__return_null' );
	}

	/**
	 * Link to the app from the Plugins screen.
	 *
	 * @param array $links Action links.
	 * @return array
	 */
	public function action_links( $links ) {
		if ( Capabilities::current_user_can_manage() ) {
			array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Create a book', 'wp-book' ) . '</a>' );
		}
		return $links;
	}

	/**
	 * URL of the app.
	 *
	 * @param string $route Hash route, e.g. 'new' or 'book/12'.
	 * @return string
	 */
	public static function url( $route = '' ) {
		return admin_url( 'admin.php?page=' . self::SLUG ) . ( '' !== $route ? '#/' . $route : '' );
	}

	/**
	 * Enqueue the app on its screen only.
	 *
	 * @param string $hook Current screen hook.
	 */
	public function enqueue( $hook ) {
		if ( $hook !== $this->hook ) {
			return;
		}
		$asset_file = WPBOOK_DIR . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		$asset = include $asset_file;
		wp_enqueue_script( 'wpbook-app', WPBOOK_URL . 'build/index.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'wpbook-app', 'wp-book' );
		wp_enqueue_style( 'wpbook-app', WPBOOK_URL . 'build/style-index.css', array( 'wp-components' ), $asset['version'] );
		wp_enqueue_media();

		wp_add_inline_script(
			'wpbook-app',
			'window.wpbookSettings = ' . wp_json_encode(
				array(
					'namespace' => 'wp-book/v2',
					'version'   => WPBOOK_VERSION,
					'pluginUrl' => WPBOOK_URL,
					'adminUrl'  => admin_url(),
					'restNonce' => wp_create_nonce( 'wp_rest' ),
					'user'      => wp_get_current_user()->display_name,
				)
			) . ';',
			'before'
		);
	}

	/**
	 * App container.
	 */
	public function render() {
		if ( ! file_exists( WPBOOK_DIR . 'build/index.asset.php' ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'BookPress', 'wp-book' ) . '</h1><div class="notice notice-error"><p>'
				. esc_html__( 'The BookPress app has not been built. Run "npm ci && npm run build" in the plugin folder.', 'wp-book' )
				. '</p></div></div>';
			return;
		}
		echo '<div id="wpbook-root" class="wpbook-root"></div>';
	}
}

<?php
/**
 * BookPress – PDF eBook Maker.
 *
 * @package WPBook
 *
 * @wordpress-plugin
 * Plugin Name:       BookPress – PDF eBook Maker
 * Plugin URI:        https://wordpress.org/plugins/wp-book/
 * Description:       Turn your posts and pages into beautiful PDF eBooks with cover designs, interior templates and a table of contents, then download them from your dashboard.
 * Version:           2.0.0-dev
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Rajan V
 * Author URI:        https://wpsmartcare.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-book
 * Domain Path:       /languages
 */

namespace WPBook;

defined( 'ABSPATH' ) || exit;

define( 'WPBOOK_VERSION', '2.0.0-dev' );
define( 'WPBOOK_FILE', __FILE__ );
define( 'WPBOOK_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPBOOK_URL', plugin_dir_url( __FILE__ ) );

if ( ! file_exists( WPBOOK_DIR . 'vendor/autoload.php' ) || ! file_exists( WPBOOK_DIR . 'vendor-prefixed/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'BookPress is missing its libraries. Please reinstall the plugin from WordPress.org.', 'wp-book' ) . '</p></div>';
		}
	);
	return;
}

require_once WPBOOK_DIR . 'vendor/autoload.php';
require_once WPBOOK_DIR . 'vendor-prefixed/autoload.php';
// Action Scheduler must be loaded while plugins load; it picks the newest copy if several plugins bundle it.
require_once WPBOOK_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

Plugin::instance()->boot();

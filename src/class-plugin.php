<?php
/**
 * Plugin bootstrap: wires every service to WordPress.
 *
 * @package WPBook
 */

namespace WPBook;

use WPBook\Admin\Admin_App;
use WPBook\Books\Book_Post_Type;
use WPBook\Files\File_Store;
use WPBook\Jobs\Generate_Book_Job;
use WPBook\Legacy\Legacy;
use WPBook\Rest\Books_Controller;
use WPBook\Rest\Content_Controller;
use WPBook\Rest\Files_Controller;
use WPBook\Rest\Jobs_Controller;
use WPBook\Rest\Meta_Controller;
use WPBook\Rest\Preview_Controller;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks. Runs once while plugins load.
	 */
	public function boot() {
		Capabilities::register();

		add_action( 'init', array( Book_Post_Type::class, 'register' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( Generate_Book_Job::HOOK, array( Generate_Book_Job::class, 'handle' ), 10, 3 );
		add_action( 'before_delete_post', array( File_Store::class, 'on_delete_post' ) );

		Legacy::register();
		if ( is_admin() ) {
			( new Admin_App() )->register();
		}
	}

	/**
	 * Register the REST API routes under wp-book/v2.
	 */
	public function register_rest_routes() {
		$controllers = array(
			new Books_Controller(),
			new Content_Controller(),
			new Meta_Controller(),
			new Jobs_Controller(),
			new Files_Controller(),
			new Preview_Controller(),
		);
		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Activation: register the post type and create the protected storage folder.
	 */
	public static function activate() {
		Book_Post_Type::register();
		File_Store::ensure_base_dir();
	}

	/**
	 * Deactivation: cancel pending generation jobs.
	 */
	public static function deactivate() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( Generate_Book_Job::HOOK, array(), Generate_Book_Job::GROUP );
		}
	}
}

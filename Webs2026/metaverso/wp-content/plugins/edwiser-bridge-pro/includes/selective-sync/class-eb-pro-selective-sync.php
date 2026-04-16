<?php
/**
 * Selective Sync Module
 * This class is responsible for selective sync module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for selective sync module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\selectiveSync;

use app\wisdmlabs\edwiserBridgePro\admin as admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Eb_Pro_Selective_Sync {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      Eb_Pro_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Instance of the class.
	 *
	 * @since 3.0.0
	 * @access   protected
	 * @var Eb_Pro_Selective_Sync The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Main CustomFields Instance
	 *
	 * Ensures only one instance of Edwiser_Bridge_Pro is loaded or can be loaded.
	 *
	 * @since 3.0.0
	 * @static
	 * @return Eb_Pro_Selective_Sync - Main instance
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @param    string $loader The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 */
	public function __construct( $loader ) {
		global $eb_pro_plugin_data;
		$this->plugin_name = $eb_pro_plugin_data['plugin_slug'];
		$this->version     = $eb_pro_plugin_data['plugin_version'];
		$this->loader      = $loader;
		$this->define_constants();
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Define the constants for the plugin.
	 *
	 * @since    3.0.0
	 */
	private function define_constants() {

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Files are included on the basis of which modules are enabled.
	 * Create an instance of the loader which will be used to register the hooks.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// If user is not admin then load frontend dependencies.
		if ( ! is_admin() ) {
			$this->load_frontend_dependencies();
		}

		/*
		 *The class responsible for defining all actions that occur for AJAX
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/selective-sync/class-eb-select-course-ajax-handler.php';

		/*
		 * Admin settings section
		 */
		include_once EB_PRO_PLUGIN_PATH . 'admin/settings/class-selective-synch-courses-settings.php';
		include_once EB_PRO_PLUGIN_PATH . 'admin/settings/class-selective-synch-users-settings.php';

		/*
		 * Load wp-list-table.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/selective-sync/class-eb-select-users-list-table.php';

		/**
		 * Loads the generally used functions.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/selective-sync/selective-synch-functions.php';

		/**
		 * Loads the generally used functions.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/selective-sync/class-eb-select-users-ajax-handler.php';
	}

	/**
	 * Load the required frontend dependencies for this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_frontend_dependencies() {
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new admin\Edwiser_Bridge_Pro_Admin();

		// user action handler.
		$user_action_handler = new Eb_Select_Users_Ajax_Handler();

		$this->loader->add_action(
			'wp_ajax_selective_users_sync',
			$user_action_handler,
			'selective_users_creation_and_linking_ajax'
		);

		$this->loader->add_action(
			'wp_ajax_all_users_sync',
			$user_action_handler,
			'all_users_creation_and_linking_ajax'
		);

		// Action to sync selected courses.
		$ajax_handle_obj = new Eb_Select_Course_Ajax_Handler(
			$this->plugin_name,
			$this->version
		);

		$this->loader->add_action(
			'wp_ajax_selective_course_sync',
			$ajax_handle_obj,
			'selected_course_synchronization_initiater'
		);

		// Adding the setting related Hooks.
		$this->loader->add_filter(
			'eb_get_settings_pages',
			$plugin_admin,
			'add_selective_synch_tab',
			10,
			1
		);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

	}
}

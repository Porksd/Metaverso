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

namespace app\wisdmlabs\edwiserBridgePro;

use app\wisdmlabs\edwiserBridgePro\admin as admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Sample_Class {
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
	}
}

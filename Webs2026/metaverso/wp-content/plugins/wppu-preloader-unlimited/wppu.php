<?php
/*
Plugin Name:       WordPress Preloader Unlimited
Plugin URI:        http://www.wppreloader.com/
Description: 	   This plugin will enable custom preloader in your WordPress site. You can change color & other setting from <a href="admin.php?page=wppu_options">WPPU</a>
Version: 		   4.4
Author:     	   pixiefy
Author URI:  	   http://pixiefy.com
License:           GPL-2.0+
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain:       wppu
Domain Path:       /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * WPPU main class
 */
final class WPPU_Pixiefy {

    /**
     * Plugin version.
     *
     * @var string
     */
    const version = '4.3';

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;


    /**
     * Initialize the plugin.
     */
    private function __construct() {

        $this->define_constanst();

        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		add_action( 'plugins_loaded', [ $this, 'setup' ] );
		add_action( 'admin_init', [ $this, 'admin_scripts' ] );
	}
	
	/**
	 * Checks if the system requirements are met
	 *
	 * @return bool True if system requirements are met, false if not
	 */
	public function requirements_met() {
		global $wp_version;

		if ( version_compare( PHP_VERSION, WPPS_REQUIRED_PHP_VERSION, '<' ) ) {
			return false;
		}

		if ( version_compare( $wp_version, WPPS_REQUIRED_WP_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Prints an error that the system requirements weren't met.
	 */
	public function requirements_error() {
		global $wp_version;

		require_once( dirname( __FILE__ ) . '/requirements-error.php' );
	}

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    public function __clone() {
        wc_doing_it_wrong( __FUNCTION__, __( 'Nope' ), '1.0' );
    }
    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    public function __wakeup() {
        wc_doing_it_wrong( __FUNCTION__, __( 'Nope' ), '1.0' );
    }
    
    /**
     * Setup file and notice callback
     *
     * @return void
     */
    function setup() {
		load_plugin_textdomain( 'wppu', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		
		if( !$this->requirements_met() ) {
			add_action( 'admin_notices', array( $this, 'requirements_error' ) );
			return;
		}
		add_action('init', [$this, 'file_loaded']);
	}

	public function file_loaded(){
		/**
		 * The core plugin class that is used to define internationalization,
		 * dashboard-specific hooks, and public-facing site hooks.
		 */
		require plugin_dir_path( __FILE__ ) . 'includes/class-wppu.php';
		$plugin = new WP_Preloader_unlimited();
		$plugin->run();

		if (!class_exists('Mobile_Detect')) {
			require_once plugin_dir_path( __FILE__ ) . 'public/inc/Mobile_Detect.php';
		}
		require_once plugin_dir_path( __FILE__ ) . 'admin/settings_admin_options.php';
		require_once plugin_dir_path( __FILE__ ) . 'public/settings-frontend.php';
	}
	
	/**
	 * Load jquery in admin options page
	 *
	 * @return void
	 */
	public function admin_scripts() {
		if ( is_admin() ){
		   if ( isset($_GET['page']) && $_GET['page'] == 'wppu_options' ) {
			  wp_enqueue_script('jquery');
			  wp_enqueue_script( 'jquery-form' );
		   }
		}
	 }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Define require constansts
     * 
     * @return void
     */
    public function define_constanst(){
        define( 'WPPS_NAME',                 'WordPress Preloader Unlimited' );
		define( 'WPPS_REQUIRED_PHP_VERSION', '5.2.17' );                          // because of get_called_class()
		define( 'WPPS_REQUIRED_WP_VERSION',  '3.8' );                          // because of esc_textarea()
    }

    /**
     * Create the transaction table
     *
     * @return void
     */
    function activate() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-wppu-activator.php';
		WP_Preloader_unlimited_Activator::activate();
    }

    /**
     * WooCommerce fallback notice.
     *
     * @return string
     */
    public function deactivate() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-wppu-deactivator.php';
		WP_Preloader_unlimited_Deactivator::deactivate();
    }
        

}// end of the class

WPPU_Pixiefy::get_instance();

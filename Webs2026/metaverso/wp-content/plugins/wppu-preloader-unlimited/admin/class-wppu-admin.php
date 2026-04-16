<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://pixiefy.com/plugins/wp-preloader-unlimited/
 * @since      1.0.0
 *
 * @package    WP_Preloader_unlimited
 * @subpackage WP_Preloader_unlimited/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    WP_Preloader_unlimited
 * @subpackage WP_Preloader_unlimited/admin
 * @author     Your Name <email@example.com>
 */
class WP_Preloader_unlimited_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$pace = get_option( 'wppu_pace_options');
		
		if ( isset($_GET['page']) && $_GET['page'] == 'wppu_options' ) {
			wp_enqueue_style('thickbox');
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style('plugin_name-admin-ui-css',
                plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css',
                false,
                $this->version,
                false
             );
			wp_enqueue_style( 'custom-css-waitMe', plugin_dir_url( __FILE__ ) . 'css/waitMe.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'awesome-css', plugin_dir_url( __FILE__ ) . 'css/awesome-loaders.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wppu-admin.css', array(), $this->version, 'all' );
			// Load the Internet Explorer 7 specific stylesheet.
			if ( is_rtl() ) {
			    wp_enqueue_style( 'wppu-rtl', plugin_dir_url( __FILE__ ) .  '/css/rtl.css', array( $this->plugin_name ), $this->version );
			}			
		}
	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( isset($_GET['page']) && $_GET['page'] == 'wppu_options' ) {
			wp_enqueue_media();
			wp_enqueue_script( 
				'custom-script-waitMe', 
				plugin_dir_url( __FILE__ ) . 'js/waitMe.min.js', 
				array(), 
				$this->version, 
				false 
			);
			wp_enqueue_script( 
				$this->plugin_name, 
				plugin_dir_url( __FILE__ ) . 'js/wppu-admin.js', 
				array(
					'jquery',
					'media-upload',
					'thickbox', 
					'jquery-ui-core', 
					'jquery-ui-tooltip',
					'jquery-ui-accordion',
					'jquery-ui-draggable',
					'jquery-ui-dialog',
					'jquery-ui-slider',
					'jquery-ui-button',
					'jquery-ui-tabs', 
					'wp-color-picker'
					), 
				$this->version, 
				false 
			);
		}
		
	}

}

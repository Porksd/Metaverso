<?php
/**
 * SSO public Module
 * This class is responsible for SSO module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for SSO module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\sso;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This is not the way to call me!' );
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     WisdmLabs, India <support@wisdmlabs.com>
 */
class Sso_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 *
	 * @var string The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 *
	 * @var string The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Initialize the public-facing hooks.
	 */
	public function init_public() {
		/**
		 * Load ascript and styes on the login page of the wp since public and admin scripts are not get loaded on the wp login page
		 */
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		/**
		 * Enqueue public scripts.
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/**
		 * Add social login buttons on the wp login form.
		 */
		add_action( 'login_form', array( $this, 'wp_login_form_social_login' ) );
		/**
		 * Add social login buttons on the edwiser user account page ( In login form ).
		 */

		add_action( 'eb_login_form', array( $this, 'social_login' ) );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'eb-pro-sso-public-style', EB_PRO_PLUGIN_URL . 'public/assets/css/sso-public-css.css', array(), $this->version );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'eb-pro-sso-public-script', EB_PRO_PLUGIN_URL . 'public/assets/js/sso-public-js.js', array( 'jquery' ), $this->version ); // @codingStandardsIgnoreLine
	}

	/**
	 * Add social login buttons on the edwiser user account page ( In login form ).
	 */
	public function social_login() {
		echo do_shortcode( "[eb_sso_social_login page = 'user-account']" );
	}

	/**
	 * Add social login buttons on the wp login form.
	 */
	public function wp_login_form_social_login() {
		echo do_shortcode( "[eb_sso_social_login page = 'wp-login']" );
	}
}

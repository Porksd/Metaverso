<?php
/**
 * SSO Module
 * This class is responsible for SSO module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for SSO module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\sso;

use app\wisdmlabs\edwiserBridgePro\admin as admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Eb_Pro_Sso {
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
		$this->add_plugin_shortcodes();
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

		include_once EB_PRO_PLUGIN_PATH . '/includes/sso/ebsso-functions.php';
		include_once EB_PRO_PLUGIN_PATH . '/includes/sso/class-sso-manage-moodle-login.php';
		include_once EB_PRO_PLUGIN_PATH . '/includes/sso/class-sso-social-login-user-manager.php';
		include_once EB_PRO_PLUGIN_PATH . '/includes/sso/social-login/facebook/class-sso-facebook-init.php';

		include_once EB_PRO_PLUGIN_PATH . '/includes/sso/social-login/google/class-sso-google-plus-init.php';

		include_once EB_PRO_PLUGIN_PATH . '/public/shortcodes/class-sso-social-login.php';

		require_once EB_PRO_PLUGIN_PATH . '/includes/sso/class-single-sign-on.php';
		$GLOBALS['ebsso'] = new Single_Sign_On( $this->plugin_name, $this->version );
	}

	/**
	 * Load the required frontend dependencies for this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_frontend_dependencies() {
		include_once EB_PRO_PLUGIN_PATH . '/public/class-sso-public.php';
		$public_side = new Sso_Public( $this->plugin_name, $this->version );
		$public_side->init_public();
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new admin\Edwiser_Bridge_Pro_Admin();

		add_filter( 'eb_get_settings_pages', array( $plugin_admin, 'sso_settings' ) );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		add_action( 'eb_after_shortcode_doc', array( $this, 'add_shortcode_desc' ) );
		// add_action( 'init', array( $this, 'start_session' ), 1 );
		add_action( 'init', array( $this, 'load_social_login_dependancy' ), 2 );

		add_action( 'clear_auth_cookie', array( $this, 'clear_auth_cookie' ) );
	}

	/**
	 * Load social login dependancy.
	 *
	 * @since 1.3.1
	 */
	public function load_social_login_dependancy() {
		$sso_settings = get_option( 'eb_sso_settings_general' );

		if ( isset( $_GET['action'] ) || isset( $_GET['code'] ) ) { // @codingStandardsIgnoreLine
			// if ( session_status() !== PHP_SESSION_ACTIVE ) {
			// 	session_start();
			// }

			if ( isset( $_GET['action'] ) ) { // @codingStandardsIgnoreLine

				if ( isset( $sso_settings['eb_sso_fb_enable'] ) && 'no' !== $sso_settings['eb_sso_fb_enable'] ) {
					$fb_sdk = new Sso_Facebook_Init( $this->plugin_name, $this->version );
					$fb_sdk->load_dependencies();
				}
			} elseif ( isset( $_GET['code'] ) && $this->is_likely_google_oauth_callback() ) { // @codingStandardsIgnoreLine
				if ( isset( $sso_settings['eb_sso_gp_enable'] ) && 'no' !== $sso_settings['eb_sso_gp_enable'] ) {
					$gp_sdk = new Sso_Google_Plus_Init( $this->plugin_name, $this->version );
					$gp_sdk->load_dependencies();
				}
			}

			// session_write_close();
		}
	}

	/**
	 * Add plugin shortcodes.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function add_plugin_shortcodes() {
		/**
		 * Create shortcode to display social login widget.
		 */
		$social_login = new Sso_Social_Login( $this->plugin_name, $this->version );
		add_shortcode( 'eb_sso_social_login', array( $social_login, 'output' ) );
	}

	/**
	 * Register session.
	 *
	 * @since 1.2
	 */
	public function start_session() {
		global $eb_session_id;
		if ( ! headers_sent() ) {
			if ( session_status() !== PHP_SESSION_ACTIVE ) {
				session_start();
			}

			$eb_session_id = session_id();
			session_write_close();
		}

	}

	/**
	 * Clear auth cookie.
	 *
	 * @since 1.2
	 */
	public function clear_auth_cookie() {
		$userinfo                  = wp_get_current_user();
		$user_id                   = $userinfo->ID;
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			//check ip from share internet
			$ip_addr = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$ip_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip_addr = $_SERVER['REMOTE_ADDR'];
		}
		set_transient(
			'eb_user_' . $ip_addr . '_wp_user_id',
			$user_id,
			4 * HOUR_IN_SECONDS
		);
	}

	/**
	 * Add shortcode description.
	 */
	public function add_shortcode_desc() {
		$html = "<div class='eb-shortcode-doc-wpra'>
					<h3>Single Sign On Shortcode Options </h3>
					<div class='eb-shortcode-doc'>
						<h4>[eb_sso_social_login]</h4>
						<div class='eb-shortcode-doc-desc'>
							<p>
								" . __( 'This shortcode shows Facebook and Goodle+ icons for login.', 'edwiser-bridge-pro' ) . "
							</p>
						</div>
					</div>
					<div class='eb-shortcode-doc'>
						<h4>[wdm_generate_link]</h4>
						<div class='eb-shortcode-doc-desc'>
							<p>
							" . __( 'This shortcode redirects user to the Moodle site. This shortcode can take following parameters: ', 'edwiser-bridge-pro' ) . "
							</p>
							<ul>
								<li><span class='eb_shortcode-doc-para'>course_id</span> : " . __( 'Moodle course id Example:', 'edwiser-bridge-pro' ) . " <span class='eb_shortcode-doc-para'>[course_id=\"2\"]</span></li>
							</ul>
						</div>
					</div>
				</div>";
		echo $html; // @codingStandardsIgnoreLine
	}

	/**
	 * Check if this is likely a Google OAuth2 callback.
	 *
	 * @return bool
	 */
	private function is_likely_google_oauth_callback() {
		// Basic validation to prevent processing obvious non-OAuth codes
		if ( ! isset( $_GET['code'] ) ) {
			return false;
		}

		$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
		
		// Skip very short codes (likely not OAuth)
		if ( strlen( $code ) < 10 ) {
			return false;
		}

		// Check for obvious non-OAuth patterns
		$non_oauth_patterns = array(
			'discount', 'coupon', 'promo', 'utm_', 'ref=', 'affiliate', 
			'campaign', 'tracking', 'source=', 'medium=', 'term='
		);
		
		foreach ( $non_oauth_patterns as $pattern ) {
			if ( stripos( $code, $pattern ) !== false ) {
				return false;
			}
		}

		// If we have a state parameter, it's very likely to be OAuth
		if ( isset( $_GET['state'] ) ) {
			return true;
		}

		// Check if the code looks like a base64url encoded string (typical for OAuth)
		if ( preg_match( '/^[A-Za-z0-9\-_]+$/', $code ) && strlen( $code ) >= 20 ) {
			return true;
		}

		// If we can't determine, be conservative and don't process
		return false;
	}
}

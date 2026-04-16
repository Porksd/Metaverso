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

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This is not the way to call me!' );
}

/**
 * Class Single Sign On
 */
class Single_Sign_On {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->version     = $version;
		$this->plugin_name = $plugin_name;
		if ( ! defined( 'EB_MOODLE_PLUGIN_URL' ) ) {
			define( 'EB_MOODLE_PLUGIN_URL', '/auth/edwiserbridge/login.php?wdm_data=' );
		}
		if ( ! defined( 'EB_MOODLE_LOGOUT_URL' ) ) {
			define( 'EB_MOODLE_LOGOUT_URL', '/auth/edwiserbridge/login.php?wdm_logout=' );
		}
		add_action( 'rest_api_init', array( $this, 'eb_sso_login_api_registration' ) );
		$this->init();
	}

	/**
	 * Initialize the class.
	 */
	public function init() {
		/**
		 * Load Initial settings.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/sso/class-sso-redirection.php';

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_ebsso_verify_key', array( $this, 'verify_key' ) );
		add_shortcode( 'wdm_generate_link', array( $this, 'generate_link_message' ) );
		add_action( 'template_redirect', array( $this, 'generate_link' ) );
		add_filter( 'eb_course_access_button', array( $this, 'course_access_button' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'logged_out' ), 11 );
		add_action( 'wp_login', array( $this, 'logged_in' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'mdl_triggered_action' ) );
		add_action( 'template_redirect', array( $this, 'eb_login_from_course_link' ) );
	}

	/**
	 * Login from course link.
	 */
	public function eb_login_from_course_link() {
		if ( isset( $_GET['mdl_course_id'] ) ) { // @codingStandardsIgnoreLine

			// Check if user is logged in or not.
			$user = wp_get_current_user();

			if ( $user->ID ) {
				$mdl_logout = new Sso_Manage_Moodle_Login( $this->plugin_name, $this->version );
				$mdl_logout->mdl_logged_in( $user->user_login, $user );
			}
		}
	}

	/**
	 * Moodle connection url.
	 *
	 * @param string $conn_options Moodle connection options.
	 */
	private function get_mdl_connection_url( $conn_options ) {
		$mdl_url = false;
		if ( isset( $conn_options['eb_url'] ) ) {
			$mdl_url = $conn_options['eb_url'];
		}
		return $mdl_url;
	}

	/**
	 * Moodle connection token.
	 *
	 * @param string $conn_options Moodle connection options.
	 */
	private function get_mdl_access_token( $conn_options ) {
		$mdl_token = false;
		if ( isset( $conn_options['eb_access_token'] ) ) {
			$mdl_token = $conn_options['eb_access_token'];
		}
		return $mdl_token;
	}

	/**
	 * Prepare token verify request.
	 *
	 * @param string $mdl_url Moodle url.
	 * @param string $mdl_token Moodle token.
	 * @param string $web_function Moodle web function.
	 * @param string $token Token.
	 */
	private function prepare_token_verify_request( $mdl_url, $mdl_token, $web_function, $token ) {
		$req_url      = $mdl_url . '/webservice/rest/server.php?wstoken=';
		$req_url     .= $mdl_token . '&wsfunction=' . $web_function . '&moodlewsrestformat=json';
		$request_args = array(
			'body'    => array( 'token' => $token ),
			'timeout' => 500,
		);
		return wp_remote_post( $req_url, $request_args );
	}

	/**
	 * Alert script
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			if ( isset( $_GET['wdm_moodle_error'] ) && 'wdm_moodle_error' === $_GET['wdm_moodle_error'] ) { // @codingStandardsIgnoreLine
				wp_enqueue_script( // @codingStandardsIgnoreLine
					'eb_sso_blockUI_js',
					EB_PRO_PLUGIN_URL . 'admin/assets/js/jquery.blockUI.js',
					array( 'jquery' )
				);

				wp_enqueue_script( 'eb_sso_moodle_js', '', array( 'jquery', 'eb_sso_blockUI_js' ) ); // @codingStandardsIgnoreLine
				$data = array(
					'error_message' => __( 'Please set the same secret key on WordPress as well as on Moodle', 'edwiser-bridge-pro' ),
				);
				wp_localize_script( 'eb_sso_moodle_js', 'eb_sso_data', $data );
			}
		}
	}

	/**
	 * Setting scripts
	 *
	 * @since 1.2
	 */
	public function admin_scripts() {
		wp_enqueue_style( 'ebsso_admin_setings_css', EBSSO_URL . '/assets/admin-settings.css' ); // @codingStandardsIgnoreLine

		wp_enqueue_script( // @codingStandardsIgnoreLine
			'ebsso_admin_setings_js',
			EBSSO_URL . '/assets/admin-settings.js',
			array( 'jquery' )
		);

		$nonce = wp_create_nonce( 'ebsso-verify-key' );

		$data = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => $nonce,
		);
		wp_localize_script( 'ebsso_admin_setings_js', 'ebssoAdSet', $data );
	}

	/**
	 * This will provide the functionality to validate the secreat key token vith moodle.
	 */
	public function verify_key() {
		/**
		 * Get Moodle Connection options
		 */
		$conn_options = get_option( 'eb_connection' );
		$mdl_url      = $this->get_mdl_connection_url( $conn_options );
		$mdl_token    = $this->get_mdl_access_token( $conn_options );
		if ( ! $mdl_url || ! $mdl_token ) {
			wp_send_json_error( __( 'Please check your Edwiser Bridge Connection Settings first!', 'edwiser-bridge-pro' ) );
		}
		$response = $this->prepare_token_verify_request( $mdl_url, $mdl_token, 'auth_edwiserbridge_verify_sso_token', $_POST['wp_key'] ); // @codingStandardsIgnoreLine
		$msg      = '';
		if ( is_wp_error( $response ) ) {
			$msg = $response->get_error_message();
			if ( function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
				global $current_user;
				wp_get_current_user();
				$error_data = array(
					'url'          => $mdl_url,
					'arguments'    => $_POST['wp_key'], // @codingStandardsIgnoreLine
					'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
					'responsecode' => '',
					'exception'    => '',
					'errorcode'    => '',
					'message'      => $msg,
					'backtrace'    => wp_debug_backtrace_summary( null, 0, false ), // @codingStandardsIgnoreLine
				);
				\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
			}
		} elseif ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ) );
			/**
			 * Check moodle plugin installed and webservice function is added into the external services.
			 */
			if ( isset( $body->exception ) && 'webservice_access_exception' === $body->exception ) {
				wp_send_json_error( __( 'Web service function is not added into the external services. Please check your Edwiser Bridge Connection Settings first!', 'edwiser-bridge-pro' ) );
			} elseif ( isset( $body->exception ) && 'dml_missing_record_exception' === $body->exception ) {
				wp_send_json_error( __( 'Please check your Edwiser Bridge Connection Settings first!', 'edwiser-bridge-pro' ) );
			}

			if ( isset( $body->exception ) && function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
				global $current_user;
				wp_get_current_user();
				$error_data = array(
					'url'          => $mdl_url,
					'arguments'    => $_POST['wp_key'], // @codingStandardsIgnoreLine
					'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
					'responsecode' => wp_remote_retrieve_response_code( $response ),
					'exception'    => $body->exception,
					'errorcode'    => $body->errorcode,
					'message'      => $body->message,
					'backtrace'    => wp_debug_backtrace_summary( null, 0, false ), // @codingStandardsIgnoreLine
				);
				\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
			}

			if ( true === $body->success ) {
				wp_send_json_success( __( 'Token verified successfully !', 'edwiser-bridge-pro' ) );
			} else {
				$msg = $body->msg;
				if ( function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
					global $current_user;
					wp_get_current_user();
					$error_data = array(
						'url'          => $mdl_url,
						'arguments'    => $_POST['wp_key'], // @codingStandardsIgnoreLine
						'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
						'responsecode' => '',
						'exception'    => '',
						'errorcode'    => '',
						'message'      => $msg,
						'backtrace'    => wp_debug_backtrace_summary( null, 0, false ), // @codingStandardsIgnoreLine
					);
					\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
				}
			}
		} else {
			$msg = __( 'Please check Moodle URL !', 'edwiser-bridge-pro' );
		}
		wp_send_json_error( $msg );
	}

	/**
	 * Used for generating moodle url.
	 *
	 * @since    1.0
	 */
	public function generate_link_message() {
		ob_start();
		if ( is_user_logged_in() ) {
			esc_html_e( 'You don\'t have moodle account.', 'edwiser-bridge-pro' );
		} else {
			esc_html_e( 'Please login to view this page.', 'edwiser-bridge-pro' );
		}
		return ob_get_clean();
	}

	/**
	 * Used for generating moodle url.
	 */
	public function generate_link() {
		global $post;

		if ( isset( $post ) && has_shortcode( $post->post_content, 'wdm_generate_link' ) ) {
			// get shortcode attribute from post content.
			$pattern = get_shortcode_regex();
			// get shortcode attribute from post content.
			preg_match_all( '/' . $pattern . '/s', $post->post_content, $matches );
			$atts = array();
			if ( isset( $matches[2] ) && in_array( 'wdm_generate_link', $matches[2] ) ) { // @codingStandardsIgnoreLine
				$i = 0;
				foreach ( $matches[2] as $key => $value ) {
					if ( 'wdm_generate_link' === $value ) {
						$i = $key;
						break;
					}
				}
				if ( isset( $matches[3][ $i ] ) ) {
					$atts = shortcode_parse_atts( $matches[3][ $i ] );
				}
			}
			if ( is_user_logged_in() ) {
				$moodle_user_id = get_user_meta( get_current_user_id(), 'moodle_user_id', true );
				if ( $moodle_user_id ) {
					$redirect_url = eb_get_mdl_url();
					if ( isset( $atts['course_id'] ) && '' !== $atts['course_id'] ) {
						$redirect_url .= '/course/view.php?id=' . $atts['course_id'];
					}
					$user       = wp_get_current_user();
					$mdl_logout = new Sso_Manage_Moodle_Login( $this->plugin_name, $this->version );

					$mdl_logout->mdl_logged_in( $user->user_login, $user, '', $redirect_url );
				}
			}
		}
	}

	/**
	 * Changed the url of course access button.
	 *
	 * @param string $access_button access button html.
	 * @param array  $access_params access button params.
	 * @since 1.0.0
	 */
	public function course_access_button( $access_button, $access_params ) {
		if ( ! is_user_logged_in() ) {
			return $access_button;
		}
		$post_id          = $access_params['post']->ID;
		$moodle_course_id = get_post_meta( $post_id, 'moodle_course_id', true );
		if ( '' == $moodle_course_id ) { // @codingStandardsIgnoreLine
			return $access_button;
		}
		$moodle_user_id = get_user_meta( get_current_user_id(), 'moodle_user_id', true );
		if ( '' == $moodle_user_id ) { // @codingStandardsIgnoreLine
			return $access_button;
		}

		$query = array(
			'moodle_user_id'   => $moodle_user_id, // moodle user id.
			'moodle_course_id' => $moodle_course_id,
		);

		// encode array as querystring.
		$final_url = get_site_url() . '?mdl_course_id=' . $moodle_course_id;

		if ( filter_var( $final_url, FILTER_VALIDATE_URL ) ) {
			$access_params['access_course_url'] = $final_url;
			$html                               = '<div class="eb_join_button"><a class="wdm-btn" href="'
					. $final_url . '" id="wdm-btn">' . __( 'Access Course', 'edwiser-bridge-pro' ) . '</a></div>';

			return $html;
		} else {
			return $access_button;
		}
		return $access_button;
	}

	/**
	 * Logging out user from moodle site.
	 *
	 * @since 1.0.0
	 */
	public function logged_out() {
		$mdl_logout = new Sso_Manage_Moodle_Login( $this->plugin_name, $this->version );
		$mdl_logout->mdl_logged_out();
	}

	/**
	 * Logged in user on moodle site.
	 *
	 * @param string $user_login user login.
	 * @param object $user user object.
	 * @since 1.0.0
	 */
	public function logged_in( $user_login, $user ) {
		// Check get parameters, if login_action is found ans also the value is moodle then this login is triggered from Moodle so please redirect user to Moodle.
		$mdl_logout = new Sso_Manage_Moodle_Login( $this->plugin_name, $this->version );
		$mdl_logout->mdl_logged_in( $user_login, $user );
	}

	/**
	 * Triggers actions - login/logout from moodle site.
	 *
	 * @since 1.2
	 */
	public function mdl_triggered_action() {
		$setting = get_option( 'eb_sso_settings_general' );
		$wp_key  = isset( $setting['eb_sso_secret_key'] ) ? (string) $setting['eb_sso_secret_key'] : '';

		if ( isset( $_GET['wdmaction'] ) && isset( $_GET['mdl_uid'] ) && isset( $_GET['verify_code'] ) ) { // @codingStandardsIgnoreLine
			$setting     = get_option( 'eb_sso_settings_general' );
			$redirect_to = eb_get_mdl_url();

			// Get stored data.
			$wp_user_id = eb_get_wp_user_id_from_moodle_id( $_GET['mdl_uid'] ); // @codingStandardsIgnoreLine

			if ( $wp_user_id ) {

				$eb_session_data = get_user_meta( $wp_user_id, 'eb_sso_user_login_logout_session', 1 );

				delete_user_meta( $wp_user_id, 'eb_sso_user_login_logout_session' );

				if ( $eb_session_data ) {
					// decrypt.
					$decrypted_args = getDecryptedQueryArgs( $eb_session_data, $wp_key );
				}

				// verify key.
				if ( isset( $decrypted_args ) && getKeyValue( $decrypted_args, 'mdl_one_time_code' ) === $_GET['verify_code'] ) { // @codingStandardsIgnoreLine

					$mdl_key   = (string) getKeyValue( $decrypted_args, 'mdl_key' );
					$mdl_uid   = getKeyValue( $decrypted_args, 'mdl_uid' );
					$mdl_email = getKeyValue( $decrypted_args, 'mdl_email' );

					$redirect_to = getKeyValue( $decrypted_args, 'redirect_to' );
					if ( ! filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
						if ( filter_var( $_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL ) ) { // @codingStandardsIgnoreLine
							$redirect_to = $_SERVER['HTTP_REFERER']; // @codingStandardsIgnoreLine
						} else {
							$redirect_to = site_url();
						}
					}

					if ( ! empty( $wp_key ) && $mdl_key === $wp_key ) {
						if ( 'logout' === $_GET['wdmaction'] ) { // @codingStandardsIgnoreLine
							add_action(
								'wp_logout',
								function() use ( $redirect_to ) {
									wp_redirect( $redirect_to ); // @codingStandardsIgnoreLine
									exit();
								}
							);
							triggerLogout( $mdl_uid );
						} elseif ( 'login' === $_GET['wdmaction'] ) { // @codingStandardsIgnoreLine
							triggerLogin( $mdl_uid, $mdl_email );
						}
					}
				}
			}
			wp_redirect( $redirect_to ); // @codingStandardsIgnoreLine
			exit();

		} elseif ( isset( $_GET['wdmaction'] ) && isset( $_GET['data'] ) ) { // @codingStandardsIgnoreLine
			$conn_options = get_option( 'eb_connection' );
			$mdl_url      = $this->get_mdl_connection_url( $conn_options );

			// If action triggered don't have moodle user id i.e mdl_uid then check if the action is to trigger Moodle login from WordPress.
			// If yes then Login Moodle user and redirect back.
			if ( is_user_logged_in() ) {
				$wp_key = isset( $setting['eb_sso_secret_key'] ) ? (string) $setting['eb_sso_secret_key'] : '';
				// Check if Moodle user is already logged in.
				$decrypted_args = getDecryptedQueryArgs( $_GET['data'], $wp_key ); // @codingStandardsIgnoreLine

				if ( getKeyValue( $decrypted_args, 'mdl_key' ) === $wp_key ) {

					$user = wp_get_current_user();

					if ( $user->ID ) {
						$mdl_logout = new Sso_Manage_Moodle_Login( $this->plugin_name, $this->version );
						$mdl_logout->mdl_logged_in( $user->user_login, $user, '', $mdl_url );
					}
				}
			} else {
				// If not logged in then redirect to the WordPress login page.
				$settings   = get_option( 'eb_sso_settings_redirection' );
				$login_page = isset( $settings['ebsso_login_page'] ) ? $settings['ebsso_login_page'] : '';
				if ( $login_page ) {
					$login_page = get_permalink( $login_page );
				} else {
					// get user-account page.
					$login_page = site_url( '/user-account' );
				}
				wp_redirect( add_query_arg( array( 'redirect_to' => $mdl_url, 'login_action' => 'moodle' ), $login_page ) ); // @codingStandardsIgnoreLine
			}
		}
	}



	/**
	 * SSO login API registration.
	 */
	public function eb_sso_login_api_registration() {
		register_rest_route(
			'edwiser-bridge',
			'/sso/',
			array(
				'methods'             => 'POST, GET',
				'callback'            => array( $this, 'external_api_endpoint_def' ),
				'permission_callback' => '__return_true',
			)
		);
	}



	/**
	 * This function parse the request coming from
	 *
	 * @param  text $request_data request Data.
	 */
	public function external_api_endpoint_def( $request_data ) {

		/**----------------------------------
		 * Handle Moodle request.
		 *-----------------------------------*/
		// Check if key is valid.
		if ( isset( $_POST['wdmargs'] ) ) { // @codingStandardsIgnoreLine
			$setting = get_option( 'eb_sso_settings_general' );
			$wp_key  = isset( $setting['eb_sso_secret_key'] ) ? (string) $setting['eb_sso_secret_key'] : '';

			$decrypted_args = getDecryptedQueryArgs( $_POST['wdmargs'], $wp_key ); // @codingStandardsIgnoreLine
			$mdl_key        = (string) getKeyValue( $decrypted_args, 'mdl_key' );
			$mdl_uid        = getKeyValue( $decrypted_args, 'mdl_uid' );
			$mdl_email      = getKeyValue( $decrypted_args, 'mdl_email' );

			if ( $mdl_key === $wp_key ) {
				$user = get_user_by( 'email', $mdl_email );
				if ( isset( $user->ID ) && ! empty( $user->ID ) ) {
					update_user_meta( $user->ID, 'eb_sso_user_login_logout_session', $_POST['wdmargs'] ); // @codingStandardsIgnoreLine
				}
			}
		}
	}


}

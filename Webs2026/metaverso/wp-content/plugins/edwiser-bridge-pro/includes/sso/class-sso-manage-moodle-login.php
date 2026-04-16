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
 * SSO Manage Moodle Login class.
 */
class Sso_Manage_Moodle_Login {

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
	 * @param string $version    Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Logging out user from moodle site.
	 *
	 * @since 1.0.0
	 */
	public function mdl_logged_out() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			//check ip from share internet
			$ip_addr = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$ip_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip_addr = $_SERVER['REMOTE_ADDR'];
		}
		$user = get_transient('eb_user_' . $ip_addr . '_wp_user_id');
		if ( isset( $user ) && '' != $user ) { // @codingStandardsIgnoreLine
			$user_id = $user;
			delete_transient('eb_user_' . $user . '_wp_user_id');
		} else {
			return;
		}
		$moodle_user_id = get_user_meta( $user_id, 'moodle_user_id', true );
		if ( '' == $moodle_user_id ) { // @codingStandardsIgnoreLine
			return '';
		}

		$logout_url = site_url();

		if ( isset( $_SERVER['HTTP_REFERER'] ) && filter_var( $_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL ) ) { // @codingStandardsIgnoreLine
			$logout_url = $_SERVER['HTTP_REFERER']; // @codingStandardsIgnoreLine
		}

		// Prevent logout loop for wp logout without nonce.
		$parsed_url = parse_url($logout_url);
		if ( isset( $parsed_url['query'] ) ) {
			parse_str($parsed_url['query'], $query_params);
		}
		if ( isset( $query_params['action'] ) && 'logout' == $query_params['action'] ) {
			$logout_url = site_url();
		}

		$hash = hash( 'md5', rand( 10, 1000 ) ); // @codingStandardsIgnoreLine
		$query = array(
			'moodle_user_id'   => $moodle_user_id,
			'logout_redirect'  => apply_filters( 'eb_sso_logout_url', $logout_url ),
			'wp_one_time_hash' => $hash,
		);

		// encode array as querystring.
		$final_url = generateMoodleLogoutUrl( $query );
		if ( filter_var( $final_url, FILTER_VALIDATE_URL ) ) {

			// Send post data.
			$details = http_build_query( $query );

			$eb_moodle_url  = eb_get_mdl_url();
			$sso_secret_key = eb_get_mdl_token();
			$wdm_data       = encryptString( $details, $sso_secret_key );

			if ( ! empty( $eb_moodle_url ) ) {

				$final_url = $eb_moodle_url . EB_MOODLE_PLUGIN_URL;

				$request_args = array(
					'body'    => array( 'wdm_data' => $wdm_data ),
					'timeout' => 100,
				);

				// Wdm data and url.
				// Set session in moodle.
				$response = wp_remote_post( $eb_moodle_url . '/auth/edwiserbridge/login.php', $request_args );
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					if ( function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
						global $current_user;
						wp_get_current_user();
						$error_data = array(
							'url'          => $eb_moodle_url . '/auth/edwiserbridge/login.php',
							'arguments'    => $request_args,
							'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
							'responsecode' => '',
							'exception'    => '',
							'errorcode'    => '',
							'message'      => $error_message,
							'backtrace'    => wp_debug_backtrace_summary( null, 0, false ), // @codingStandardsIgnoreLine
						);
						\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
					}
				}

				// Now redirect user with only Moodle user id.
				wp_redirect( $eb_moodle_url . '/auth/edwiserbridge/login.php?logout_id=' . $moodle_user_id . '&veridy_code=' . $hash ); // @codingStandardsIgnoreLine
				exit;
			}
		}
	}

	/**
	 * Logged in user on moodle site.
	 *
	 * @param string $user_login       User login.
	 * @param object $user             User object.
	 * @param string $social_redirect  Social redirect url.
	 * @param string $redirect         Redirect url.
	 * @since 1.0.0
	 */
	public function mdl_logged_in( $user_login, $user, $social_redirect = '', $redirect = '' ) {
		// unnecessary variable.
		unset( $user_login );
		$moodle_user_id = get_user_meta( $user->ID, 'moodle_user_id', true );
		if ( empty( $moodle_user_id ) ) {
			return;
		}

		$redirection                 = new Sso_Redirection( $this->plugin_name, $this->version );
		$default_redirect            = '';
		$ignore_setting_redirect_url = 0;

		if ( ! empty( $social_redirect ) ) {
			$default_redirect = $social_redirect;
		} elseif ( ! empty( $redirect ) ) {
			$ignore_setting_redirect_url = 1;
			$default_redirect            = $redirect;
		}

		$hash  = hash( 'md5', rand( 10, 1000 ) ); // @codingStandardsIgnoreLine
		$query = array(
			'moodle_user_id'   => $moodle_user_id,
			'login_redirect'   => $redirection->get_login_redirect_url( $user, $default_redirect, $ignore_setting_redirect_url ),
			'wp_one_time_hash' => $hash,
		);

		// Send post data.
		$details        = http_build_query( $query );
		$eb_moodle_url  = eb_get_mdl_url();
		$sso_secret_key = eb_get_mdl_token();
		$wdm_data       = encryptString( $details, $sso_secret_key );

		if ( ! empty( $eb_moodle_url ) ) {
			$final_url = $eb_moodle_url . EB_MOODLE_PLUGIN_URL;

			$request_args = array(
				'body'    => array( 'wdm_data' => $wdm_data ),
				'timeout' => 100,
			);

			// Wdm data and url.
			// Set session in moodle.
			$response = wp_remote_post( $eb_moodle_url . '/auth/edwiserbridge/login.php', $request_args );
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				if ( function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
					global $current_user;
					wp_get_current_user();
					$error_data = array(
						'url'          => $eb_moodle_url . '/auth/edwiserbridge/login.php',
						'arguments'    => $request_args,
						'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
						'responsecode' => '',
						'exception'    => '',
						'errorcode'    => '',
						'message'      => $error_message,
						'backtrace'    => wp_debug_backtrace_summary( null, 0, false ), // @codingStandardsIgnoreLine
					);
					\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
				}
			}

			$final_url = $eb_moodle_url . '/auth/edwiserbridge/login.php?login_id=' . $moodle_user_id . '&veridy_code=' . $hash;
			// Now redirect user with only Moodle user id.
		}

		wp_redirect( $final_url ); // @codingStandardsIgnoreLine
		exit;
	}
}

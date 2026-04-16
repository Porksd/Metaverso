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
 * Class Sso_Gp_Logout_User
 *
 * @package app\wisdmlabs\edwiserBridgePro\includes\sso
 */
class Sso_Gp_Logout_User {
	/**
	 * Sso_Gp_Logout_User constructor.
	 */
	public function init() {
		if ( \is_user_logged_in() ) {
			add_action( 'wp_logout', array( $this, 'gp_logout_user' ) );
		}
	}

	/**
	 * Logout user from google plus.
	 */
	public function gp_logout_user() {
		global $eb_user_id;
		if ( empty( $eb_user_id ) ) {
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				//check ip from share internet
				$ip_addr = $_SERVER['HTTP_CLIENT_IP'];
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//to check ip is pass from proxy
				$ip_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$ip_addr = $_SERVER['REMOTE_ADDR'];
			}
			$eb_user_id = get_transient('eb_user_' . $ip_addr . '_eb_user_id');
			delete_transient('eb_user_' . $ip_addr . '_eb_user_id');
		}
		delete_transient('eb_user_' . $eb_user_id . '_token');
		Sso_Google_Plus_Init::get_google_client()->revokeToken();
	}
}

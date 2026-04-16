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
 * Class Sso_Fb_Logout_User
 *
 * @package app\wisdmlabs\edwiserBridgePro\includes\sso
 */
class Sso_Fb_Logout_User {

	/**
	 * FB Client Helper.
	 *
	 * @var string
	 */
	private $fb_client_helper;

	/**
	 * Sso_Fb_Logout_User constructor.
	 */
	public function init() {
		$this->fb_client_helper = Sso_Facebook_Init::get_faceboook_client_helper();
		if ( \is_user_logged_in() ) {
			add_action( 'wp_logout', array( $this, 'gp_logout_user' ) );
		}
	}

	/**
	 * Logout user.
	 */
	public function gp_logout_user() {
		global $eb_user_id;
		// unset( $_SESSION['facebook_access_token'] );
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
		delete_transient('eb_user_' . $eb_user_id . '_facebook_access_token');
		// unset( $_SESSION['userData'] );
		$this->fb_client_helper->getReRequestUrl( get_site_url(), array( 'email' ) );
	}
}

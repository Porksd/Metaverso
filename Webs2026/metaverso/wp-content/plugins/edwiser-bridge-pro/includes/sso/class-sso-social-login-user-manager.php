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
 * SSO Social Login User Manager class.
 */
class Sso_Social_Login_User_Manager {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * User table name.
	 *
	 * @var string
	 */
	private $user_tbl;

	/**
	 * Logger instance.
	 *
	 * @var object
	 */
	private $eb_logger;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->eb_logger   = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();
	}

	/**
	 * Check user details.
	 *
	 * @param array  $social_prof_data Social profile data.
	 * @param string $redirect         Redirect URL.
	 */
	public function check_user_details( $social_prof_data = array(), $redirect = '' ) {
		global $wpdb;
		$user_data  = false;
		$check_keys = array( 'oauth_uid', 'first_name', 'last_name', 'email' );
		foreach ( $check_keys as $key ) {
			if ( empty( $social_prof_data[ $key ] ) ) {
				auth_redirect();
				exit();
			}
		}

		if ( ! empty( $social_prof_data ) ) {
			$this->eb_logger->logger()->add( 'SSO', 'SSO Log: Checking user is already exist or not for the emial address: ' . $social_prof_data['email'] . 'using provider: ' . $social_prof_data['oauth_provider'] );
			$this->user_tbl  = $wpdb->prefix . 'gp_oauth_users';
			$stmt_check_user = "SELECT * FROM " . $this->user_tbl . " WHERE oauth_provider = '" . $social_prof_data['oauth_provider'] . "' AND oauth_uid = '" . $social_prof_data['oauth_uid'] . "'"; // @codingStandardsIgnoreLine
			$prev_result      = $wpdb->get_row( $stmt_check_user, ARRAY_A ); // @codingStandardsIgnoreLine

			if ( null === $prev_result ) {
				$this->add_user( $social_prof_data );
			} else {

				$where = array(
					'oauth_provider' => $prev_result['oauth_provider'],
					'oauth_uid'      => $prev_result['oauth_uid'],
				);

				$this->update_user_data( $social_prof_data, $where );
			}
			$user_data = $wpdb->get_row( $stmt_check_user, ARRAY_A ); // @codingStandardsIgnoreLine
		}
		$this->redirect_user( $social_prof_data['email'], $user_data, $redirect );
	}

	/**
	 * Redirect user.
	 *
	 * @param string $social_login_emial Social login email.
	 * @param array  $user_data        User data.
	 * @param string $redirect         Redirect URL.
	 */
	private function redirect_user( $social_login_emial, $user_data, $redirect ) {
		if ( $user_data ) {
			/**
			 * Get WordPress user id by email address.
			 */
			$wp_user = get_user_by( 'email', $social_login_emial );

			/**
			 * Login user to WordPress site
			 */
			$this->eb_logger->logger()->add( 'SSO', 'SSO Log: User registrerd with email address: ' . $social_login_emial );
			setLoginData( $wp_user );
			$mdl_login = new Sso_Manage_Moodle_Login( $this->plugin_name, $this->version );

			$mdl_login->mdl_logged_in( '', $wp_user, '', $redirect );
		} else {
			$this->eb_logger->logger()->add( 'SSO', 'SSO Log: User registration failed' );
			auth_redirect();
			exit();
		}
	}

	/**
	 * Add user.
	 *
	 * @param array $user_data User data.
	 */
	private function add_user( $user_data ) {
		global $wpdb;
		$wpdb->insert( $this->user_tbl, $user_data ); // @codingStandardsIgnoreLine
		$this->eb_logger->logger()->add( 'SSO', 'SSO Log: Creating new WordPress user with email: ' . $user_data['email'] . 'using provider: ' . $user_data['oauth_provider'] );
		/**
		 * Create WordPress user on sucessfull registration this will call the edwiser bridge create user to create new user.
		 */
		createEbUser( $user_data );
		/**
		 * Get WordPress user id by email address
		 */
		$wp_user = get_user_by( 'email', $user_data['email'] );
		/**
		 * Update google OAuth user data. Add wp user id to identify the user.
		 */
		$this->update_user_data( array( 'wp_user_id' => $wp_user->ID ), array( 'email' => $user_data['email'] ) );
	}

	/**
	 * Update user data.
	 *
	 * @param array $user_data User data.
	 * @param array $where     Where condition.
	 */
	private function update_user_data( $user_data, $where ) {
		if ( isset( $user_data['email'] ) ) {
			global $wpdb;
			$wp_user = get_user_by( 'email', $user_data['email'] );
			if ( ! $wp_user ) {
				createEbUser( $user_data );
			}
			$this->eb_logger->logger()->add( 'SSO', 'SSO Log: updating user with email: ' . $user_data['email'] );
			$wpdb->update( $this->user_tbl, $user_data, $where ); // @codingStandardsIgnoreLine
		}
	}
}

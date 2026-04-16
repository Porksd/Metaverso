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
 * SSO Facebook User Manager
 */
class Sso_Fb_User_Manager {

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
	 * FB Client.
	 *
	 * @var string
	 */
	private $fb_client;

	/**
	 * FB Client Helper.
	 *
	 * @var string
	 */
	private $fb_client_helper;

	/**
	 * FB Oauth2 Service.
	 *
	 * @var string
	 */
	private $fb_oauth2_service;

	/**
	 * Logger.
	 *
	 * @var string
	 */
	private $eb_logger;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name       = $plugin_name;
		$this->version           = $version;
		$this->eb_logger         = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();
		$this->fb_client         = Sso_Facebook_Init::get_faceboook_client();
		$this->fb_client_helper  = Sso_Facebook_Init::get_faceboook_client_helper();
		$this->fb_oauth2_service = Sso_Facebook_Init::get_facebook_oauth2_service();
	}

	/**
	 * Login with facebook.
	 */
	public function facebook_login() {
		global $eb_user_id;
		$helper = $this->fb_client->getRedirectLoginHelper();

		$user_data = null;
		// Try to get access token.
		try {
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				//check ip from share internet
				$ip_addr = $_SERVER['HTTP_CLIENT_IP'];
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//to check ip is pass from proxy
				$ip_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$ip_addr = $_SERVER['REMOTE_ADDR'];
			}
			if ( empty( $eb_user_id ) ) {
				$eb_user_id = get_transient( 'eb_user_' . $ip_addr . '_eb_user_id' );
			}
			if ( empty( $eb_user_id ) ) {
				$eb_user_id = bin2hex(openssl_random_pseudo_bytes(16));
			}
			set_transient(
				'eb_user_' . $ip_addr . '_eb_user_id',
				$eb_user_id,
				HOUR_IN_SECONDS
			);

			$facebook_access_token = get_transient('eb_user_' . $eb_user_id . '_facebook_access_token');
			// Already login.
			if ( ! empty( $facebook_access_token ) ) {
				$access_token = $facebook_access_token;
			} else {
				$access_token = $helper->getAccessToken();
			}

			if ( isset( $access_token ) ) {

				if ( ! empty( $facebook_access_token ) ) {
					$this->fb_client->setDefaultAccessToken( $facebook_access_token );
				} else {
					// Put short-lived access token in session.
					$facebook_access_token = (string) $access_token;
					set_transient(
						'eb_user_' . $eb_user_id . '_facebook_access_token',
						(string) $access_token,
						HOUR_IN_SECONDS
					);

					// OAuth 2.0 client handler helps to manage access tokens.
					$this->fb_oauth2_service = $this->fb_client->getOAuth2Client();

					// Exchanges a short-lived access token for a long-lived one.
					$long_lived_access_token           = $this->fb_oauth2_service->getLongLivedAccessToken( $facebook_access_token );
					$facebook_access_token = (string) $long_lived_access_token;
					set_transient(
						'eb_user_' . $eb_user_id . '_facebook_access_token',
						$facebook_access_token,
						HOUR_IN_SECONDS
					);

					// Set default access token to be used in script.
					$this->fb_client->setDefaultAccessToken( $facebook_access_token );
				}

				// Redirect the user back to the same page if url has "code" parameter in query string.
				if ( isset( $_GET['code'] ) ) { // @codingStandardsIgnoreLine

					// Getting user facebook profile info.
					try {

						$profile_request = $this->fb_client->get( '/me?fields=name,first_name,last_name,email,link,gender,locale,picture' );

						$fb_user_profile = $profile_request->getGraphNode()->asArray();
						$picture         = getArrayDataByIndex( $fb_user_profile, 'picture' );
						$fb_user_data    = array(
							'oauth_provider' => 'facebook',
							'oauth_uid'      => getArrayDataByIndex( $fb_user_profile, 'id' ),
							'first_name'     => getArrayDataByIndex( $fb_user_profile, 'first_name' ),
							'last_name'      => getArrayDataByIndex( $fb_user_profile, 'last_name' ),
							'email'          => getArrayDataByIndex( $fb_user_profile, 'email' ),
							'gender'         => getArrayDataByIndex( $fb_user_profile, 'gender' ),
							'locale'         => getArrayDataByIndex( $fb_user_profile, 'locale' ),
							'picture'        => getArrayDataByIndex( $picture, 'url' ),
							'link'           => getArrayDataByIndex( $fb_user_profile, 'link' ),
						);
						$user_manager    = new Sso_Social_Login_User_Manager( $this->plugin_name, $this->version );
						$redirect        = $this->get_state();
						// Remove the stored session data in options table.
						$this->reset_facebbok_session_data( $eb_session_id );

						$user_data = $user_manager->check_user_details( $fb_user_data, $redirect );
					} catch ( FacebookResponseException $e ) {
						$this->eb_logger->logger()->add( 'SSO Log: Facebook login Failed to fetch user profile data.' . serialize( $e->getMessage() ) ); // @codingStandardsIgnoreLine
						// Remove the stored session data in options table.
						$this->reset_facebbok_session_data();

						auth_redirect();
						exit;
					} catch ( FacebookSDKException $e ) {
						$this->eb_logger->logger()->add( 'SSO Log: Facebook login Failed.' . serialize( $e->getMessage() ) ); // @codingStandardsIgnoreLine
						// Remove the stored session data in options table.
						$this->reset_facebbok_session_data();

						auth_redirect();
						exit;
					}
				}
			}
		} catch ( FacebookResponseException $e ) {
			$this->eb_logger->logger()->add( 'SSO Log: Facebook login Failed got facebook responce exception.' . serialize( $e->getMessage() ) ); // @codingStandardsIgnoreLine
			// Remove the stored session data in options table.
			$this->reset_facebbok_session_data();

			session_destroy();
			// Redirect user back to app login page.
			auth_redirect();
			exit;
		} catch ( FacebookSDKException $e ) {
			$this->eb_logger->logger()->add( 'SSO Log: Facebook login Failed got facebook SDK exception.' . serialize( $e->getMessage() ) ); // @codingStandardsIgnoreLine
			// Remove the stored session data in options table.
			$this->reset_facebbok_session_data();

			auth_redirect();
			exit;
		}
	}

	/**
	 * Get the state data.
	 */
	private function get_state() {
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
		}
		$fb_session_data = maybe_unserialize( get_option( $eb_user_id ) );
		// Below is the code to get the state data from memory.
		if ( isset( $fb_session_data['state-data'] ) ) {
			$state = base64_decode( $fb_session_data['state-data'] ); // @codingStandardsIgnoreLine
			$state = json_decode( $state );
			return $state;
		}
	}

	/**
	 * Remove option data saved for facebook login where session id is the key.
	 */
	public function reset_facebbok_session_data() {
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
		}
		if ( ! empty( $eb_user_id ) ) {
			delete_option( $eb_user_id );
		}
	}
}

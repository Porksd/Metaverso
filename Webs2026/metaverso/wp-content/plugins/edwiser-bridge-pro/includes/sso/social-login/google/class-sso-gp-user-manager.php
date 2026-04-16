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
 * SSO_Gp_User_Manager class.
 *
 * @package EdwiserBridgePro/includes/sso
 */
class Sso_Gp_User_Manager {
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
	 * Google Client.
	 *
	 * @var string
	 */
	private $google_client;

	/**
	 * Google Oauth2 Service.
	 *
	 * @var string
	 */
	private $g_oauth2_service;

	/**
	 * Logger.
	 *
	 * @var string
	 */
	private $eb_logger;

	/**
	 * Constructor.
	 *
	 * @param string $lugin_name Plugin name.
	 * @param string $version    Plugin version.
	 */
	public function __construct( $lugin_name, $version ) {
		$this->lugin_name       = $lugin_name;
		$this->version          = $version;
		$this->eb_logger        = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();
		$this->google_client    = Sso_Google_Plus_Init::get_google_client();
		$this->g_oauth2_service = Sso_Google_Plus_Init::get_google_oauth2_service();
	}

	/**
	 * Login with google.
	 */
	public function google_login() {
		global $eb_user_id;
		$user_data = null;
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
			// Process Google OAuth2 callback
			if ( isset( $_GET['code'] ) && $this->is_google_oauth_callback() ) { // @codingStandardsIgnoreLine
				try {
					// Validate the authorization code before using it
					$auth_code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
					if ( empty( $auth_code ) ) {
						return;
					}
										
					// Let the Google OAuth2 library handle the actual validation
					$this->google_client->authenticate( $auth_code );
					$token = $this->google_client->getAccessToken();
					
					if ( ! empty( $token ) ) {
						set_transient(
							'eb_user_' . $eb_user_id . '_token',
							$token,
							HOUR_IN_SECONDS
						);
					}
				} catch ( Exception $e ) {
					// Clear any existing transients to force re-authentication
					delete_transient( 'eb_user_' . $eb_user_id . '_token' );
					return;
				}
			} else {
				// Debug why we're not processing
				if ( isset( $_GET['code'] ) ) {
					$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
				}
				return;
			}

			if ( ! empty( $token ) ) {
				try {
					$this->google_client->setAccessToken( $token );
				} catch ( Exception $e ) {
					$this->eb_logger->logger()->add( 'SSO Log: Google OAuth login access token exception' . serialize( $e->getMessage() ) ); // @codingStandardsIgnoreLine
				}
			}
			if ( $this->google_client->getAccessToken() ) {
				try {
					// Get user profile data from google.
					$gp_user_profile = $this->g_oauth2_service->userinfo->get();
					$gp_user_data    = array(
						'oauth_provider' => 'google',
						'oauth_uid'      => getArrayDataByIndex( $gp_user_profile, 'id' ),
						'first_name'     => getArrayDataByIndex( $gp_user_profile, 'given_name' ),
						'last_name'      => getArrayDataByIndex( $gp_user_profile, 'family_name' ),
						'email'          => getArrayDataByIndex( $gp_user_profile, 'email' ),
						'gender'         => getArrayDataByIndex( $gp_user_profile, 'gender' ),
						'locale'         => getArrayDataByIndex( $gp_user_profile, 'locale' ),
						'picture'        => getArrayDataByIndex( $gp_user_profile, 'picture' ),
						'link'           => getArrayDataByIndex( $gp_user_profile, 'link' ),
					);
				} catch ( Exception $e ) {
					$this->eb_logger->logger()->add( 'SSO Log: Google OAuth login failed to fetch google OAuth profile data.' . serialize( $e->getMessage() ) ); // @codingStandardsIgnoreLine
					auth_redirect();
					exit();
				}
				$user_manager = new Sso_Social_Login_User_Manager( $this->lugin_name, $this->version );
				$redirect     = $this->get_state();
				$user_data    = $user_manager->check_user_details( $gp_user_data, $redirect );
			}
		} catch ( Exception $e ) {
			$this->eb_logger->logger()->add( 'SSO Log: Google OAuth login failed with exception: ' . serialize( $e->getMessage() ) ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Get state.
	 *
	 * @return string
	 */
	private function get_state() {
		if ( isset( $_GET['state'] ) ) { // @codingStandardsIgnoreLine
			$state = base64_decode( $_GET['state'] ); // @codingStandardsIgnoreLine
			$state = json_decode( $state );
			return $state;
		} else {
			return get_site_url();
		}
	}

	/**
	 * Check if this is a Google OAuth2 callback.
	 *
	 * @return bool
	 */
	private function is_google_oauth_callback() {
		// Check if we have the required parameters for Google OAuth2
		if ( ! isset( $_GET['code'] ) || isset( $_GET['error'] ) ) {
			return false;
		}

		$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
		
		// Google OAuth2 codes are typically longer and have specific patterns
		// Skip very short codes that are likely not OAuth
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
		// Google OAuth codes usually contain alphanumeric characters, hyphens, and underscores
		if ( preg_match( '/^[A-Za-z0-9\-_]+$/', $code ) && strlen( $code ) >= 20 ) {
			return true;
		}

		// If we can't determine, be conservative and don't process
		return false;
	}

}

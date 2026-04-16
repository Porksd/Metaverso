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

if ( ! function_exists( 'generateMoodleUrl' ) ) {

	/**
	 * GenerateMoodleUrl() used for generating moodle login url.
	 *
	 * @since 1.0.0
	 * @param array $query query array.
	 */
	function generateMoodleUrl( $query = array() ) {
		$final_url = get_site_url();
		if ( isset( $query['moodle_course_id'] ) ) {
			$final_url .= '?mdl_course_id=' . $query['moodle_course_id'];
		}
		return $final_url;
	}
}

if ( ! function_exists( '\ebsso\eb_generate_moodle_url' ) ) {

	/**
	 * Generate moodle url.
	 *
	 * @param array $query query array.
	 */
	function eb_generate_moodle_url( $query = array() ) {
		// encode array as querystring.
		$details        = http_build_query( $query );
		$eb_moodle_url  = eb_get_mdl_url();
		$sso_secret_key = eb_get_mdl_token();

		if ( '' == $eb_moodle_url && '' == $sso_secret_key ) { // @codingStandardsIgnoreLine
			return __( 'Something went wrong', 'edwiser-bridge-pro' );
		}

		$final_url = $eb_moodle_url . EB_MOODLE_PLUGIN_URL . encryptString( $details, $sso_secret_key );

		return $final_url;
	}
}


if ( ! function_exists( '\ebsso\eb_get_mdl_url' ) ) {

	/**
	 * Get moodle url.
	 */
	function eb_get_mdl_url() {
		$connection_options = get_option( 'eb_connection' );
		$eb_moodle_url      = isset( $connection_options['eb_url'] ) ? $connection_options['eb_url'] : '';

		if ( empty( $eb_moodle_url ) ) {
			$eb_moodle_url = false;
		}

		if ( substr( $eb_moodle_url, -1 ) == '/' ) { // @codingStandardsIgnoreLine
			$eb_moodle_url = substr( $eb_moodle_url, 0, -1 );
		}

		return $eb_moodle_url;
	}
}



if ( ! function_exists( '\ebsso\eb_get_mdl_token' ) ) {

	/**
	 * Get moodle token.
	 */
	function eb_get_mdl_token() {
		$sso_data       = get_option( 'eb_sso_settings_general' );
		$sso_secret_key = isset( $sso_data['eb_sso_secret_key'] ) ? $sso_data['eb_sso_secret_key'] : '';

		if ( empty( $sso_secret_key ) ) {
			$sso_secret_key = false;
		}

		return $sso_secret_key;
	}
}

/*
* Used for genrating moodle logout url.
*
* @since    1.0.0
*/
if ( ! function_exists( '\ebsso\generateMoodleLogoutUrl' ) ) {

	/**
	 * Generate moodle logout url.
	 *
	 * @param array $query query array.
	 */
	function generateMoodleLogoutUrl( $query = array() ) {
		// encode array as querystring.
		$details            = http_build_query( $query );
		$connection_options = get_option( 'eb_connection' );
		$eb_moodle_url      = isset( $connection_options['eb_url'] ) ? $connection_options['eb_url'] : '';
		$sso_data           = get_option( 'eb_sso_settings_general' );
		$sso_secret_key     = isset( $sso_data['eb_sso_secret_key'] ) ? $sso_data['eb_sso_secret_key'] : '';
		if ( '' == $eb_moodle_url && '' == $sso_secret_key ) { // @codingStandardsIgnoreLine
			return __( 'Something went wrong', 'edwiser-bridge-pro' );
		}
		$final_url = $eb_moodle_url . EB_MOODLE_LOGOUT_URL . encryptString( $details, $sso_secret_key );

		return $final_url;
	}
}

/*
* encrypt moodle url with value as data and key as encyption key.
*
* @since 1.0.0
*/
if ( ! function_exists( '\ebsso\encryptString' ) ) {

	/**
	 * Encrypt string.
	 *
	 * @param string $value value.
	 * @param string $key key.
	 */
	function encryptString( $value, $key ) {
		if ( ! $value ) {
			return '';
		}

		$token         = $value; // The value to be encrypted
		$enc_method    = 'AES-256-ECB'; // The encryption method (AES-256 with ECB mode)
		$enc_key       = openssl_digest( $key, 'SHA256', true ); // Hash the encryption key to 256 bits using SHA-256
		// $enc_iv        = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $enc_method ) ); // ECB mode does not require an IV, so this line is commented out
		$crypted_token = openssl_encrypt( $token, $enc_method, $enc_key, 0 ); // Encrypt the token using AES-256-ECB, no IV needed

		$data = base64_encode( $crypted_token ); // @codingStandardsIgnoreLine // Encode the encrypted token in Base64 format
		
		// Convert the Base64 encoded string to URL-safe Base64 by replacing characters
		$data = str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), $data );

		return trim( $data ); // Return the URL-safe Base64-encoded encrypted token
	}
}

/*
* Decrypt query argument.
*
* @since 1.2
*/
if ( ! function_exists( '\ebsso\getDecryptedQueryArgs' ) ) {

	/**
	 * Get decrypted query args.
	 *
	 * @param string $base64 base64.
	 * @param string $key key.
	 */
	function getDecryptedQueryArgs( $base64, $key ) {
		$data = str_replace( array( '-', '_' ), array( '+', '/' ), $base64 ); // Convert URL-safe Base64 back to standard Base64
		$mod4 = strlen( $data ) % 4; // Check if padding is needed
		if ( $mod4 ) {
			$data .= substr( '====', $mod4 ); // Add the necessary padding
		}

		// Decode the Base64 data
		$crypttext = base64_decode( $data ); // @codingStandardsIgnoreLine

		// if ( preg_match( '/^(.*)::(.*)$/', $crypttext, $regs ) ) {
		// 	list(, $crypted_token, $enc_iv) = $regs;
		// 	$enc_method                     = 'AES-128-CTR';
		// 	$enc_key                        = openssl_digest( $key, 'SHA256', true );
		// 	$decrypted_data                 = openssl_decrypt( $crypted_token, $enc_method, $enc_key, 0, hex2bin( $enc_iv ) );
		// }

		// AES-256-ECB does not use an IV, so there's no need to split or handle IV here.
		$enc_method = 'AES-256-ECB'; // Encryption method
		$enc_key = openssl_digest( $key, 'SHA256', true ); // Hash the encryption key to 256 bits

		// Decrypt the ciphertext
		$decrypted_data = openssl_decrypt( $crypttext, $enc_method, $enc_key, 0 );

		// Trim the decrypted data to remove extra spaces or characters
		$decrypted_args = trim( $decrypted_data );

		// Return the decrypted result
		return $decrypted_args;
	}
}

/*
* Function to return query argument from given string.
*
* @since 1.2
*/
if ( ! function_exists( '\ebsso\getKeyValue' ) ) {

	/**
	 * Get key value.
	 *
	 * @param string $string string.
	 * @param string $key key.
	 */
	function getKeyValue( $string, $key ) {
		$key  = $key;
		$list = explode( '&', str_replace( '&amp;', '&', $string ) );
		foreach ( $list as $pair ) {
			$item = explode( '=', $pair );
			if ( strtolower( $key ) === strtolower( $item[0] ) ) {
				return urldecode( $item[1] );
			}
		}
		return '';
	}
}

/*
* Function to trigger logout.
*
* @since 1.2
*/
if ( ! function_exists( '\ebsso\triggerLogout' ) ) {

	/**
	 * Trigger logout.
	 *
	 * @param string $mdl_uid moodle user id.
	 */
	function triggerLogout( $mdl_uid ) {
		if ( is_user_logged_in() ) {
			$wp_mdl_uid = get_user_meta( get_current_user_id(), 'moodle_user_id', true );
			if ( $wp_mdl_uid && $wp_mdl_uid == $mdl_uid ) { // @codingStandardsIgnoreLine
				wp_logout();
			}
		}
	}
}

/*
* Function to trigger login.
*
* @since 1.2
*/
if ( ! function_exists( '\ebsso\triggerLogin' ) ) {

	/**
	 * Trigger login.
	 *
	 * @param string $mdl_uid moodle user id.
	 * @param string $mdl_email moodle user email.
	 */
	function triggerLogin( $mdl_uid, $mdl_email ) {
		if ( is_user_logged_in() ) {
			return;
		}

		$user = get_user_by( 'email', $mdl_email );

		if ( is_object( $user ) ) {
			$wp_mdl_uid = get_user_meta( $user->ID, 'moodle_user_id', true );
			if ( $wp_mdl_uid && $wp_mdl_uid == $mdl_uid ) { // @codingStandardsIgnoreLine
				setLoginData( $user );
			}
		}
	}
}

if ( ! function_exists( "\ebsso\setLoginData" ) ) {

	/**
	 * Set login data.
	 *
	 * @param string $user user.
	 */
	function setLoginData( $user ) {
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );
	}
}

if ( ! function_exists( '\ebsso\createEbUser' ) ) {

	/**
	 * Create edwiser bridge user.
	 *
	 * @param array $user_data user data.
	 */
	function createEbUser( $user_data ) {
		$first_name = isset( $user_data['first_name'] ) ? $user_data['first_name'] : '';
		$last_name  = isset( $user_data['last_name'] ) ? $user_data['last_name'] : '';
		$eb_loader  = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();

		$eb_user_mang = \app\wisdmlabs\edwiserBridge\Eb_User_Manager::instance( $eb_loader->getPluginName(), $eb_loader->getVersion() );

		$user_id = $eb_user_mang->createWordpressUser( $user_data['email'], $first_name, $last_name );
	}
}

if ( ! function_exists( '\ebsso\getArrayDataByIndex' ) ) {

	/**
	 * Get array data by index.
	 *
	 * @param array  $data data.
	 * @param string $key key.
	 * @param string $value value.
	 */
	function getArrayDataByIndex( $data, $key, $value = '' ) {
		if ( isset( $data[ $key ] ) ) {
			$value = $data[ $key ];
		}
		return $value;
	}
}

if ( ! function_exists( '\ebsso\getSocialRedirect' ) ) {

	/**
	 * Get social redirect.
	 *
	 * @param array  $get get.
	 * @param string $redirect_url redirect url.
	 */
	function getSocialRedirectToURL( $get, $redirect_url = '' ) {
		$post_content = null;
		if ( isset( $_SERVER['HTTP_REFERER'] ) && filter_var( $_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL ) ) { // @codingStandardsIgnoreLine
			$postid       = url_to_postid( $_SERVER['HTTP_REFERER'] ); // @codingStandardsIgnoreLine
			$post_content = $postid ? get_post( $postid )->post_content : null;
		}
		$redirect_url = getRedirectUrl( $get, $post_content, $redirect_url );

		foreach ( $get as $key => $value ) {
			if ( 'is_enroll' === $key ) {
				$key = 'auto_enroll';
			}
			// sub url like /wordpres/user-account is shown in parameter q so skipping it.
			if ( 'q' === $key ) {
				continue;
			}
			$redirect_url = add_query_arg( $key, $value, $redirect_url );
		}
		$redirect_url = apply_filters( 'eb_sso_set_social_login_redirect_url', $redirect_url );
		$state        = json_encode( $redirect_url ); // @codingStandardsIgnoreLine
		$state        = base64_encode( $state ); // @codingStandardsIgnoreLine
		return $state;
	}
}

/**
 * Get redirect url.
 *
 * @param array  $get get.
 * @param string $post_content post content.
 * @param string $redirect_url redirect url.
 */
function getRedirectUrl( $get, $post_content, $redirect_url = '' ) {
	if ( isset( $post_content ) && has_shortcode( $post_content, 'woocommerce_my_account' ) && ! isset( $_GET['mdl_course_id'] ) ) { // @codingStandardsIgnoreLine
		$redirect_url = $_SERVER['HTTP_REFERER']; // @codingStandardsIgnoreLine
	} elseif ( isset( $get['redirect_to'] ) && filter_var( $get['redirect_to'], FILTER_VALIDATE_URL ) ) {
		$redirect_url = $get['redirect_to'];
	} elseif ( isset( $get['redirect'] ) && filter_var( $get['redirect'], FILTER_VALIDATE_URL ) ) {
		$redirect_url = $get['redirect'];
		unset( $get['redirect'] );
	}
	return $redirect_url;
}

if ( ! function_exists( 'eb_get_wp_user_id_from_moodle_id' ) ) {
	/**
	 * FUnction accptes moodle user id and returns WordPress user id and if not exists then false
	 *
	 * @param text $mdl_user_id mdl_user_id.
	 */
	function eb_get_wp_user_id_from_moodle_id( $mdl_user_id ) {
		if ( isset( $mdl_user_id ) && ( empty( $mdl_user_id ) || ! is_numeric( $mdl_user_id ) ) ) {
			return false;
		}
		global $wpdb;
		$result = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_value=%d AND meta_key = 'moodle_user_id'", $mdl_user_id ) ); // @codingStandardsIgnoreLine
		return $result;
	}
}



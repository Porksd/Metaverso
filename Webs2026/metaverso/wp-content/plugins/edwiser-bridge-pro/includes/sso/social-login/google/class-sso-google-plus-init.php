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
 * SSO Google Plus Init class.
 *
 * @since 3.0.0
 */
class Sso_Google_Plus_Init {

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
	protected static $g_client = null;

	/**
	 * Google Oauth2 Service.
	 *
	 * @var string
	 */
	protected static $g_oauth2_service = null;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    3.0.0
	 */
	public function load_dependencies() {
		$keys   = array(
			'eb_sso_gp_client_id',
			'eb_sso_gp_secret_key',
			'eb_sso_gp_enable',
		);
		$option = $this->get_setting_data( $keys );
		if ( false === $option || ! $this->check_is_social_login_enabled( $option, 'eb_sso_gp_enable' ) ) {
			return;
		}
		$this->load_google_plus_config( $option );
		include_once 'class-sso-gp-logout-user.php';
		include_once 'class-sso-gp-user-manager.php';
		$gp_logout = new Sso_Gp_Logout_User();
		$gp_logout->init();

		if ( ! is_user_logged_in() && isset( $_GET['code'] ) ) { // @codingStandardsIgnoreLine
			$user_manager = new Sso_Gp_User_Manager( $this->plugin_name, $this->version );
			$user_manager->google_login();
		}
		return true;
	}

	/**
	 * Get setting data.
	 *
	 * @param array $keys Keys array.
	 */
	private function get_setting_data( $keys = array() ) {
		$option = get_option( 'eb_sso_settings_general' );
		if ( false !== $option ) {
			foreach ( $keys as $key ) {
				if ( ! $this->check_is_set( $option, $key ) ) {
					$option = false;
				}
			}
		}
		return $option;
	}

	/**
	 * Check if social login is enabled.
	 *
	 * @param array  $data Data array.
	 * @param string $key  Key.
	 */
	private function check_is_social_login_enabled( $data, $key ) {
		if ( isset( $data[ $key ] ) && 'no' === $data[ $key ] ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if key is set.
	 *
	 * @param array  $data Data array.
	 * @param string $key  Key.
	 */
	private function check_is_set( $data, $key ) {
		$value = false;
		if ( isset( $data[ $key ] ) ) {
			$value = trim( $data[ $key ] );
		}
		if ( empty( $value ) ) {
			$value = false;
		}
		return $value;
	}

	/**
	 * Load config for google.
	 *
	 * @param array $option Option array.
	 */
	private function load_google_plus_config( $option ) {
		if ( false === $option ) {
			return;
		}
		$client_id     = isset( $option['eb_sso_gp_client_id'] ) ? $option['eb_sso_gp_client_id'] : '';
		$client_secret = isset( $option['eb_sso_gp_secret_key'] ) ? $option['eb_sso_gp_secret_key'] : '';

		// Validate OAuth2 configuration
		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return;
		}

		self::include_google_files();
		self::$g_client = new \Google_Client();
		self::$g_client->setClientId( $client_id );
		self::$g_client->setClientSecret( $client_secret );
		self::$g_client->setRedirectUri( home_url() );
		self::$g_client->setScopes( array( 'https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile' ) );
		// Send Client Request.
		self::$g_client->setState( getSocialRedirectToURL( $_GET, '' ) ); // @codingStandardsIgnoreLine

		self::$g_oauth2_service = new \Google_Service_Oauth2( $this->get_google_client() );

	}

	/**
	 * Include google files.
	 */
	public static function include_google_files() {
		require_once 'Google/Config.php';
		require_once 'Google/Service.php';
		require_once 'Google/Task/Runner.php';
		require_once 'Google/Http/REST.php';
		require_once 'Google/Resource.php';
		require_once 'Google/Model.php';
		require_once 'Google/Oauth2.php';
		require_once 'Google/Utils.php';
		require_once 'Google/Http/Request.php';
		require_once 'Google/Auth/Abstract.php';
		require_once 'Google/Exception.php';
		require_once 'Google/Auth/Exception.php';
		require_once 'Google/Auth/OAuth2.php';
		require_once 'Google/Http/CacheParser.php';
		require_once 'Google/IO/Abstract.php';
		require_once 'Google/Task/Retryable.php';
		require_once 'Google/IO/Exception.php';
		require_once 'Google/IO/Curl.php';
		require_once 'Google/Logger/Abstract.php';
		require_once 'Google/Logger/Null.php';
		require_once 'Google/Client.php';
	}

	/**
	 * Get google client.
	 */
	public static function get_google_client() {
		return self::$g_client;
	}

	/**
	 * Get google oauth2 service.
	 */
	public static function get_google_oauth2_service() {
		return self::$g_oauth2_service;
	}

	/**
	 * Add google login button.
	 */
	public function add_google_login_button() {
		if ( null === self::$g_client ) {
			return;
		}
		$auth_url = self::$g_client->createAuthUrl();
		ob_start();
		?>
		<a href="<?php echo filter_var( $auth_url, FILTER_SANITIZE_URL ); ?>">
			<img  class="eb-sso-social-login-icon" src="<?php echo esc_url( EB_PRO_PLUGIN_URL . 'public/assets/images/ic_google_plus.jpg' ); ?>"/>
		</a>
		<?php
		$login = ob_get_clean();
		return $login;
	}
}

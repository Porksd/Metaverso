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

/**
 * Note : Below are the instructions for replacing facebook SDK.
 * In Facebook SDK we used custom data set and get functions.
 * src -> PersistentData -> FacebookMemoryPersistentDataHandler.php This is the file where all memory related data operations are handled.
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\sso;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This is not the way to call me!' );
}

/**
 * SSO Facebook Init class.
 */
class Sso_Facebook_Init {

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
	protected static $fb_client = null;

	/**
	 * FB Client Helper.
	 *
	 * @var string
	 */
	protected static $fb_client_helper = null;

	/**
	 * FB Oauth2 Service.
	 *
	 * @var string
	 */
	protected static $fb_oauth2_service = null;

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
			'eb_sso_fb_app_id',
			'eb_sso_fb_app_secret_key',
			'eb_sso_fb_enable',
		);
		$option = $this->get_setting_data( $keys );
		if ( false === $option || ! $this->check_is_social_login_enabled( $option, 'eb_sso_fb_enable' ) ) {
			return;
		}

		$this->load_facebook_plus_config( $option );

		include_once 'class-sso-fb-user-manager.php';
		include_once 'class-sso-fb-logout-user.php';
		$fb_logout = new Sso_Fb_Logout_User();
		$fb_logout->init();

		if ( ! is_user_logged_in() && isset( $_GET['action'] ) && $_GET['action'] == 'facebook_login' ) { // @codingStandardsIgnoreLine

			$fb_user_mang = new Sso_Fb_User_Manager( $this->plugin_name, $this->version );
			$fb_user_mang->facebook_login();
		}
		return true;
	}

	/**
	 * Load config for facebook.
	 *
	 * @param array $option Option array.
	 */
	private function load_facebook_plus_config( $option ) {
		if ( false === $option ) {
			return;
		}

		$app_id     = $option['eb_sso_fb_app_id'];
		$app_secret = $option['eb_sso_fb_app_secret_key'];

		include_once 'src/autoload.php';

		self::$fb_client         = new \Facebook\Facebook(
			array(
				'app_id'                  => $app_id,
				'app_secret'              => $app_secret,
				'default_graph_version'   => 'v2.10',
				'persistent_data_handler' => 'memory',
			)
		);
		self::$fb_client_helper  = self::$fb_client->getRedirectLoginHelper();
		self::$fb_oauth2_service = self::$fb_client->getOAuth2Client();
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
	 * Get facebook client.
	 */
	public static function get_faceboook_client() {
		return self::$fb_client;
	}

	/**
	 * Get facebook client helper.
	 */
	public static function get_faceboook_client_helper() {
		return self::$fb_client_helper;
	}

	/**
	 * Get facebook oauth2 service.
	 */
	public static function get_facebook_oauth2_service() {
		return self::$fb_oauth2_service;
	}

	/**
	 * Add facebook login button.
	 */
	public function add_facebook_login_button() {

		if ( null === self::$fb_client_helper ) {
			return;
		}
		$permissions = array( 'email' ); // Optional permissions.
		$state       = getSocialRedirectToURL( $_GET, '' ); // @codingStandardsIgnoreLine

		// Here we will store data in the Memory.
		$persistent_data_handler = self::$fb_client_helper->getPersistentDataHandler();

		$persistent_data_handler->set( 'state-data', $state );

		$url = get_site_url() . '/?action=facebook_login';

		$login_url = self::$fb_client_helper->getLoginUrl( $url, $permissions );

		$this->get_faceboook_client();
		ob_start();
		?>
		<a href="<?php echo filter_var( $login_url, FILTER_SANITIZE_URL ); ?>">
			<img  class="eb-sso-social-login-icon" src="<?php echo esc_url( EB_PRO_PLUGIN_URL . 'public/assets/images/facebook.png' ); ?>"/>
		</a>
		<?php
		$login = ob_get_clean();
		return $login;
	}
}

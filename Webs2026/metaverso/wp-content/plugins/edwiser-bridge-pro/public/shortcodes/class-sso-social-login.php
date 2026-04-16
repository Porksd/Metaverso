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
 * SSO Social Login class.
 */
class Sso_Social_Login {

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
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Check if google plus is enabled.
	 */
	public function ebsso_check_if_google_plus_enabled() {
		$keys   = array(
			'eb_sso_gp_client_id',
			'eb_sso_gp_secret_key',
			'eb_sso_gp_enable',
		);
		$option = $this->get_setting_data( $keys );
		if ( false === $option || ! $this->check_is_social_login_enabled( $option, 'eb_sso_gp_enable' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if facebook is enabled.
	 */
	public function ebsso_check_if_fb_enabled() {

		$keys   = array(
			'eb_sso_fb_app_id',
			'eb_sso_fb_app_secret_key',
			'eb_sso_fb_enable',
		);
		$option = $this->get_setting_data( $keys );
		if ( false === $option || ! $this->check_is_social_login_enabled( $option, 'eb_sso_fb_enable' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Get the setting data.
	 *
	 * @param array $keys Array of keys.
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
	 * Check if the value is set.
	 *
	 * @param array  $data Array of data.
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
	 * Check if the social login is enabled.
	 *
	 * @param array  $data Array of data.
	 * @param string $key  Key.
	 */
	private function check_is_social_login_enabled( $data, $key ) {
		if ( isset( $data[ $key ] ) && 'no' === $data[ $key ] ) {
			return false;
		}
		return true;
	}


	/**
	 * Function responsible for the social icons on the WordPress login page and the user-account page.
	 *
	 * @param array $attr Attributes.
	 * @return string
	 */
	public function output( $attr ) {
		$google_login = new Sso_Google_Plus_Init( $this->plugin_name, $this->version );
		$fb_login     = new Sso_Facebook_Init( $this->plugin_name, $this->version );
		$sso_settings = get_option( 'eb_sso_settings_general' );

		if ( ! isset( $attr['page'] ) ) {
			$attr = array(
				'page' => 'shortcode',
			);
		}
		if ( is_user_logged_in() ) {
			return;
		}

		if ( isset( $attr['page'] ) && 'user-account' === $attr['page'] ) {
			ob_start();
			?>
			<div>
				<ul class="eb-sso-cont-login-btns">
					<li>
					<?php

					if ( $google_login->load_dependencies() && isset( $sso_settings['eb_sso_gp_enable'] ) && ( 'both' === $sso_settings['eb_sso_gp_enable'] || 'user_account' === $sso_settings['eb_sso_gp_enable'] ) ) {
						echo $google_login->add_google_login_button(); // @codingStandardsIgnoreLine
					}
					?>
					</li>
					<li>
					<?php

					if ( $fb_login->load_dependencies() && isset( $sso_settings['eb_sso_fb_enable'] ) && ( 'both' === $sso_settings['eb_sso_fb_enable'] || 'user_account' === $sso_settings['eb_sso_fb_enable'] ) ) {
						echo $fb_login->add_facebook_login_button(); // @codingStandardsIgnoreLine
					}
					?>
					</li>
					<?php do_action( 'eb_sso_add_more_social_login_options_user_accnt_page' ); ?>
				</ul>
			</div>
			<?php
			return ob_get_clean();
		}  elseif ( isset( $attr['page'] ) && 'wp-login' === $attr['page'] ) {
			ob_start();
			?>
			<div>
				<ul class="eb-sso-cont-login-btns">
					<li>
					<?php

					if ( $google_login->load_dependencies() && isset( $sso_settings['eb_sso_gp_enable'] ) && ( 'both' === $sso_settings['eb_sso_gp_enable'] || 'wp_login_page' === $sso_settings['eb_sso_gp_enable'] ) ) {
						echo $google_login->add_google_login_button(); // @codingStandardsIgnoreLine
					}
					?>
					</li>
					<li>
					<?php

					if ( $fb_login->load_dependencies() && isset( $sso_settings['eb_sso_fb_enable'] ) && ( 'both' === $sso_settings['eb_sso_fb_enable'] || 'wp_login_page' === $sso_settings['eb_sso_fb_enable'] ) ) {
						echo $fb_login->add_facebook_login_button(); // @codingStandardsIgnoreLine
					}
					?>
					</li>
					<?php do_action( 'eb_sso_add_more_social_login_options_wp_login_page' ); ?>
				</ul>
			</div>

			<?php
			return ob_get_clean();
		} elseif ( isset( $attr['page'] ) && 'shortcode' === $attr['page'] ) {
			ob_start();
			?>
			<div>
				<ul class="eb-sso-cont-login-btns">
					<li>
					<?php

					if ( $google_login->load_dependencies() ) {
						echo $google_login->add_google_login_button(); // @codingStandardsIgnoreLine
					}
					?>
					</li>
					<li>
					<?php

					if ( $fb_login->load_dependencies() ) {
						echo $fb_login->add_facebook_login_button(); // @codingStandardsIgnoreLine
					}
					?>
					</li>
					<?php do_action( 'eb_sso_add_more_social_login_options_wp_login_page' ); ?>
				</ul>
			</div>

			<?php
			return ob_get_clean();
		}
	}
}

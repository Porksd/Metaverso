<?php
/**
 * SSO Module
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

/*
 * EDW General Settings
 *
 * @link       https://edwiser.org
 * @since      1.0.0
 *
 * @package    Edwiser Bridge
 * @subpackage Edwiser Bridge/admin
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sso_Settings_Social_Login' ) ) {

	/**
	 * Bridge_Woocommerce_Settings.
	 */
	class Sso_Settings_Social_Login {

		/**
		 * Get social login settings.
		 */
		public function get_social_login_settings() {
			global $current_tab;
			$option = get_option( 'eb_' . $current_tab );

			$settings = apply_filters(
				'sso_social_login_settings_fields',
				array(
					array(
						'title' => __( 'Social Login Settings', 'edwiser-bridge-pro' ),
						'type'  => 'title',
						'id'    => 'sso_social_login_settings',
						'class' => 'sso-social-login-settings',
					),
					array(
						'title'    => __( 'Google OAuth login ', 'edwiser-bridge-pro' ),
						'desc'     => '<br/>' .
							__(
								'Select page on which you want to enable Google OAuth login',
								'edwiser-bridge-pro'
							),
						'id'       => 'eb_sso_gp_enable',
						'type'     => 'select',
						'default'  => __( 'Select Page', 'edwiser-bridge-pro' ),
						'css'      => 'min-width:300px;',
						'options'  => array(
							'no'            => __( 'Disable', 'edwiser-bridge-pro' ),
							'user_account'  => __( 'Edwiser user-account page', 'edwiser-bridge-pro' ),
							'wp_login_page' => __( 'WP login page', 'edwiser-bridge-pro' ),
							'both'          => __( 'Edwiser user-account and WP login page', 'edwiser-bridge-pro' ),
						),
						'desc_tip' => __(
							'This enables Google OAuth login on the selected page.',
							'edwiser-bridge-pro'
						),
					),
					array(
						'type' => 'sectionend',
						'id'   => 'sso_social_login_settings',
					),
					array(
						'title' => '',
						'type'  => 'title',
						'id'    => 'sso_gp_settings',
						'class' => 'sso-gp-settings',
					),

					array(
						'title'    => __( 'Client Id', 'edwiser-bridge-pro' ),
						'desc'     => __( 'Enter your google OAuth client id here.', 'edwiser-bridge-pro' ),
						'id'       => 'eb_sso_gp_client_id',
						'default'  => $this->get_option_value( $option, 'eb_sso_gp_client_id' ),
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Client Secret', 'edwiser-bridge-pro' ),
						'desc'     => __( 'Enter your google OAuth app secret key here.', 'edwiser-bridge-pro' ),
						'id'       => 'eb_sso_gp_secret_key',
						'default'  => $this->get_option_value( $option, 'eb_sso_gp_secret_key' ),
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'sso_gp_settings',
					),
					array(
						'title' => '',
						'type'  => 'title',
						'id'    => 'sso-fb-settings',
						'class' => 'sso-fb-settings',
					),
					array(
						'title'    => __( 'Facebook login ', 'edwiser-bridge-pro' ),
						'desc'     => '<br/>' .
							__(
								'Select page on which you want to enable Facebook login',
								'edwiser-bridge-pro'
							),
						'id'       => 'eb_sso_fb_enable',
						'type'     => 'select',
						'default'  => __( 'Select Page', 'edwiser-bridge-pro' ),
						'css'      => 'min-width:300px;',
						'options'  => array(
							'no'            => __( 'Disable', 'edwiser-bridge-pro' ),
							'user_account'  => __( 'Edwiser user-account page', 'edwiser-bridge-pro' ),
							'wp_login_page' => __( 'WP login page', 'edwiser-bridge-pro' ),
							'both'          => __( 'Edwiser user-account & WP login page', 'edwiser-bridge-pro' ),
						),
						'desc_tip' => __(
							'This enables Facebook OAuth login on the selected page.',
							'edwiser-bridge-pro'
						),
					),
					array(
						'type' => 'sectionend',
						'id'   => '',
					),
					array(
						'title' => '',
						'type'  => 'title',
						'id'    => 'sso_fb_settings',
						'class' => 'sso-fb-settings',
					),
					array(
						'title'    => __( 'App Id', 'edwiser-bridge-pro' ),
						'desc'     => __( 'Enter your facebook app id here.', 'edwiser-bridge-pro' ),
						'id'       => 'eb_sso_fb_app_id',
						'default'  => $this->get_option_value( $option, 'eb_sso_fb_app_id' ),
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'App Secret', 'edwiser-bridge-pro' ),
						'desc'     => __( 'Enter your facebook app Secret key here.', 'edwiser-bridge-pro' ),
						'id'       => 'eb_sso_fb_app_secret_key',
						'default'  => $this->get_option_value( $option, 'eb_sso_fb_app_secret_key' ),
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'sso_fb_settings',
					),
				)
			);
			return $settings;
		}

		/**
		 * Get option value from option array.
		 *
		 * @param array  $data    option array.
		 * @param string $key     option key.
		 * @param string $default default value.
		 */
		private function get_option_value( $data, $key, $default = '' ) {
			if ( isset( $data[ $key ] ) ) {
				return $data[ $key ];
			} else {
				return $default;
			}
		}
	}
}

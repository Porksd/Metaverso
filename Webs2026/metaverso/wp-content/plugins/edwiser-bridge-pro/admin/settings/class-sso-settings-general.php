<?php
/**
 * Selective Sync Module
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sso_Settings_General' ) ) {

	/**
	 * Bridge_Woocommerce_Settings.
	 */
	class Sso_Settings_General {

		/**
		 * Get settings array.
		 */
		public function get_general_settings() {
			global $current_tab;
			$option   = get_option( 'eb_' . $current_tab );
			$settings = apply_filters(
				'sso_social_login_settings_fields',
				array(
					array(
						'title' => __( 'General Settings', 'edwiser-bridge-pro' ),
						'type'  => 'title',
						'id'    => 'sso_options',
					),
					array(
						'title'    => __( 'Secret Key', 'edwiser-bridge-pro' ),
						'desc'     => __( 'Enter your secret key here.', 'edwiser-bridge-pro' ),
						'id'       => 'eb_sso_secret_key',
						'default'  => $this->get_option_value( $option, 'eb_sso_secret_key' ),
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => '',
						'desc'     => '',
						'id'       => 'eb_sso_verify_key',
						'default'  => __( 'Verify token with moodle', 'edwiser-bridge-pro' ),
						'type'     => 'button',
						'desc_tip' => false,
						'class'    => 'button secondary',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'sso_sl_settings',
					),
				)
			);
			return $settings;
		}

		/**
		 * Get option value.
		 *
		 * @param array  $data    Data array.
		 * @param string $key     Key.
		 * @param string $default Default value.
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

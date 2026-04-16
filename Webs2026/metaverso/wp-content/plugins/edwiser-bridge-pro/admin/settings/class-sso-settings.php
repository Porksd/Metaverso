<?php
/**
 * SSO Module
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sso_Settings' ) ) {

	/**
	 * Bridge_Woocommerce_Settings.
	 */
	class Sso_Settings extends \app\wisdmlabs\edwiserBridge\EB_Settings_Page {

		/**
		 * General settings.
		 *
		 * @var array
		 */
		private $general_settings = null;

		/**
		 * Redirection settings.
		 *
		 * @var array
		 */
		private $redirect_settings = null;

		/**
		 * Social login settings.
		 *
		 * @var array
		 */
		private $social_login_settings = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			include_once 'class-sso-settings-general.php';
			include_once 'class-sso-settings-redirection.php';
			include_once 'class-sso-settings-save.php';
			include_once 'class-sso-settings-social-login.php';
			$this->general_settings      = new Sso_Settings_General();
			$this->redirect_settings     = new Sso_Settings_Redirection();
			$this->social_login_settings = new Sso_Settings_Social_Login();
			$this->_id                   = 'sso_settings_general';
			$this->label                 = __( 'Single Sign On', 'edwiser-bridge-pro' );
			add_filter( 'eb_settings_tabs_array', array( $this, 'addSettingsPage' ), 20 );
			add_action( 'eb_settings_' . $this->_id, array( $this, 'output' ) );
			add_action( 'eb_settings_save_' . $this->_id, array( $this, 'save' ) );
			add_action( 'eb_sections_' . $this->_id, array( $this, 'outputSections' ) );
		}

		/**
		 * Get sections.
		 */
		public function getSections() {
			$sections = array(
				''             => __( 'General', 'edwiser-bridge-pro' ),
				'redirection'  => __( 'Redirection', 'edwiser-bridge-pro' ),
				'social_login' => __( 'Social login', 'edwiser-bridge-pro' ),
			);
			return apply_filters( 'eb_get_sections_' . $this->_id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;
			$settings = $this->getSettings( $current_section );
			\app\wisdmlabs\edwiserBridge\Eb_Admin_Settings::outputFields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @param string $current_section Current section.
		 */
		public function getSettings( $current_section = '' ) {
			if ( 'redirection' === $current_section ) {
				$settings = array();
				$this->redirect_settings->get_user_redirection_settings();
			} elseif ( isset( $_GET['section'] ) && 'social_login' === $_GET['section'] ) { // @codingStandardsIgnoreLine.
				$settings = $this->social_login_settings->get_social_login_settings( $_POST ); // @codingStandardsIgnoreLine.
			} else {
				$settings = $this->general_settings->get_general_settings();
			}
			return apply_filters( 'eb_get_settings_' . $this->_id, $settings );
		}

		/**
		 * Save settings.
		 *
		 * @since  1.0.0
		 */
		public function save() {
			if ( empty( $_POST ) ) { // @codingStandardsIgnoreLine.
				return false;
			}
			$save_settings = new Sso_Settings_Save();
			if ( isset( $_GET['section'] ) && 'redirection' === $_GET['section'] ) { // @codingStandardsIgnoreLine.
				$save_settings->save_redirection_settigns( $_POST ); // @codingStandardsIgnoreLine.
			} elseif ( isset( $_GET['section'] ) && 'social_login' === $_GET['section'] ) { // @codingStandardsIgnoreLine.
				$save_settings->save_social_login_settings( $_POST ); // @codingStandardsIgnoreLine.
			} else {
				$save_settings->save_general_settigns( $_POST ); // @codingStandardsIgnoreLine.
			}
		}
	}
}

return new Sso_Settings();

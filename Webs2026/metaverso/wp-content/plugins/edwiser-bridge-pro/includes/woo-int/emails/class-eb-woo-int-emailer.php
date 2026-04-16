<?php
/**
 * Woo Integration Module
 * This class is responsible for Woo Integration module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for Woo Integration module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\wooInt;

use app\wisdmlabs\edwiserBridge as edwiserBridge;

if ( ! class_exists( 'Eb_Woo_Int_Emailer' ) ) {

	/**
	 * Class woo integration emailer
	 */
	class Eb_Woo_Int_Emailer {
		/**
		 * This function send an course enrollment email on order completion
		 *
		 * @param  [Array] $args Arguments array.
		 */
		public function send_course_enrollment_email( $args ) {
			$email_tmpl_data = edwiserBridge\EB_Email_Template::getEmailTmplContent( 'eb_emailtmpl_woocommerce_moodle_course_notifn' );
			$allow_notify    = get_option( 'eb_emailtmpl_woocommerce_moodle_course_notifn_notify_allow' );
			if ( $email_tmpl_data && 'ON' === $allow_notify ) {
				$email_tmpl_obj = new edwiserBridge\EB_Email_Template();
				return $email_tmpl_obj->sendEmail( $args['user_email'], $args, $email_tmpl_data );
			}
		}
	}
}

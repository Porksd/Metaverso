<?php
/**
 * Bulk Purchase Module
 * This class is responsible for Bulk Purchase module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for Bulk Purchase module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use app\wisdmlabs\edwiserBridge as edwiserBridge;

if ( ! class_exists( 'Eb_Bp_Emailer' ) ) {

	/**
	 * Class manages the emial functionality.
	 */
	class Eb_Bp_Emailer {

		/**
		 * Function sends the email on the buulk purchase.
		 *
		 * @param array $args Array of the template data.
		 */
		public function send_bulk_purchase_email( $args ) {
			$email_tmpl_data = edwiserBridge\EB_Email_Template::getEmailTmplContent( 'eb_emailtmpl_bulk_prod_purchase_notifn' );
			$allow_notify    = get_option( 'eb_emailtmpl_bulk_prod_purchase_notifn_notify_allow' );
			if ( $email_tmpl_data && 'ON' === $allow_notify ) {
				$email_tmpl_obj = new edwiserBridge\EB_Email_Template();
				return $email_tmpl_obj->sendEmail( $args['user_email'], $args, $email_tmpl_data );
			}
		}

		/**
		 * Function sends the email on the user cohort enrollment.
		 *
		 * @param array $args Array of the template data.
		 */
		public function send_cohort_enrollment_email( $args ) {
			$email_tmpl_data = edwiserBridge\EB_Email_Template::getEmailTmplContent( 'eb_emailtmpl_student_enroll_in_cohort_notifn' );
			$allow_notify    = get_option( 'eb_emailtmpl_student_enroll_in_cohort_notifn_notify_allow' );
			if ( $email_tmpl_data && 'ON' === $allow_notify ) {
				$email_tmpl_obj = new edwiserBridge\EB_Email_Template();
				return $email_tmpl_obj->sendEmail( $args['user_email'], $args, $email_tmpl_data );
			}
		}

		/**
		 * Function sends the email on user unenrollment from the cohort.
		 *
		 * @param array $args Array of the template data.
		 */
		public function send_cohort_unenrollment_email( $args ) {
			$email_tmpl_data = edwiserBridge\EB_Email_Template::getEmailTmplContent( 'eb_emailtmpl_student_unenroll_in_cohort_notifn' );
			$allow_notify    = get_option( 'eb_emailtmpl_student_unenroll_in_cohort_notifn_notify_allow' );
			if ( $email_tmpl_data && 'ON' === $allow_notify ) {
				$email_tmpl_obj = new edwiserBridge\EB_Email_Template();
				return $email_tmpl_obj->sendEmail( $args['user_email'], $args, $email_tmpl_data );
			}
		}

		/**
		 * Function sends the email on cohort deletion.
		 *
		 * @param array $args Array of the template data.
		 */
		public function bp_send_cohort_delete_email( $args ) {
			$email_tmpl_data = edwiserBridge\EB_Email_Template::getEmailTmplContent( 'eb_emailtmpl_cohort_deletion' );
			$allow_notify    = get_option( 'eb_emailtmpl_cohort_deletion_notify_allow' );
			if ( $email_tmpl_data && 'ON' === $allow_notify ) {
				$email_tmpl_obj = new edwiserBridge\EB_Email_Template();
				return $email_tmpl_obj->sendEmail( $args['user_email'], $args, $email_tmpl_data );
			}
		}

		/**
		 * Function sends the email on the buulk purchase refund.
		 *
		 * @param array $args Array of the template data.
		 */
		public function bp_send_group_refund_email( $args ) {
			$email_tmpl_data = edwiserBridge\EB_Email_Template::getEmailTmplContent( 'eb_emailtmpl_bulk_refund' );
			$allow_notify    = get_option( 'eb_emailtmpl_bulk_refund_notify_allow' );
			if ( $email_tmpl_data && 'ON' === $allow_notify ) {
				$email_tmpl_obj = new edwiserBridge\EB_Email_Template();
				return $email_tmpl_obj->sendEmail( $args['user_email'], $args, $email_tmpl_data );
			}
		}

		/**
		 * Function sends the email on course enrollment if it was bulk product but purchased normally i.e in single quantity.
		 *
		 * @param array $args Array of the template data.
		 */
		public function send_course_enrollment_email( $args ) {
			$email_tmpl_data = edwiserBridge\EB_Email_Template::getEmailTmplContent( 'eb_emailtmpl_woocommerce_moodle_course_notifn' );
			$allow_notify    = get_option( 'eb_emailtmpl_woocommerce_moodle_course_notifn_notify_allow' );
			if ( $email_tmpl_data && 'ON' === $allow_notify ) {
				$email_tmpl_obj = new edwiserBridge\EB_Email_Template();
				return $email_tmpl_obj->sendEmail( $args['user_email'], $args, $email_tmpl_data );
			}
		}

		/**
		 * Function sends the email on new group creation from admin dashboard.
		 *
		 * @param array $args Array of the template data.
		 */
		public function bp_send_group_creation_email( $args ) {
			$email_tmpl_data = edwiserBridge\EB_Email_Template::getEmailTmplContent( 'eb_emailtmpl_new_group_creation' );
			$allow_notify    = get_option( 'eb_emailtmpl_new_group_creation_notify_allow' );
			if ( $email_tmpl_data && 'ON' === $allow_notify ) {
				$email_tmpl_obj = new edwiserBridge\EB_Email_Template();
				return $email_tmpl_obj->sendEmail( $args['user_email'], $args, $email_tmpl_data );
			}
		}

	}
}

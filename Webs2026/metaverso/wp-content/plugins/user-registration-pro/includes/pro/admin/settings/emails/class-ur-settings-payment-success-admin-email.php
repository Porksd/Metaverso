<?php
/**
 * Configure Email
 *
 * @category Class
 * @author   WPEverest
 * @since   1.3.3
 * @package UserRegistrationPayments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UR_Settings_Payment_Success_Admin_Email', false ) ) :

	/**
	 * UR_Settings_Payment_Success_Admin_Email Class.
	 */
	class UR_Settings_Payment_Success_Admin_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'payment_success_admin_email';
			$this->title       = esc_html__( 'Payment Success Admin Email', 'user-registration' );
			$this->description = esc_html__( 'Email sent to the admin after successful payment from user', 'user-registration' );
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_payment_success_admin_email',
				array(
					'title'    => __( 'Emails', 'user-registration' ),
					'sections' => array(
						'payment_success_email' => array(
							'title'        => esc_html__( 'Payment Success Admin Email', 'user-registration' ),
							'type'         => 'card',
							'desc'         => '',
							'back_link'    => ur_back_link( __( 'Return to emails', 'user-registration' ), admin_url( 'admin.php?page=user-registration-settings&tab=email' ) ),
							'preview_link' => ur_email_preview_link(
								__( 'Preview', 'user-registration' ),
								$this->id
							),
							'settings'     => array(
								array(
									'title'    => __( 'Enable this email', 'user-registration' ),
									'desc'     => __( 'Enable this email sent to the admin after succesful payment from user.', 'user-registration' ),
									'id'       => 'user_registration_enable_payment_success_admin_email',
									'default'  => 'yes',
									'type'     => 'toggle',
									'autoload' => false,
								),
								array(
									'title'    => __( 'Email Receipents', 'user-registration' ),
									'desc'     => __( 'Use comma to send emails to multiple receipents.', 'user-registration' ),
									'id'       => 'user_registration_payments_admin_email_receipents',
									'default'  => get_option( 'admin_email' ),
									'type'     => 'text',
									'css'      => 'min-width: 350px;',
									'autoload' => false,
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Subject', 'user-registration' ),
									'desc'     => __( 'The email subject you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_payment_success_admin_email_subject',
									'type'     => 'text',
									'default'  => __( 'Payment Received', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Content', 'user-registration' ),
									'desc'     => __( 'The email content you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_payment_success_admin_email',
									'type'     => 'tinymce',
									'default'  => $this->ur_get_payment_success_admin_email(),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
							),
						),
					),
				)
			);

			return apply_filters( 'user_registration_get_settings_' . $this->id, $settings );
		}

		/**
		 * Get payment success email.
		 */
		public static function ur_get_payment_success_admin_email() {

			$message = apply_filters(
				'user_registration_payment_admin_email_message',
				sprintf(
					__(
						'Hi Admin,

			You have received payment from {{username}}.
			Click <a href="{{home_url}}/wp-admin/user-edit.php?user_id={{user_id}}">Here</a> to view payment details.

			Thank You!',
						'user-registration'
					)
				)
			);

			return $message;
		}
	}
endif;

return new UR_Settings_Payment_Success_Admin_Email();

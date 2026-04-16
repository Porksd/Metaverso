<?php
/**
 * Validate custom fields on form submission.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Edwiser Bridge - Custom Fields
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\customFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}
/**
 * Edwiser_Custom_Field_Validation Class.
 */
class Edwiser_Custom_Field_Validation {

	/**
	 * Validate fields on checkout page.
	 */
	public function eb_validate_custom_field_on_checkout_page() {
		$this->eb_validate_custom_fields( 'checkout' );
	}

	/**
	 * Validate fields on WooCommerce registration page.
	 *
	 * @param array  $errors error array.
	 * @param string $username username.
	 * @param string $email email.
	 */
	public function eb_validate_custom_field_on_woo_reg_page( $errors, $username, $email ) {
		return $this->eb_validate_custom_fields( 'woo-reg', $errors );
	}

	/**
	 * Validate fields on my account page.
	 *
	 * @param array $args args.
	 * @param array $user_form_data user form data.
	 */
	public function eb_validate_custom_field_on_my_accnt_page( &$args, &$user_form_data ) {
		$this->eb_validate_custom_fields( 'my-accnt' );
	}

	/**
	 * Validate fields on Edwiser Bridge registration page.
	 *
	 * @param array  $errors error array.
	 * @param string $firstname first name.
	 * @param string $lastname last name.
	 * @param string $email email.
	 */
	public function eb_validate_custom_field_on_eb_reg_page( $errors, $firstname, $lastname, $email ) {
		return $this->eb_validate_custom_fields( 'eb-reg', $errors );
	}

	/**
	 * Validate fields on user account page.
	 *
	 * @param array $required required fields.
	 */
	public function eb_validate_custom_field_on_user_accnt_page( $required ) {
		$fields = get_option( 'edwiser_custom_fields', array() );
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $field_name => $field_details ) {
				if ( $this->eb_is_field_enabled( $field_details['enabled'] ) && $this->eb_is_field_required( $field_details['required'] ) ) {
					$required[ $field_name ] = $field_details['label'];
				}
			}
		}
		return $required;
	}

	/**
	 * Validate custom fields.
	 *
	 * @param string $page page.
	 * @param array  $errors error array.
	 */
	public function eb_validate_custom_fields( $page, $errors = '' ) {
		if ( '' === $errors ) {
			$errors = new \WP_Error();
		}
		$fields = get_option( 'edwiser_custom_fields', array() );
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $field_name => $field_details ) {
				if ( $this->eb_is_field_enabled( $field_details['enabled'] ) && $this->eb_is_field_required( $field_details['required'] ) && $this->eb_is_field_empty( $field_name ) ) {
					switch ( $page ) {
						case 'checkout':
						case 'my-accnt':
							wc_add_notice( '<b>' . $field_details['label'] . '</b>' . esc_html__( ' is a required field.', 'edwiser-bridge-pro' ), 'error' );
							break;
						case 'woo-reg':
						case 'eb-reg':
							$errors->add( $field_name . '_error', $field_details['label'] . __( ' is required!', 'edwiser-bridge-pro' ) );
							break;
						default:
							// Do nothing.

					}
				}
			}
		}
		return $errors;
	}

	/**
	 * Check if field is enabled.
	 *
	 * @param int $enabled enabled.
	 */
	public function eb_is_field_enabled( $enabled ) {
		if ( isset( $enabled ) && 1 == $enabled ) { // @codingStandardsIgnoreLine
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if field is required.
	 *
	 * @param int $required required.
	 */
	public function eb_is_field_required( $required ) {
		if ( isset( $required ) && 1 == $required ) { // @codingStandardsIgnoreLine
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if field is empty.
	 *
	 * @param string $field_name field name.
	 */
	public function eb_is_field_empty( $field_name ) {
		if ( isset( $_POST[$field_name] ) && empty( $_POST[$field_name] ) ) { // @codingStandardsIgnoreLine
			return true;
		} else {
			return false;
		}
	}
}

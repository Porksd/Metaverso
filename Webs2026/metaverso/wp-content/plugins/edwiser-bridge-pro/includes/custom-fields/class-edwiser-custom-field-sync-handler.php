<?php
/**
 * Save and sync custom fields data to moodle.
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
 * Edwiser_Custom_Field_Sync_Handler Class.
 */
class Edwiser_Custom_Field_Sync_Handler {
	/**
	 * Sync Custom Fields data on checkout
	 *
	 * @param int $user_id user id.
	 *
	 * @since 1.0.0
	 */
	public function eb_sync_custom_field_on_checkout_page( $user_id ) {
		// get moodle user id.
		$moodle_user_id = get_user_meta( $user_id, 'moodle_user_id', true );
		if ( is_numeric( $moodle_user_id ) ) {
			$user_data = $this->eb_cf_create_user_data( $user_id );

			if ( ! empty( $user_data ) ) {
				$this->eb_cf_sync_user_data_to_moodle( $user_data, $moodle_user_id );
			}
		}
	}

	/**
	 * Sync Custom Fields data from woo-reg, my-account, eb-reg, user-account page
	 *
	 * @param array $user_data user data.
	 * @param int   $update update.
	 *
	 * @since 1.0.0
	 */
	public function eb_sync_custom_field( $user_data, $update ) {
		if (isset($user_data['email']) && !empty($user_data['email'])) {
			$user = get_user_by('email', $user_data['email']);
			$user_id = $user ? $user->ID : null;
		} else {
			$user_id = null;
		}

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Prevent saving/updating custom fields if user_id is still empty or 0 (happens during forgot password).
		if ( empty( $user_id ) || $user_id == 0 ) {
			return $user_data;
		}

		$custom_fields = $this->eb_cf_create_user_data( $user_id );
		if ( ! empty( $custom_fields ) ) {
			$user_data['customfields'] = $custom_fields;
		}

		return $user_data;
	}

	/**
	 * Sync custom fields when linking a user account to moodle
	 *
	 * @param array $user_data user data.
	 *
	 * @since 1.0.0
	 */
	public function eb_sync_custom_field_on_user_link( $user_data ) {
		$user_id        = get_user_by( 'email', $user_data['user_email'] )->ID;
		$moodle_user_id = get_user_meta( $user_id, 'moodle_user_id', true );

		if ( empty( $moodle_user_id ) ) {
			return;
		}
		$custom_fields = $this->eb_cf_get_custom_fields_data_from_db( $user_id );
		if ( ! empty( $custom_fields ) ) {
			$this->eb_cf_sync_user_data_to_moodle( $custom_fields, $moodle_user_id );
		}
	}

	/**
	 * Save custom fields data when user is not linked to moodle
	 *
	 * @param int $user_id user id.
	 *
	 * @since 1.0.0
	 */
	public function eb_save_custom_field_in_user_meta( $user_id ) {
		// $moodle_user_id = get_user_meta( $user_id, 'moodle_user_id', true );
		// if ( empty( $moodle_user_id ) ) {
		$this->eb_cf_create_user_data( $user_id );
		// }
	}

	/**
	 * Sync custom fields to Moodle.
	 *
	 * @param array $user_data user data.
	 * @param int   $moodle_user_id moodle user id.
	 *
	 * @since 1.0.0
	 */
	public function eb_cf_sync_user_data_to_moodle( $user_data, $moodle_user_id ) {
		$request_data = array(
			'id'           => $moodle_user_id,
			'customfields' => $user_data,
		);

		$response = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->connection_helper()->connect_moodle_with_args_helper(
			'core_user_update_users',
			array(
				'users' => array( $request_data ),
			)
		);
	}

	/**
	 * Save custom fields data from moodle to WordPress.
	 *
	 * @param int   $user_id user id.
	 * @param array $user_data user data.
	 *
	 * @since 1.0.0
	 */
	public function eb_cf_save_custom_fields_data_from_moodle( $user_id, $user_data ) {
		$fields = get_option( 'edwiser_custom_fields', array() );

		$field_data = isset( $user_data['custom_fields'] ) ? json_decode( $user_data['custom_fields'], true ) : array();

		if ( ! empty( $field_data ) && ! empty( $fields ) ) {
			foreach ( $fields as $field_name => $field ) {
				if ( isset( $field['enabled'] ) && 1 == $field['enabled'] ) { // @codingStandardsIgnoreLine
					$field_value = isset( $field_data[ $field_name ] ) ? $field_data[ $field_name ] : '';
					if ( 'date' === $field['type'] && '' !== $field_value ) {
						$gmt_date    = get_date_from_gmt( date( 'Y-m-d H:i:s', $field_value ) ); // @codingStandardsIgnoreLine
						$field_value = date( 'Y-m-d', strtotime( $gmt_date ) ); // @codingStandardsIgnoreLine
					}
					if ( '' !== $field_value ) {
						update_user_meta( $user_id, $field_name, $field_value );
					}
				}
			}
		}
	}

	/**
	 * Create user data array to sync with moodle.
	 *
	 * @param int $user_id user id.
	 *
	 * @since 1.0.0
	 */
	public function eb_cf_create_user_data( $user_id ) {
		$user_data = array();
		$fields    = get_option( 'edwiser_custom_fields', array() );
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $field_name => $field_details ) {
				if ( isset( $field_details['enabled'] ) && 1 == $field_details['enabled'] ) { // @codingStandardsIgnoreLine
					// get field value.
					if ( 'checkbox' === $field_details['type'] ) {
						$field_value = isset( $_POST[$field_name] ) ? sanitize_text_field( $_POST[$field_name] ) : 0;
					} else {
						$field_value = isset( $_POST[$field_name] ) ? sanitize_text_field( $_POST[$field_name] ) : get_user_meta( $user_id, $field_name, true );
					}
					// apply filter to field value before updating.
					$field_value = apply_filters( 'eb_cf_before_update_field_value', $field_value, $field_name, $user_id, $field_details );

					// update user meta.
					update_user_meta( $user_id, $field_name, $field_value );

					if ( ! isset( $field_details['sync-on-moodle'] ) || ! $field_details['sync-on-moodle'] ) {
						continue;
					}

					// if type is date then convert date to epoch.
					if ( 'date' === $field_details['type'] ) {
						$field_value = strtotime( $field_value );
					}

					array_push(
						$user_data,
						array(
							'type'  => $field_name,
							'value' => $field_value,
						)
					);
				}
			}
		}
		$user_data = apply_filters( 'eb_cf_user_data', $user_data, $user_id );
		return $user_data;
	}

	/**
	 * Get custom fields data from db.
	 *
	 * @param int $user_id user id.
	 *
	 * @since 1.0.0
	 */
	public function eb_cf_get_custom_fields_data_from_db( $user_id ) {
		$user_data = array();
		$fields    = get_option( 'edwiser_custom_fields', array() );
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $field_name => $field_details ) {
				if ( isset( $field_details['enabled'] ) && 1 == $field_details['enabled'] ) { // @codingStandardsIgnoreLine
					// get field value.
					$field_value = get_user_meta( $user_id, $field_name, true );

					// if type is checkbox then convert value to 1 or 0.
					if ( 'checkbox' === $field_details['type'] ) {
						$field_value = ! empty( $field_value ) ? $field_value : 0;
					}

					if ( ! isset( $field_details['sync-on-moodle'] ) || ! $field_details['sync-on-moodle'] ) {
						continue;
					}

					// if type is date then convert date to epoch.
					if ( 'date' === $field_details['type'] ) {
						$field_value = strtotime( $field_value );
					}

					array_push(
						$user_data,
						array(
							'type'  => $field_name,
							'value' => $field_value,
						)
					);
				}
			}
		}
		return $user_data;
	}

}

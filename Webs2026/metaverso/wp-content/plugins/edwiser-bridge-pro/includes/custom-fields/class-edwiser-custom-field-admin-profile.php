<?php
/**
 * Custom Fields Admin Profile Handler
 * This class is responsible for displaying custom fields on the WordPress admin user profile page.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\customFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Edwiser_Custom_Field_Admin_Profile
 */
class Edwiser_Custom_Field_Admin_Profile {

	/**
	 * Initialize the class and set up hooks.
	 */
	public function __construct() {
		// Add custom fields to WordPress admin user profile page
		add_action( 'show_user_profile', array( $this, 'eb_show_custom_fields_on_admin_profile' ) );
		add_action( 'edit_user_profile', array( $this, 'eb_show_custom_fields_on_admin_profile' ) );
	}

	/**
	 * Display custom fields on WordPress admin user profile page with enhanced styling.
	 *
	 * @param WP_User $user User object.
	 */
	public function eb_show_custom_fields_on_admin_profile( $user ) {
		// Only show on admin pages
		if ( ! is_admin() ) {
			return;
		}

		$custom_fields = get_option( 'edwiser_custom_fields', array() );
		
		if ( empty( $custom_fields ) || ! is_array( $custom_fields ) ) {
			return;
		}

		// Only show enabled fields
		$enabled_fields = array_filter( $custom_fields, function( $field ) {
			return isset( $field['enabled'] ) && 1 == $field['enabled']; // @codingStandardsIgnoreLine
		} );

		if ( empty( $enabled_fields ) ) {
			return;
		}

		// CSS is now loaded from external file for better maintainability

		echo '<div class="eb-custom-fields-section">';
		echo '<h2><span class="dashicons dashicons-admin-users" style="margin-right: 8px;"></span>' . esc_html__('Edwiser Bridge Custom Fields', 'edwiser-bridge-pro') . '</h2>';
		echo '<p class="description">' . esc_html__('All enabled Edwiser Custom Fields configured on the WordPress site will be presented here in a view-only format.', 'edwiser-bridge-pro') . '</p>';
		echo '<table class="form-table">';

		foreach ( $enabled_fields as $field_name => $field_details ) {
			$field_value = get_user_meta( $user->ID, $field_name, true );
			$field_label = isset( $field_details['label'] ) ? $field_details['label'] : ucfirst( str_replace( '_', ' ', $field_name ) );
			$field_type  = isset( $field_details['type'] ) ? $field_details['type'] : 'text';

			echo '<tr>';
			echo '<th><label for="' . esc_attr( $field_name ) . '">' . esc_html( $field_label ) . '</label></th>';
			echo '<td>';

			switch ( $field_type ) {
				case 'textarea':
					echo '<textarea name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" rows="3" cols="30" class="regular-text" readonly>' . esc_textarea( $field_value ) . '</textarea>';
					break;
					
				case 'select':
					$options = isset( $field_details['options'] ) ? $field_details['options'] : array();
					if ( ! empty( $options ) ) {
						echo '<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" disabled>';
						echo '<option value="">' . esc_html__( 'Select an option', 'edwiser-bridge-pro' ) . '</option>';
						foreach ( $options as $option_value => $option_label ) {
							$selected = ( $field_value == $option_value ) ? 'selected' : ''; // @codingStandardsIgnoreLine
							echo '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . esc_html( $option_label ) . '</option>';
						}
						echo '</select>';
					} else {
						echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" class="regular-text" readonly />';
					}
					break;
					
				case 'checkbox':
					$checked = ( ! empty( $field_value ) ) ? 'checked' : '';
					echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="1" ' . $checked . ' disabled />';
					echo '<span class="description">' . esc_html__( 'This field is read-only', 'edwiser-bridge-pro' ) . '</span>';
					break;
					
				case 'date':
					$display_value = ! empty( $field_value ) ? date( 'Y-m-d', strtotime( $field_value ) ) : '';
					echo '<input type="date" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $display_value ) . '" class="regular-text" readonly />';
					break;
					
				case 'radio':
					$options = isset( $field_details['options'] ) ? $field_details['options'] : array();
					if ( ! empty( $options ) ) {
						foreach ( $options as $option_value => $option_label ) {
							$checked = ( $field_value == $option_value ) ? 'checked' : ''; // @codingStandardsIgnoreLine
							echo '<label><input type="radio" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $option_value ) . '" ' . $checked . ' disabled /> ' . esc_html( $option_label ) . '</label><br>';
						}
					} else {
						echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" class="regular-text" readonly />';
					}
					break;
					
				default:
					echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" class="regular-text" readonly />';
					break;
			}

			// Add description if available
			if ( isset( $field_details['description'] ) && ! empty( $field_details['description'] ) ) {
				echo '<p class="description">' . esc_html( $field_details['description'] ) . '</p>';
			}

			// Add field type indicator
			echo '<p class="description"><strong>' . esc_html__( 'Field Type:', 'edwiser-bridge-pro' ) . '</strong> ' . esc_html( ucfirst( $field_type ) ) . '</p>';

			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
		echo '</div>';
	}
}

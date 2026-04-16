<?php
/**
 * Setup plugin menus in WP admin.
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
 * Edwiser_Custom_Field_Frontend_Handler class.
 */
class Edwiser_Custom_Field_Frontend_Handler {

	/**
	 * Show fields on Checkout page.
	 */
	public function eb_show_fields_on_checkout_page() {
		// Hook to add fields on the checkput page woocommerce_after_order_notes.
		$this->eb_parse_custom_fields( 'checkout', 'woocommerce-input-wrapper' );
	}

	/**
	 * Show fields on Woocommerce Registration page.
	 */
	public function eb_show_fields_on_woo_reg_page() {
		// Hook woocommerce_register_form.
		$this->eb_parse_custom_fields( 'woo-reg', 'woocommerce-Input woocommerce-Input--text input-text' );
	}

	/**
	 * Show fields on My Account page.
	 */
	public function eb_show_fields_on_woo_my_accnt_page() {
		// Hook woocommerce_edit_account_form.
		$this->eb_parse_custom_fields( 'woo-my-accnt', 'woocommerce-Input' );
	}

	/**
	 * Show fields on Edwiser Registration page.
	 */
	public function eb_show_fields_on_edwiser_reg_page() {
		// Hook eb_registration_form.
		$this->eb_parse_custom_fields( 'eb-reg', '' );
	}

	/**
	 * Show fields on User Account page.
	 */
	public function eb_show_fields_on_edwiser_user_accnt_page() {
		// Hook eb_edit_user_profile.
		$this->eb_parse_custom_fields( 'eb-user-accnt', '' );
	}

	/**
	 * Parse Fields to show on selected pages.
	 *
	 * @param string $page        page name.
	 * @param string $input_class input class.
	 */
	public function eb_parse_custom_fields( $page, $input_class = '' ) {
		$current_user_id = get_current_user_id();

		$custom_fields = get_option( 'edwiser_custom_fields', array() );

		if ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) {
			$display_header = true;

			foreach ( $custom_fields as $name => $field_details ) {
				// Check if the field is enabled for the page.
				if ( isset( $field_details['enabled'] ) && 1 == $field_details['enabled'] && isset( $field_details[$page] ) && 1 == $field_details[$page] ) { // @codingStandardsIgnoreLine
					// check if woo-int is active or not and then show the fields.
					$modules_data = get_option( 'eb_pro_modules_data' );
					if ( isset( $modules_data['woo_integration'] ) && 'active' !== $modules_data['woo_integration'] && in_array( $page, array( 'checkout', 'woo-reg', 'woo-my-accnt' ) ) ) { // @codingStandardsIgnoreLine
						continue;
					}

					if ( $display_header ) {
						// Here comes the overall pagewise wrapper start for ALL fields.
						$this->eb_pagewise_all_fields_wrapper_start( $page );
						$display_header = false;
					}

					// get deault value user wise.
					$field_value = get_user_meta( $current_user_id, $name, 1 );
					if ( empty( $field_value ) ) {
						$field_value = $field_details['default-val'];
					}

					// Here comes the overall pagewise wrapper for EACH field.
					$this->eb_pagewise_field_wrapper_start( $page, $field_details['class'] );

					$required     = '';
					$required_txt = '';

					if ( $field_details['required'] ) {
						$required     = '<span class="required">*</span>';
						$required_txt = 'required ';
					}
					$allowed_tags = \app\wisdmlabs\edwiserBridge\wdm_eb_get_allowed_html_tags();

					// Switch case for each field type to show them on fronend.
					switch ( $field_details['type'] ) {
						case 'text':
						case 'number':
							?>
							<label for="eb_cf_<?php echo esc_attr( $name ); ?>">
								<?php echo esc_html( $field_details['label'] ) . wp_kses( $required, $allowed_tags ); ?>
							</label>
							<input placeholder="<?php echo esc_html( $field_details['placeholder'] ); ?>" class="input-text" type="<?php echo esc_html( $field_details['type'] ); ?>" name="<?php echo esc_html( $name ); ?>" id="eb_cf_<?php echo esc_attr( $name ); ?>" value="<?php echo esc_html( $field_value ); ?>" <?php echo esc_html( $required_txt ); ?>>
							<?php
							break;

						case 'textarea':
							?>
							<label for="eb_cf_<?php echo esc_attr( $name ); ?>">
								<?php echo esc_html( $field_details['label'] ) . wp_kses( $required, $allowed_tags ); ?>
							</label>
							<textarea placeholder="<?php echo esc_html( $field_details['placeholder'] ); ?>" class="input-text"  type="<?php echo esc_html( $field_details['type'] ); ?>" name="<?php echo esc_html( $name ); ?>" id="eb_cf_<?php echo esc_attr( $name ); ?>" rows="4" <?php echo esc_html( $required_txt ); ?>><?php echo esc_html( $field_value ); ?></textarea>
							<?php
							break;

						case 'select':
							?>
							<label for="eb_cf_<?php echo esc_attr( $name ); ?>">
								<?php echo esc_html( $field_details['label'] ) . wp_kses( $required, $allowed_tags ); ?>
							</label>
							<select class="input-text" name="<?php echo esc_html( $name ); ?>" id="eb_cf_<?php echo esc_attr( $name ); ?>" <?php echo esc_html( $required_txt ); ?>>
								<?php
								foreach ( $field_details['options'] as $value => $text ) {
									$selected = '';
									if ( $value === $field_value ) {
										$selected = 'selected';
									}
									?>
									<option value="<?php echo esc_html( $value ); ?>"  <?php echo esc_html( $selected ); ?>> <?php echo esc_html( $text ); ?> </option>
									<?php
								}
								?>
							</select>
							<?php
							break;
						case 'checkbox':
							$checked = '';
							if ( $field_value ) {
								$checked = 'checked';
							}
							?>
							<label for="eb_cf_<?php echo esc_attr( $name ); ?>">
								<input type="checkbox" name="<?php echo esc_html( $name ); ?>" id="eb_cf_<?php echo esc_attr( $name ); ?>" <?php echo esc_html( $required_txt ); ?> <?php echo esc_html( $checked ); ?>>
								<?php echo esc_html( $field_details['label'] ) . wp_kses( $required, $allowed_tags ); ?>
							</label>
							<?php
							break;
						case 'date':
							?>
							<label for="eb_cf_<?php echo esc_attr( $name ); ?>">
								<?php echo esc_html( $field_details['label'] ) . wp_kses( $required, $allowed_tags ); ?>
							</label>
							<input placeholder="<?php echo esc_html( $field_details['placeholder'] ); ?>" class="input-text" type="date" name="<?php echo esc_html( $name ); ?>" id="eb_cf_<?php echo esc_attr( $name ); ?>" value="<?php echo esc_html( $field_value ); ?>" <?php echo esc_html( $required_txt ); ?>>
							<?php
							break;
						default:
							break;
					}
					?>
					<?php

					// Here comes the overall pagewise wrapper end for EACH field.
					$this->eb_pagewise_field_wrapper_end( $page );
				}
			}

			if ( false === $display_header ) {
				// Here comes the overall pagewise wrapper start for ALL fields.
				$this->eb_pagewise_all_fields_wrapper_end( $page );
			}
		}
	}

	/**
	 * Pagewise field wrapper.
	 *
	 * @param string $page page name.
	 * @param string $field_class field class.
	 */
	public function eb_pagewise_field_wrapper_start( $page, $field_class ) {
		// switch case for each page.
		switch ( $page ) {
			case 'checkout':
				?>
				<p class="form-row form-row-wide <?php echo esc_html( $field_class ); ?>" id="order_comments_field">
				<?php
				break;

			case 'woo-reg':
			case 'woo-my-accnt':
				?>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide <?php echo esc_html( $field_class ); ?>">
				<?php
				break;
			case 'eb-reg':
				?>
				<p class="form-row form-row-wide eb-profile-txt-field <?php echo esc_html( $field_class ); ?>">
				<?php
				break;
			case 'eb-user-accnt':
				?>
				<div class="eb-profile-txt-field <?php echo esc_html( $field_class ); ?>">
				<?php
				break;
			default:
				?>
				<p class="<?php echo esc_html( $field_class ); ?>">
				<?php
				break;
		}

	}

	/**
	 * Pagewise field wrapper end.
	 *
	 * @param string $page page name.
	 */
	public function eb_pagewise_field_wrapper_end( $page ) {
		// switch case for each page.
		switch ( $page ) {
			case 'checkout':
			case 'woo-reg':
			case 'woo-my-accnt':
			case 'eb-reg':
				?>
				</p>
				<?php
				break;

			case 'eb-user-accnt':
				?>
				</div>
				<?php
				break;
			default:
				?>
				</p>
				<?php
				break;
		}

	}


	/**
	 * All fields wrapper start.
	 *
	 * @param string $page page name.
	 */
	public function eb_pagewise_all_fields_wrapper_start( $page ) {
		// switch case for each page.
		switch ( $page ) {
			case 'checkout':
			case 'woo-reg':
			case 'eb-reg':
				break;
			case 'woo-my-accnt':
			case 'eb-user-accnt':
				?>
				<fieldset>
					<legend><?php esc_html_e( 'Additional Fields', 'edwiser-bridge-pro' ); ?></legend>
				<?php
				break;

			default:
				?>
				<fieldset>
				<?php
				break;
		}
	}

	/**
	 * All fields wrapper end.
	 *
	 * @param string $page page name.
	 */
	public function eb_pagewise_all_fields_wrapper_end( $page ) {
		// switch case for each page.
		switch ( $page ) {
			case 'checkout':
			case 'woo-reg':
			case 'eb-reg':
				break;
			case 'woo-my-accnt':
			case 'eb-user-accnt':
				?>
				</fieldset>
				<?php
				break;
			default:
				?>
				</fieldset>
				<?php
				break;
		}

	}
}

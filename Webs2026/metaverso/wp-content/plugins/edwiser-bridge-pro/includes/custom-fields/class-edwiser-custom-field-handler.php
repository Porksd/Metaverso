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
 * Class Edwiser_Custom_Field_Handler
 */
class Edwiser_Custom_Field_Handler {

	/**
	 * Function which will output the custom fields table.
	 */
	public function eb_output_custom_fields() {
		// Check if post data is set. if set then call the save function.
		if ( isset( $_POST['eb_custom_field_submit'] ) && ! empty( $_POST['eb_custom_field_submit'] && isset( $_POST['eb_custom_field_nonce'] ) && wp_verify_nonce( $_POST['eb_custom_field_nonce'], 'eb_custom_field_nonce' ) ) ) { // @codingStandardsIgnoreLine
			$this->save_custom_field_order();
		}

		ob_start();
		$table_headers = array(
			'sort'        => '',
			'checkbox'    => "<input type='checkbox' name='' class='eb_cf_bulk_action_header_cb'>",
			'name'        => __( 'Name', 'edwiser-bridge-pro' ),
			'type'        => __( 'Type', 'edwiser-bridge-pro' ),
			'label'       => __( 'Label', 'edwiser-bridge-pro' ),
			'placeholder' => __( 'Placeholder', 'edwiser-bridge-pro' ),
			'required'    => __( 'Required', 'edwiser-bridge-pro' ),
			'enabled'     => __( 'Enabled', 'edwiser-bridge-pro' ),
			'edit'        => __( 'Manage', 'edwiser-bridge-pro' ),
		);

		$table_headers = apply_filters( 'eb_custom_field_table_headers', $table_headers );

		// Table data will have unique key as the field name.
		// As we also can not add 2 fields with the same name.
		$table_data = get_option( 'edwiser_custom_fields', array() );
		?>

		<div class ='eb_custom_fields_wrap' >
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
						<!-- Table bulk action wrapper div -->
			<form method="post"  action="">

				<?php wp_nonce_field( 'eb_custom_field_nonce', 'eb_custom_field_nonce' ); ?>
				<?php $this->eb_cf_bulk_actions(); ?>

				<!-- Table wrapper div -->
				<div>
					<table class='eb_custom_field_tbl'>
						<thead>
							<tr>
								<?php
								foreach ( $table_headers as $class => $table_header ) {
									?>
									<th class = "<?php echo 'eb-cf-tbl-thead-' . esc_html( $class ); ?>"> <?php echo $table_header; ?> </th>
									<?php
								}
								?>
							</tr>
						</thead>

						<tbody>
							<?php

							if ( is_array( $table_data ) && count( $table_data ) ) {

								// Foreach for each row.
								foreach ( $table_data as $data_key => $field_data ) {
									?>
									<tr>
										<?php
										// foreach for each column.
										foreach ( $table_headers as $key => $table_header ) {
											// Adding swicth case for each column.
											switch ( $key ) {
												case 'sort':
													?>
													<td style="width: 5%; text-align:center;"> <span class="dashicons dashicons-menu"></span> </td>
													<?php
													break;

												case 'checkbox':
													?>
													<td>
														<input type='checkbox' name='' class='eb_cf_bulk_action_cb'>
													</td>
													<?php
													break;

												case 'name':
													?>
													<td> 
														<span class="eb-cf-tbl-name-lbl"><?php echo esc_html( $data_key ); ?></span> 
														<input type="hidden" class="eb-cf-tbl-name" name="eb-cf-tbl-name[]"  value="<?php echo esc_html( $data_key ); ?>">
													</td>
													<?php
													break;

												case 'type':
													?>
													<td>
														<span class="eb-cf-tbl-type-lbl"><?php echo esc_html( $field_data['type'] ); ?></span>
														<input type="hidden" class="eb-cf-tbl-type" name="eb-cf-tbl-type[]" value="<?php echo esc_html( $field_data['type'] ); ?>">
													</td>
													<?php
													break;

												case 'label':
													?>
													<td>
														<span class="eb-cf-tbl-label-lbl"><?php echo esc_html( $field_data['label'] ); ?></span>
														<input type="hidden" class="eb-cf-tbl-label" name="eb-cf-tbl-label[]" value="<?php echo esc_html( $field_data['label'] ); ?>">
														</td>
													<?php
													break;

												case 'placeholder':
													?>
													<td>
														<span class="eb-cf-tbl-placeholder-lbl"><?php echo esc_html( $field_data['placeholder'] ); ?></span>
														<input type="hidden" class="eb-cf-tbl-placeholder" name="eb-cf-tbl-placeholder[]" value="<?php echo esc_html( $field_data['placeholder'] ); ?>">
													</td>
													<?php
													break;

												case 'required':
													?>
													<td>
														<?php
														if ( $field_data['required'] ) {
															?>
															<span class="eb-cf-tbl-required-lbl"> <span class="dashicons dashicons-saved"></span> </span>
															<input type="hidden" class="eb-cf-tbl-required" name="eb-cf-tbl-required[]" value="1">
															<?php
														} else {
															?>
															<span class="eb-cf-tbl-required-lbl"> - </span>
															<input type="hidden" class="eb-cf-tbl-required" name="eb-cf-tbl-required[]" value="0">
															<?php
														}
														?>
													</td>

													<?php
													break;

												case 'enabled':
													?>
													<td>
														<?php
														if ( $field_data['enabled'] ) {
															?>
															<span class="eb-cf-tbl-enabled-lbl"> <span class="dashicons dashicons-saved"></span> </span>
															<input type="hidden" class="eb-cf-tbl-enabled" name="eb-cf-tbl-enabled[]" value="1">
															<?php
														} else {
															?>
															<span class="eb-cf-tbl-enabled-lbl"> - </span>
															<input type="hidden" class="eb-cf-tbl-enabled" name="eb-cf-tbl-enabled[]" value="0">
															<?php
														}
														?>
													</td>
													<?php
													break;

												case 'edit':
													$options = '';
													if ( isset( $field_data['options'] ) ) {
														$options = json_encode( $field_data['options'] ); // @codingStandardsIgnoreLine
														$options = str_replace( '"', "'", $options );
													}

													?>
													<td>
														<span title="<?php esc_html_e( 'Edit Row', 'edwiser-bridge-pro' ); ?>" class="dashicons dashicons-edit-page eb-cf-edit"></span>
														<span title="<?php esc_html_e( 'Delete Row', 'edwiser-bridge-pro' ); ?>" class="dashicons dashicons-trash eb-cf-remove"></span>
														<input type="hidden" class="eb-cf-tbl-class" name="eb-cf-tbl-class[]" value="<?php echo esc_html( $field_data['class'] ); ?>">
														<input type="hidden" class="eb-cf-tbl-default-val" name="eb-cf-tbl-default-val[]" value="<?php echo esc_html( $field_data['default-val'] ); ?>">
														<input type="hidden" class="eb-cf-tbl-sync-on-moodle" name="eb-cf-tbl-sync-on-moodle[]" value="<?php echo esc_html( $field_data['sync-on-moodle'] ); ?>">
														<input type="hidden" class="eb-cf-tbl-checkout" name="eb-cf-tbl-checkout[]" value="<?php echo esc_html( $field_data['checkout'] ); ?>">
														<input type="hidden" class="eb-cf-tbl-woo-reg" name="eb-cf-tbl-woo-reg[]" value="<?php echo esc_html( $field_data['woo-reg'] ); ?>">
														<input type="hidden" class="eb-cf-tbl-woo-my-accnt" name="eb-cf-tbl-woo-my-accnt[]" value="<?php echo esc_html( $field_data['woo-my-accnt'] ); ?>">
														<input type="hidden" class="eb-cf-tbl-eb-reg" name="eb-cf-tbl-eb-reg[]" value="<?php echo esc_html( $field_data['eb-reg'] ); ?>">
														<input type="hidden" class="eb-cf-tbl-eb-user-accnt" name="eb-cf-tbl-eb-user-accnt[]" value="<?php echo esc_html( $field_data['eb-user-accnt'] ); ?>">
														<input type="hidden" class="eb-cf-tbl-options" value="<?php echo esc_attr( $options ); ?>" name="eb-cf-tbl-options[]" >

													</td>
													<?php
													break;

												default:
													break;
											}
										}
										?>
									</tr>
									<?php
								}
							} else {
								?>
								<tr class="eb_cf_empty_table">
									<td colspan="9" style="text-align:center;"><?php esc_html_e( 'Currently no fields available.', 'edwiser-bridge-pro' ); ?> </td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>

				<!-- Table bulk action wrapper div -->
				<?php $this->eb_cf_bulk_actions(); ?>
			</form>

			<!-- Pop up content div -->
			<?php $this->eb_cf_pop_up_content(); ?>
			<?php $this->eb_cf_pop_up_delete(); ?>


		</div>
		<?php
		ob_flush();
	}


	/**
	 * Custom fields bulk actions.
	 */
	public function eb_cf_bulk_actions() {
		?>
		<div class ='eb_field_bulk_action_wrap'>
			<div class ='eb_field_action_left'>
				<select name='' class='eb_custom_field_btns eb_cf_bulk_action_select'>
					<option value = ''> <?php esc_html_e( 'Select Action', 'edwiser-bridge-pro' ); ?> </option>
					<option value = 'enable'> <?php esc_html_e( 'Enable', 'edwiser-bridge-pro' ); ?> </option>
					<option value = 'disable'> <?php esc_html_e( 'Disable', 'edwiser-bridge-pro' ); ?> </option>
					<option value = 'delete'> <?php esc_html_e( 'Delete', 'edwiser-bridge-pro' ); ?> </option>
				</select>
				<button class ='button eb_custom_field_btns eb_cf_bulk_action_btns'> <?php esc_html_e( 'Apply', 'edwiser-bridge-pro' ); ?> </button>
				<button class ='button-primary eb_custom_field_btns eb_cf_add_new_field_btn '> <?php esc_html_e( ' + Add Field', 'edwiser-bridge-pro' ); ?> </button>

			</div>
			<div class ='eb_field_action_right'>
				<input class="button-primary eb_custom_field_btns" type="submit" value="<?php esc_html_e( 'Save Field order', 'edwiser-bridge-pro' ); ?>" name="eb_custom_field_submit">
			</div>
		</div>

		<?php
	}


	/**
	 * Field pop up content.
	 */
	public function eb_cf_pop_up_content() {
		?>
		<div class="eb-cf-pop-up-cont-wrap">
			<!-- Table error msg -->

			<div title="<?php esc_html_e( 'Field Settings', 'edwiser-bridge-pro' ); ?>" class="eb-cf-pop-up-cont">
				<div>
					<div class='eb_cf_error_msg'></div>
					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label ><?php esc_html_e( 'Type', 'edwiser-bridge-pro' ); ?></label>
							<span class="help_tip eb-cf-dialog-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_html_e( 'Field type according to which fields will be shown on pages.', 'edwiser-bridge-pro' ); ?>" height="20" width="20"></span>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<select class="eb-cf-pop-up-inp" name='eb-cf-dialog-type' >
								<option value="text"><?php esc_html_e( 'Text', 'edwiser-bridge-pro' ); ?></option>
								<option value="number"><?php esc_html_e( 'Number', 'edwiser-bridge-pro' ); ?></option>
								<option value="date"><?php esc_html_e( 'Date', 'edwiser-bridge-pro' ); ?></option>
								<option value="textarea"><?php esc_html_e( 'Textarea', 'edwiser-bridge-pro' ); ?></option>
								<option value="select"><?php esc_html_e( 'Select', 'edwiser-bridge-pro' ); ?></option>
								<option value="checkbox"><?php esc_html_e( 'Checkbox', 'edwiser-bridge-pro' ); ?></option>
							</select>
						</div>
					</div>

					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label><?php esc_html_e( 'Name', 'edwiser-bridge-pro' ); ?> *</label>
							<span class="help_tip eb-cf-dialog-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_html_e( 'Field Name which should be same as the Moodle short name.', 'edwiser-bridge-pro' ); ?>" height="20" width="20"></span>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="eb-cf-pop-up-inp" id="" type="text" name='eb-cf-dialog-name' placeholder="<?php esc_html_e( 'Field Name ( Moodle Short Name ) ', 'edwiser-bridge-pro' ); ?>" value="" required="">
							<div style="padding-top: 3px;padding-left: 3px;">
								<?php esc_html_e( 'Field name should be same as the ', 'edwiser-bridge-pro' ) . '<b>' . esc_html_e( 'Moodle field short name. ', 'edwiser-bridge-pro' ) . '</b>'; ?>
							</div>
						</div>
					</div>

					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label><?php esc_html_e( 'Label', 'edwiser-bridge-pro' ); ?></label>
							<span class="help_tip eb-cf-dialog-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_html_e( 'Field label which will be shown on the pages for input fields.', 'edwiser-bridge-pro' ); ?>" height="20" width="20"></span>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="eb-cf-pop-up-inp" id="" type="text" name='eb-cf-dialog-label' placeholder="<?php esc_html_e( 'Field Label', 'edwiser-bridge-pro' ); ?>" value="" required="">
						</div>
					</div>

					<div class="eb-cf-pop-up-fields eb-cf-dialog-def-val-wrap">
						<div class="eb-cf-pop-up-lbl">
							<label><?php esc_html_e( 'Default Value', 'edwiser-bridge-pro' ); ?></label>
							<span class="help_tip eb-cf-dialog-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_html_e( 'Default value which will be shown while showing fields for thr first type, If field type is select then option value should be entered here.', 'edwiser-bridge-pro' ); ?>" height="20" width="20"></span>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="eb-cf-pop-up-inp" id="" type="text" name='eb-cf-dialog-default-val' placeholder="<?php esc_html_e( 'Field Default value', 'edwiser-bridge-pro' ); ?>" value="" required="">
						</div>
					</div>

					<div class="eb-cf-pop-up-fields eb-cf-dialog-placeholder-wrap">
						<div class="eb-cf-pop-up-lbl">
							<label><?php esc_html_e( 'Placeholder', 'edwiser-bridge-pro' ); ?></label>
							<span class="help_tip eb-cf-dialog-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_html_e( 'Placeholder shown for the fields.', 'edwiser-bridge-pro' ); ?>" height="20" width="20"></span>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="eb-cf-pop-up-inp" id="" type="text" name='eb-cf-dialog-placeholder' placeholder="<?php esc_html_e( 'Field Placeholder', 'edwiser-bridge-pro' ); ?>" value="" required="">
						</div>
					</div>

					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label><?php esc_html_e( 'Class', 'edwiser-bridge-pro' ); ?></label>
							<span class="help_tip eb-cf-dialog-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_html_e( 'This class will get added on the Field wrapper element.', 'edwiser-bridge-pro' ); ?>" height="20" width="20"></span>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="eb-cf-pop-up-inp" id="" type="text" name='eb-cf-dialog-class' placeholder="<?php esc_html_e( 'Field Css Class', 'edwiser-bridge-pro' ); ?>" value="" required="">
						</div>
					</div>

					<div class="eb-cf-pop-up-fields eb-cf-pop-up-options-field eb-cf-hide">
						<div class="eb-cf-pop-up-lbl">
							<label><?php esc_html_e( 'Options', 'edwiser-bridge-pro' ); ?></label>
							<span class="help_tip eb-cf-dialog-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_html_e( 'Options which will get displayed on the selected pages.', 'edwiser-bridge-pro' ); ?>" height="20" width="20"></span>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="eb-cf-dialog-option-inp eb-cf-dialog-option-val" type="text" name="eb-cf-dialog-option-val[]" placeholder="<?php esc_html_e( 'Option Value', 'edwiser-bridge-pro' ); ?>">
							<input class="eb-cf-dialog-option-inp eb-cf-dialog-option-txt" type="text" name="eb-cf-dialog-option-txt[]" placeholder="<?php esc_html_e( 'Option Text', 'edwiser-bridge-pro' ); ?>">
							<span class="eb-cf-dialog-option-btn">
								<span class="eb-cf-dialog-option-add-new dashicons dashicons-plus-alt"></span>
								<span class="eb-cf-dialog-option-first-remove dashicons dashicons-dismiss"></span>
							</span>
						</div>
					</div>

					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label></label>
							<span class="help_tip eb-cf-dialog-tooltip dashicons dashicons-editor-help" data-tip="<?php esc_html_e( 'By default all pages shown on the checkout page enabling will enable fields for all selected pages.', 'edwiser-bridge-pro' ); ?>" title="" height="20" width="20"></span>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="" id="eb-cf-dialog-enabled" type="checkbox" name='eb-cf-dialog-enabled' value="" required="">
							<label for='eb-cf-dialog-enabled'><?php esc_html_e( 'Enabled', 'edwiser-bridge-pro' ); ?></label>

						</div>
					</div>

					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label></label>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="" id="eb-cf-dialog-required" type="checkbox" name='eb-cf-dialog-required' value="" required="">
							<label for='eb-cf-dialog-required'> <?php esc_html_e( 'Required', 'edwiser-bridge-pro' ); ?> </label>
						</div>
					</div>

					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label></label>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="" id="eb-cf-dialog-sync-moodle" type="checkbox" name='eb-cf-dialog-sync-moodle' value="" required="">
							<label for='eb-cf-dialog-sync-moodle'><?php esc_html_e( 'Sync On Moodle', 'edwiser-bridge-pro' ); ?></label>
						</div>
					</div>

					<?php
					$modules_data = get_option( 'eb_pro_modules_data' );
					if ( isset( $modules_data['woo_integration'] ) && 'active' === $modules_data['woo_integration'] ) {
						?>
						<div class="eb-cf-pop-up-fields">
							<div class="eb-cf-pop-up-lbl">
								<label></label>
							</div>
							<div class="eb-cf-pop-up-right-field">
								<input class="" id="eb-cf-dialog-checkout" type="checkbox" name='eb-cf-dialog-checkout' value="" required="">
								<label for="eb-cf-dialog-checkout"><?php esc_html_e( 'Display on checkout page', 'edwiser-bridge-pro' ); ?></label>
							</div>
						</div>
						<div class="eb-cf-pop-up-fields">
							<div class="eb-cf-pop-up-lbl">
								<label></label>
							</div>
							<div class="eb-cf-pop-up-right-field">
								<input class="" id="eb-cf-dialog-woo-reg" type="checkbox" name='eb-cf-dialog-woo-reg' value="" required="">
								<label for="eb-cf-dialog-woo-reg"><?php esc_html_e( 'Display on Woocommerce registration page', 'edwiser-bridge-pro' ); ?></label>
							</div>
						</div>
						<div class="eb-cf-pop-up-fields">
							<div class="eb-cf-pop-up-lbl">
								<label></label>
							</div>
							<div class="eb-cf-pop-up-right-field">
								<input class="" id="eb-cf-dialog-woo-my-accnt" type="checkbox" name='eb-cf-dialog-woo-my-accnt' value="" required="">
								<label for="eb-cf-dialog-woo-my-accnt"><?php esc_html_e( 'Display on My Account page', 'edwiser-bridge-pro' ); ?></label>
							</div>
						</div>
						<?php
					}
					?>
					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label></label>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="" id="eb-cf-dialog-eb-reg" type="checkbox" name='eb-cf-dialog-eb-reg' value="" required="">
							<label for="eb-cf-dialog-eb-reg"><?php esc_html_e( 'Display on Edwiser registration page', 'edwiser-bridge-pro' ); ?></label>
						</div>
					</div>
					<div class="eb-cf-pop-up-fields">
						<div class="eb-cf-pop-up-lbl">
							<label></label>
						</div>
						<div class="eb-cf-pop-up-right-field">
							<input class="" id="eb-cf-dialog-eb-user-accnt" type="checkbox" name='eb-cf-dialog-eb-user-accnt' value="" required="">
							<label for="eb-cf-dialog-eb-user-accnt"><?php esc_html_e( 'Display on Edwiser User Account page', 'edwiser-bridge-pro' ); ?></label>
						</div>
					</div>

				</div>
			</div>
		</div>

		<?php

	}

	/**
	 * Field pop up content.
	 */
	public function eb_cf_pop_up_delete() {
		?>
		<div class="eb-cf-pop-up-cont-wrap">
			<!-- Table error msg -->

			<div title="<?php esc_html_e( 'Delete Custom Field', 'edwiser-bridge-pro' ); ?>" class="eb-cf-pop-up-delete">
				<div class='eb_cf_delete_error_msg'></div>
				<p>
					<?php esc_html_e( 'Are you sure you want to delete this custom field?', 'edwiser-bridge-pro' ); ?>
				</p>
			</div>
		</div>

		<?php

	}

	/**
	 * Save custom fields
	 */
	public function save_custom_fields() {
		$response = array(
			'status'  => 'error',
			'message' => __( 'Error while saving custom fields', 'edwiser-bridge-pro' ),
		);
		// check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'eb_cf_dialog_nonce' ) ) {
			$response['message'] = __( 'Invalid nonce', 'edwiser-bridge-pro' );
			wp_send_json( $response );
		}

		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array(); // @codingStandardsIgnoreLine
		if ( empty( $data ) ) {
			$response['message'] = __( 'No data found', 'edwiser-bridge-pro' );
			wp_send_json( $response );
		}

		// get old custom fields.
		$custom_fields = get_option( 'edwiser_custom_fields', array() );

		$field_name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		if ( empty( $field_name ) ) {
			$response['message'] = __( 'Field name is required', 'edwiser-bridge-pro' );
			wp_send_json( $response );
		}
		unset( $data['name'] );

		// check if this is new field or existing field.
		if ( isset( $data['old_name'] ) ) {
			$old_name = sanitize_text_field( $data['old_name'] );
			unset( $data['old_name'] );
			// if old name is not same as new name then remove old field.
			if ( $old_name !== $field_name ) {
				unset( $custom_fields[ $old_name ] );
			}
		}

		// for select field.
		if ( 'select' === $data['type'] ) {
			$options = isset( $data['options'] ) ? $data['options'] : '';
			$options = stripslashes( $options );
			$options = str_replace( "'", '"', $options );
			$options = (array) json_decode( $options );
			// unset options from data.
			unset( $data['options'] );
			foreach ( $options as $option_value => $option_text ) {
				$data['options'][ $option_value ] = sanitize_text_field( $option_text );
			}
		}

		$custom_fields[ $field_name ] = $data;
		update_option( 'edwiser_custom_fields', $custom_fields );

		$response['status']  = 'success';
		$response['message'] = __( 'Custom field saved successfully', 'edwiser-bridge-pro' );
		wp_send_json( $response );

	}

	/**
	 * Delete custom fields
	 */
	public function delete_custom_fields() {
		$response = array(
			'status'  => 'error',
			'message' => __( 'Error while deleting custom fields', 'edwiser-bridge-pro' ),
		);
		// check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'eb_cf_dialog_nonce' ) ) {
			$response['message'] = __( 'Invalid nonce', 'edwiser-bridge-pro' );
			wp_send_json( $response );
		}

		$field = isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : ''; // @codingStandardsIgnoreLine
		if ( '' === $field ) {
			$response['message'] = __( 'No data found', 'edwiser-bridge-pro' );
			wp_send_json( $response );
		}

		// get old custom fields.
		$custom_fields = get_option( 'edwiser_custom_fields', array() );
		unset( $custom_fields[ $field ] );
		update_option( 'edwiser_custom_fields', $custom_fields );
		$response['status']  = 'success';
		$response['message'] = __( 'Custom field deleted successfully', 'edwiser-bridge-pro' );
		wp_send_json( $response );
	}

	/**
	 * Save order of custom fields.
	 */
	public function save_custom_field_order() {
		$custom_fields_name = isset( $_POST['eb-cf-tbl-name'] ) ? wp_unslash( $_POST['eb-cf-tbl-name'] ) : array(); // @codingStandardsIgnoreLine

		$custom_fields     = get_option( 'edwiser_custom_fields', array() );
		$custom_fields_new = array();
		foreach ( $custom_fields_name as $field_name ) {
			$custom_fields_new[ $field_name ] = $custom_fields[ $field_name ];
		}
		update_option( 'edwiser_custom_fields', $custom_fields_new );
	}

	/**
	 * Handle bulk actions.
	 */
	public function handle_bulk_actions() {
		$response = array(
			'status'  => 'error',
			'message' => __( 'Error while performing bulk action', 'edwiser-bridge-pro' ),
		);
		// check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'eb_cf_dialog_nonce' ) ) {
			$response['message'] = __( 'Invalid nonce', 'edwiser-bridge-pro' );
			wp_send_json( $response );
		}

		$action             = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$custom_field_names = isset( $_POST['custom_fields'] ) ? wp_unslash( $_POST['custom_fields'] ) : array(); // @codingStandardsIgnoreLine
		if ( '' === $action || empty( $custom_field_names ) ) {
			$response['message'] = __( 'No data found', 'edwiser-bridge-pro' );
			wp_send_json( $response );
		}

		// get old custom fields.
		$custom_fields = get_option( 'edwiser_custom_fields', array() );
		if ( 'enable' === $action ) {
			foreach ( $custom_fields as $field_name => $field ) {
				if ( in_array( $field_name, $custom_field_names ) ) { // @codingStandardsIgnoreLine
					$custom_fields[ $field_name ]['enabled'] = 1;
				}
			}
		} elseif ( 'disable' === $action ) {
			foreach ( $custom_fields as $field_name => $field ) {
				if ( in_array( $field_name, $custom_field_names ) ) { // @codingStandardsIgnoreLine
					$custom_fields[ $field_name ]['enabled'] = 0;
				}
			}
		} elseif ( 'delete' === $action ) {
			foreach ( $custom_fields as $field_name => $field ) {
				if( in_array( $field_name, $custom_field_names ) ) { // @codingStandardsIgnoreLine
					unset( $custom_fields[ $field_name ] );
				}
			}
		}

		update_option( 'edwiser_custom_fields', $custom_fields );
		$response['status']  = 'success';
		$response['message'] = __( 'Bulk action performed successfully', 'edwiser-bridge-pro' );
		wp_send_json( $response );

	}
}

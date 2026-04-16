<?php

/**
 * User_Registration_Payments_Admin
 *
 * @package  User_Registration_Payments_Admin
 * @since  1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class User_Registration_Payments_Admin
 */
class User_Registration_Payments_Admin {


	/**
	 * User_Registration_Payments_Admin Constructor
	 */
	public function __construct() {

		// Payment Status on users tab.
		add_filter( 'manage_users_columns', array( $this, 'add_column_head' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'add_column_cell' ), 10, 3 );

		// Payment Status Display.
		add_action( 'user_registration_after_user_extra_information', array( $this, 'show_payment_status' ), 99 );

		// Payment Settings Hooks.
		add_filter( 'user_registration_get_settings_pages', array( $this, 'add_payment_setting' ), 10, 1 );

		// Payment Fields Hooks.
		add_action( 'user_registration_extra_fields', array( $this, 'render_payment_fields_section' ) );

		// Update User Payment.
		add_filter( 'wp_pre_insert_user_data', array( $this, 'update_payment_status' ), 10, 4 );

		// total field one time dragable
		add_filter( 'user_registration_one_time_draggable_form_fields', array( $this, 'ur_total_field_one_time_drag' ), 10, 1 );

		// Payment Fields Settings Hooks.
		add_filter( 'user_registration_single_item_advance_class', array( $this, 'field_advance_settings' ) );
		add_filter( 'user_registration_total_field_advance_class', array( $this, 'total_field_advance_settings' ) );
		add_filter( 'user_registration_multiple_choice_advance_class', array( $this, 'multiple_choice_advance_settings' ) );
		add_filter( 'user_registration_quantity_field_advance_class', array( $this, 'quantity_field_advance_settings' ) );
		add_filter( 'user_registration_field_options_general_settings', array( $this, 'field_general_settings' ), 10, 2 );
		add_filter( 'user_registration_login_options', array( $this, 'add_payment_login_option' ) );

		// Frontend message settings.
		add_filter( 'user_registration_frontend_messages_settings', array( $this, 'add_paypal_frontend_message' ) );

		// Range Fields Settings Hooks.
		add_filter( 'user_registration_range_field_advance_settings', array( $this, 'custom_advance_setting' ) );

		// Payment Fields Data Hooks.
		add_filter( 'user_registration_form_field_quantity_field_params', array( $this, 'add_target_field' ), 10, 2 );

		// Sanitize Input values.
		add_filter( 'user_registration_field_setting_single_item', array( $this, 'sanitize_single_item_settings' ) );
		add_filter( 'user_registration_field_setting_multiple_choice', array( $this, 'sanitize_multiple_choice_settings' ) );
		add_filter( 'user_registration_form_setting_user_registration_paypal_interval_count', 'absint' );
	}

	/**
	 * Add the column header for the email status column
	 *
	 * @param array $columns Columns.
	 *
	 * @return array
	 */
	public function add_column_head( $columns ) {
		if ( ! current_user_can( 'edit_user' ) ) {
			return $columns;
		}

		$the_columns['ur_user_payment_status'] = __( 'Payment Status', 'user-registration' );

		$newcol  = array_slice( $columns, 0, -1 );
		$newcol  = array_merge( $newcol, $the_columns );
		$columns = array_merge( $newcol, array_slice( $columns, 1 ) );

		return $columns;
	}

	/**
	 * Payment Status display on user profile
	 *
	 * @param mixed $user User Data.
	 * @return void
	 * @throws Exception Error Messages.
	 */
	public function show_payment_status( $user ) {

		// Get form id.
		$form_id = get_user_meta( $user->ID, 'ur_form_id', true );

		// Check if PayPal payment is enabled or not.
		$paypal_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard', false ) );

		// Check if Stripe payment is enabled or not.
		$stripe_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_stripe', false ) );

		// Filter to check if other payments are enabled.
		$payment_is_enabled = apply_filters( 'user_registration_enable_payment', $paypal_is_enabled ? $paypal_is_enabled : $stripe_is_enabled );

		if ( ! $payment_is_enabled ) {
			return;
		}

		// Return if current user cannot edit users.
		if ( ! current_user_can( 'edit_user' ) ) {
			throw new Exception( 'You donot have enough permission to perform this action' );
		}

		$payment_status          = array(
			'ur_payment_transaction'  => esc_html__( 'Transaction ID', 'user-registration' ),
			'ur_payment_method'       => esc_html__( 'Payment Method', 'user-registration' ),
			'ur_payment_currency'     => esc_html__( 'Payment Currency', 'user-registration' ),
			'ur_payment_total_amount' => esc_html__( 'Total Amount', 'user-registration' ),
		);
		$ur_payment_subscription = get_user_meta( $user->ID, 'ur_payment_subscription', true );
		$ur_payment_method       = get_user_meta( $user->ID, 'ur_payment_method', true );

		if ( '' !== $ur_payment_subscription ) {
			$payment_status['ur_payment_interval']               = esc_html__( 'Subscription Period', 'user-registration' );
			$payment_status['ur_payment_customer']               = esc_html__( 'Customer ID', 'user-registration' );
			$payment_status['ur_payment_subscription']           = esc_html__( 'Subscription ID', 'user-registration' );
			$payment_status['ur_payment_subscription_status']    = esc_html__( 'Subscription Status', 'user-registration' );
			$payment_status['ur_payment_subscription_plan_name'] = esc_html__( 'Subscription Plan Name', 'user-registration' );
			$payment_status['ur_payment_subscription_expiry']    = esc_html__( 'Subscription Expiry Date', 'user-registration' );
		}
		$payment_status['ur_payment_status'] = esc_html__( 'Payment Status', 'user-registration' );

		if ( 'paypal_standard' === $ur_payment_method ) {
			$payment_status['ur_payment_recipient'] = esc_html__( 'Payment Recipient', 'user-registration' );
			$payment_status['ur_payment_note']      = esc_html__( 'Payment Note', 'user-registration' );
		}
		$payment_status['ur_payment_mode'] = esc_html__( 'Payment Mode', 'user-registration' );
		?>
		<h3><?php esc_html_e( 'Payment Status', 'user-registration' ); ?></h3>
		<table class="form-table">
			<?php
			$payment_method = get_user_meta( $user->ID, 'ur_payment_method', true );
			if ( '' != $payment_method ) {

				$subscription_status = '';
				$subscription_id     = '';
				$customerid          = '';
				foreach ( $payment_status as $meta_key => $label ) {

					$value = get_user_meta( $user->ID, $meta_key, true );

					if ( 'ur_payment_subscription_status' === $meta_key ) {
						$value = 'cancel_at_end_of_cycle' === $value ? 'active' : $value;
					} elseif ( 'ur_payment_method' === $meta_key ) {
						$value = ( 'credit_card' == $value ) ? __( 'Stripe ( Credit Card )', 'user-registration' ) : $value;
						$value = ( 'ideal' == $value ) ? __( 'Stripe ( iDEAL )', 'user-registration' ) : $value;
						$value = ( 'paypal_standard' == $value ) ? __( 'PayPal Standard', 'user-registration' ) : $value;
					} elseif ( 'ur_payment_mode' === $meta_key ) {

						if ( 'test' == $value ) {
							$value = __( 'Test/Sandbox', 'user-registration' );
						} elseif ( 'production' === $value || 'live' == $value ) {
							$value = __( 'Production', 'user-registration' );
						}
					} elseif ( 'ur_payment_currency' === $meta_key ) {
						$currencies = ur_payment_integration_get_currencies();
						$value      = $currencies[ $value ]['name'] . ' ( ' . $value . ' ' . $currencies[ $value ]['symbol'] . ' )';
					} elseif ( 'ur_payment_status' === $meta_key ) {
						$completed_selected = 'completed' === $value ? 'selected="selected"' : '';
						$pending_selected   = 'pending' === $value ? 'selected="selected"' : '';
						echo '
								<tr>
									<th>
										<label for="' . esc_attr( $meta_key ) . '">' . esc_html( $label ) . '</label>
									</th>
									<td>
										<select name="' . esc_attr( $meta_key ) . '" id="' . esc_attr( $meta_key ) . '">
											<option ' . esc_attr( $completed_selected ) . ' value="completed">Completed</option>
											<option ' . esc_attr( $pending_selected ) . ' value="pending">Pending</option>
										</select>
									</td>
								</tr>';

						break;
					}

					echo '<tr>
						<th><label for="' . esc_attr( $meta_key ) . '">' . esc_html( $label ) . '</label>
						</th>
						<td>
							' . esc_html( $value ) . '
						</td>
					</tr>';
				}
			} else {
				echo '<tr><th><label>' . esc_html__( 'Payments Details not available.', 'user-registration' ) . '</label></th></tr>';
			}
			?>
		</table>

		<?php
	}

	/**
	 * Update user payment status value.
	 *
	 * @param [array] $data Data.
	 * @param [bool]  $update Is update process.
	 * @param [int]   $user_id User Id.
	 * @param [array] $userdata User Data.
	 * @return array $data
	 */
	public function update_payment_status( $data, $update, $user_id, $userdata ) {
		if ( $update ) {
			if ( ! empty( $_POST['ur_payment_status'] ) ) {
				update_user_meta( $user_id, 'ur_payment_status', sanitize_text_field( $_POST['ur_payment_status'] ) );
			}
		}

		return $data;
	}

	/**
	 * Set the status value for each user in the users list
	 *
	 * @param string $val Value.
	 * @param string $column_name Column Name.
	 * @param int    $user_id User Id.
	 *
	 * @return string
	 */
	public function add_column_cell( $val, $column_name, $user_id ) {
		if ( ! current_user_can( 'edit_user' ) ) {
			return false;
		}

		if ( 'ur_user_payment_status' === $column_name ) {
			$val = get_user_meta( $user_id, 'ur_payment_status', true );
		}

		return $val;
	}

	/**
	 * Add payment setting.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public function add_payment_setting( $settings ) {
		if ( class_exists( 'UR_Settings_Page' ) ) {

			$settings[] = include_once __DIR__ . '/admin/settings/class-ur-pro-payment-settings.php';
		}
		return $settings;
	}

	/**
	 * Html for Payment Fields
	 *
	 * @return void
	 */
	public function render_payment_fields_section() {
		echo '<h2 class="ur-toggle-heading">' . esc_html__( 'Payment Fields', 'user-registration' ) . '</h2><hr/>';
		$this->get_payment_fields();
	}

	/**
	 * Payment fields Render
	 *
	 * @return void
	 */
	public function get_payment_fields() {

		$payment_fields = apply_filters( 'user_registration_payment_fields', user_registration_payment_fields() );

		echo ' <ul id = "ur-draggabled" class="ur-registered-list ur-payment-fields" > ';
		foreach ( $payment_fields as $field ) {
			$get_list = new UR_Admin_Menus();
			$get_list->ur_get_list( $field );
		}
		echo ' </ul > ';
	}

	/**
	 * Advance settings for single item.
	 *
	 * @param mixed $file_data File Data.
	 * @return array
	 */
	public function field_advance_settings( $file_data ) {

		$path                   = __DIR__ . '/form/settings/class-ur-setting-single-item.php';
		$file_data['file_path'] = $path;
		return $file_data;
	}

	/**
	 * Multiple Choice  Advance class.
	 *
	 * @param array $file_data File Data.
	 * @return array
	 */
	public function multiple_choice_advance_settings( $file_data ) {
		$path                   = __DIR__ . '/form/settings/class-ur-setting-multiple-choice.php';
		$file_data['file_path'] = $path;
		return $file_data;
	}

	/**
	 * Advance settings for Total.
	 *
	 * @param mixed $file_data File Data.
	 * @return array
	 */
	public function total_field_advance_settings( $file_data ) {
		$path                   = __DIR__ . '/form/settings/class-ur-setting-total-field.php';
		$file_data['file_path'] = $path;
		return $file_data;
	}

	/**
	 * Advance settings for Quantity.
	 *
	 * @param mixed $file_data File Data.
	 * @return array
	 */
	public function quantity_field_advance_settings( $file_data ) {
		$path                   = __DIR__ . '/form/settings/class-ur-setting-quantity-field.php';
		$file_data['file_path'] = $path;
		return $file_data;
	}

	/**
	 *  Modify general settings.
	 *
	 * @param array  $general_settings Setting for field.
	 * @param string $id field Id.
	 * @return  array $general_settings
	 */
	public function field_general_settings( $general_settings, $id ) {

		switch ( $id ) {
			case 'user_registration_single_item':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}
				break;
			case 'user_registration_total_field':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}
				break;
			case 'user_registration_multiple_choice':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}

				$new_settings     = array(
					'options' => array(
						'setting_id'  => 'options',
						'type'        => 'checkbox',
						'label'       => __( 'Options', 'user-registration' ),
						'name'        => 'ur_general_setting[options]',
						'placeholder' => '',
						'required'    => true,
						'options'     => array(
							array(
								'label' => __( 'First Choice', 'user-registration' ),
								'value' => '10.00',
							),
							array(
								'label' => __( 'Second Choice', 'user-registration' ),
								'value' => '20.00',
							),
							array(
								'label' => __( 'Third Choice', 'user-registration' ),
								'value' => '30.00',
							),
						),
						'tip'         => __( 'Add options to let users select from.', 'user-registration' ),
					),
				);
				$general_settings = ur_insert_after_helper( $general_settings, $new_settings, 'field_name' );
				break;
			case 'user_registration_quantity':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}
				break;
		}

		return $general_settings;
	}

	/**
	 * Add Payment Before Registration option.
	 *
	 * @param  array $options Other login options.
	 * @return  array
	 */
	public function add_payment_login_option( $options ) {

		$options['payment'] = esc_html__( 'Payment before login', 'user-registration' );
		return $options;
	}

	/**
	 * Add paypal frontend messages.
	 *
	 * @param array $settings Settings.
	 */
	public function add_paypal_frontend_message( $settings ) {
		$settings['sections']['payment_pending_messages_settings'] = array(
			'title'    => __( 'Payment Messages', 'user-registration' ),
			'type'     => 'card',
			'desc'     => '',
			'settings' => array(
				array(
					'title'    => __( 'Payment Before Login', 'user-registration' ),
					'desc'     => __( 'Enter the text message for pending payment error message before login.', 'user-registration' ),
					'id'       => 'user_registration_pro_pending_payment_error_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'Your account is still pending payment. Process the payment by clicking on this: <a id="payment-link" href="%s">link</a>', 'user-registration' ),
				),
				array(
					'title'    => __( 'Payment Before Registration', 'user-registration' ),
					'desc'     => __( 'Enter the text message after for pending  payment.', 'user-registration' ),
					'id'       => 'user_registration_payment_before_registration_pending_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'User Registered. Payment Processing...', 'user-registration' ),
				),
				array(
					'title'    => __( 'Payment Completed', 'user-registration' ),
					'desc'     => __( 'Enter the text message after for payment completed.', 'user-registration' ),
					'id'       => 'user_registration_payment_completed_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'User Registered. Payment Completed.', 'user-registration' ),
				),
			),
		);

		return $settings;
	}

	/**
	 * Add custom advance setting
	 *
	 * @param array $settings Settings.
	 */

	public function custom_advance_setting( $fields ) {
		$custom_advance_setting = array(
			'enable_payment_slider' => array(
				'type'     => 'toggle',
				'data-id'  => 'range_advance_setting_enable_payment_slider',
				'label'    => __( 'Enable Payment Slider', 'user-registration' ),
				'name'     => 'range_advance_setting[enable_payment_slider]',
				'class'    => 'ur_advance_setting ur-settings-enable-payment-slider',
				'default'  => 'false',
				'required' => false,
				'tip'      => __( 'Enable this if you want use text Payment slider ', 'user-registration' ),
			),
		);
		$fields                 = array_merge( $fields, $custom_advance_setting );
		return $fields;
	}

	/**
	 * Make total field one time draggable.
	 *
	 * @since 1.2.0
	 * @param array $fields One time draggable fields.
	 * @return array    One time draggable fields.
	 */
	public function ur_total_field_one_time_drag( $fields ) {
		$fields[] = 'total_field';
		return $fields;
	}

	/**
	 * Add Target Field Name for Quantity Field in form data.
	 *
	 * @param [array] $data Quantity Field Data.
	 * @param [array] $fields Quantity Field Settings.
	 * @return array $data
	 */
	public function add_target_field( $data, $fields ) {

		if ( ! isset( $fields->advance_setting->target_field ) ) {
			exit;
		}

		$data->extra_params['target_field'] = $fields->advance_setting->target_field;

		return $data;
	}

	/**
	 * Sanitize negative inputs for single item price value.
	 *
	 * @param [object] $setting Single Item Setting.
	 * @return object
	 */
	public function sanitize_single_item_settings( $setting ) {

		$default_value = abs( floatval( $setting->advance_setting->default_value ) );

		$setting->advance_setting->default_value = $default_value;

		return $setting;
	}


	/**
	 * Sanitize negative and invalid inputs for multiple choice price value.
	 *
	 * @param [object] $setting Multiple Choice Setting.
	 * @return object
	 */
	public function sanitize_multiple_choice_settings( $setting ) {
		foreach ( $setting->general_setting->options as $key => $item ) {
			$setting->general_setting->options[ $key ]->value = abs( floatval( $item->value ) );
		}
		return $setting;
	}
}

new User_Registration_Payments_Admin();

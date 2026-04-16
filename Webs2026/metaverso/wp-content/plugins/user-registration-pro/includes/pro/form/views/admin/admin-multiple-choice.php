<?php
/**
 * Form View: Input Type Multiple Choice
 * @package User_Registration_Payments
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Compatibility for older version. Get string value from options in advanced settings. Modified since @1.5.7
$default_options = isset( $this->field_defaults['default_options'] ) ? $this->field_defaults['default_options'] : array();
$old_options     = isset( $this->admin_data->advance_setting->choices ) ? explode( ',', trim( $this->admin_data->advance_setting->choices, ',' ) ) : $default_options;
$options         = isset( $this->admin_data->general_setting->options ) ? $this->admin_data->general_setting->options : $old_options;
$default_values  = isset( $this->admin_data->general_setting->default_value ) ? $this->admin_data->general_setting->default_value : array();
?>

<div class="ur-input-type-multiple_choice ur-admin-template">
	<div class="ur-label">
		<label><?php echo esc_html( $this->get_general_setting_data( 'label' ) ); ?></label>
	</div>
	<div class="ur-field" data-field-key="multiple_choice">
		<?php
		if ( count( $options ) < 1 ) {
			echo "<label><input type = 'checkbox'  value='1' disabled/></label>";
		}

		foreach ( $options as $option ) {
			$label   = is_array( $option ) ? $option[ 'label' ] : $option->label;
			$value   = is_array( $option ) ? $option[ 'value' ] : $option->value;
			$currency   = get_option( 'user_registration_payment_currency', 'USD' );
			$currencies = ur_payment_integration_get_currencies();
			$currency = $currency . ' ' . $currencies[ $currency ]['symbol'];
			$checked = in_array( $label, $default_values ) ? 'checked' : '';
			echo "<label><input type = 'checkbox'  value='" . esc_attr( trim( $label ) ) . "' " . $checked . ' disabled/>' . esc_html( trim( $label ) ) . ' - ' .$currency. ' ' .esc_html($value). '</label>';
		}
		?>
	</div>
</div>

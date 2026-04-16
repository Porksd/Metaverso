<?php
/**
 * UserRegistrationPayments Functions.
 *
 * General core functions available on both the front-end and admin.
 *
 * @package UserRegistrationPayments/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'user_registration_field_keys', 'ur_get_payment_field_type', 10, 2 );
add_filter( 'user_registration_single_item_admin_template', 'ur_add_single_item_template' );
add_filter( 'user_registration_total_field_admin_template', 'ur_add_total_field_template' );
add_filter( 'user_registration_multiple_choice_admin_template', 'ur_add_multiple_choice_payment_template' );
add_filter( 'user_registration_quantity_field_admin_template', 'ur_add_quantity_field_template' );
add_filter( 'user_registration_sanitize_field', 'ur_sanitize_payment_fields', 10, 2 );
add_filter( 'user_registration_payments_currencies', 'ur_support_extra_currencies' );

add_filter( 'user_registration_form_field_single_item_path', 'ur_add_single_item_field' );
add_filter( 'user_registration_form_field_total_field_path', 'ur_add_total_field' );
add_filter( 'user_registration_form_field_multiple_choice_path', 'ur_add_multiple_choice_payment_field' );
add_filter( 'user_registration_form_field_quantity_field_path', 'ur_add_quantity_field' );


/**
 * Sanitize payment fields on frontend submit
 *
 * @param  mixed  $form_data Form Data.
 * @param  string $field_key Field Key.
 * @return array
 */
function ur_sanitize_payment_fields( $form_data, $field_key ) {
	switch ( $field_key ) {
		case 'single_item':
			$form_data->value = user_registration_sanitize_amount( $form_data->value, 'USD' );
			break;
	}

	return $form_data;
}

/**
 * Add single item field
 */
function ur_add_single_item_field() {
	include_once __DIR__ . '/form/class-ur-form-field-single-item.php';
}

/**
 * Add total field
 */
function ur_add_total_field() {
	include_once __DIR__ . '/form/class-ur-form-field-total.php';
}

/*
 * Add Multiple Choice Payment field
 */
function ur_add_multiple_choice_payment_field() {
	include_once __DIR__ . '/form/class-ur-form-field-multiple-choice.php';
}

/**
 * Add quantity field
 */
function ur_add_quantity_field() {
	include_once __DIR__ . '/form/class-ur-form-field-quantity.php';
}

/**
 * Single item field template
 *
 * @return  string
 */
function ur_add_single_item_template() {
	$path = __DIR__ . '/form/views/admin/admin-single-item.php';
	return $path;
}

/**
 * Total field template
 *
 * @return  string
 */
function ur_add_total_field_template() {
	$path = __DIR__ . '/form/views/admin/admin-total-field.php';
	return $path;
}

/*
 * Multiple Choice field template
 *
 * @return  string
 */
function ur_add_multiple_choice_payment_template() {
	$path = __DIR__ . '/form/views/admin/admin-multiple-choice.php';
	return $path;
}

/*
 * Quantity field template
 *
 * @return  string
 */
function ur_add_quantity_field_template() {
	$path = __DIR__ . '/form/views/admin/admin-quantity-field.php';
	return $path;
}

/**
 * Assign field type to single item
 *
 * @param  string $field_type Field Type.
 * @param  string $field_key Field Key.
 * @return string
 */
function ur_get_payment_field_type( $field_type, $field_key ) {

	if ( 'single_item' === $field_key ) {
		$field_type = 'single_item';
	}
	if ( 'total_field' === $field_key ) {
		$field_type = 'total_field';
	}
	if ( 'multiple_choice' === $field_key ) {
		$field_type = 'multiple_choice';
	}
	if ( 'quantity_field' === $field_key ) {
		$field_type = 'quantity_field';
	}
	return $field_type;
}

/**
 * All payment fields
 *
 * @return  array
 */
function user_registration_payment_fields() {
	return apply_filters(
		'user_registration_payment_fields',
		array(
			'single_item',
			'total_field',
			'multiple_choice',
			'quantity_field',
		)
	);
}

/**
 * Get supported currencies.
 *
 * @since 1.0.0
 *
 * @return array
 */
function ur_payment_integration_get_currencies() {

	$currencies = array(
		'USD' => array(
			'name'                => esc_html__( 'U.S. Dollar', 'user-registration' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'GBP' => array(
			'name'                => esc_html__( 'Pound Sterling', 'user-registration' ),
			'symbol'              => '&pound;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'EUR' => array(
			'name'                => esc_html__( 'Euro', 'user-registration' ),
			'symbol'              => '&euro;',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'AUD' => array(
			'name'                => esc_html__( 'Australian Dollar', 'user-registration' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'BRL' => array(
			'name'                => esc_html__( 'Brazilian Real', 'user-registration' ),
			'symbol'              => 'R$',
			'symbol_pos'          => 'left',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'CAD' => array(
			'name'                => esc_html__( 'Canadian Dollar', 'user-registration' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'CZK' => array(
			'name'                => esc_html__( 'Czech Koruna', 'user-registration' ),
			'symbol'              => '&#75;&#269;',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'DKK' => array(
			'name'                => esc_html__( 'Danish Krone', 'user-registration' ),
			'symbol'              => 'kr.',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'HKD' => array(
			'name'                => esc_html__( 'Hong Kong Dollar', 'user-registration' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'right',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'HUF' => array(
			'name'                => esc_html__( 'Hungarian Forint', 'user-registration' ),
			'symbol'              => 'Ft',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'ILS' => array(
			'name'                => esc_html__( 'Israeli New Sheqel', 'user-registration' ),
			'symbol'              => '&#8362;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'MYR' => array(
			'name'                => esc_html__( 'Malaysian Ringgit', 'user-registration' ),
			'symbol'              => '&#82;&#77;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'MXN' => array(
			'name'                => esc_html__( 'Mexican Peso', 'user-registration' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'NOK' => array(
			'name'                => esc_html__( 'Norwegian Krone', 'user-registration' ),
			'symbol'              => 'Kr',
			'symbol_pos'          => 'left',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'NZD' => array(
			'name'                => esc_html__( 'New Zealand Dollar', 'user-registration' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'PHP' => array(
			'name'                => esc_html__( 'Philippine Peso', 'user-registration' ),
			'symbol'              => 'Php',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'PLN' => array(
			'name'                => esc_html__( 'Polish Zloty', 'user-registration' ),
			'symbol'              => '&#122;&#322;',
			'symbol_pos'          => 'left',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'RUB' => array(
			'name'                => esc_html__( 'Russian Ruble', 'user-registration' ),
			'symbol'              => 'pyб',
			'symbol_pos'          => 'right',
			'thousands_separator' => ' ',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'SGD' => array(
			'name'                => esc_html__( 'Singapore Dollar', 'user-registration' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'ZAR' => array(
			'name'                => esc_html__( 'South African Rand', 'user-registration' ),
			'symbol'              => 'R',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'SEK' => array(
			'name'                => esc_html__( 'Swedish Krona', 'user-registration' ),
			'symbol'              => 'Kr',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'CHF' => array(
			'name'                => esc_html__( 'Swiss Franc', 'user-registration' ),
			'symbol'              => 'CHF',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'TWD' => array(
			'name'                => esc_html__( 'Taiwan New Dollar', 'user-registration' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'THB' => array(
			'name'                => esc_html__( 'Thai Baht', 'user-registration' ),
			'symbol'              => '&#3647;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
	);

	return apply_filters( 'user_registration_payments_currencies', $currencies );
}

/**
 * Sanitize Amount.
 *
 * Returns a sanitized amount by stripping out thousands separators.
 *
 * @since 1.0.0
 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/formatting.php#L24
 *
 * @param string $amount Amount.
 * @param string $currency Currency.
 *
 * @return string $amount
 */
function user_registration_sanitize_amount( $amount, $currency = 'USD' ) {

	$currency      = strtoupper( $currency );
	$currencies    = ur_payment_integration_get_currencies();
	$thousands_sep = $currencies[ $currency ]['thousands_separator'];
	$decimal_sep   = $currencies[ $currency ]['decimal_separator'];
	$is_negative   = false;

	// Sanitize the amount.
	if ( ',' === $decimal_sep && false !== ( strpos( $amount, $decimal_sep ) ) ) {
		if ( ( '.' === $thousands_sep || ' ' === $thousands_sep ) && false !== ( strpos( $amount, $thousands_sep ) ) ) {
			$amount = str_replace( $thousands_sep, '', $amount );
		} elseif ( empty( $thousands_sep ) && false !== ( strpos( $amount, '.' ) ) ) {
			$amount = str_replace( '.', '', $amount );
		}
		$amount = str_replace( $decimal_sep, '.', $amount );
	} elseif ( ',' === $thousands_sep && false !== ( strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( $thousands_sep, '', $amount );
	}

	if ( $amount < 0 ) {
		$is_negative = true;
	}

	$amount   = preg_replace( '/[^0-9\.]/', '', $amount );
	$decimals = apply_filters( 'user_registration_sanitize_amount_decimals', 2, $amount );
	$amount   = number_format( (float) $amount, $decimals, '.', '' );

	if ( $is_negative ) {
		$amount *= - 1;
	}

	return $amount;
}

/**
 * Check if range is payment slider
 *
 * @param string $field_name Field Name.
 * @param int    $form_id Form ID.
 *
 * @since 1.1.4
 * @return  boolean $payment_slider
 */
function check_is_range_payment_slider( $field_name, $form_id ) {
	$post_content_array = ( $form_id ) ? UR()->form->get_form( $form_id, array( 'content_only' => true ) ) : array();
	$payment_slider     = false;

	if ( ! is_null( $post_content_array ) ) {
		foreach ( $post_content_array as $post_content_row ) {
			foreach ( $post_content_row as $post_content_grid ) {
				foreach ( $post_content_grid as $fields ) {
					if ( $field_name === $fields->general_setting->field_name && 'range' === $fields->field_key && ( isset( $fields->advance_setting->enable_payment_slider ) && ur_string_to_bool( $fields->advance_setting->enable_payment_slider ) ) ) {
						$payment_slider = true;
					}
				}
			}
		}
	}
	return $payment_slider;
}

/**
 * Support Extra currencies
 *
 * @param array $currencies currency.
 *
 * @since 1.4.3
 *
 * @return array $currencies.
 */
function ur_support_extra_currencies( $currencies ) {
	$extra_currencies = array(
		'CNY' => array(
			'name'                => esc_html__( 'Chinese Renmenbi ', 'user-registration' ),
			'symbol'              => '&yen;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'RON' => array(
			'name'                => esc_html__( 'Romanian Leu', 'user-registration' ),
			'symbol'              => 'lei',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'HRK' => array(
			'name'                => esc_html__( 'Croatian kuna', 'user-registration' ),
			'symbol'              => 'kn',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'INR' => array(
			'name'                => esc_html__( 'Indian rupee', 'user-registration' ),
			'symbol'              => '&#8377;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'TRY' => array(
			'name'                => esc_html__( 'Turkish lira', 'user-registration' ),
			'symbol'              => '&#8378;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'NGN' => array(
			'name'                => esc_html__( 'Nigerian naira', 'user-registration' ),
			'symbol'              => '&#8358;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'ZMW' => array(
			'name'                => esc_html__( 'Zambian Kwacha', 'user-registration' ),
			'symbol'              => 'ZK',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'GHS' => array(
			'name'                => esc_html__( 'Ghanaian cedi', 'user-registration' ),
			'symbol'              => 'GH&#8373;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
	);

	$currencies = array_merge( $currencies, $extra_currencies );
	return $currencies;
}

if ( ! function_exists( 'paypal_supported_currencies_list' ) ) {
	/**
	 * Paypal Supported Currencies list.
	 * From https://developer.paypal.com/docs/reports/reference/paypal-supported-currencies/
	 *
	 * @since 1.4.3
	 */
	function paypal_supported_currencies_list() {
		return array(
			'AUD',
			'BRL',
			'CAD',
			'CNY',
			'CZK',
			'DKK',
			'EUR',
			'HKD',
			'HUF',
			'ILS',
			'JPY',
			'MYR',
			'MXN',
			'TWD',
			'NZD',
			'NOK',
			'PHP',
			'PLN',
			'GBP',
			'RUB',
			'SGD',
			'SEK',
			'CHF',
			'THB',
			'USD',
		);
	}
}

if ( ! function_exists( 'ur_add_enable_selling_price_options' ) ) {
	/**
	 * Enable Discount price options.
	 */
	function ur_add_enable_selling_price_options( $general_setting, $id ) {

		if ( 'user_registration_multiple_choice' === $id ) {
			$setting_array   = array(
				'setting_id'  => 'selling-price',
				'type'        => 'toggle',
				'label'       => __( 'Enable Selling Price', 'user-registration' ),
				'name'        => 'ur_general_setting[selling_price]',
				'placeholder' => '',
				'required'    => true,
				'default'     => 'false',
				'tip'         => __( 'Check this option to enable selling price of this field.', 'user-registration' ),
			);
			$index           = array_search( 'description', array_keys( $general_setting ) );
			$general_setting = array_slice( $general_setting, 0, $index + 1, true ) + array( 'selling_price' => $setting_array ) + array_slice( $general_setting, $index + 1, null, true );
		}
		return $general_setting;
	}
}
add_filter( 'user_registration_field_options_general_settings', 'ur_add_enable_selling_price_options', 10, 2 );

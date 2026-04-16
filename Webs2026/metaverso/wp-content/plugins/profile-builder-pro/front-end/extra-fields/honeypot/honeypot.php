<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* handle field output */
function wppb_honeypot_handler( $output, $form_location, $field, $user_id, $field_check_errors, $request_data ){
    if ( $field['field'] == 'Honeypot' && $form_location != 'back_end' ){
        $item_title = apply_filters( 'wppb_'.$form_location.'_input_custom_field_'.$field['id'].'_item_title', wppb_icl_t( 'plugin profile-builder-pro', 'custom_field_'.$field['id'].'_title_translation', $field['field-title'], true ) );

        $extra_attr = apply_filters( 'wppb_extra_attribute', '', $field, $form_location );

        $error_mark = array_key_exists( $field['id'], $field_check_errors ) ? '<span class="wppb-required">*</span>' : '';

        $output = '
            <label for="1_website_url1" style="position: absolute; left: -9999px;">'.$item_title.$error_mark.'</label>
            <input class="'. apply_filters( 'wppb_fields_extra_css_class', '', $field ) .'" name="1_website_url1" maxlength="'. apply_filters( 'wppb_maximum_character_length', 250, $field ) .'" type="text" id="1_website_url" tabindex="-1" autocomplete="nope" style="position: absolute; left: -9999px;" value="" '. $extra_attr .'/>';

        return apply_filters( 'wppb_'.$form_location.'_honeypot_custom_field_'.$field['id'], $output, $form_location, $field, $user_id, $field_check_errors, $request_data );
    }
}
add_filter( 'wppb_output_form_field_honeypot', 'wppb_honeypot_handler', 10, 6 );
add_filter( 'wppb_admin_output_form_field_honeypot', 'wppb_honeypot_handler', 10, 6 );

/* handle field validation */
function wppb_check_honeypot_value( $message, $field, $request_data, $form_location ){
    if( $field['field'] == 'Honeypot' ){
        if ( isset( $request_data['1_website_url1'] ) && ( trim( $request_data['1_website_url1'] ) !== '' ) ){
            return __( 'Something isn\'t right.', 'profile-builder' );
        }
    }

    return $message;
}
add_filter( 'wppb_check_form_field_honeypot', 'wppb_check_honeypot_value', 10, 4 );

add_filter( 'wppb_field_css_class', 'wppb_honeypot_remove_field_class', 20, 3 );
function wppb_honeypot_remove_field_class( $classes, $field, $error ){
    
    if( is_admin() )
        return $classes;

    if( $field['field'] == 'Honeypot' ){
        return 'wppb-1website-url' . $error;
    }

    return $classes;

}
<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* handle CPT Select output */
function wppb_select_taxonomy_handler( $output, $form_location, $field, $user_id, $field_check_errors, $request_data ){
	if ( $field['field'] == 'Select (Taxonomy)' ){

		/* turn it in a select2 */
		wp_enqueue_script( 'wppb_select2_js', WPPB_PLUGIN_URL .'assets/js/select2/select2.min.js', array( 'jquery' ), PROFILE_BUILDER_VERSION );
		wp_enqueue_style( 'wppb_select2_css', WPPB_PLUGIN_URL .'assets/css/select2/select2.min.css', array(), PROFILE_BUILDER_VERSION );
		wp_enqueue_script( 'wppb-select-taxonomy-script', WPPB_PAID_PLUGIN_URL.'front-end/extra-fields/select-taxonomy/select-taxonomy.js', array('wppb_select2_js'), PROFILE_BUILDER_VERSION, true );
		wp_enqueue_style( 'wppb-select-taxonomy-style', WPPB_PAID_PLUGIN_URL.'front-end/extra-fields/select-taxonomy/select-taxonomy-style.css', array(), PROFILE_BUILDER_VERSION );

		$item_title = apply_filters( 'wppb_'.$form_location.'_taxonomy_select_custom_field_'.$field['id'].'_item_title', wppb_icl_t( 'plugin profile-builder-pro', 'custom_field_'.$field['id'].'_title_translation', $field['field-title'], true ) );
		$item_description = wppb_icl_t( 'plugin profile-builder-pro', 'custom_field_'.$field['id'].'_description_translation', $field['description'], true );

		$extra_attr = apply_filters( 'wppb_extra_attribute', '', $field, $form_location );

		if( $form_location != 'register' )
			$taxonomy_value = ( ( wppb_user_meta_exists ( $user_id, $field['meta-name'] ) != null ) ? get_user_meta( $user_id, $field['meta-name'], true ) : $field['default-option'] );
		else
			$taxonomy_value = ( isset( $field['default-option'] ) ? trim( $field['default-option'] ) : '' );

		$taxonomy_value = ( isset( $request_data[wppb_handle_meta_name( $field['meta-name'] )] ) ? trim( $request_data[wppb_handle_meta_name( $field['meta-name'] )] ) : $taxonomy_value );

		$args = apply_filters( 'wppb_taxonomy_select_args', array( 'taxonomy' => $field['taxonomy'], 'hide_empty' => false ), $field );
		// Get the terms
		$wppb_taxonomy_terms = get_terms($args);


		if ( $form_location != 'back_end' ){
			$error_mark = ( ( $field['required'] == 'Yes' ) ? '<span class="wppb-required" title="'.wppb_required_field_error($field["field-title"]).'">*</span>' : '' );

			if ( array_key_exists( $field['id'], $field_check_errors ) )
				$error_mark = '<img src="'.WPPB_PLUGIN_URL.'assets/images/pencil_delete.png" title="'.wppb_required_field_error($field["field-title"]).'"/>';

			$output = '
				<label for="'.$field['meta-name'].'">'.$item_title.$error_mark.'</label>
				<select name="'.$field['meta-name'].'" id="'.$field['meta-name'].'" class="custom_field_taxonomy_select '. apply_filters( 'wppb_fields_extra_css_class', '', $field ) .'" '. $extra_attr .'>';

			if( ! empty( $wppb_taxonomy_terms ) ) {
				$extra_select_option = apply_filters( 'wppb_extra_select_option', '', $field, $item_title );
				if( ! empty( $extra_select_option ) ) {
					$output .= $extra_select_option;
				} else {
					$output .= '<option value="">'. __( '...Choose', 'profile-builder' ) .'</option>';
				}

				foreach( $wppb_taxonomy_terms as $terms ) {
					if ( $terms->name == '' )
						$terms->name = 'No title. ID: ' . $terms->term_id;

					$output .= '<option value="'. esc_attr( $terms->term_id ) .'"  '. selected( $terms->term_id, $taxonomy_value, false ) .' >'. apply_filters( 'wppb_fields_taxonomy_select_label', esc_html( $terms->name ), $terms->term_id) .'</option>';
				}
			}

			$output .= '</select>';
			if( !empty( $item_description ) )
				$output .= '<span class="wppb-description-delimiter">'.$item_description.'</span>';

		}else{
			$item_title = ( ( $field['required'] == 'Yes' ) ? $item_title .' <span class="description">('. __( 'required', 'profile-builder' ) .')</span>' : $item_title );
			$output = '
				<table class="form-table">
					<tr>
						<th><label for="'.$field['meta-name'].'">'.$item_title.'</label></th>
						<td>
							<select class="custom_field_taxonomy_select" name="'.$field['meta-name'].'" id="'.$field['meta-name'].'"  '. $extra_attr .'/>';
			if( !empty( $wppb_taxonomy_terms ) ){
				$output .= apply_filters( 'wppb_taxonomy_select_field_first_option', '<option value="">'. __('...Choose', 'profile-builder') .'</option>', $field, $form_location );
				foreach( $wppb_taxonomy_terms as $terms ){
					if ( $terms->name == '' )
						$terms->name = 'No title. ID: ' . $terms->term_id;

					$output .= '<option value="'. esc_attr( $terms->term_id ) .'"  '. selected( $terms->term_id, $taxonomy_value, false ) .' >'. esc_html( $terms->name ) .'</option>';
				}
			}

			$output .=      '</select>
							<span class="description">'.$item_description.'</span>
						</td>
					</tr>
				</table>';
		}

		return apply_filters( 'wppb_'.$form_location.'_taxonomy_select_custom_field_'.$field['id'], $output, $form_location, $field, $user_id, $field_check_errors, $request_data, $taxonomy_value );
	}
}
add_filter( 'wppb_output_form_field_select-taxonomy', 'wppb_select_taxonomy_handler', 10, 6 );
add_filter( 'wppb_admin_output_form_field_select-taxonomy', 'wppb_select_taxonomy_handler', 10, 6 );

/* handle field save */
function wppb_save_select_taxonomy_value( $field, $user_id, $request_data, $form_location ){
	if( $field['field'] == 'Select (Taxonomy)' ){
		if ( isset( $request_data[wppb_handle_meta_name( $field['meta-name'] )] ) )
			update_user_meta( $user_id, $field['meta-name'], $request_data[wppb_handle_meta_name( $field['meta-name'] )] );
	}
}
add_action( 'wppb_save_form_field', 'wppb_save_select_taxonomy_value', 10, 4 );
add_action( 'wppb_backend_save_form_field', 'wppb_save_select_taxonomy_value', 10, 4 );

/* handle field validation */
function wppb_check_select_taxonomy_value( $message, $field, $request_data, $form_location ){
	if( $field['field'] == 'Select (Taxonomy)' ){
		if( $field['required'] == 'Yes' ){
			if ( ( isset( $request_data[wppb_handle_meta_name( $field['meta-name'] )] ) && ( trim( $request_data[wppb_handle_meta_name( $field['meta-name'] )] ) == '' ) ) || !isset( $request_data[wppb_handle_meta_name( $field['meta-name'] )] ) ){
				return wppb_required_field_error( $field["field-title"] );
			}
		}
	}

	return $message;
}
add_filter( 'wppb_check_form_field_select-taxonomy', 'wppb_check_select_taxonomy_value', 10, 4 );

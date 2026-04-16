<?php
/**
 * Defines the common functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Function to get the user profile url.
 *
 * @param int   $user_id User id.
 * @param bool  $with_a Boolean auth.
 * @param mixed $default default value.
 */
function get_user_profile_url( $user_id = '', $with_a = true, $default = '&ndash;' ) {
	$url = $default;

	$user_info = get_userdata( $user_id );

	if ( $user_info ) {
		$edit_link = get_edit_user_link( $user_id );
		$url       = $with_a ? '<a class="mucp_username_redirection" href="' . esc_url( $edit_link ) . '">' . $user_info->user_login . '</a>' : $edit_link;
	}

	return apply_filters( 'mucp_user_profile_url', $url, $user_id, $with_a, $default );
}

/**
 * Updating cohort id in the order meta.
 *
 * @param int $order_id Order id.
 * @param int $new_cohort_id New cohort id.
 * @param int $quantity Quntity.
 */
function update_cohort_id_in_order_meta( $order_id, $new_cohort_id, $quantity ) {
	$order       = wc_get_order( $order_id );
	$cohort_data = $order->get_meta( 'eb_bp_mdl_cohort_id', 1 );

	if ( $cohort_data ) {
		$cohort_data[ $new_cohort_id ] = $quantity;
	} else {
		$cohort_data = array( $new_cohort_id => $quantity );
	}
	$order->update_meta_data( 'eb_bp_mdl_cohort_id', $cohort_data );
	$order->save();
}


/**
 * Function to get the cohort detials.
 *
 * @param int $mdl_cohort_id moodle cohort id.
 */
function get_cohort_details( $mdl_cohort_id ) {
	global $wpdb;
	$tbl_name    = $wpdb->prefix . 'bp_cohort_info';
	$results     = $wpdb->get_row( $wpdb->prepare( "SELECT MDL_COHORT_ID, PRODUCTS, COURSES, COHORT_NAME, NAME FROM {$tbl_name} WHERE mdl_cohort_id = %d", $mdl_cohort_id ), ARRAY_A ); // @codingStandardsIgnoreLine
	$prods_array = $results['PRODUCTS'];
	$products    = maybe_unserialize( $results['PRODUCTS'] );
	$courses     = maybe_unserialize( $results['COURSES'] );
	$products    = array_values( $products );
	$min_qty     = min( $products );
	return array(
		'quantity'      => $min_qty,
		'name'          => '' !== $results['NAME'] ? $results['NAME'] : $results['COHORT_NAME'],
		'courses'       => $courses,
		'products'      => $prods_array,
		'mdl_cohort_id' => $results['MDL_COHORT_ID'],
	);
}

/**
 * Function to get the user role.
 */
function eb_bp_get_wp_user_reg_role() {
	$role       = '';
	$eb_options = get_option( 'eb_general', array() );
	if ( isset( $eb_options['eb_default_role'] ) && ! empty( $eb_options['eb_default_role'] ) ) {
		$role = apply_filters( 'eb_registration_role', $eb_options['eb_default_role'] );
	} else {
		$role = get_option( 'default_role' );
	}
	return $role;
}


if ( ! function_exists( 'eb_bp_get_allowed_html_tags' ) ) {
	/**
	 * Returns the list of the tags allowed in the wp_kses function.
	 */
	function eb_bp_get_allowed_html_tags() {
		$allowed_tags             = wp_kses_allowed_html( 'post' );
		$allowed_tags['form']     = array(
			'method' => array(),
			'target' => array(),
			'action' => array(),
		);
		$allowed_tags['input']    = array(
			'class'   => array(),
			'id'      => array(),
			'name'    => array(),
			'value'   => array(),
			'type'    => array(),
			'data-id' => array(),
		);
		$allowed_tags['checkbox'] = array(
			'class' => array(),
			'id'    => array(),
			'name'  => array(),
			'value' => array(),
			'type'  => array(),
		);
		$allowed_tags['select']   = array(
			'class'  => array(),
			'id'     => array(),
			'name'   => array(),
			'value'  => array(),
			'type'   => array(),
			'style'  => array(),
			'data-*' => true,
		);
		$allowed_tags['option']   = array(
			'class'    => array(),
			'value'    => array(),
			'selected' => array(),
		);
		$allowed_tags['script']   = array(
			'src'  => array(),
			'type' => array(),
		);
		$allowed_tags['a']        = array(
			'href'          => array(),
			'target'        => array(),
			'class'         => array(),
			'id'            => array(),
			'data-cohortid' => array(),
			'data-userid'   => array(),
		);
		$allowed_tags['img']      = array(
			'src'     => array(),
			'width'   => array(),
			'alt'     => array(),
			'class'   => array(),
			'height'  => array(),
			'loading' => array(),
		);
		$allowed_tags['span']     = array(
			'style'         => array(),
			'id'            => array(),
			'class'         => array(),
			'data-cohortid' => array(),
			'data-userid'   => array(),
		);
		$allowed_tags['h4']       = array(
			'style' => array(),
			'id'    => array(),
			'class' => array(),

		);
		$allowed_tags['h2'] = array(
			'style' => array(),
			'id'    => array(),
			'class' => array(),

		);
		return $allowed_tags;
	}
}



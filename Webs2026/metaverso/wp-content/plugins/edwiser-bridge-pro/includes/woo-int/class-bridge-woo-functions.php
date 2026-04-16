<?php
/**
 * Woo Integration Module
 * This class is responsible for Woo Integration module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for Woo Integration module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\wooInt;

/**
 * Functionality to check if the Woocommerce Membership plugin is activated.
 */
function check_woocommerce_membership_is_active() {
	$activated_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );

	if ( in_array( 'woocommerce-memberships/woocommerce-memberships.php', $activated_plugins ) ) { // @codingStandardsIgnoreLine
		return true;
	}
	return false;
}


/**
 * Returns WordPress course ids which are associated to the product Id.
 *
 * @param  int $product_id product id.
 */
function get_wp_courses_from_product_id( $product_id ) {
	$product_meta       = get_post_meta( $product_id, 'product_options', 1 );
	$associated_courses = isset( $product_meta['moodle_post_course_id'] ) ? $product_meta['moodle_post_course_id'] : array();
	return $associated_courses;
}


/**
 * Returns WordPress course ids which are associated to the product Id.
 *
 * @param  int $product_id product id.
 */
function get_mdl_courses_from_product_id( $product_id ) {
	$product_meta       = get_post_meta( $product_id, 'product_options', 1 );
	$associated_courses = isset( $product_meta['moodle_course_id'] ) ? $product_meta['moodle_course_id'] : '';
	$associated_courses = explode( ',', $associated_courses );
	return $associated_courses;
}



/**
 * The function will check if the array key exist or not and dose the arrya key associated a non empty value.
 *
 * @param array  $data_array array to check.
 * @param string $key key to check.
 */
function check_value_set( $data_array, $key ) {
	$value = false;

	if ( is_array( $data_array ) && array_key_exists( $key, $data_array ) && $data_array[ $key ] ) {

		$value = empty( $data_array[ $key ] ) ? false : $data_array[ $key ];
	}
	return $value;
}

/**
 * Get accociated courses with the product.
 *
 * @param int $course_ids course id.
 */
function wi_get_associated_courses( $course_ids ) {
	// get course titles and short by name.
	$course_titles = array();
	foreach ( $course_ids as $single_course_id ) {
		$course_titles[ $single_course_id ] = get_the_title( $single_course_id );
	}
	asort( $course_titles );
	?>
	<ul class="bridge-woo-available-courses">
		<?php
		foreach ( $course_titles as $single_course_id => $single_course_title ) {
			if ( 'publish' === get_post_status( $single_course_id ) ) {
				?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $single_course_id ) ); ?>" target="_blank"><?php echo esc_html( $single_course_title ); ?></a>
					<?php do_action( 'wi_after_associated_course', $single_course_id ); ?>
				</li>
				<?php
			}
		}
		?>
	</ul>
	<?php

}

/**
 * Get prodct id from product.
 *
 * @param object $_product product object.
 * @param array  $single_item single item.
 */
function eb_get_product_id_from_product( $_product, $single_item ) {

	if ( $_product && $_product->is_type( 'variable' ) && isset( $single_item['variation_id'] ) ) {
		// The line item is a variable product, so consider its variation.
		$product_id = $single_item['variation_id'];
	} else {
		$product_id = $single_item['product_id'];
	}

	return $product_id;

}

<?php
/**
 * Woo Int Public Module
 * This class is responsible for SSO module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for SSO module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

if ( ! empty( $product_id ) ) {
	$product_options = get_post_meta( $product_id, 'product_options', true );

	if ( ! empty( $product_options ) ) {
		if ( \app\wisdmlabs\edwiserBridgePro\includes\wooInt\check_value_set( $product_options, 'moodle_post_course_id' ) ) {
			?>
			<div class="wi-asso-courses-wrapper">
				<h5><?php esc_html_e( 'Associated Courses', 'edwiser-bridge-pro' ); ?></h5>

				<?php \app\wisdmlabs\edwiserBridgePro\includes\wooInt\wi_get_associated_courses( $product_options['moodle_post_course_id'] ); ?>
			</div>
			<?php
		}
	}
}

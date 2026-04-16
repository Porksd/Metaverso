<?php
/**
 * Setup product settings.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * The class provides the functionality for the admin settings for bulk purchase plugin.
 */
class Edwiser_Bulk_Purchase_Product_Settings {

	/**
	 * Provides the functionlity to display the bulk purchase filed in product meta.
	 *
	 * @param integer $product_id the product id where the filed need to be displayed.
	 * @param string  $post_type  the post type of the product.
	 * @param integer $index      the index of the product.
	 */
	public function wdm_display_group_purchase_fields( $product_id, $post_type = '', $index = 0 ) {
		global $post;

		$checked           = 'on';
		$current           = 'off';
		$current_reuse_qty = 'off';
		$product_options   = get_post_meta( $product_id, 'product_options', true );
		$wrap_class        = 'ebbp_product_settings_wrap';
		$_product          = wc_get_product( $product_id );

		if ( false !== $_product && ( $_product->is_type( 'simple' ) || $_product->is_type( 'subscription' ) ) ) {
			$wrap_class = '';
		}

		if ( isset( $product_options['moodle_course_group_purchase'] ) && ! empty( $product_options['moodle_course_group_purchase'] ) ) {
			$current = $product_options['moodle_course_group_purchase'];
		}

		if ( isset( $product_options['bp_reuse_quantity'] ) && ! empty( $product_options['bp_reuse_quantity'] ) ) {
			$current_reuse_qty = $product_options['bp_reuse_quantity'];
		}

		if ( 'product_variation' === $post_type ) {
			$variation_id = '[' . $index . ']';
		} else {
			$variation_id = '[]';
		}

		?>
		<div class="<?php echo esc_attr( $wrap_class ); ?>">
			<div class="ebbp_product_settings show_if_simple">
				<p class='form-field'>
					<label>
						<?php esc_attr_e( 'Group Purchase', 'edwiser-bridge-pro' ); ?>
					</label>
					<input type="checkbox" class="moodle_course_group_purchase" name ="moodle_course_group_purchase<?php echo esc_attr( $variation_id ); ?>" <?php echo ( $checked === $current ) ? 'checked' : ''; ?> >
					<img class="help_tip" data-tip='<?php esc_attr_e( 'Allow user to purchase course product in bulk.', 'edwiser-bridge-pro' ); ?>' src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" />
					<input type="hidden" class="moodle_course_group_purchase_hidden" name ="moodle_course_group_purchase_hidden<?php echo esc_attr( $variation_id ) ; ?>" value="<?php echo ( $checked === $current ) ? 'on' : 'off'; // @codingStandardsIgnoreLine ?>" >
					<?php wp_nonce_field( 'ebbp_product_meta_nonce_action', 'ebbp_product_meta_nonce_filed' ); ?>
				</p>
			</div>

			<div class="ebbp_product_settings bp-reuse-qty-contain <?php echo ( $checked === $current ) ? 'bp-show' : 'bp-hide'; ?>">
				<p class='form-field'>
					<label>
						<?php esc_attr_e( 'Reuse quantity after user is removed from the group', 'edwiser-bridge-pro' ); ?>
					</label>
					<input type="checkbox" class="bp_reuse_quantity" name ="bp_reuse_quantity<?php echo esc_attr( $variation_id ) ; ?>" value="off" <?php echo ( $checked === $current_reuse_qty ) ? 'checked' : ''; // @codingStandardsIgnoreLine ?> >
					<img class="help_tip" data-tip='<?php esc_attr_e( 'User can reuse the seats again even after removing users from group.', 'edwiser-bridge-pro' ); ?>' src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" />

					<!-- Adding this hidden input box because unchecked checkbopxes dont submit any value but in case of the variation we need it. -->
					<input type="hidden" class="bp_reuse_quantity_hidden" name ="bp_reuse_quantity_hidden<?php echo esc_attr( $variation_id ) ; ?>" value="<?php echo ( $checked === $current_reuse_qty ) ? 'on' : 'off'; // @codingStandardsIgnoreLine ?>" >
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Provides the functionality to save the group purchase meta for the product.
	 *
	 * @param int $variation_id the  id of the current variation.
	 * @param int $key variation key.
	 */
	public function wdm_save_group_purchase_field( $variation_id = 0, $key = 0 ) {
		$post_data = array();
		if ( isset( $_POST['_wpnonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce_field'] ) ), 'check_sync_action' ) ) {
			$post_data = wp_unslash( $_POST );
		}

		if ( ! isset( $post_data['action'] ) || 'handle_product_synchronization' !== $post_data['action'] ) {
			$post_id   = isset( $post_data['ID'] ) ? $post_data['ID'] : 0;
			$post_type = get_post_type( $post_id );

			if ( $variation_id ) {
				$post_id = $variation_id;
			}
			$the_post = wp_is_post_revision( $post_id );
			if ( $the_post ) {
				$post_id = $the_post;
			}
			if ( $post_id ) {
				$product_options = get_post_meta( $post_id, 'product_options', true );

				if ( ! isset( $product_options ) || ! is_array( $product_options ) ) {
					$product_options = array();
				}

				if ( isset( $_POST['moodle_course_group_purchase_hidden'][ $key ] ) && 'on' === $_POST['moodle_course_group_purchase_hidden'][ $key ] ) {

					$product_options['moodle_course_group_purchase'] = sanitize_text_field( wp_unslash( $_POST['moodle_course_group_purchase_hidden'][ $key ] ) );
					if ( isset( $_POST['bp_reuse_quantity_hidden'][ $key ] ) && ! empty( $_POST['bp_reuse_quantity_hidden'][ $key ] ) ) {

						$product_options['bp_reuse_quantity'] = sanitize_text_field( wp_unslash( $_POST['bp_reuse_quantity_hidden'][ $key ] ) );
					}
				}

				update_post_meta( $post_id, 'product_options', $product_options );
			}
		}
	}
}

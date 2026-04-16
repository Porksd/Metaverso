<?php
/**
 * The file that defines the woocommerce refund functionality.
 *
 * @since 2.0.1
 * @author     WisdmLabs, India <support@wisdmlabs.com>
 * @package BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

use app\wisdmlabs\edwiserBridgePro\includes as includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Eb_Bp_Users_Refund_Manager' ) ) {
	/**
	 * Class provides the refund functionality.
	 */
	class Eb_Bp_Users_Refund_Manager {

		/**
		 * If the cohort from any order is deleted then the same cohort ids present in the different order id are removed in this function.
		 *
		 * @param int   $order_id Order id.
		 * @param array $cohorts The list of the cohorts.
		 */
		public function cohort_exists( $order_id, $cohorts ) {
			global $wpdb;
			$new_cohort_array = array();
			if ( is_array( $cohorts ) ) {
				foreach ( $cohorts as $cohort_id => $quantity ) {
					$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_cohort_info WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine.
					if ( $result ) {
						$new_cohort_array[ $cohort_id ] = $quantity;
					}
				}
			}
			$order = wc_get_order( $order_id );
			$order->update_meta_data( 'eb_bp_mdl_cohort_id', $new_cohort_array );
			$order->save();
			return $new_cohort_array;
		}



		/**
		 * Dropdown, input box and the checkbox on woocommerce order refund added from this function
		 *
		 * @param object $order Object of the order.
		 */
		public function refund_html_content( $order ) {
			global $wpdb;
			$order_id = $order->get_id();
			$cohort   = $order->get_meta( 'eb_bp_mdl_cohort_id', 1 );
			$cohort   = $this->cohort_exists( $order_id, $cohort );

			if ( $cohort ) {
				ob_start();
				?>
				<div class="bp-refund-wrapper">
					<div class="bp-refund-heading"><?php esc_attr_e( 'Bulk Purchase Refund', 'edwiser-bridge-pro' ); ?></div>
					<table class="wc-order-totals">
						<tr title="<?php esc_attr_e( 'You cannot rollback this action!', 'edwiser-bridge-pro' ); ?>">
							<td class="label">
								<label><?php esc_attr_e( 'Refund Method:', 'edwiser-bridge-pro' ); ?></label>
							</td>
							<td class="total">
								<select class="bp-refund-inp-fields" name="bp-refund-type">
									<option value=""><?php esc_attr_e( 'Select Refund Type', 'edwiser-bridge-pro' ); ?></option>
									<option value="bp-partial-refund"><?php esc_attr_e( 'Partial Refund', 'edwiser-bridge-pro' ); ?></option>
									<option value="bp-full-refund"><?php esc_attr_e( 'Full Refund', 'edwiser-bridge-pro' ); ?></option>
								</select>
								<div class="clear"></div>
							</td>
						</tr>

				<?php

				foreach ( $cohort as $cohort_id => $quantity ) {

					$result     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_cohort_info WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine.
					foreach ( maybe_unserialize( $result->PRODUCTS ) as $key => $value ) { // @codingStandardsIgnoreLine.
						$key      = $key;
						$avbl_qty = $value;
					}
					$avlbl_ref_qty = $avbl_qty > $quantity ? $quantity : $avbl_qty;

					if ( $result ) {
						$cohort_name = ! empty( $result->NAME ) ? $result->NAME : $result->COHORT_NAME; // @codingStandardsIgnoreLine.
						?>
						<tr class="bp-refund-qty-wrapper">
							<td class="label">
								<label><?php echo esc_attr_e( 'Quantity to be refunded for ', 'edwiser-bridge-pro' ) . esc_html( $cohort_name ) . ' : '; ?></label>
							</td>
							<td class="total">
								<div class="eb-tooltip">
									<input data-availqty= "<?php echo esc_attr( $avlbl_ref_qty ); ?>" step="1" type="number" name="bp-partial-refund-quantity_<?php echo esc_attr( $cohort_id ); ?>" min="0" max="<?php echo esc_attr( $avlbl_ref_qty ); ?>" class="bp-refund-inp-fields bp-partial-refund-fields">
									<span class="eb-tooltiptext"> <?php esc_attr_e( 'Refund quantity should be less than or equal to the available quantity.' ); ?></span>
								</div>
								<span style="padding-left: 10px; font-weight: 600">
									/
									<span style="padding-left: 5px">
										<?php echo esc_attr( $avlbl_ref_qty ); ?>
									</span>
								</span>
								<div class="clear"></div>
							</td>
						</tr>

						<?php
					}
				}
				?>
						<tr class="bp-refund-checkbox-wrapper">
							<td class="label">
								<label><?php esc_attr_e( 'To do Full Refund please tick checkbox (This will unenroll users from group and delete group ):', 'edwiser-bridge-pro' ); ?></label>
							</td>
							<td class="total">
								<input type="checkbox" class="text" id="bp-full-refund-check" name="bp-full-refund-check" class="bp-refund-inp-fields" />
								<div class="clear"></div>
							</td>
						</tr>
					</table>
					<input type="hidden" id="bp_order_id" name="bp_order_id" value="<?php echo esc_attr( $order_id ); ?>" />
					<?php wp_nonce_field( 'bp_refund_unenrol', 'bp_refund_unenrol' ); ?>
					<div class="clear"></div>
				</div>
				<?php
				$html = ob_get_clean();

				wp_localize_script(
					'bp_admin_refund_js',
					'bpRefund',
					array(
						'order' => $order,
						'html'  => $html,
					)
				);

				wp_enqueue_script( 'bp_admin_refund_js' );
			}
		}

		/**
		 * The function handles the refund functionality.
		 *
		 * @param int $order_id Order Id to perform the refund.
		 * @param int $refund_id Id of the refund.
		 */
		public function refund_handler( $order_id, $refund_id ) {
			global $wpdb;
			$order = wc_get_order( $order_id );
			$refund_data = $order->get_meta( 'bp-refund-data', 1 );
			$refund_id   = $refund_id;

			if ( isset( $refund_data['refund-type'] ) && ! empty( $refund_data['refund-type'] ) ) {
				$cohorts   = $order->get_meta( 'eb_bp_mdl_cohort_id', 1 );
				if ( 'bp-partial-refund' === $refund_data['refund-type'] ) {
					foreach ( $cohorts as $cohort_id => $quantity ) {
						$quantity = $quantity;
						if ( isset( $refund_data[ 'bp-partial-refund-quantity_' . $cohort_id ] ) && ! empty( $refund_data[ 'bp-partial-refund-quantity_' . $cohort_id ] ) ) {
							$this->decreasebulk_quantity( $cohort_id, $refund_data[ 'bp-partial-refund-quantity_' . $cohort_id ], $order_id );
						}
					}
				}

				if ( 'bp-full-refund' === $refund_data['refund-type'] ) {
					if ( isset( $refund_data['full-refund'] ) && ! empty( $refund_data['full-refund'] ) && 'on' === $refund_data['full-refund'] ) {
						$email_args      = array();
						$table_name      = $wpdb->prefix . 'bp_cohort_info';
						$cohort_id_array = array_keys( $cohorts );
						// gathering email related data.
						foreach ( $cohorts as $cohort_id => $quantity ) {
							$row            = $wpdb->get_row( $wpdb->prepare( "SELECT NAME, COHORT_NAME, COHORT_MANAGER, PRODUCTS FROM {$table_name} WHERE MDL_COHORT_ID = %d;", $cohort_id ) ); // @codingStandardsIgnoreLine.
							$cohort_manager = get_user_by( 'ID', $row->COHORT_MANAGER ); // @codingStandardsIgnoreLine.
							$products       = maybe_unserialize( $row->PRODUCTS ); // @codingStandardsIgnoreLine.
							$quantity       = 0;
							foreach ( array_values( $products ) as $qty ) {
								$quantity = $qty;
								break;
							}

							array_push(
								$email_args,
								array(
									'username'         => $cohort_manager->user_login,
									'user_email'       => $cohort_manager->user_email,
									'first_name'       => $cohort_manager->first_name,
									'last_name'        => $cohort_manager->last_name,
									'order_id'         => $order_id,
									'bulk_refund_type' => 'fully',
									'refunded_bulk_quantity' => $quantity,
									'group_name'       => $row->NAME ? $row->NAME : $row->COHORT_NAME, // @codingStandardsIgnoreLine.
								)
							);
						}

						$cohort_manger = new includes\bulkPurchase\Eb_Bp_Manage_Cohort();
						$result        = $cohort_manger->delete_cohort( $cohort_id_array );
						if ( $result ) {
							$order->delete_meta_data( 'eb_bp_mdl_cohort_id' );

							foreach ( $email_args as $args ) {
								do_action( 'eb_bp_bulk_purchase_refund', $args );
							}
						}
					}
				}
			}

			// DELETE ORDER META OF THE REFUND.
			$order->delete_meta_data( 'bp-refund-data' );
			$order->save();
		}

		/**
		 * On partial refund decrese available quantity of the group.
		 *
		 * @param int $cohort_id The id of cohort.
		 * @param int $quantity  Number of amount need to descrease.
		 * @param int $order_id   Associated order id.
		 */
		public function decreasebulk_quantity( $cohort_id, $quantity, $order_id ) {
			global $wpdb;
			if ( $quantity ) {
				$table_name = $wpdb->prefix . 'bp_cohort_info';
				$row        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine.
				$products   = array();

				foreach ( maybe_unserialize( $row->PRODUCTS ) as $product_id => $qty ) { // @codingStandardsIgnoreLine.
					if ( $qty >= $quantity ) {
						$qty = $qty - $quantity;
						if ( $qty >= 0 ) {
							$products[ $product_id ] = $qty;
						}
					}
				}

				$wpdb->update( // @codingStandardsIgnoreLine.
					$table_name,
					array(
						'PRODUCTS' => maybe_serialize( $products ),
					),
					array(
						'MDL_COHORT_ID' => $cohort_id,
					)
				);
				$cohort_manager = get_user_by( 'ID', $row->COHORT_MANAGER ); // @codingStandardsIgnoreLine.

				$args = array(
					'username'               => $cohort_manager->user_login,
					'user_email'             => $cohort_manager->user_email,
					'first_name'             => $cohort_manager->first_name,
					'last_name'              => $cohort_manager->last_name,
					'order_id'               => $order_id,
					'bulk_refund_type'       => 'partially',
					'refunded_bulk_quantity' => $quantity,
					'group_name'             => $row->NAME ? $row->NAME : $row->COHORT_NAME, // @codingStandardsIgnoreLine.
				);

				do_action( 'eb_bp_bulk_purchase_refund', $args );
			}
		}

		/**
		 * Save refund fields data added on the refund settings.
		 * this function called ion the ajax as there is no other way to get that settings on the form save
		 */
		public function save_refund_data() {

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ebbp_refund_nonce' ) ) {
				print 'Sorry, your nonce did not verify.';
				exit;
			}

			$post_data = wp_unslash( $_POST );
			if ( isset( $post_data['refund-type'] ) && ! empty( $post_data['refund-type'] ) && isset( $post_data['order-id'] ) ) {

				$order_id    = $post_data['order-id'];
				$refund_data = array(
					'refund-type' => $post_data['refund-type'],
					'full-refund' => $post_data['full-refund'],
				);

				$order   = wc_get_order( $order_id );
				$cohorts = $order->get_meta( 'eb_bp_mdl_cohort_id', true );
				foreach ( $cohorts as $cohort_id => $quantity ) {
					$quantity = $quantity;
					$refund_data[ 'bp-partial-refund-quantity_' . $cohort_id ] = $post_data[ 'bp-partial-refund-quantity_' . $cohort_id ];
				}
				$order->update_meta_data( 'bp-refund-data', $refund_data );
				$order->save();
			}
		}
	}
}

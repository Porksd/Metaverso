<?php
/**
 * Handles the self enrollment functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use app\wisdmlabs\edwiserBridge as edwiserBridge;

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'Eb_Bp_Self_Enroll' ) ) {

	/**
	 * Class to enroll the self.
	 */
	class Eb_Bp_Self_Enroll {

		/**
		 * Product names array.
		 *
		 * @var array $bulk_enrol_page_url Products array.
		 */
		protected $bulk_product_names = array();

		/**
		 * Translate the Group enrollment string.
		 *
		 * @param mixed  $output Output data.
		 * @param object $obj Object array.
		 */
		public function translate_enrolled_self( $output, $obj ) {
			unset( $obj );
			return str_replace(
				array( 'Group Enrollment', 'yes' ),
				array(
					__( 'Group Enrollment', 'edwiser-bridge-pro' ),
					__( 'yes', 'edwiser-bridge-pro' ),
				),
				$output
			);
		}

		/**
		 * Translate meta key.
		 *
		 * @param  string        $key  The meta key.
		 * @param  WC_Meta_Data  $meta The meta object.
		 * @param  WC_Order_Item $item The order item object.
		 */
		public function eb_translate_order_item_meta_key( $key, $meta, $item ) {

			if ( 'Group Enrollment' === $meta->key ) {
				$key = __( 'Group Enrollment', 'edwiser-bridge-pro' );
			}
			return $key;
		}

		/**
		 * Translate meta key.
		 *
		 * @param  string        $value  The meta value.
		 * @param  WC_Meta_Data  $meta   The meta object.
		 * @param  WC_Order_Item $item   The order item object.
		 */
		public function eb_translate_order_item_meta_value( $value, $meta, $item ) {

			if ( 'Group Enrollment' === $meta->key && 'yes' === $meta->value ) {
				$value = __( 'Yes', 'edwiser-bridge-pro' );
			} elseif ( 'Group Enrollment' === $meta->key && 'no' === $meta->value ) {
				$value = __( 'No', 'edwiser-bridge-pro' );
			}
			return $value;
		}

		/**
		 * Filterting the meta data of an order item if order is not a group enrollment.
		 *
		 * @param  array         $meta_data Meta data array.
		 * @param  WC_Order_Item $item      Item object.
		 * @return array                    The formatted meta.
		 */
		public function eb_hide_unwanted_order_item_meta( $meta_data, $item ) {
			$new_meta = array();
			foreach ( $meta_data as $id => $meta_array ) {
				// if Group Enrollment Value is no then no need to show it in order or mail.
				if ( 'Group Enrollment' === $meta_array->key && 'no' === $meta_array->value ) {
					continue;
				}
				$new_meta[ $id ] = $meta_array;
			}
			return $new_meta;
		}


		/**
		 * Adding 'Group Registration' item meta if group_registration enabled by user.
		 *
		 * @param int    $item_id cart item.
		 * @param object $values list of item meta.
		 */
		public function wdm_add_values_to_order_item_meta( $item_id, $values ) {
			if ( isset( $values->legacy_values['wdm_edwiser_self_enroll'] ) && 'no' !== $values->legacy_values['wdm_edwiser_self_enroll'] ) {
				wc_add_order_item_meta( $item_id, 'Group Enrollment', 'yes' );
			} else {
				wc_add_order_item_meta( $item_id, 'Group Enrollment', 'no' );
			}
		}

		/**
		 *
		 * Checking if group registration enabled by the user for product.
		 *
		 * @param object $item item object.
		 * @param array  $values list of item meta.
		 * @param string $key meta key.
		 */
		public function get_cart_items_from_session( $item, $values, $key ) {
			$product_id = $values['product_id'];
			$post_meta  = get_post_meta( $product_id, 'product_options', true );
			if ( isset( $post_meta['moodle_course_group_purchase'] ) && 'on' === $post_meta['moodle_course_group_purchase'] ) {
				if ( isset( $values['quantity'] ) && $values['quantity'] > 1 ) {
					$item['wdm_edwiser_self_enroll'] = ( isset( $values['quantity'] ) && $values['quantity'] > 1 ) ? 'on' : 'no';
				} else {
					$item['wdm_edwiser_self_enroll'] = ( isset( $item['wdm_edwiser_self_enroll_checkbox'] ) && 'on' === $item['wdm_edwiser_self_enroll_checkbox'] ) ? 'on' : 'no';
				}
			}
			unset( $key );
			return $item;
		}


		/**
		 * Setting cart item data for checking if group registration is checked by user.
		 *
		 * @param array $cart_item_meta cart item meta data.
		 * @param int   $product_id product id added in cart.
		 */
		public function wdm_ld_add_cart_item_custom_data_save( $cart_item_meta, $product_id ) {
			$postdata = wp_unslash( $_POST ); // @codingStandardsIgnoreLine
			if ( isset( $postdata['wdm_edwiser_self_enroll'] ) && '' !== $postdata['wdm_edwiser_self_enroll'] ) {
				$cart_item_meta['wdm_edwiser_self_enroll']          = $postdata['wdm_edwiser_self_enroll'];
				$cart_item_meta['wdm_edwiser_self_enroll_checkbox'] = $postdata['wdm_edwiser_self_enroll'];
			}
			unset( $product_id );
			return $cart_item_meta;
		}

		/**
		 * Hiding select quantity using js,it will be displayed only if user checked group registration checkbox.
		 */
		public function wdm_ld_woocommerce_before_add_to_cart_button() {
			global $post, $product;

			if ( $product->is_type( 'simple' ) ) {
				$product_id = $post->ID;
				$post_meta  = get_post_meta( $product_id, 'product_options', true );
				$this->group_purchase_checkbox_renderer( $post_meta );
			} elseif ( $product->is_type( 'variable' ) ) {
				$available_variations = $product->get_available_variations();
				$variation_settings   = array();

				if ( ! empty( $available_variations ) ) {

					foreach ( $available_variations as $single_variation ) {
						$variation_id = $single_variation['variation_id'];
						$post_meta    = get_post_meta( $variation_id, 'product_options', true );
						$this->group_purchase_checkbox_renderer( $post_meta, 'bp_enable_group_purchase_' . $variation_id );
					}
				}
			}
		}

		/**
		 * Renders the Group purchase checkbox on the woo commerce single product page.
		 *
		 * @param array  $post_meta product meta array.
		 * @param string $variation_id the id for the variation bulk check enable wraper.
		 */
		private function group_purchase_checkbox_renderer( $post_meta, $variation_id = 'moodle_course_group_purchase' ) {
			if ( isset( $post_meta['moodle_course_group_purchase'] ) && ! empty( $post_meta['moodle_course_group_purchase'] ) ) {
				if ( 'on' === $post_meta['moodle_course_group_purchase'] ) {
					$genral_settings = get_option( 'eb_general', array() );
					$ebgp_lbl        = isset( $genral_settings['mucp_group_pur_lbl'] ) ? $genral_settings['mucp_group_pur_lbl'] : __( 'Enable Group Purchase', 'edwiser-bridge-pro' );
					$style           = '';
					if ( 'moodle_course_group_purchase' !== $variation_id ) {
						$style = 'display: none;';
					}
					?>
					<div class="wdm_edwiser_bulk_purchase" id="<?php echo esc_attr( $variation_id ); ?>" style='<?php echo esc_attr( $style ); ?>'>
						<input type="checkbox" name="wdm_edwiser_self_enroll" id="wdm_edwiser_self_enroll" >
						<?php
						echo apply_filters( 'wdm_edwiser_bulk_purchase_label', $ebgp_lbl ); // @codingStandardsIgnoreLine
						?>
					</div>
					<?php
				}
			}
		}
		/**
		 * Provides the functionality to get the group product of the user.
		 *
		 * @param int $user_id user id whose group product information required.
		 *
		 * @return array Array of the group product id's.
		 *
		 * @since 1.0.1
		 */
		public function get_group_prducts( $user_id ) {
			$group_products = get_user_meta( $user_id, 'group_products', true );
			if ( ! isset( $group_products ) || empty( $group_products ) ) {
				$group_products = array();
			}

			return $group_products;
		}

		/**
		 * Function to update the product quantity after the enrollment.
		 *
		 * @param array $items An array of the ordered product quantity.
		 * @param array $old_prod_arr Old product array.
		 */
		public function product_quantity_after_order_complete( $items, $old_prod_arr ) {

			$new_prod_arr = array();
			foreach ( $items as $item => $property ) {

				$product_id = $property['product_id'];
				$_product   = wc_get_product( $property['product_id'] );

				if ( $_product && $_product->is_type( 'variable' ) && isset( $property['variation_id'] ) ) {

					// The line item is a variable product, so consider its variation.
					$product_id = $property['variation_id'];
				}

				foreach ( $old_prod_arr as $key => $value ) {
					if ( $product_id === $key ) {
						$new_prod_arr[ $key ] = $value + $property['qty'];
					}
				}
				unset( $item );
			}

			return $new_prod_arr;
		}

		/**
		 * Function to get the courses associated with a particular product.
		 *
		 * @param int $product_id Product array.
		 */
		public function wdm_courses_associated_with_product( $product_id ) {
			global $wpdb;
			$tbl_name = $wpdb->prefix . 'eb_moodle_course_products';
			$courses  = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT `moodle_post_id` FROM `{$tbl_name}` WHERE `product_id` = %d ", $product_id ) ); // @codingStandardsIgnoreLine
			return $courses;
		}

		/**
		 * Function to enroll previous user to new courses when new product is added to particular cohort.
		 *
		 * @param array  $enrolled_users Enrolled users array.
		 * @param string $course_id Course id.
		 * @param int    $enrolled_by Cohort manager id.
		 * @param array  $product_id Product id.
		 * @param array  $cohort_name Name of the cohort.
		 */
		public function wdm_update_moodle_enrollment( $enrolled_users, $course_id, $enrolled_by, $product_id, $cohort_name ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'moodle_enrollment';
			$user_role  = 'Student';
			foreach ( $enrolled_users as $user ) {
				$wpdb->insert( // @codingStandardsIgnoreLine
					$table_name,
					array(
						'user_id'       => $user,
						'course_id'     => $course_id,
						'role_id'       => 5,
						'time'          => date( 'Y-m-d h:i:s' ), // @codingStandardsIgnoreLine
						'enrolled_by'   => $enrolled_by,
						'product_id'    => $product_id,
						'mdl_cohort_id' => $cohort_name,
						'role'          => $user_role,
					),
					array(
						'%d',
						'%d',
						'%d',
						'%s',
						'%d',
						'%d',
						'%s',
						'%s',
					)
				);
			}
		}

		/**
		 * Update the cohort when new products are added to cohort
		 *
		 * @param object $order_data Order detials.
		 * @param int    $order_id Order id.
		 */
		public function wdm_update_cohort_info( $order_data, $order_id ) {
			global $wpdb;
			foreach ( $order_data as $cohort_details ) {
				// code...
				$mdl_cohort_id = $cohort_details['cohort_id'];
				$order         = new \WC_Order( $order_id );
				$user          = $order->get_user();
				$cuser_id      = $user->ID;

				$table_name        = $wpdb->prefix . 'bp_cohort_info';
				$results           = $wpdb->get_row( $wpdb->prepare( "SELECT PRODUCTS, COURSES, COHORT_NAME, COHORT_MANAGER, INCOMP_ORD FROM {$table_name} WHERE MDL_COHORT_ID = %d", $mdl_cohort_id ) ); // @codingStandardsIgnoreLine
				$products          = maybe_unserialize( $results->PRODUCTS ); // @codingStandardsIgnoreLine
				$courses           = maybe_unserialize( $results->COURSES ); // @codingStandardsIgnoreLine
				$cohort_name       = $results->COHORT_NAME; // @codingStandardsIgnoreLine
				$cohort_manager_id = $results->COHORT_MANAGER; // @codingStandardsIgnoreLine
				$products_list     = array_keys( $products );
				$courses_list      = array_values( $courses );
				$incomp_order      = maybe_unserialize( $results->INCOMP_ORD ); // @codingStandardsIgnoreLine
				// Getting list of users who are already enrolled for cohort.
				$table_name     = $wpdb->prefix . 'moodle_enrollment';
				$enrolled_users = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$table_name} WHERE enrolled_by = %d AND MDL_COHORT_ID = %d", $cuser_id, $mdl_cohort_id ) ); // @codingStandardsIgnoreLine
				$product        = $cohort_details['product_id'];
				$quantity       = $cohort_details['quantity'];
				if ( in_array( $product, $products_list, true ) ) {
					$products[ $product ] += $quantity;
				} else {
					$products[ $product ] = intval( $quantity ) - count( $enrolled_users );
				}
				$courses_product  = $this->wdm_courses_associated_with_product( $product );
				$unenroll_courses = array_diff( $courses_product, $courses_list );
				$cohrt_manager    = new Eb_Bp_Manage_Cohort();
				$cohrt_manager->enroll_cohort_in_courses( $unenroll_courses, $cohort_name, $cohort_manager_id );
				foreach ( $courses_product as $course ) {
					if ( ! in_array( $course, $courses_list, true ) && ! in_array( $course, $courses, true ) ) {
						array_push( $courses, $course );
						// Enrolling previous user to new courses.
						if ( ! empty( $enrolled_users ) ) {
							$this->wdm_update_moodle_enrollment( $enrolled_users, $course, $cuser_id, $product, $mdl_cohort_id );
						}
					}
				}
				$table_name = $wpdb->prefix . 'bp_cohort_info';
				$key        = array_search( $order_id, $incomp_order, true );
				// Removing current order id from the incomplete orders column.
				if ( false !== $key ) {
					unset( $incomp_order[ $key ] );
				}

				$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET PRODUCTS = %s, COURSES = %s, INCOMP_ORD = %s  WHERE MDL_COHORT_ID = %d", serialize( $products ), serialize( $courses ), serialize( $incomp_order ), $mdl_cohort_id ) ); // @codingStandardsIgnoreLine

				$quantity = 0;
				// FUNCTIONALITY TO GET THE COHORT QUANTITY.
				foreach ( $products as $key => $value ) {
					$key      = $key;
					$quantity = $value;
				}

				// update cohort id in order meta.
				update_cohort_id_in_order_meta( $order_id, $mdl_cohort_id, $quantity );
			}
		}

		/**
		 * Save the product qty in user meta.
		 *
		 * @param int $order_id Order id.
		 */
		public function wdm_save_product_qty( $order_id, $old_status, $new_status ) {
			$order   = wc_get_order( $order_id );
			$items   = $order->get_items();
			$user    = $order->get_user();
			$user_id = $user->ID;

			if ( 'completed' === $new_status ) {
				$flag_bp                      = false;
				$email_args                   = array(
					'user_email' => $user->user_email,
					'order_id'   => $order_id,
				);
				$order_meta                   = wc_get_order_item_meta( $order_id, 'Group Enrollment' );
				$non_bulk_purchase_prod_count = 0;

				foreach ( $items as $item_id => $prop ) {
					$order_meta   = wc_get_order_item_meta( $item_id, 'Group Enrollment' );
					$item_prod_id = wc_get_order_item_meta( $item_id, '_product_id' );

					/**
					 * Check is the group enroll is enabled
					 */
					if ( 'no' === $order_meta ) { // @codingStandardsIgnoreLine
						// continue;
					} else {
						$flag_bp = true;
					}
					$prop = $prop;
				}

				/*
				* Trigger email for the non bulk products.
				* If there is only simple product i.e bulk purchase is not checkd in backend also then email will be sent by woo-int plugin but if there is any bulk product purchased individually then its mail should be sent from Bulk purchase plugin.
				*/
				if ( $non_bulk_purchase_prod_count ) {
					// Chesking if grou purchase is enabled to the product.
					$email_args = array(
						'user_email'    => $user->user_email,
						'order_id'      => $order_id,
						'username'      => $user->user_login,
						'first_name'    => $user->first_name,
						'last_name'     => $user->last_name,
						'bulk_purchase' => 1,
					);

					do_action( 'eb_bp_send_normal_enrollemnt_mail', $email_args );
				}

				$order_data = $order->get_meta( 'add_product_from_enroll_page', true );

				if ( ! empty( $order_data ) ) {
					$this->wdm_update_cohort_info( $order_data, $order_id );
				}
				if ( 0 !== $user_id ) {
					$this->bulk_product_names = array();
					global $wpdb;
					$table_name    = $wpdb->prefix . 'bp_cohort_info';
					$query         = "SELECT ID, COHORT_NAME, NAME, PRODUCTS, COURSES, INCOMP_ORD, SYNC, idnumber  FROM  {$table_name} WHERE COHORT_MANAGER = $user_id";
					$results       = $wpdb->get_results( $query, ARRAY_A ); // @codingStandardsIgnoreLine
					$cohrt_manager = new Eb_Bp_Manage_Cohort();

					foreach ( $results as $tbl_row ) {
						$incomp_order = $tbl_row['INCOMP_ORD'];
						$incomp_order = maybe_unserialize( $incomp_order );
						$present      = in_array( $order_id, $incomp_order, true );
						$prod_array   = maybe_unserialize( $tbl_row['PRODUCTS'] );
						$courses      = $tbl_row['COURSES'];
						$courses      = maybe_unserialize( $courses );

						if ( $present ) {
							$prod_qty       = $this->product_quantity_after_order_complete( $items, $prod_array );
							$cohort_details = array(
								'success'     => $tbl_row['SYNC'] ? 1 : 0,
								'cohort_name' => $tbl_row['COHORT_NAME'],
							);
							$cohrt_manager->update_cohort_on_user_enrollment( $order_id, $user_id, $cohort_details, $courses, $prod_qty, $incomp_order, $tbl_row['idnumber'] );
						}
					}
				}
				if ( $flag_bp && isset( $present ) && $present ) {

					$email_args = array(
						'user_email' => $user->user_email,
						'order_id'   => $order_id,
						'username'   => $user->user_login,
						'first_name' => $user->first_name,
						'last_name'  => $user->last_name,
					);

					do_action( 'eb_bp_bulk_purchase_email', $email_args );
				}
			} elseif ( ( 'failed' == $new_status || 'cancelled' == $new_status ) &&  0 !== $user_id ){
				global $wpdb;
				$table_name    = $wpdb->prefix . 'bp_cohort_info';
				$query         = "SELECT ID, COHORT_NAME, NAME, PRODUCTS, COURSES, INCOMP_ORD, SYNC, idnumber  FROM  {$table_name} WHERE COHORT_MANAGER = $user_id";
				$results       = $wpdb->get_results( $query, ARRAY_A ); // @codingStandardsIgnoreLine
				$cohrt_manager = new Eb_Bp_Manage_Cohort();
				foreach ( $results as $tbl_row ) {
					$incomp_order = $tbl_row['INCOMP_ORD'];
					$incomp_order = maybe_unserialize( $incomp_order );
					$present      = in_array( $order_id, $incomp_order, true );
					if( $present ) {
						// delete cohort entry
						$wpdb->delete( $table_name, array( 'ID' => $tbl_row['ID'] ) );
					}
				}
			}
		}

		/**
		 * Enroll user who purchased group into the courses.
		 *
		 * @param string $product_id Product id.
		 * @param object $order Order data.
		 * @param object $item  Item object.
		 */
		private function enroll_user_to_course( $product_id, $order, $item ) {
			global $wpdb;

			$_product = wc_get_product( $product_id );

			if ( $_product && $_product->is_type( 'variable' ) && isset( $item['variation_id'] ) ) {

				// The line item is a variable product, so consider its variation.
				$product_id = $item['variation_id'];
			}

			$product_options = get_post_meta( $product_id, 'product_options', true );
			$tbl_name        = $wpdb->prefix . 'moodle_enrollment';

			// enroll user in to the course.
			$user          = $order->get_user();
			$order_user    = $user->ID;
			$mdl_course_id = $product_options['moodle_post_course_id'];

			if ( isset( $product_options['moodle_course_group_purchase'] ) && 'on' === $product_options['moodle_course_group_purchase'] && ! empty( $mdl_course_id ) ) {
				$args             = array(
					'user_id'  => $order_user,
					'courses'  => $mdl_course_id,
					'unenroll' => 0,
					'suspend'  => 0,
				);
				$is_user_enrolled = $this->is_user_enrolled( $mdl_course_id, $order_user, $order );
				$course_enrolled  = edwiserBridge\edwiser_bridge_instance()->enrollmentManager()->updateUserCourseEnrollment( $args );
				if ( isset( $course_enrolled ) && ! empty( $course_enrolled ) && $is_user_enrolled ) {
					$courses = '(' . implode( ',', $mdl_course_id ) . ')';
					$query   = $wpdb->prepare( "update `{$tbl_name}` set enrolled_by = %d, product_id = %d  where user_id = %d and course_id in {$courses}", $order_user, $product_id, $order_user ); // @codingStandardsIgnoreLine
					$wpdb->query( $query ); // @codingStandardsIgnoreLine
				}

				return 0;
			} else {
				return 1;
			}

		}

		/**
		 * Function to update the userrole on WordPress
		 *
		 * @param int $user_id user id.
		 */
		public function update_wordpress_user_role( $user_id ) {
			if ( ! user_can( $user_id, 'manage_options' ) ) {
				wp_update_user(
					array(
						'ID'   => $user_id,
						'role' => 'non_editing_teacher',
					)
				);
			}
		}

		/**
		 * Provides the functionality for the ssaving and updating the product
		 * quntity data into the database on order completion.
		 *
		 * @param array  $group_products array of the purchased products to update the quntity.
		 * @param object $order the currant orders object.
		 * @param int    $order_id currant  order id.
		 */
		public function save_product_quantity( $group_products, $order, $order_id ) {
			$group_products = $this->check_is_quantity_empty( $group_products );
			$user           = $order->get_user();
			$user_id        = $user->ID;
			if ( ! empty( $group_products ) && $this->is_eb_bp_order_mark_completed( $user_id, $order_id ) ) {
				update_user_meta( $user_id, 'group_products', $group_products );
			}
		}

		/**
		 * Provides the functionality to check is the product array have.
		 * associated product quantity null or less than zero or zero then.
		 * remove the product form the gropu product array.
		 *
		 * @param array $group_products array of group product.
		 */
		private function check_is_quantity_empty( $group_products ) {
			foreach ( $group_products as $key => $val ) {
				if ( null === $val || $val <= 0 ) {
					unset( $group_products[ $key ] );
				}
			}
			return $group_products;
		}

		/**
		 * Provides the funcrtionality to set the order status compleated.
		 *
		 * @param int $user_id Id of the user who has placed the product order.
		 * @param int $order_id order id to update the compleat status.
		 */
		private function set_eb_bp_order_status( $user_id, $order_id ) {
			$eb_bp_orders = get_user_meta( $user_id, 'eb_bp_compleated_orders', true );
			if ( is_array( $eb_bp_orders ) ) {
				$eb_bp_orders[ $order_id ] = 1;
			} else {
				$eb_bp_orders = array( $order_id => 1 );
			}
			update_user_meta( $user_id, 'eb_bp_compleated_orders', $eb_bp_orders );
		}

		/**
		 * Provides the functionality to check is user's order of product is
		 * compleated previously or not.
		 *
		 * @param int $user_id Id of the user who has placed the product order.
		 * @param int $order_id order id to update the compleat status.
		 */
		private function is_eb_bp_order_mark_completed( $user_id, $order_id ) {
			$eb_bp_orders = get_user_meta( $user_id, 'eb_bp_compleated_orders', true );
			$flag         = true;
			if ( is_array( $eb_bp_orders ) && array_key_exists( $order_id, $eb_bp_orders ) && 1 === $eb_bp_orders[ $order_id ] ) {
				$flag = false;
			} else {
				$this->set_eb_bp_order_status( $user_id, $order_id );
			}
			return $flag;
		}

		/**
		 * Provides the functionality to update the product quntity.
		 *
		 * @param array $product array of the products purchased by the user.
		 * @param array $group_products Array of the group product.
		 * @param int   $product_id  Currant product id.
		 */
		public function update_product_quantity( $product, $group_products, $product_id ) {
			$flag      = false;
			$post_meta = get_post_meta( $product['product_id'], 'product_options', true );
			if ( isset( $post_meta['moodle_course_group_purchase'] ) && ! empty( $post_meta['moodle_course_group_purchase'] ) ) {
				if ( 'on' === $post_meta['moodle_course_group_purchase'] ) {
					if ( ! isset( $group_products[ $product_id ] ) && empty( $group_products[ $product_id ] ) ) {
						$group_products[ $product_id ] = 0;
					}
					$group_products[ $product_id ] = $group_products[ $product_id ] + $product['qty'];
					$flag                          = true;
				}
			}
			return array(
				'flag'        => $flag,
				'product_qty' => $group_products[ $product_id ],
			);
		}

		/**
		 * Check is user already enrolled for the all the courses in the product
		 *
		 * @param int    $mdl_course_id moodle course id.
		 * @param int    $order_user user id who has orderd the product.
		 * @param object $order object of the currant order.
		 */
		public function is_user_enrolled( $mdl_course_id, $order_user, $order ) {
			global $wpdb;
			$tbl_name      = $wpdb->prefix . 'moodle_enrollment';
			$user          = $order->get_user();
			$order_user    = $user->ID;
			$courses       = '(' . implode( ',', $mdl_course_id ) . ')';
			$query         = $wpdb->prepare( "SELECT course_id FROM `{$tbl_name}` WHERE  `user_id` = '%d' AND course_id in {$courses}", $order_user ); // @codingStandardsIgnoreLine
			$res           = $wpdb->get_results( $query ); // @codingStandardsIgnoreLine
			$enrlled_array = array();
			foreach ( $res as $cid ) {
				$enrlled_array[] = $cid->course_id;
			}
			$mdl_course_id = array_diff( $mdl_course_id, $enrlled_array );
			if ( empty( $mdl_course_id ) ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Emial template content parser function.
		 *
		 * @param mixed $content email content.
		 */
		public function eb_email_tmpl_content( $content ) {
			$bulk_product_names  = '{BULK_PRODUCT_NAMES}';
			$bulk_enrol_page_url = '{BULK_ENROL_PAGE_URL}';
			$user_name           = '';
			$url                 = '';

			// BULK_PRODUCT_NAMES.
			if ( count( $this->bulk_product_names ) ) {
				$bulk_product_names = implode( ', ', $this->bulk_product_names );
			}

			// BULK_ENROL_PAGE_URL.
			$setting = get_option( 'eb_general', array() );
			if ( isset( $setting['mucp_group_enrol_page_id'] ) ) {
				$status = get_post_status( $setting['mucp_group_enrol_page_id'] );
				if ( 'trash' !== $status ) {
					$url = get_permalink( $setting['mucp_group_enrol_page_id'] );
				} else {
					$page = get_page_by_title( 'Enroll Students' );
					$url  = get_permalink( $page->ID );
				}
			}

			if ( $url ) {
				$bulk_enrol_page_url = esc_url( $url );
			}

			if ( is_user_logged_in() ) {
				$cur_user  = wp_get_current_user();
				$user_name = $cur_user->first_name;
			}

			$tmpl_content             = str_replace(
				array(
					'{FIRST_NAME}',
					'{BULK_PRODUCT_NAMES}',
					'{BULK_ENROL_PAGE_URL}',
				),
				array(
					$user_name,
					$bulk_product_names,
					$bulk_enrol_page_url,
				),
				$content['content']
			);
			$content['content']       = $tmpl_content;
			$this->bulk_product_names = array();
			return $content;
		}
	}
}
new \app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Self_Enroll();

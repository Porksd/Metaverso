<?php
/**
 * Handles the cohort functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Eb_Bp_Manage_Cohort' ) ) {

	/**
	 * Class provides the cohort managment functionality.
	 */
	class Eb_Bp_Manage_Cohort {

		/**
		 * Update the user profile company information on the woocomerce checkout.
		 *
		 * @param int $user_id user id.
		 * @since 1.2.0
		 */
		public function update_user_profile( $user_id ) {
			$cohort_name = isset( $_POST["cohort_name"] ) ? $_POST["cohort_name"] : '' ; // @codingStandardsIgnoreLine
			if ( isset( $cohort_name ) ) {
				update_user_meta( $user_id, 'cohort_name', $cohort_name );
			}
		}

		/**
		 * Checks if all the cohorts coming from checkout page has name set in post data.
		 */
		private function check_all_cohort_has_name() {
			$all_cohort_name_set = 1;

			if ( isset( $_POST['diff_cohort_name'] ) && ! empty( $_POST['diff_cohort_name'] ) ) { // @codingStandardsIgnoreLine
				foreach ( $_POST['diff_cohort_name'] as $value ) { // @codingStandardsIgnoreLine
					if ( empty( $value ) ) {
						$all_cohort_name_set = 0;
					}
				}
			}
			return $all_cohort_name_set;
		}

		/**
		 * This function runs for every item in the cart. so need to add data of each product added fropm the enroll-students page.
		 *
		 * @param int $item item.
		 * @param int $cart_item_key cart_item_key.
		 * @param int $values values.
		 * @param int $order order.
		 */
		public function save_cart_item_meta_into_order_meta( $item, $cart_item_key, $values, $order ) {
			$item          = $item;
			$cart_item_key = $cart_item_key;
			$order         = $order;
			if ( ! isset( $values['enroll-students'] ) && empty( $values['enroll-students'] ) ) {
				return;
			}

			$session_data = array();
			if ( WC()->session->get( 'add_product_from_enroll_page' ) ) {
				$session_data = WC()->session->get( 'add_product_from_enroll_page' );
			}

			$product_id = $values['product_id'];
			$_product   = wc_get_product( $values['product_id'] );

			if ( $_product && $_product->is_type( 'variable' ) && isset( $values['variation_id'] ) ) {
				// The line item is a variable product, so consider its variation.
				$product_id = $values['variation_id'];
			}

			// create array of products added from the enroll-students page with cohort-id.
			$temp_array = array(
				'cohort_id'  => isset( $values['cohort_id'] ) ? $values['cohort_id'] : '',
				'product_id' => $product_id,
				'quantity'   => $values['quantity'],
			);

			// add current products metadata to the session.
			array_push( $session_data, $temp_array );
			WC()->session->set( 'add_product_from_enroll_page', $session_data );
		}



		/**
		 * Callback for the order checkout complete.
		 * This will process the order and update the cohort info into the databse.
		 *
		 * @param Number $order_id the order ID.
		 */
		public function handle_order_placed( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( empty($order) ) {
				if ( method_exists( $this->edwiser_bridge, 'logger' ) ) {
					$this->edwiser_bridge->logger()->add( 'error', 'Order not found or invalid for order_id: ' . $order_id );
				}
				return 0;
			}
			
			$items = $order->get_items();

			// Getting the session data.
			$session_data = WC()->session->get( 'add_product_from_enroll_page' );

			if ( WC()->session->get( 'eb-bp-create-same-product' ) ) {
				WC()->session->set( 'eb-bp-create-same-product', 0 );
			}
			// $order   = wc_get_order( $order_id );
			$courses = $this->get_order_courses_ids( $order );
			// if $courses then the order has bulk purchase product.
			$products = $this->get_bulk_product_array_from_order( $order_id );
			$user_id = $order->get_user_id();

			if ( $products['bulkProduct'] ) {
				$all_cohort_name_set = $this->check_all_cohort_has_name();

				if ( ! ( isset( $_POST['cohort_name'] ) && ! empty( $_POST['cohort_name'] ) ) && ! ( isset( $_POST['diff_cohort_name'] ) && ! empty( $_POST['diff_cohort_name'] ) ) ) {//Gutenberg checkout
					foreach ( $items as $item ) {
						$product_id  = $item->get_product_id();
						$cohort_name = get_the_title( $product_id );
						$_product    = wc_get_product( $item['product_id'] );

						if ( $_product && $_product->is_type( 'variable' ) && isset( $item['variation_id'] ) ) {
							// The line item is a variable product, so consider its variation.
							$product_id = $item['variation_id'];
						}

						$quantity = $item->get_quantity();
						if ( ! $this->is_product_bulk( $product_id, $item ) ) {
							continue;
						}

						$cohort_details = 0;
						if ( ! empty( $session_data ) ) {
							$cohort_details = $this->is_added_from_enroll_students_page( $session_data, $product_id, $quantity );
						}
						$arr_prod                = array();
						$courses                 = $this->get_courses_from_product( $product_id );
						$arr_prod[ $product_id ] = 0;
						if ( 0 !== $cohort_details ) {
							$cohort_data      = $this->get_cohort_name_by_id( $cohort_details['cohort_id'] );
							$cohort_name      = $cohort_data['cohort_name'];
							$use_same_cohort  = true;
							$cohort_disp_name = $cohort_data['name'];
						} else {
							$diff_cohort_names = isset( $_POST['diff_cohort_name'][ $product_id ] ) ? sanitize_text_field( wp_unslash( $_POST['diff_cohort_name'][ $product_id ] ) ) : wc_get_product( $product_id )->get_name() . ' - Order #' . $order_id; // @codingStandardsIgnoreLine
							$cohort_disp_name  = $diff_cohort_names;
							$use_same_cohort   = false;
						}

						$this->update_cohort_info(
							$courses,
							$order_id,
							$user_id,
							$arr_prod,
							$cohort_name,
							$use_same_cohort,
							$cohort_disp_name
						);
					}
				} elseif ( isset( $_POST['cohort_name'] ) && ! empty( $_POST['cohort_name'] ) ) { // @codingStandardsIgnoreLine
					// if cohort name field exist i.e the checkbox on the cart is checked then create only one group else create diffrent groups.
					$cohort_name = sanitize_text_field( wp_unslash( $_POST['cohort_name'] ) ); // @codingStandardsIgnoreLine
					$this->update_cohort_info(
						$courses,
						$order_id,
						$user_id,
						$products['product'],
						$cohort_name,
						false,
						$cohort_name
					);
				} elseif ( isset( $_POST['diff_cohort_name'] ) && ! empty( $_POST['diff_cohort_name'] ) ) {
					// when the checkbox on cart page to create same cohort is not checked then this block executes.
					foreach ( $items as $item ) {
						$product_id  = $item->get_product_id();
						$cohort_name = get_the_title( $product_id );
						$_product    = wc_get_product( $item['product_id'] );

						if ( $_product && $_product->is_type( 'variable' ) && isset( $item['variation_id'] ) ) {
							// The line item is a variable product, so consider its variation.
							$product_id = $item['variation_id'];
						}

						$quantity = $item->get_quantity();
						if ( ! $this->is_product_bulk( $product_id, $item ) ) {
							continue;
						}

						$cohort_details = 0;
						if ( ! empty( $session_data ) ) {
							$cohort_details = $this->is_added_from_enroll_students_page( $session_data, $product_id, $quantity );
						}
						$arr_prod                = array();
						$courses                 = $this->get_courses_from_product( $product_id );
						$arr_prod[ $product_id ] = 0;
						if ( 0 !== $cohort_details ) {
							$cohort_data      = $this->get_cohort_name_by_id( $cohort_details['cohort_id'] );
							$cohort_name      = $cohort_data['cohort_name'];
							$use_same_cohort  = true;
							$cohort_disp_name = $cohort_data['name'];
						} else {
							$diff_cohort_names = isset( $_POST['diff_cohort_name'][ $product_id ] ) ? sanitize_text_field( wp_unslash( $_POST['diff_cohort_name'][ $product_id ] ) ) : ''; // @codingStandardsIgnoreLine
							$cohort_disp_name  = $all_cohort_name_set ? $diff_cohort_names : '';
							$use_same_cohort   = false;
						}

						$this->update_cohort_info(
							$courses,
							$order_id,
							$user_id,
							$arr_prod,
							$cohort_name,
							$use_same_cohort,
							$cohort_disp_name
						);
					}
				}
			}
			// Updating the order meta.
			if ( ! empty( $session_data ) ) {
				if ( empty( wc_get_order_item_meta( $order_id, 'add_product_from_enroll_page', true ) ) ) {
					$order->update_meta_data( 'add_product_from_enroll_page', $session_data );
					$order->save();
				}
				WC()->session->__unset( 'add_product_from_enroll_page' );
			}
		}

		/**
		 * Function checks if the product is added from the user enrollment page.
		 *
		 * @param array $session_data Array of the session data.
		 * @param int   $product_id Product id.
		 * @param int   $quantity order quantity.
		 */
		private function is_added_from_enroll_students_page( $session_data, $product_id, $quantity ) {
			foreach ( $session_data as $cohort_details ) {
				if ( $cohort_details['product_id'] === $product_id && (int) $cohort_details['quantity'] === $quantity ) {
					return $cohort_details;
				}
			}
			return 0;
		}

		/**
		 * Checks if the bulk purchase is enabled for the given product.
		 *
		 * @param int   $product_id product id.
		 * @param array $product_meta product meta array.
		 */
		private function is_product_bulk( $product_id, $product_meta ) {
			$product_options = get_post_meta( $product_id, 'product_options', true );
			if ( isset( $product_options['moodle_course_group_purchase'] ) && 'on' === $product_options['moodle_course_group_purchase'] ) {
				if ( isset( $product_meta['Group Enrollment'] ) && 'yes' === $product_meta['Group Enrollment'] ) {
					return 1;
				}
			}
			return 0;
		}


		/**
		 * Function retruns the cohort name by cohort id.
		 *
		 * @param int $cohort_id cohort id.
		 */
		private function get_cohort_name_by_id( $cohort_id ) {
			global $wpdb;
			$tbl_cohort_info = $wpdb->prefix . 'bp_cohort_info';
			$result          = $wpdb->get_row( $wpdb->prepare( "select COHORT_NAME AS cohort_name, NAME AS name from {$tbl_cohort_info} where MDL_COHORT_ID='%d'", $cohort_id ) ); // @codingStandardsIgnoreLine

			$name = $result->cohort_name;
			if ( ! empty( $result->name ) ) {
				$name = $result->name;
			}
			return array(
				'name'        => $name,
				'cohort_name' => $result->cohort_name,
			);
		}

		/**
		 * Function to get the courses from product.
		 *
		 * @param int $product_id Product id.
		 */
		private function get_courses_from_product( $product_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'eb_moodle_course_products';
			$result     = $wpdb->get_col( $wpdb->prepare( "SELECT moodle_post_id FROM {$table_name} WHERE product_id = %d", $product_id ) ); // @codingStandardsIgnoreLine
			return $result;
		}

		/**
		 * Function to get products and its quantity.
		 *
		 * @param int $order_id woocommerce order id.
		 */
		private function get_bulk_product_array_from_order( $order_id ) {
			$order_obj     = new \WC_Order( $order_id );
			$order_item    = $order_obj->get_items();
			$product_array = array();
			$bulk_product  = 0;
			foreach ( $order_item as $single_item ) {

				if ( isset( $single_item['product_id'] ) ) {
					$_product = wc_get_product( $single_item['product_id'] );

					if ( $_product && $_product->is_type( 'variable' ) && isset( $single_item['variation_id'] ) ) {
						// The line item is a variable product, so consider its variation.
						$product_id = $single_item['variation_id'];
					} else {
						$product_id = $single_item['product_id'];
					}

					$product_options = get_post_meta( $product_id, 'product_options', true );
					if ( isset( $product_options['moodle_course_group_purchase'] ) && 'on' === $product_options['moodle_course_group_purchase'] ) {
						if ( isset( $single_item['Group Enrollment'] ) && 'yes' === $single_item['Group Enrollment'] ) {
							$bulk_product                 = 1;
							$product_array[ $product_id ] = 0;
						}
					}
				}
			}

			return array(
				'bulkProduct' => $bulk_product,
				'product'     => $product_array,
			);
		}

		/**
		 * Provides the functionality to insert the cohort information into the databse.
		 *
		 * @param array  $course_ids array of the course ids.
		 * @param int    $order_id Order id.
		 * @param int    $user_id User id.
		 * @param array  $products array of the products.
		 * @param string $cohort_name cohort name.
		 * @param string $use_same_cohort should use same cohort or not.
		 * @param string $name name of the cohort.
		 */
		public function update_cohort_info( $course_ids, $order_id, $user_id, $products, $cohort_name, $use_same_cohort = false, $name = '' ) {
			global $wpdb;

			if ( ! empty( $order_id ) ) {
				$orders = array( $order_id );
			} else {
				$orders = array();
			}
			$courses      = $course_ids;
			$cohort_exist = $this->add_in_same_cohort( $cohort_name, $course_ids );
			$tbl_cohort   = $wpdb->prefix . 'bp_cohort_info';
			$products     = serialize( $products ); // @codingStandardsIgnoreLine
			if ( $use_same_cohort ) {
				$cohort_exist['cohort_name']        = $cohort_name;
				$cohort_exist['add_in_same_cohort'] = true;
			}

			if ( true === $cohort_exist['add_in_same_cohort'] ) {

				$cohort_name = $cohort_exist['cohort_name'];
				$result      = $wpdb->get_results( $wpdb->prepare( "SELECT `INCOMP_ORD`,`COURSES` FROM {$tbl_cohort} where cohort_name='%s'", $cohort_name ), ARRAY_A ); // @codingStandardsIgnoreLine
				if ( count( $result ) > 0 ) {
					$orders = $this->un_uerialize( $result[0]['INCOMP_ORD'] );
					array_push( $orders, $order_id );
				} else {
					$orders = array( $order_id );
				}

				$wpdb->update( // @codingStandardsIgnoreLine
					$tbl_cohort,
					array(
						'NAME'           => $name,
						'COHORT_MANAGER' => $user_id,
						'INCOMP_ORD'     => serialize( $orders ), // @codingStandardsIgnoreLine
					),
					array(
						'cohort_name' => stripslashes( $cohort_name ),
					)
				);
			} else {

				$cohort_name = $this->genrate_cohort_name( $cohort_name, $user_id );
				$user        = get_userdata( $user_id );
				$wpdb->insert( // @codingStandardsIgnoreLine
					$tbl_cohort,
					array(
						'NAME'           => $name,
						'cohort_name'    => stripslashes( $cohort_name ),
						'PRODUCTS'       => $products,
						'COURSES'        => serialize( $courses ), // @codingStandardsIgnoreLine
						'COHORT_MANAGER' => $user_id,
						'INCOMP_ORD'     => serialize( $orders ), // @codingStandardsIgnoreLine
						'idnumber'       => $this->genrate_cohort_idnumber( $user->user_login ),
					)
				);
			}
		}

		/**
		 * Unserializas the string and prints the array.
		 *
		 * @param string $serialize the serialized array.
		 */
		private function un_uerialize( $serialize ) {
			$data_array = maybe_unserialize( $serialize );
			if ( is_array( $data_array ) && count( $data_array ) > 0 ) {
				return $data_array;
			}
			return array();
		}

		/**
		 * Provides the functionality to get the associated courses in order.
		 *
		 * @param object $order object of the woocomerce order.
		 * @return Array of the courses IDs.
		 * @since 1.2.0
		 */
		private function get_order_courses_ids( $order ) {
			$list_of_course_ids = array();

			$items = $order->get_items(); // Get Item details.

			foreach ( $items as $single_item => $item_meta ) {
				$single_item = $single_item;
				$product_id  = '';
				if ( isset( $item_meta['product_id'] ) ) {
					$_product = wc_get_product( $item_meta['product_id'] );

					if ( $_product && $_product->is_type( 'variable' ) && isset( $item_meta['variation_id'] ) ) {
						// The line item is a variable product, so consider its variation.
						$product_id = $item_meta['variation_id'];
					} else {
						$product_id = $item_meta['product_id'];
					}
				}

				if ( is_numeric( $product_id ) ) {
					$product_options = get_post_meta( $product_id, 'product_options', true );
					$group_purchase  = 'no';
					if ( 'yes' === apply_filters( 'is_order_group_purchase', $group_purchase, $single_item ) ) {
						if ( ! empty( $product_options ) && isset( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) && isset( $item_meta['Group Enrollment'] ) && 'yes' === $item_meta['Group Enrollment'] ) {
							$line_item_course_ids = $product_options['moodle_post_course_id'];
							if ( ! empty( $list_of_course_ids ) ) {
								$list_of_course_ids = array_unique( array_merge( $list_of_course_ids, $line_item_course_ids ), SORT_REGULAR );
							} else {
								$list_of_course_ids = $line_item_course_ids;
							}
						}
					}
				}
			}//foreach ends.
			return $list_of_course_ids;
		}

		/**
		 * Provides the functionality to check is the cohort exists for the user.
		 *
		 * @param int $cohort_name cohort name.
		 * @param int $course_ids Course ids array.
		 */
		private function add_in_same_cohort( $cohort_name, $course_ids ) {
			global $wpdb;
			$table_name      = $wpdb->prefix . 'bp_cohort_info';
			$results         = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE cohort_name like '%s'", $cohort_name . '%' ), ARRAY_A ); // @codingStandardsIgnoreLine
			$new_cohort_name = null;
			foreach ( $results as $res ) {
				$existing_courses = maybe_unserialize( $res['COURSES'] );
				sort( $existing_courses );
				sort( $course_ids );
				if ( $existing_courses === $course_ids && $cohort_name === $res['COHORT_NAME'] ) {
					$new_cohort_name = $res['COHORT_NAME'];
					break;
				}
			}

			if ( null !== $new_cohort_name ) {
				return array(
					'add_in_same_cohort' => true,
					'cohort_name'        => $new_cohort_name,
				);
			}
			return array( 'add_in_same_cohort' => false );
		}

		/**
		 * Provides the functionality for to check is the cohort exists for the user
		 * otherwise genrate the new cohort name
		 *
		 * @param string $cohort_name name of the cohort.
		 * @param int    $user_id User id.
		 */
		private function genrate_cohort_name( $cohort_name, $user_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'bp_cohort_info';

			$result = $wpdb->get_var( "SELECT ID FROM $table_name ORDER BY ID DESC LIMIT 1" ); // @codingStandardsIgnoreLine

			$result = ++$result;

			$user_info   = get_userdata( $user_id );
			$cohort_name = $user_info->user_login . '_' . $cohort_name;
			if ( $result > 0 ) {
				$cohort_name = $cohort_name . '_' . $result;
			}
			return $cohort_name;
		}

		/**
		 * Provides the functionality to generate cohort idbumber.
		 *
		 * @param int $user_login user login.
		 * @return string idnumber.
		 */
		private function genrate_cohort_idnumber( $user_login ) {
			$idnumber_exists = true;
			while ( $idnumber_exists ) {
				$idnumber = $user_login . current_time( 'timestamp' );
				global $wpdb;
				$table_name = $wpdb->prefix . 'bp_cohort_info';
				$results    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE idnumber like '%s'", $idnumber . '%' ), ARRAY_A ); // @codingStandardsIgnoreLine
				if ( $wpdb->num_rows > 0 ) {
					$idnumber_exists = true;
				} else {
					$idnumber_exists = false;
				}
			}
			return $idnumber;
		}

		/**
		 * Provides the functionality for the update the order on the order compleated.
		 *
		 * @param int   $user_id User id.
		 * @param array $incompl_ord Incmplete orders.
		 * @param int   $cur_ord_id Order id.
		 * @param int   $cohort_name cohort_name id.
		 * @param int   $product_array product array.
		 */
		private function update_cohort_on_order_complete( $user_id, $incompl_ord, $cur_ord_id, $cohort_name, $product_array ) {
			global $wpdb;
			$tbl_cohort_info = $wpdb->prefix . 'bp_cohort_info';
			$key             = array_search( $cur_ord_id, $incompl_ord, true );

			if ( false !== $key ) {
				unset( $incompl_ord[ $key ] );
			}
			$result = $wpdb->update( // @codingStandardsIgnoreLine
				$tbl_cohort_info,
				array(
					'COHORT_MANAGER' => $user_id,
					'INCOMP_ORD'     => serialize( $incompl_ord ), // @codingStandardsIgnoreLine
					'PRODUCTS'       => serialize( $product_array ), // @codingStandardsIgnoreLine
				),
				array(
					'COHORT_MANAGER' => $user_id,
					'cohort_name'    => $cohort_name,
				)
			);
			return $result;
		}

		/**
		 * Get pending orders from the DB and remove the current order id from it.
		 *
		 * @param int   $order_id Order id.
		 * @param array $incomp_order Array of the incomplete orders.
		 */
		private function get_pending_orders( $order_id, $incomp_order ) {
			$key = array_search( $order_id, $incomp_order, true );
			if ( false === $key ) {
				unset( $incomp_order[ $key ] );
			}
			return $incomp_order;
		}

		/**
		 * Function returns the list of the products from cohort.
		 *
		 * @param string $cohort_name Name of the cohort.
		 */
		private function get_cohort_products( $cohort_name ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'bp_cohort_info';
			$prodcts    = $wpdb->get_var( $wpdb->prepare( "SELECT PRODUCTS FROM {$table_name} WHERE cohort_name = %s", $cohort_name ) ); // @codingStandardsIgnoreLine
			return $prodcts;
		}

		/**
		 * Function creates the cohort on moodle.
		 *
		 * @param int    $user_id     User id.
		 * @param string $cohort_name Name of the cohort.
		 * @param string $idnumber idnumber.
		 */
		public function mdl_create_cohort( $user_id, $cohort_name, $idnumber ) {
			/*code added for cohort id  starts here*/
			$rev_cohort_name = strrev( $cohort_name );
			$occurrence      = strpos( $rev_cohort_name, '_' );

			if ( $occurrence > 3 || 0 === $occurrence ) {
				$substr = '';
			} else {
				$substr = substr( $rev_cohort_name, 0, $occurrence );
				$substr = strrev( $substr );
			}

			$products = $this->un_uerialize( $this->get_cohort_products( $cohort_name ) );
			$products = array_keys( $products );
			$products = implode( '_', $products );

			/*code added for cohort id ends here*/

			$moodle_function = 'core_cohort_create_cohorts';
			$args            = array(
				'categorytype'      => array(
					'type'  => 'system',
					'value' => '',
				),
				'name'              => $cohort_name,
				'idnumber'          => $idnumber,
				'descriptionformat' => 1,
				'description'       => ' ',
				'visible'           => 1,
			);
			$eb_con_helper   = self::get_connection_helper();
			$response        = $eb_con_helper->connect_moodle_with_args_helper( $moodle_function, array( 'cohorts' => array( $args ) ) );
			if ( isset( $response['success'] ) && $response['success'] ) {
				$response_data = $response['response_data'];
				$this->update_moodle_cohort_id( $idnumber, $response_data[0], $user_id );
			} elseif ( isset( $response['response_message'] ) ) {
				new Eb_Bp_Admin_Notices( $response['response_message'], 2 );
			}

			return $response;
		}
		/**
		 * Checks is the cohort is synced with moodle.
		 *
		 * @param string $idnumber cohort manager id.
		 * @param string $moodle_resp name of the cohort.
		 * @param int    $user_id user id.
		 */
		private function update_moodle_cohort_id( $idnumber, $moodle_resp, $user_id ) {
			global $wpdb;
			$wpdb->update( // @codingStandardsIgnoreLine
				$wpdb->prefix . 'bp_cohort_info',
				array(
					'COHORT_NAME'   => $moodle_resp->name,
					'MDL_COHORT_ID' => $moodle_resp->id,
					'SYNC'          => 1,
				),
				array(
					'idnumber'       => $idnumber,
					'COHORT_MANAGER' => $user_id,
				)
			);
		}

		/**
		 * Function to delete the cohort.
		 *
		 * @param array $cohort_id_array Array of the cohort ids.
		 */
		public function delete_cohort( $cohort_id_array ) {
			global $wpdb;
			$moodle_function = 'auth_edwiserbridge_delete_cohort';
			$request_args    = array();

			foreach ( $cohort_id_array as $cohort_id ) {
				array_push( $request_args, array( 'cohortid' => $cohort_id ) );
			}

			// DELETE MOODLE COHORT FUNCTIONALITY.
			$conn_helper = self::get_connection_helper();
			$response    = $conn_helper->connect_moodle_with_args_helper( $moodle_function, array( 'cohort' => $request_args ) );
			if ( isset( $response['response_data']->status ) && $response['response_data']->status ) {
				foreach ( $cohort_id_array as $cohort_id ) {
					if ( empty( $cohort_id ) ) {
						continue;
					}
					$table_name   = $wpdb->prefix . 'bp_cohort_info';
					$enrol_tbl    = $wpdb->prefix . 'moodle_enrollment';
					$del_row_info = $wpdb->get_row( $wpdb->prepare( "SELECT NAME AS name, COHORT_NAME AS cohort_name, COHORT_MANAGER AS cohort_manager FROM {$table_name} WHERE MDL_COHORT_ID = %d;", $cohort_id ) ); // @codingStandardsIgnoreLine

					$wpdb->delete( // @codingStandardsIgnoreLine
						$table_name,
						array( 'MDL_COHORT_ID' => $cohort_id ),
						array( '%d' )
					);
					$wpdb->delete( // @codingStandardsIgnoreLine
						$enrol_tbl,
						array( 'mdl_cohort_id' => $cohort_id ),
						array( '%d' )
					);
					$cohort_man = get_user_by( 'ID', $del_row_info->cohort_manager );
					$group_name = $del_row_info->name ? $del_row_info->name : $del_row_info->cohort_name;
					$args       = array(
						'group_name' => $group_name,
						'user_email' => $cohort_man->user_email,
						'username'   => $cohort_man->user_login,
						'first_name' => $cohort_man->first_name,
						'last_name'  => $cohort_man->last_name,
					);
					do_action( 'eb_bp_cohort_delete', $args );
				}
				return 1;
			}
			return 0;
		}

		/**
		 * Function enrolls the cohort into the course.
		 *
		 * @param array  $courses array of the course ids.
		 * @param string $cohort_name Name of the cohort.
		 * @param int    $user_id User id.
		 */
		public function enroll_cohort_in_courses( $courses, $cohort_name, $user_id ) {
			global $wpdb;
			$tbl_cohort_info = $wpdb->prefix . 'bp_cohort_info';
			$stmt            = "SELECT `MDL_COHORT_ID` FROM $tbl_cohort_info WHERE COHORT_MANAGER='$user_id' AND cohort_name='$cohort_name'";
			$cohort_id       = $wpdb->get_var( $stmt ); // @codingStandardsIgnoreLine
			$moodle_function = 'auth_edwiserbridge_manage_cohort_enrollment';
			$conn_helper     = self::get_connection_helper();

			foreach ( $courses as $course ) {
				$course_id = get_post_meta( $course, 'moodle_course_id' );
				$conn_helper->connect_moodle_with_args_helper(
					$moodle_function,
					array(
						'cohort' => array(
							array(
								'courseid' => $course_id[0],
								'cohortid' => $cohort_id,
							),
						),
					)
				);
			}
		}
		/**
		 * Function updates the cohort detials on the user enrollment.
		 *
		 * @param int    $order_id Order id.
		 * @param int    $user_id  User id.
		 * @param string $cohort_name Nam of the cohort.
		 * @param array  $courses Array of the course ids.
		 * @param array  $product_array Array of the products.
		 * @param int    $incomp_order Incomplete orders.
		 * @param int    $idnumber idnumber.
		 */
		public function update_cohort_on_user_enrollment( $order_id, $user_id, $cohort_name, $courses, $product_array, $incomp_order, $idnumber ) {
			$cohort_created = true;
			if ( ! $cohort_name['success'] ) {
				$cohort_created = $this->mdl_create_cohort( $user_id, $cohort_name['cohort_name'], $idnumber );
			}

			if ( $cohort_created['success'] ) {
				$this->enroll_cohort_in_courses( $courses, $cohort_name['cohort_name'], $user_id );
				$incompl_ord = $this->get_pending_orders( $order_id, $incomp_order );
				$this->update_cohort_on_order_complete( $user_id, $incompl_ord, $order_id, $cohort_name['cohort_name'], $product_array );
				// updating cohort id in order meta.

				$quantity = 0;
				// FUNCTIONALITY TO GET THE COHORT QUANTITY.
				foreach ( $product_array as $key => $value ) {
					$key      = $key;
					$quantity = $value;
				}
				update_cohort_id_in_order_meta( $order_id, $cohort_created['response_data'][0]->id, $quantity );
			}
		}

		/**
		 * Fnnction returns the object of the edwiser bridge connection helper class.
		 */
		public static function get_connection_helper() {
			$eb_loader = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();
			return $eb_loader->connection_helper();
		}



		/**
		 * Provides the functionality to update the cohort name and user role on
		 * course enrollment.
		 *
		 * @param Array  $courses array of the course id's user has been enrolled.
		 * @param String $cohort_name name of the cohort user enrolled.
		 * @param Number $user_id user id who has beend enrolled to the courses.
		 */
		public function update_enrollment_records( $courses, $cohort_name, $user_id ) {
			global $wpdb;
			$moodle_enrollment = $wpdb->prefix . 'moodle_enrollment';
			$wpdb->query( $wpdb->prepare( "update {moodle_enrollment} set cohort_name = %s, role = %s  where user_id = %d and course_id in ({$courses})", $cohort_name, 'Manager', $user_id ) ); // @codingStandardsIgnoreLine
		}
	}
}

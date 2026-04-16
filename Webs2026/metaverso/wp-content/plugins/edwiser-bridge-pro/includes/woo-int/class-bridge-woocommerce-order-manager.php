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

use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

/**
 * This class is responsible for Woo Integration Order Manager.
 */
class Bridge_Woocommerce_Order_Manager {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string The current version of this plugin.
	 */
	private $version;

	/**
	 * Edwiser Bridge instance.
	 *
	 * @var object
	 */
	private $edwiser_bridge;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

		$this->edwiser_bridge = new EdwiserBridge();
	}

	/**
	 * This function checks, if order contains products associated with courses
	 * Enroll customer in corresponding course
	 *
	 * @param integer $order_id     The order ID.
	 * @access public
	 * @since 1.0.0
	 */
	public function handle_order_complete( $order_id ) {

		if ( ! empty( $order_id ) ) {
			$order        = wc_get_order( $order_id ); // Get Order details.
			$is_processed = $order->get_meta( '_is_processed', true );

			if ( ! empty( $is_processed ) ) {
				$this->edwiser_bridge->logger()->add( 'user', 'Order id ' . $order_id . ' is already processed' );
				return 0;
			}

			
			$user       = $order->get_user();
			$email_args = array(
				'user_email' => $user->user_email,
				'order_id'   => $order_id,
				'username'   => $user->user_login,
				'first_name' => $user->first_name,
				'last_name'  => $user->last_name,
			);
			// WCS is active.
			$subscription = 0;
			if ( defined( 'WOOINT_WCS_VER' ) ) {
				if ( version_compare( WOOINT_WCS_VER, '2.0', '>=' ) && \wcs_order_contains_subscription( $order ) ) {
					$subscription = 1;
				} elseif ( version_compare( WOOINT_WCS_VER, '2.0', '<' ) && \WC_Subscriptions_Order::order_contains_subscription( $order ) ) {
					$subscription = 1;
				}
			}

			$user_id = $order->get_user_id();

			$is_for_someone_else = $order->get_meta( '_order_for_someone_else', true );
			if ( ! empty( $is_for_someone_else ) && 'yes' === $is_for_someone_else ) {
				$user_email               = $order->get_meta( '_recipient_email', true );
				$user                     = get_user_by( 'email', $user_email );
				$user_id                  = $user->ID;
				$email_args['user_email'] = $user_email;
				$email_args['username']   = $user->user_login;
				$email_args['first_name'] = $user->first_name;
				$email_args['last_name']  = $user->last_name;
			}

			try {
				// Comprehensive order validation
				if ( empty( $order ) ) {
					global $current_user;
					wp_get_current_user();
					if ( function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
						
					$error_data = array(
						'url'          => $_SERVER['REQUEST_URI'] ?? 'N/A',
						'arguments'    => array( 'order_id' => $order_id ),
						'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
						'responsecode' => '',
						'exception'    => '',
						'errorcode'    => '',
						'message'      => 'Invalid order or order ID is null in handle_order_complete',
						'backtrace'    => wp_debug_backtrace_summary( null, 0, false ),
					);
					\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
					}
					// Return early to prevent fatal errors
					return 0;
				}
			} catch ( \Throwable $e ) {
				global $current_user;
				wp_get_current_user();
				if ( function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
					
				$error_data = array(
					'url'          => $_SERVER['REQUEST_URI'] ?? 'N/A',
					'arguments'    => array(
						'order_id' => $order_id,
						'file'     => $e->getFile(),
						'line'     => $e->getLine(),
						'trace'    => $e->getTraceAsString(),
					),
					'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
					'responsecode' => '',
					'exception'    => '',
					'errorcode'    => '',
					'message'      => 'Error processing order in handle_order_complete: ' . $e->getMessage(),
					'backtrace'    => wp_debug_backtrace_summary( null, 0, false ),
				);
				\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
				}
				// Return early to prevent fatal errors
				return 0;
			}

			// check if this order have purchase for someones else.
			$list_of_course_ids = self::get_moodle_course_ids_for_order( $order, 1, $order_id );

			if ( ! empty( $list_of_course_ids ) ) {
				$user = get_userdata( intval( $user_id ) );
				$this->edwiser_bridge->user_manager()->link_moodle_user( $user );

				$course_enrolled = self::enroll_user_in_courses( $user_id, $list_of_course_ids );
				if ( 1 === $course_enrolled ) {
					$order->update_meta_data( '_is_processed', true );
					// handling membership orders.
					$membership_handler = new Bridge_Woo_Membership_Handler( $this->plugin_name, $this->version );
					$membership_handler->handle_membsership_order( $order, $user_id, $order_id );
				}

				// Added email send functionality here because it was send even on bulk purchase orders.
				include_once 'emails/class-eb-woo-int-emailer.php';
				$plugin_emailer = new Eb_Woo_Int_Emailer();
				$plugin_emailer->send_course_enrollment_email( $email_args );
			} elseif ( 1 === $subscription ) {
				$order->update_meta_data( '_is_processed', true );
			}
			$order->save();
		}

	}

	/**
	 * This function checks, if order is already processed,
	 * It finds associated product courses and
	 * suspend customer enrollment in corresponding course
	 *
	 * @param integer $order_id     The order ID.
	 * @access public
	 * @since 1.0.0
	 */
	public function handle_order_cancel( $order_id ) {
		if ( ! empty( $order_id ) ) {
			$order = wc_get_order( $order_id ); // Get Order details.

			$subscription = 0;

			if ( defined( 'WOOINT_WCS_VER' ) && ( ( version_compare( WOOINT_WCS_VER, '2.0', '>=' ) && \wcs_order_contains_subscription( $order ) ) || ( version_compare( WOOINT_WCS_VER, '2.0', '<' ) && \WC_Subscriptions_Order::order_contains_subscription( $order ) ) ) ) {
				$subscription = 1;
			}

			$is_processed = $order->get_meta( '_is_processed', true );

			$this->edwiser_bridge->logger()->add( 'user', 'Check if User enrolled for Order ID - ' . $order_id );

			if ( empty( $is_processed ) ) {
				$this->edwiser_bridge->logger()->add( 'user', 'No User enrollment for Order ID - ' . $order_id );
				return 0;
			}

			$user_id = $order->get_user_id();

			$is_for_someone_else = $order->get_meta( '_order_for_someone_else', true );
			if ( 'yes' === $is_for_someone_else ) {
				$user_email = $order->get_meta( '_recipient_email', true );
				$user       = get_user_by( 'email', $user_email );
				$user_id    = $user->ID;
			}

			$list_of_course_ids = self::get_moodle_course_ids_for_order( $order, 0, $order_id );

			if ( ! empty( $list_of_course_ids ) ) {
				$course_enrolled = self::enroll_user_in_courses( $user_id, $list_of_course_ids, 0, 1 );

				if ( 1 === $course_enrolled ) {
					$order->update_meta_data( '_is_processed', true );
				}
			} elseif ( 1 === $subscription ) {
				$order->update_meta_data( '_is_processed', true );
			}
			$order->save();
		}
	}

	/**
	 * This function is used to create Moodle user if, new Customer is created on WordPress.
	 * This event is executed when new Order is created,
	 *
	 * @param interger $order_id   The order ID.
	 * @param array    $posted_data  The posted data.
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function create_moodle_user_for_created_customer( $order_id ) {
		// if ( empty( $posted_data ) ) {
		// 	$posted_data = '';
		// }
		$product_exist = false;

		if ( ! empty( $order_id ) ) {

			$order = wc_get_order( $order_id );
			$user_id = $order->get_user_id();

			$eb_general                        = get_option( 'eb_woo_int_settings' );
			$purchase_for_someone_else_enabled = isset( $eb_general['wi_enable_purchase_for_someone_else'] ) && 'yes' === $eb_general['wi_enable_purchase_for_someone_else'] ? true : false;
			$is_for_someone_else               = isset( $_POST['purchase_for_someone_else'] ) && '1' === $_POST['purchase_for_someone_else'] ? true : false; // @codingStandardsIgnoreLine
			if ( $purchase_for_someone_else_enabled && $is_for_someone_else ) {
				$first_name = isset(  $_POST['recipient_first_name']  ) ? sanitize_text_field(  $_POST['recipient_first_name']  ) : ''; // @codingStandardsIgnoreLine
				$last_name  = isset(  $_POST['recipient_last_name']  ) ? sanitize_text_field(  $_POST['recipient_last_name']  ) : ''; // @codingStandardsIgnoreLine
				$email      = isset(  $_POST['recipient_email']  ) ? sanitize_email(  $_POST['recipient_email']  ) : ''; // @codingStandardsIgnoreLine

				// update order meta.
				$order->update_meta_data( '_order_for_someone_else', 'yes' );
				$order->update_meta_data( '_recipient_first_name', $first_name );
				$order->update_meta_data( '_recipient_last_name', $last_name );
				$order->update_meta_data( '_recipient_email', $email );
				$order->save();

				// create user.
				$user = get_user_by( 'email', $email );
				if ( ! $user ) {
					$user_id = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->user_manager()->create_wordpress_user( $email, $first_name, $last_name );
				} else {
					$user_id = $user->ID;
				}
			}

			$membership_handler = new Bridge_Woo_Membership_Handler( $this->plugin_name, $this->version );
			$items              = $order->get_items(); // Get Item details.
			foreach ( $items as $single_item ) {
				$product_id = isset( $single_item['product_id'] ) ? $single_item['product_id'] : '';
				if ( ! empty( $product_id ) ) {
					$product_options = get_post_meta( $product_id, 'product_options', true );
					$product         = wc_get_product( $product_id );

					if ( ! empty( $product_options['moodle_course_id'] ) ) {
						$product_exist = true;
						break;
					} elseif ( $product->is_type( 'variable' ) && isset( $single_item['variation_id'] ) ) {
						$product_options = get_post_meta( $single_item['variation_id'], 'product_options', true );
						if ( ! empty( $product_options['moodle_course_id'] ) ) {
							$product_exist = true;
							break;
						}
					}

					/**---------------------------------------------
					 * check if the membership is enabled
					 * then check if the membership is linked to the current product.
					 * then check if the products associated to the membership have courses assciated.
					 *----------------------------------------------*/

					if ( check_woocommerce_membership_is_active() ) {
						$associated_memberships = $membership_handler->get_products_associated_with_membership( $single_item );

						// check if the product has any membership associated.
						if ( ! empty( $associated_memberships ) ) {
							// it can happen that the product have more than one membership if so then get all products of all the memberships and then get courses to add in list_of_course_ids.
							foreach ( $associated_memberships as $membership ) {
								$membership_products = $membership_handler->get_products_from_membership_id( $membership );
								foreach ( $membership_products as $product_id ) {
									$new_courses = get_wp_courses_from_product_id( $product_id );

									if ( ! empty( $new_courses ) ) {
										$product_exist = true;
										break;
									}
								}
							}
						}
					}
				}
			}

			$creae_moodle_acc = true;
			if ( isset($eb_general['wi_disable_checkout_user_creation']) && 'yes' === $eb_general['wi_disable_checkout_user_creation'] ) {
				$creae_moodle_acc = false;
			}

			if ( true === $product_exist && $creae_moodle_acc ) {
				$this->edwiser_bridge->logger()->add( 'user', 'Link Moodle User for User ID  ' . $user_id );  // add User log.

				$user = get_userdata( intval( $user_id ) );

				$user->user_login = strtolower( $user->user_login );

				$this->edwiser_bridge->logger()->add( 'user', 'Log from WooIntegration' );

				$this->edwiser_bridge->logger()->add( 'user', 'User Object JSON Encoded : ' . json_encode( $user ) ); // @codingStandardsIgnoreLine

				$this->edwiser_bridge->userManager()->linkMoodleUser( $user );
			}// if ends - Need to process for Moodle User creation.

			// for updating the user profile fields.
			do_action( 'wi_woo_checkout_customer_user_created', $user_id );

		}//if ends - Order id present

	} // function ends - create_moodle_user_for_created_customer.

	/**
	 * This function used to change generated password with User entered password during checkout
	 *
	 * @param string $password      This contains WordPress generated password.
	 * @return string $password
	 * @access public
	 * @since 1.0.0
	 */
	public function add_user_submitted_password( $password ) {

		if ( isset( $_POST['account_password'] ) ) { // @codingStandardsIgnoreLine
			$password = $_POST['account_password']; // @codingStandardsIgnoreLine
		}

		return $password;
	}

	/**
	 * This function is used to enroll user into courses, if subscription is activated.
	 *
	 * @param integer $user_id     The id of the user whose subscription is to be activated.
	 * @param string  $subscription_key  The key representing the given subscription.
	 * @access public
	 * @return void
	 */
	public function handle_activated_subscription( $user_id, $subscription_key ) {
		self::change_enrollment_per_subscription_status( $user_id, $subscription_key, 0 );
	}

	/**
	 * This function is used to suspend enrollment of user for courses, if subscription is cancelled/expired/put on hold.
	 *
	 * @param integer $user_id     The id of the user whose subscription is to be activated.
	 * @param string  $subscription_key  The key representing the given subscription.
	 * @access public
	 * @return void
	 */
	public function handle_cancelled_subscription( $user_id, $subscription_key ) {
		self::change_enrollment_per_subscription_status( $user_id, $subscription_key, 1 );
	}

	/**
	 * This function is called internally to enroll user into set of courses.
	 * This calls, 'update_user_course_enrollment()' for User enrollment
	 *
	 * @param integer $user_id     The id of the user whose subscription is to be activated.
	 * @param array   $course_id_list     List of Moodle post course ids.
	 * @param integer $suspend      The suspend status for courses.
	 * @param integer $unenroll  The unenroll status for courses.
	 * @param string  $start_date  The start date for courses.
	 * @param string  $end_date    The end date for courses.
	 *
	 * @return integer $course_enrolled    return status of course enrollment 1 - successfull 0 - problem in enrollment status change
	 * @access private
	 */
	public function enroll_user_in_courses( $user_id, $course_id_list, $suspend = 0, $unenroll = 0, $start_date = '0000-00-00 00:00:00', $end_date = false ) {
		$args = array(
			'user_id'    => $user_id,
			'courses'    => $course_id_list,
			'unenroll'   => $unenroll,
			'suspend'    => $suspend,
			'start_date' => $start_date,
			'end_date'   => $end_date,
		);

		$course_enrolled = $this->edwiser_bridge->enrollment_manager()->update_user_course_enrollment( $args ); // enroll user to course.

		if ( 1 === $course_enrolled ) {
			if ( 1 === $suspend ) {
				$this->edwiser_bridge->logger()->add( 'user', 'User enrollment suspended for courses - ' . serialize( $course_id_list ) ); // @codingStandardsIgnoreLine
			} else {
				$this->edwiser_bridge->logger()->add( 'user', 'User enrolled for courses - ' . serialize( $course_id_list ) ); // @codingStandardsIgnoreLine
			}
		} else {
			$this->edwiser_bridge->logger()->add( 'user', 'Enrollment response ' . $course_enrolled );
		}

		return $course_enrolled;
	}

	/**
	 * This function is used to change enrollment status as per subscription status
	 * It internally calls, self::_enroll_user_in_courses() to change enrollment status of course
	 *
	 * @param integer $user_id     The id of the user whose subscription is to be activated.
	 * @param string  $subscription_key  The key representing the given subscription.
	 * @param integer $suspend_status  The status for enrollment.
	 *
	 * @access private
	 * @return void
	 */
	private function change_enrollment_per_subscription_status( $user_id, $subscription_key, $suspend_status ) {
		$item = \WC_Subscriptions_Order::get_item_by_subscription_key( $subscription_key );
		if ( ! empty( $item ) ) {

			$product_id = '';
			if ( check_value_set( $item, 'variation_id' ) && is_numeric( $item['variation_id'] ) && $item['variation_id'] > 0 ) {
				$product_id = $item['variation_id'];
			} elseif ( check_value_set( $item, 'product_id' ) && is_numeric( $item['product_id'] ) ) {
				$product_id = $item['product_id'];
			}
			if ( ! empty( $product_id ) ) {
				$product_options = get_post_meta( $product_id, 'product_options', true );
				if ( check_value_set( $product_options, 'moodle_post_course_id' ) ) {
					self::enroll_user_in_courses( $user_id, $product_options['moodle_post_course_id'], $suspend_status );

					if ( 1 === $suspend_status ) {
						$this->edwiser_bridge->logger()->add( 'user', 'Subscription suspended for User ' . $user_id );
					} else {
						$this->edwiser_bridge->logger()->add( 'user', 'Subscription activated for User ' . $user_id );
					}
				}
			}
		}
	}

	/**
	 * This function is used to fetch list of Moodle courses associated with product items of specified order
	 *
	 * @param object  $order     This is $order object.
	 * @param integer $skip_subscription     This is flag to skip subscription.
	 *
	 * @return array $list_of_course_ids    This returns array of Moodle course post ids
	 * @access private
	 */
	public function get_moodle_course_ids_for_order( $order, $skip_subscription = 0, $order_id = 0) {

		try {
			// Check if order is valid
			if ( empty( $order ) ) {
				global $current_user;
				wp_get_current_user();
				if ( function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
					$error_data = array(
						'url'          => $_SERVER['REQUEST_URI'] ?? 'N/A',
						'arguments'    => array( 
							'order_id' => $order_id,
							'order_type' => gettype( $order ),
							'order_class' => is_object( $order ) ? get_class( $order ) : 'N/A'
						),
						'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
						'responsecode' => '',
						'exception'    => '',
						'errorcode'    => '',
						'message'      => 'Invalid order object in get_moodle_course_ids_for_order',
						'backtrace'    => wp_debug_backtrace_summary( null, 0, false ),
					);
					\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
				}
				return array();
			}

			// Get order ID if not provided
			if ( empty( $order_id ) ) {
				$order_id = $order->get_id();
			}
		} catch ( \Throwable $e ) {
			global $current_user;
			wp_get_current_user();
			if ( function_exists( '\app\wisdmlabs\edwiserBridge\wdm_log_json' ) ) {
				
			$error_data = array(
				'url'          => $_SERVER['REQUEST_URI'] ?? 'N/A',
				'arguments'    => array(
					'order_id' => $order_id,
					'file'     => $e->getFile(),
					'line'     => $e->getLine(),
					'trace'    => $e->getTraceAsString(),
				),
				'user'         => isset( $current_user ) ? $current_user->user_login . '(' . $current_user->first_name . ' ' . $current_user->last_name . ')' : '',
				'responsecode' => '',
				'exception'    => '',
				'errorcode'    => '',
				'message'      => 'Error processing order in get_moodle_course_ids_for_order: ' . $e->getMessage(),
				'backtrace'    => wp_debug_backtrace_summary( null, 0, false ),
			);
			\app\wisdmlabs\edwiserBridge\wdm_log_json( $error_data );
			}
			// Return early to prevent fatal errors
			return array();
		}
		
		$list_of_course_ids           = array();
		$total_associated_memberships = array();

		// $order_id = $order->get_id();
		$this->edwiser_bridge->logger()->add( 'user', 'Check Line Items for Order ID - ' . $order_id );

		// create Membership object.
		$membership_handler = new Bridge_Woo_Membership_Handler( $this->plugin_name, $this->version );

		$items = $order->get_items(); // Get Item details.
		foreach ( $items as $single_item ) {
			$product_id = '';

			if ( isset( $single_item['product_id'] ) ) {
				$_product = wc_get_product( $single_item['product_id'] );

				if ( 1 === $skip_subscription && defined( 'WOOINT_WCS_VER' ) && \WC_Subscriptions_Product::is_subscription( $_product ) ) {
						// if a subscription do not fetch course_ids.
						continue;
				}

				$product_id = eb_get_product_id_from_product( $_product, $single_item );

			}

			if ( is_numeric( $product_id ) ) {
				$product_options = get_post_meta( $product_id, 'product_options', true );
				$group_purchase  = 'no';
				// Removing the condition since the mail was not getting sent by on bulk purchase enabled product single qty purchase.
				if ( 'no' === apply_filters( 'is_order_group_purchase', $group_purchase, $single_item->get_id() ) ) {
					if ( check_value_set( $product_options, 'moodle_post_course_id' ) ) {
						$line_item_course_ids = $product_options['moodle_post_course_id'];

						if ( ! empty( $list_of_course_ids ) ) {
							$list_of_course_ids = array_unique( array_merge( $list_of_course_ids, $line_item_course_ids ), SORT_REGULAR );
						} else {
							$list_of_course_ids = $line_item_course_ids;
						}
					}
				}
			}

			// check if the woocoommerce membership plugin is active.
			$membership_processed_data    = $this->merge_membership_courses( $membership_handler, $list_of_course_ids, $single_item, $total_associated_memberships );
			$list_of_course_ids           = $membership_processed_data['course_list'];
			$total_associated_memberships = $membership_processed_data['total_memberships'];
		}

		// update order meta for memberships if has any memberships this is used while updating the membership-id column of the moodle_enrollment table.
		if ( ! empty( $total_associated_memberships ) ) {
			$order->update_meta_data( 'eb_order_associated_memberships', maybe_serialize( $total_associated_memberships ) );
			$order->save();
		}

		$this->edwiser_bridge->logger()->add( 'user', 'Courses IDs from Line Items  ' . serialize( $list_of_course_ids ) );  // @codingStandardsIgnoreLine

		return $list_of_course_ids;
	}


	/**
	 * Created this new function because of the Cyclomatic Complexity this will merge courses associated to membership with the existing list of coureses
	 *
	 * @param object $membership_handler     This is $membership_handler object.
	 * @param array  $list_of_course_ids     This is list of course ids.
	 * @param object $single_product_item      This is single product item.
	 * @param array  $total_associated_memberships     This is total associated memberships.
	 */
	public function merge_membership_courses( $membership_handler, $list_of_course_ids, $single_product_item, $total_associated_memberships ) {
		// check if the woocoommerce membership plugin is active.
		if ( check_woocommerce_membership_is_active() ) {
			$associated_memberships = $membership_handler->get_products_associated_with_membership( $single_product_item );
			// check if the product has any membership associated.
			if ( ! empty( $associated_memberships ) ) {
				// it can happen that the product have more than one membership if so then get all products of all the memberships and then get courses to add in list_of_course_ids.
				foreach ( $associated_memberships as $membership ) {
					$membership_products = $membership_handler->get_products_from_membership_id( $membership );

					foreach ( $membership_products as $product_id ) {
						$list_of_course_ids = is_array( $list_of_course_ids ) ? $list_of_course_ids : array(); // Validate list_of_course_ids array.
						$new_courses        = get_wp_courses_from_product_id( $product_id );
						$new_courses        = is_array( $new_courses ) ? $new_courses : array(); // Validate new_courses array.
						$list_of_course_ids = array_unique( array_merge( $list_of_course_ids, $new_courses ) );
					}
				}

				// update the total_associated_memberships.
				$total_associated_memberships = array_unique( array_merge( $total_associated_memberships, $associated_memberships ) );
			}
			// update order meta for memberships if has any memberships this is used while updating the membership-id column of the moodle_enrollment table.
		}
		return array(
			'course_list'       => $list_of_course_ids,
			'total_memberships' => $total_associated_memberships,
		);
	}



	/**
	 * Function to update course access if subscription status updates.
	 * Handles enrollment or unenrollment only for subscription orders.
	 *
	 * @param object $subscription Subscription object.
	 * @param string $new_status New status of subscription.
	 * @param string $old_status Old status of subscription.
	 * @since 1.1.3
	 */
	public function wcs_status_updated( $subscription, $new_status, $old_status ) {
		if ( get_class( $subscription ) !== 'WC_Subscription' ) {
			return;
		}
		// do not unenroll for pending cancel.
		if ( 'pending-cancel' === $new_status ) {
			return;
		}

		if ( 'active' === $new_status ) {
			update_post_meta( $subscription->get_id(), '_eb_subscription_processed', 1 );
		}

		$is_processed = get_post_meta( $subscription->get_id(), '_eb_subscription_processed', true );
		if ( ( 'on-hold' === $new_status || 'cancelled' === $new_status ) && ! $is_processed ) {
			return;
		}

		// Suspend or not w.r.t. subscription status.
		$statuses = array(
			'completed' => true,
			'active'    => false, // do not suspend if subscription is active.
			'failed'    => true,
			'on-hold'   => true, // do not suspend if subscription is on-hold.
			'cancelled' => true,
			'switched'  => true,
			'expired'   => true,
		);

		// add filter to change the suspend status.
		$statuses = apply_filters( 'eb_subscription_suspend_status', $statuses );

		$do_not_proceed_arr = array(
			'pending-cancel', // do not suspend if subscription is pending-cancel.
			// 'on-hold', // do not suspend if subscription is on-hold.
		);

		$process_rquest_array = array();

		// Process request if status is presemnt in any of the array_keys in the above array.
		if ( array_key_exists( $new_status, $statuses ) ) {
			$suspend = isset( $statuses[ $new_status ] ) && ! $statuses[ $new_status ] ? 0 : 1;
			$user    = get_user_by( 'id', $subscription->get_user_id() );

			$order_id = $subscription->get_parent_id();

			// Check admin saved setting on subscription expiration.
			$unenroll           = 0;
			$sub_expire_setting = $this->check_subscription_expiration_settings( $new_status );
			// if do-nothing setting is saved.
			if ( -1 === $sub_expire_setting ) {
				return;
			}
			extract( $sub_expire_setting, EXTR_OVERWRITE ); // @codingStandardsIgnoreLine.

			$items = $subscription->get_items();
			if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
				foreach ( $items as $item ) {
					$product_id = $item->get_product_id();// new.
					$product    = $item->get_product( $product_id );

					if ( $product->is_type( 'subscription_variation' ) ) {
						$product_id = $item->get_variation_id();// new.
					} else {
						$product_id = $item->get_product_id();// new.
					}

					$product_options = get_post_meta( $product_id, 'product_options', true );
					if ( isset( $product_options['moodle_post_course_id'] ) ) {
						if ( isset( $product_options['eb_apply_subscription_expiry'] ) && 'yes' === $product_options['eb_apply_subscription_expiry'] ) {
							$end_date = $subscription->get_date( 'end' );
						} else {
							$end_date = false;
						}
						$course_enrolled = self::enroll_user_in_courses(
							$subscription->get_user_id(), // new.
							$product_options['moodle_post_course_id'],
							$suspend,
							$unenroll,
							$subscription->get_date( 'start' ), // start date.
							$end_date // end date.
						);

						$email_args = array(
							'user_email' => $user->user_email,
							'order_id'   => $order_id,
							'username'   => $user->user_login,
							'first_name' => $user->first_name,
							'last_name'  => $user->last_name,
						);

						if ( 1 === $course_enrolled && ! $suspend ) {
							// Added email send functionality here because it was send even on bulk purchase orders.
							include_once 'emails/class-eb-woo-int-emailer.php';
							$plugin_emailer = new Eb_Woo_Int_Emailer();
							$plugin_emailer->send_course_enrollment_email( $email_args );
						}
					}
				}
			} else {
				// loop for older versions.
				foreach ( $items as $item ) {
						$product_id           = $item['product_id'];
						$product_variation_id = $item['variation_id'];
						$product              = \wc_get_product( $product_id );

					if ( $product->is_type( 'variable' ) || $product->is_type( 'subscription_variation' ) ) {
						$product_options = get_post_meta( $product_variation_id, 'product_options', true );
					} else {
						$product_options = get_post_meta( $product_id, 'product_options', true );
					}

					if ( isset( $product_options['moodle_post_course_id'] ) ) {
						if ( isset( $product_options['eb_apply_subscription_expiry'] ) && 'yes' === $product_options['eb_apply_subscription_expiry'] ) {
							$end_date = $subscription->get_date( 'end' );
						} else {
							$end_date = false;
						}

						$course_enrolled = self::enroll_user_in_courses(
							$subscription->order->user_id,
							$product_options['moodle_post_course_id'],
							$suspend,
							$unenroll,
							$subscription->get_date( 'start' ), // start date.
							$end_date // end date.
						);

						$email_args = array(
							'user_email' => $user->user_email,
							'order_id'   => $order_id,
							'username'   => $user->user_login,
							'first_name' => $user->first_name,
							'last_name'  => $user->last_name,
						);

						if ( 1 === $course_enrolled ) {
							// Added email send functionality here because it was send even on bulk purchase orders.
							include_once 'emails/class-eb-woo-int-emailer.php';
							$plugin_emailer = new Eb_Woo_Int_Emailer();
							$plugin_emailer->send_course_enrollment_email( $email_args );
						}
					}
				}
			}
		}
	}

	/**
	 * Function to check subscription expiration settings.
	 *
	 * @param string $new_status new status.
	 */
	private function check_subscription_expiration_settings( $new_status ) {
		$sub_expire_setting = array();
		if ( 'expired' === $new_status || 'cancelled' === $new_status ) {
			$woo_int_settings           = get_option( 'eb_woo_int_settings', array() );
			$on_subscription_expiration = $woo_int_settings['wi_on_subscription_expiration'];
			if ( 'do-nothing' === $on_subscription_expiration ) {
				return -1;
			} elseif ( 'suspend' === $on_subscription_expiration ) {
				$sub_expire_setting['suspend'] = 1;
			} elseif ( 'unenroll' === $on_subscription_expiration ) {
				$sub_expire_setting['unenroll'] = 1;
			}
		}
		return $sub_expire_setting;
	}

	/**
	 * Function to update enrollment/unenrollment when order status changes.
	 * This function does not handle subsciption orders.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $old_status Old order status.
	 * @param string $new_status New order status.
	 * @since 1.1.3
	 */
	public function wc_order_status_changed( $order_id, $old_status, $new_status ) {
		// enrol w.r.t. order status?
		$statuses = array(
			'completed'  => true,
			'processing' => false,
			'on-hold'    => false,
			'cancelled'  => false,
			'failed'     => false,
			'refunded'   => false,
		);

		if ( isset( $statuses[ $new_status ] ) && true === $statuses[ $new_status ] ) {
			// Enrol.
			$this->handle_order_complete( $order_id );
		} elseif ( 'cancelled' === $new_status && false === $statuses[ $new_status ] ) {
			// Unenrol.
			$this->handle_order_cancel( $order_id );
		}

		do_action( 'wooint_after_order_status_changed', $order_id, $old_status, $new_status );
	}

	/**
	 * This function will disable guest checkout option if cart contains course associated products.
	 *
	 * @param string $value ( yes to enable guest checkout , no to disable guest checkout ).
	 * @return $value ( yes to enable guest checkout , no to disable guest checkout ).
	 */
	public function disable_guest_checkout( $value ) {
		if ( is_admin() ) {
			return $value;
		}

		if ( WC()->cart ) {
			$cart = WC()->cart->get_cart();
			foreach ( $cart as $item ) {
				$_product    = $item['data'];
				$_product_id = $_product->get_id();

				$product_options = get_post_meta( $_product_id, 'product_options', true );
				if ( check_value_set( $product_options, 'moodle_post_course_id' ) ) {
					$value = 'no';
					break;
				}
			}
		}
		return $value;
	}

	/**
	 * This function shows purchase for someone else data on order edit page
	 *
	 * @param object $order Order object.
	 */
	public function show_purchase_for_someone_else_data( $order ) {
		$is_for_someone_else = $order->get_meta( '_order_for_someone_else', true );
		if ( ! empty( $is_for_someone_else ) && 'yes' === $is_for_someone_else ) {
			$user_email               = $order->get_meta( '_recipient_email', true );
			$user                     = get_user_by( 'email', $user_email );

			echo '<p><strong>' . __( 'Course purchased for someone else: ', 'edwiser-bridge-pro'). '</strong>' . __( 'Yes', 'edwiser-bridge-pro' ) . '</p>';
			echo '<p><strong>' . __( 'Enrolled User: ', 'edwiser-bridge-pro'). '</strong><a href="' . esc_url( get_edit_user_link( $user->ID ) ) . '">' . $user_email . '</a></p>';
		}
	}
}

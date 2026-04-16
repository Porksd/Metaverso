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
 * Functionality to handle all the membership related tasks.
 *
 * Below is the list of functionalities added
 *
 * ----------- Functionality 1 ----------
 * If any of the product is associated with membership then add all the courses of all products associated to that membership.
 * called on the order completion hook, Defination is in function get_moodle_course_ids_for_order
 *
 * ------------- Functionality 2 ----------
 * Update membership-id column in moodle_enrollment table for all courses which associated to the memberships i.e all courses associated to all products in membership.
 * called on the order completion hook, Defination is in this file.
 *
 * @since 2.0.0
 */
class Bridge_Woo_Membership_Handler {

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Constructor to initialize the class variables.
	 *
	 * @param string $plugin_name The plugin name.
	 * @param string $version     The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Get associated membership ids to the course.
	 *
	 * @param  string $course_id The course id.
	 * @param  string $user_id   The user id.
	 */
	public function get_membership_id( $course_id, $user_id ) {
		global $wpdb;
		$tbl_name        = $wpdb->prefix . 'moodle_enrollment';
		$old_memberships = $wpdb->get_var( $wpdb->prepare( "SELECT membership_id FROM $tbl_name WHERE course_id = %d AND user_id = %d", array( $course_id, $user_id ) ) ); // @codingStandardsIgnoreLine
		$old_memberships = maybe_unserialize( $old_memberships );
		return $old_memberships;
	}


	/**
	 * Functionality to get products associated to the membership
	 *
	 * @param int $membership_id The membership id.
	 * @return array of the associated products
	 */
	public function get_products_from_membership_id( $membership_id ) {
		$associated_products = get_post_meta( $membership_id, '_product_ids', 1 );
		return $associated_products;
	}


	/**
	 * Functionality to get courses from the membership id
	 *
	 * @param int $membership_id The membership id.
	 * @return $total_courses all associated courses to the membership
	 */
	public function get_courses_from_membership_id( $membership_id ) {
		$products_list = $this->get_products_from_membership_id( $membership_id );

		$total_courses = array();
		foreach ( $products_list as $product_id ) {
			$new_courses = get_wp_courses_from_product_id( $product_id );
			if ( is_array( $new_courses ) ) { // Added this check.
				$total_courses = array_unique( array_merge( $total_courses, $new_courses ) );
			}
		}
		return $total_courses;
	}


	/**
	 * Functionality to alter table and store associated membership ids of the users.
	 */
	public function add_membership_column_in_moodle_enrollment() {
		global $wpdb;

		$usr_enrol_tbl = $wpdb->prefix . 'moodle_enrollment';
		$col_name      = 'membership_id';
		$col_type      = 'varchar( 200 )';
		$query         = "SHOW COLUMNS FROM `$usr_enrol_tbl` LIKE '$col_name';";
		$exists        = $wpdb->query( $query ); // @codingStandardsIgnoreLine

		// Checks the column exist or not if not exist then add the column into the databse.
		if ( ! $exists ) {
			$query = "ALTER TABLE `$usr_enrol_tbl` ADD COLUMN ( `$col_name` $col_type );";
			$wpdb->query( $query ); // @codingStandardsIgnoreLine
		}
	}


	/**
	 * This function handles the orders having products which are associated to the membership.
	 *
	 * @param  object $order  object of the woocommerce order.
	 * @param  int    $user_id user id.
	 */
	public function handle_membsership_order( $order, $user_id, $order_id = 0 ) {
		// $order_id = $order->get_id();
		if (empty($order_id)) {
			$order_id = $order->get_id();
		}

		// get post meta where all the memberships are stored.
		$order_memberships = $order->get_meta( 'eb_order_associated_memberships', 1 );

		$order_memberships = maybe_unserialize( $order_memberships );

		if ( $order_memberships && ! empty( $order_memberships ) ) {
			// foreach throgh each membership.
			foreach ( $order_memberships as $membership ) {
				$membership_products = $this->get_products_from_membership_id( $membership );
				// for each for each associated product.
				foreach ( $membership_products as $product_id ) {
					$courses = get_wp_courses_from_product_id( $product_id );
					// for each throgh each course of the product.
					foreach ( $courses as $course_id ) {
						// Update membership ids on moodle enrollment table.
						$this->update_membership_id_on_moodle_enrollment_tbl( $course_id, $user_id, $order_memberships );
					}
				}
			}
			$order->delete_meta_data( 'eb_order_associated_memberships' );
			$order->save();
		}
	}


	/**
	 * Check if the product is associated with membership or membership is asscoiated with the product return all memberships to which a product is associated if not associated then return blank array.
	 *
	 * @param int $single_item The single item.
	 */
	public function get_products_associated_with_membership( $single_item ) {
		$total_product_memberships = array();
		$product                   = wc_get_product( $single_item['product_id'] );
		$membership_plans          = $this->get_membership_plans();
		$variation_memberships     = array();
		$product_id                = $single_item['product_id'];

		if ( $product && $product->is_type( 'variable' ) && isset( $single_item['variation_id'] ) ) {
			// The line item is a variable product, so consider its variation.
			$variation_id = $single_item['variation_id'];

			// get memberships associated with the variation.
			$variation_memberships = $this->return_memberships_associated_with_product( $variation_id, $membership_plans );

			// merge both the memberships and create new array.
		}

		$total_product_memberships = $this->return_memberships_associated_with_product( $product_id, $membership_plans );
		$total_product_memberships = array_unique( array_merge( $total_product_memberships, $variation_memberships ) );

		return $total_product_memberships;
	}





	/**
	 * This function is responsible to return the associated memberships to the product.
	 *
	 * @param int $product_id The product id.
	 * @param int $membership_plans The membership plans.
	 */
	public function return_memberships_associated_with_product( $product_id, $membership_plans ) {
		$associated_memberships = array();

		foreach ( $membership_plans as $membership ) {
			if ( in_array( $product_id, $membership['associated_products'] ) && ! in_array( $membership['membership_id'], $associated_memberships ) ) { // @codingStandardsIgnoreLine
				array_push( $associated_memberships, $membership['membership_id'] );
			}
		}
		return $associated_memberships;
	}


	/**
	 * Update membership id in the moodle enrollment table.
	 *
	 * @param int   $course_id The course id.
	 * @param int   $user_id The user id.
	 * @param array $new_memberships The new memberships.
	 */
	public function update_membership_id_on_moodle_enrollment_tbl( $course_id, $user_id, $new_memberships ) {
		global $wpdb;
		$tbl_name = $wpdb->prefix . 'moodle_enrollment';

		// get previous membership_id data.
		$old_memberships = $this->get_membership_id( $course_id, $user_id );

		if ( ! empty( $old_memberships ) ) {
			// check if the Membership ID is already in the array.
			$new_memberships = array_unique( array_merge( $old_memberships, $new_memberships ) );
		}

		$new_memberships = maybe_serialize( $new_memberships );

		// updating membsership id.
		$wpdb->query( $wpdb->prepare( "UPDATE $tbl_name SET membership_id = %s WHERE course_id = %d AND user_id = %d", array( $new_memberships, $course_id, $user_id ) ) ); // @codingStandardsIgnoreLine
	}




	/**
	 * Return all available membership plans.
	 *
	 * @since 2.0.0
	 */
	public function get_membership_plans() {
		$membership_plans_array = array();

		$args              = array( 'posts_per_page' => -1 );
		$args['post_type'] = 'wc_membership_plan';
		$membership_plans  = get_posts( $args );

		if ( ! empty( $membership_plans ) ) {
			foreach ( $membership_plans as $membership ) {
				$associated_products = $this->get_products_from_membership_id( $membership->ID );

				if ( ! empty( $associated_products ) ) {
					array_push(
						$membership_plans_array,
						array(
							'membership_id'       => $membership->ID,
							'associated_products' => $associated_products,
						)
					);
				}
			}
		}
		return $membership_plans_array;
	}




	/**
	 * Handle membership status chanege.
	 *
	 * @param int    $user_membership The user membership.
	 * @param string $old_status The old status.
	 * @param string $new_status The new status.
	 */
	public function handle_membsership_status_change( $user_membership, $old_status, $new_status ) {
		$user_id       = $user_membership->get_user_id();
		$membership_id = $user_membership->get_plan_id();
		$order_manager = new Bridge_Woocommerce_Order_Manager( $this->plugin_name, $this->version );

		switch ( $new_status ) {
			case 'active':
				// Enroll user to the course but what if the old status of the user is delayed, pending cancellation and cancelled at that time user will get enrolled again in the course so perform all these actions only if the old status of the user is paused and expired.

				$total_courses = $this->get_courses_from_membership_id( $membership_id );

				// process only if membership have any courses associated.
				if ( ! empty( $total_courses ) ) {
					$this->add_enrollment_entry_with_membership_id( $total_courses, $user_id, $membership_id );
				}
				// }

				break;

			case 'paused':
				// suspend user from all the courses and remove user enrollment from the wp courses.
				// get all products from membership.

				$total_courses = $this->get_courses_from_membership_id( $membership_id );
				// process only if membership have any courses associated.
				if ( ! empty( $total_courses ) ) {
					$order_manager->enroll_user_in_courses( $user_id, $total_courses, 1 );
				}

				break;

			case 'expired':
			case 'cancelled':
				// check if the count is more than 1 then just delete the memberships from the moodle enrollment table.
				// and if the count is 1 then delete whole role.
				$option_name = 'wi_on_membership_' . $new_status;

				$total_courses = $this->get_courses_from_membership_id( $membership_id );
				// process only if membership have any courses associated.
				if ( ! empty( $total_courses ) ) {
					$woo_int_settings = maybe_unserialize( get_option( 'eb_woo_int_settings', false ) );

					if ( isset( $woo_int_settings[ $option_name ] ) && 'suspend' === $woo_int_settings[ $option_name ] ) {
						$order_manager->enroll_user_in_courses( $user_id, $total_courses, 1 );
					} elseif ( isset( $woo_int_settings[ $option_name ] ) && 'unenroll' === $woo_int_settings[ $option_name ] ) {
						$order_manager->enroll_user_in_courses( $user_id, $total_courses, 0, 1 );
					}
				}

				break;
			default:
				break;
		}
	}



	/**
	 * Add enrollment record here a new record can be added or the existing one can be modified.
	 *
	 * @param array $total_courses The total courses.
	 * @param int   $user_id The user id.
	 * @param int   $membership_id The membership id.
	 */
	public function add_enrollment_entry_with_membership_id( $total_courses, $user_id, $membership_id ) {
		global $wpdb;
		$tbl_name = $wpdb->prefix . 'moodle_enrollment';

		foreach ( $total_courses as $course_id ) {
			// get exitsing record if any.
			$old_memberships = $wpdb->get_var( $wpdb->prepare( "SELECT membership_id, act_cnt FROM $tbl_name WHERE course_id = %d AND user_id = %d", array( $course_id, $user_id ) ) ); // @codingStandardsIgnoreLine
			$old_memberships = maybe_unserialize( $old_memberships );

			$order_manager = new Bridge_Woocommerce_Order_Manager( $this->plugin_name, $this->version );
			if ( empty( $old_memberships ) ) {
				// if no enrollment entry of the user for the same course then update all the things.
				$order_manager->enroll_user_in_courses( $user_id, array( $course_id ) );
			}
			$membsership_array = array( $membership_id );
			$this->update_membership_id_on_moodle_enrollment_tbl( $course_id, $user_id, $membsership_array );
		}
	}
}

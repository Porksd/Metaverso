<?php
/**
 * Handles the cohort functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

require_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/eb-bp-functions.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Eb_Bp_Cohort_Manage_User' ) ) {

	/**
	 * Class provides the cohort managment functionality.
	 */
	class Eb_Bp_Cohort_Manage_User {

		/**
		 * Connection helper object refrance variable.
		 *
		 * @var $con_helper Connection helper object refrance variable.
		 */
		private $con_helper;

		/**
		 * Class cnstructor.
		 */
		public function __construct() {
			$this->con_helper = Eb_Bp_Manage_Cohort::get_connection_helper();
		}

		/**
		 * Provides functionality to add the user into the cohort.
		 *
		 * @param int    $user_id user id.
		 * @param string $mdl_cohort_id moodle cohort id.
		 * @param string $user_role user role.
		 * @param int    $enrolled_by user id who is enrolling user by defualt 0.
		 * @param int    $send_mail is send mail enabled.
		 */
		public function add_user_to_cohort( $user_id, $mdl_cohort_id, $user_role, $enrolled_by = 0, $send_mail = 1 ) {
			// Get Moodle Cohort Id from cohort Id.
			if ( 0 !== $enrolled_by ) {
				$cohort_manager_id = $enrolled_by;
			} else {
				$cohort_manager_id = $user_id;
				$user_role         = 'manager';
			}

			$moodle_function = 'core_cohort_add_cohort_members';
			$users_moodle_id = $this->get_users_moodleid( $user_id );
			$args            = array(
				'cohorttype' => array(
					'type'  => 'id',
					'value' => $mdl_cohort_id,
				),
				'usertype'   => array(
					'type'  => 'id',
					'value' => $users_moodle_id,
				),
			);

			$responce = $this->con_helper->connect_moodle_with_args_helper( $moodle_function, array( 'members' => array( $args ) ) );

			if ( isset( $responce['success'] ) && 1 === $responce['success'] ) {
				if ( 'manager' === $user_role ) {
					$this->update_user_role( $user_id, 4 );
				} else {
					$this->update_user_role( $user_id, 5 );
				}
				$args = $this->prepare_email_args( $cohort_manager_id, $user_id, $mdl_cohort_id );

				if ( $send_mail ) {
					do_action( 'eb_bp_new_user_to_cohort', $args );
				}
				return true;
			}
			return false;
		}

		/**
		 * Provides the functionality to retrive the moodle user id of the user
		 *
		 * @param int $user_id WP user id.
		 */
		private function get_users_moodleid( $user_id ) {
			return get_user_meta( $user_id, 'moodle_user_id', true );
		}

		/**
		 * Function to update the user role.
		 *
		 * @param int $user_id user id.
		 * @param int $role user role id.
		 */
		private function update_user_role( $user_id, $role ) {
			$user_data = array(
				'userid' => $this->get_users_moodleid( $user_id ),
				'roleid' => $role,
			);
			$this->con_helper->connect_moodle_with_args_helper( 'moodle_role_assign', array( 'assignments' => array( $user_data ) ) );
		}

		/**
		 * Function to delete the user from cohort
		 *
		 * @param int $user_id wp user id.
		 * @param int $mdl_cohort_id moodle cohort id.
		 * @param int $enrolled_by id of the user who is enrolling user.
		 */
		public function delete_user_from_cohort( $user_id, $mdl_cohort_id, $enrolled_by ) {

			if ( isset( $user_id ) && ! empty( $user_id ) && isset( $user_id ) && ! empty( $mdl_cohort_id ) ) {
				$moodle_function = 'core_cohort_delete_cohort_members';
				if ( 0 !== $enrolled_by ) {
					$cohort_manager_id = $enrolled_by;
				} else {
					$cohort_manager_id = $user_id;
				}
				$moodle_user_id = get_user_meta( $user_id, 'moodle_user_id', true );
				$args           = array(
					'cohortid' => $mdl_cohort_id,
					'userid'   => $moodle_user_id,
				);

				// added Below code to avoid events triggered from Moodle side.
				$details = get_cohort_details( $mdl_cohort_id );

				update_user_meta( $user_id, 'eb_pending_enrollment', $details['courses'] );

				$responce = $this->con_helper->connect_moodle_with_args_helper( $moodle_function, array( 'members' => array( $args ) ) );

				if ( 1 === $responce['success'] ) {
					$this->delete_cohort_user_from_wordpress( $mdl_cohort_id, $user_id );
					$response = array(
						'status'  => true,
						'message' => __( 'Unenrolled successfully!', 'edwiser-bridge-pro' ),
					);
				} else {
					$response = array(
						'status'  => false,
						'message' => __( 'Unable to remove user from the cohort', 'edwiser-bridge-pro' ),
					);
				}

				if ( $response['status'] ) {
					$qty             = $this->reuse_qty_after_user_removed_from_group( $mdl_cohort_id );
					$response['qty'] = $qty;
					$args            = $this->prepare_email_args( $cohort_manager_id, $user_id, $mdl_cohort_id );

					do_action( 'eb_bp_remove_user_from_cohort', $args );
				}
				return $response;
			}
		}


		/**
		 * Reuse the quantity of the removed user i.e add the quantity again in the group if any user is removed.
		 *
		 * @param int $cohort_id Id of the cohort.
		 */
		private function reuse_qty_after_user_removed_from_group( $cohort_id ) {
			global $wpdb;
			$table_name         = $wpdb->prefix . 'bp_cohort_info';
			$result             = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine.
			$new_products_array = array();
			$update             = 0;
			$prevent            = 0;
			$products           = isset( $result->PRODUCTS ) ? $result->PRODUCTS : array(); // @codingStandardsIgnoreLine. Ignoring this as only capital letters are used in DB

			foreach ( maybe_unserialize( $result->PRODUCTS ) as $product_id => $qty ) { // @codingStandardsIgnoreLine.
				$product_options = get_post_meta( $product_id, 'product_options', 1 );
				if ( isset( $product_options['bp_reuse_quantity'] ) && 'on' === $product_options['bp_reuse_quantity'] ) {
					$update                            = 1;
					$new_products_array[ $product_id ] = ++$qty;
				} else {
					$prevent = 1;
				}
			}

			if ( $update && ! $prevent ) {
				$wpdb->update( // @codingStandardsIgnoreLine.
					$table_name,
					array( 'PRODUCTS' => maybe_serialize( $new_products_array ) ),
					array( 'MDL_COHORT_ID' => $cohort_id )
				);
				return 1;
			}
			return 0;
		}

		/**
		 * Function will preapre the email template args.
		 *
		 * @param int $cohort_manager_id cohort manager id.
		 * @param int $user_id user id.
		 * @param int $mdl_cohort_id moodle cohort id.
		 */
		private function prepare_email_args( $cohort_manager_id, $user_id, $mdl_cohort_id ) {
			$user = get_userdata( $user_id );
			return array(
				'user_email'        => $user->user_email,
				'username'          => $user->user_login,
				'last_name'         => $user->last_name,
				'first_name'        => $user->first_name,
				'mdl_cohort_id'     => $mdl_cohort_id,
				'cohort_manager_id' => $cohort_manager_id,
			);
		}

		/**
		 * Function provides the functionality yo delete the user from cohort.
		 *
		 * @param string $cohort_name cohort name.
		 * @param int    $user_id user id.
		 */
		private function delete_cohort_user_from_wordpress( $cohort_name, $user_id ) {
			global $wpdb;
			$wpdb->delete( // @codingStandardsIgnoreLine.
				"{$wpdb->prefix}moodle_enrollment",
				array(
					'mdl_cohort_id' => $cohort_name,
					'user_id'       => $user_id,
				)
			);
		}

		/**
		 * Function deletes the all the users from the cohort.
		 *
		 * @param int $cohort_id id of the cohort.
		 */
		public function delete_all_users_from_cohort( $cohort_id ) {
			global $wpdb;
			return $wpdb->delete( "{$wpdb->prefix}moodle_enrollment", array( 'mdl_cohort_id' => $cohort_id ) ); // @codingStandardsIgnoreLine.
		}



		/**
		 * Function to update the user role on moodle.
		 *
		 * @param int $role_id role id.
		 * @param int $user_id user id.
		 */
		public function update_moodle_user_profile( $role_id, $user_id ) {

			$user_id = get_user_meta( $user_id, 'moodle_user_id' );

			$moodle_function = 'core_role_assign_roles';
			$user_data       = array(
				'roleid'    => $role_id,
				'userid'    => $user_id[0],
				'contextid' => 1,
			);
			$response        = $this->con_helper->connect_moodle_with_args_helper( $moodle_function, array( 'assignments' => array( $user_data ) ) );
			return( 1 === $response['success'] ? true : false );
		}
	}
}

<?php
/**
 * Handles the Course purchase related functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

use app\wisdmlabs\edwiserBridge as edwiserBridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Create new user and update the course enrollment of user.
 */
if ( ! class_exists( 'Eb_Bp_User_Manager' ) ) {

	/**
	 * Class to mange the bulk purchase functionality.
	 */
	class Eb_Bp_User_Manager {


		/**
		 * Call to create user function for creating WordPress and moodle user.
		 */
		public function __construct() {
			add_action( 'wp_ajax_create_wordpress_user', array( $this, 'enroll_user_in_cohort' ) );
		}

		/**
		 * Function to update the pending course enrollment.
		 *
		 * @param int   $user_id User id.
		 * @param array $prd_course_arr Product course array.
		 */
		public function eb_update_pending_course_enrollment( $user_id, $prd_course_arr ) {
			$prod_ids        = maybe_unserialize( $prd_course_arr );
			$course_post_ids = $this->get_product_courses( array_keys( $prod_ids ) );

			// Adding thsi data to DB, so that the 2 way sync don't enroll the user again in the course.
			update_user_meta( $user_id, 'eb_pending_enrollment', array_keys( $course_post_ids ) );
		}

		/**
		 * Function to delete the pending course enrollment.
		 *
		 * @param int $user_id User id.
		 */
		public function eb_delete_pending_course_enrollment( $user_id ) {

			$prod_ids        = maybe_unserialize( $prd_course_arr );
			$course_post_ids = $this->get_product_courses( array_keys( $prod_ids ) );

			// Adding thsi data to DB, so that the 2 way sync don't enroll the user again in the course.
			delete_user_meta( $user_id, 'eb_pending_enrollment' );

		}

		/**
		 * Create WordPress and moodle user and enroll the user in cources.
		 */
		public function enroll_user_in_cohort() {
			if ( ! isset( $_POST['nonce_bp_enroll'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_bp_enroll'] ) ), 'wdm_ebbp_enroll_nonce' ) ) {
				wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
			}
			/**
			 * Validae the request data.
			 */
			$post_data = wp_unslash( $_POST );

			if ( $this->is_invalid_user_req_data( $post_data ) ) {
				wp_send_json_error( __( 'Invalid user data.', 'edwiser-bridge-pro' ) );
			}
			/**
			 * Declare the arrays for the messages.
			 */
			$enroll_error  = array();
			$enroll_suc    = array();
			$enrolled_user = array();
			$curr_user_id  = get_current_user_id();
			$details       = $this->get_cohort_details( sanitize_text_field( $post_data['mdl_cohort_id'] ) );
			$rem_prd_cnt   = $details['quantity'];
			$mdl_cohort_id = $details['mdl_cohort_id'];
			$fname_arr     = array_map( 'sanitize_text_field', $post_data['firstname'] );
			$lname_arr     = array_map( 'sanitize_text_field', $post_data['lastname'] );
			$email_arr     = array_map( 'sanitize_text_field', $post_data['email'] );
			$user_role     = 'Student';
			$wp_user_role  = eb_bp_get_wp_user_reg_role();
			$proces_users  = 0;
			$is_csv_users  = 0;

			if ( isset( $post_data['total'] ) && sanitize_text_field( $post_data['total'] ) ) {
				$is_csv_users = 1;
			}

			/**
			 * Check requested sets are available or not
			 */
			if ( count( $email_arr ) > $details['quantity'] ) {
				wp_send_json_error( __( 'Available sets quantity is less than requested quantity.', 'edwiser-bridge-pro' ) );
			}

			$users       = array();
			$cnt_records = count( $email_arr );
			for ( $cnt = 0; $cnt < $cnt_records; $cnt++ ) {
				$status = false;
				if ( $this->check_is_empty( $fname_arr, $cnt ) && $this->check_is_empty( $lname_arr, $cnt ) && $this->check_is_empty( $email_arr, $cnt ) ) {
					$proces_users ++;

					$first_name = $fname_arr[ $cnt ];
					$last_name  = $lname_arr[ $cnt ];
					$email      = $email_arr[ $cnt ];
					$user       = get_user_by( 'email', $email );
					$password   = wp_generate_password();
					$user_id    = 0;

					// Creating WP user.
					if ( email_exists( $email ) ) {
						$user      = get_user_by( 'email', $email );
						$user_id   = $user->ID;
						$user_name = $user->user_login;
						// check if the user is already enrolled in the group.
						// Remaining.
						if ( $this->is_user_already_enrolled( $details['courses'], $user->ID ) ) {
							$enrolled_user[] = $user->user_email;
							continue;
						}
					} else {
						$user_name = sanitize_user( current( explode( '@', $email ) ), true );

						// Ensure username is unique.
						$append     = 1;
						$o_username = $user_name;

						while ( username_exists( $user_name ) ) {
							$user_name = $o_username . $append;
							++$append;
						}

						$wp_user_data = apply_filters(
							'eb_bp_cohort_new_user_data',
							array(
								'user_login' => $user_name,
								'first_name' => $first_name,
								'last_name'  => $last_name,
								'user_pass'  => $password,
								'user_email' => $email,
								'role'       => $wp_user_role,
							)
						);

						$user_id = wp_insert_user( $wp_user_data );

						// Sending email.
						$args = array(
							'user_email' => $email,
							'username'   => $user_name,
							'first_name' => $first_name,
							'last_name'  => $last_name,
							'password'   => $password,
						);
						// do_action( 'eb_created_user', $args );
						do_action( 'eb_created_user_bulk_enroll', $args );

						if ( is_wp_error( $user_id ) ) {
							continue;
						}
					}

					// create a array for API request.
					array_push(
						$users,
						array(
							'firstname' => $first_name,
							'lastname'  => $last_name,
							'password'  => $password,
							'username'  => strtolower( $user_name ),
							'email'     => $email,
						)
					);
				}

				// Update pending Course enrollemngt entries so that 2 way sync won't get processed.
				$this->eb_update_pending_course_enrollment( $user_id, $details['products'] );
			}

			if ( $users && ! empty( $users ) ) {
				$conn_helper     = Eb_Bp_Manage_Cohort::get_connection_helper();
				$moodle_function = 'auth_edwiserbridge_manage_user_cohort_enrollment';
				$response        = $conn_helper->connect_moodle_with_args_helper(
					$moodle_function,
					array(
						'cohort_id' => $mdl_cohort_id,
						'users'     => $users,
					)
				);

				// check common error of all users first like cohort exist or not.
				if ( isset( $response['response_data']->users ) ) {
					foreach ( $response['response_data']->users as $moodle_user ) {
						// get the response['created'] data and check if the user created in Moodle if yes then update the moodle user id.
						if ( ( isset( $moodle_user->creation_error ) && $moodle_user->creation_error ) || ( isset( $moodle_user->enrolled ) && ! $moodle_user->enrolled ) ) {
							// create error array.
							$enroll_error[] = $moodle_user->email;
						} else {
							$user = get_user_by( 'email', $moodle_user->email );

							if ( ! get_user_meta( $user->ID, 'moodle_user_id', 1 ) ) {
								update_user_meta( $user->ID, 'moodle_user_id', $moodle_user->user_id );
								// Send email to the newly created users.
								$args = array(
									'user_email' => $moodle_user->email,
									'username'   => $moodle_user->username,
									'first_name' => $user->first_name,
									'last_name'  => $user->last_name,
									'password'   => $moodle_user->password,
								);
								// create a new action hook with user details as argument.
								do_action( 'eb_linked_to_existing_wordpress_user', $args );
							}

							// update data in WP.
							$status = $this->enroll_user( $mdl_cohort_id, $user->ID, $curr_user_id, $user_role, $details['products'] );

							// Update WordPress user role depending upon the email id.
							// That is if the user himself enrolling into course then assign non editing teacher.
							// If normal user then use the role in the settings.
							$this->update_wordpres_user_role( $user->ID, $user->user_email );

							// Send email.
							$email_args = array(
								'user_email'        => $user->user_email,
								'username'          => $user->user_login,
								'last_name'         => $user->last_name,
								'first_name'        => $user->first_name,
								'mdl_cohort_id'     => $mdl_cohort_id,
								'cohort_manager_id' => $curr_user_id,
							);

							do_action( 'eb_bp_new_user_to_cohort', $email_args );

							$rem_prd_cnt--;
							$this->update_bp_cohort_info_table_on_enrollment(
								$mdl_cohort_id,
								$rem_prd_cnt
							);

							// Update success users array.
							$enroll_suc[] = $moodle_user->email;
						}
					}
				}
			}

			$current_user     = wp_get_current_user();
			$details['name']  = str_replace( $current_user->use_login . '_', '', $details['name'] );
			$details['name'] .= ' (' . $rem_prd_cnt . ') ';

			if ( $is_csv_users && isset( $post_data['total'] ) && isset( $post_data['processed_users'] ) ) {
				if ( ( sanitize_text_field( $post_data['processed_users'] ) + $proces_users ) == sanitize_text_field( $post_data['total'] ) ) { // @codingStandardsIgnoreLine
					$this->set_csv_users_response_data( $current_user->ID, $mdl_cohort_id, $enroll_error, $enrolled_user, $enroll_suc );
					$csv_response = $this->get_csv_users_response_data( $current_user->ID, $mdl_cohort_id, $enroll_error, $enrolled_user, $enroll_suc );

					$enroll_error  = $csv_response['enrollment_err'];
					$enrolled_user = $csv_response['already_enrolled_user'];
					$enroll_suc    = $csv_response['enrollment_suc'];
				} else {
					$this->set_csv_users_response_data( $current_user->ID, $mdl_cohort_id, $enroll_error, $enrolled_user, $enroll_suc );
				}
			}

			/**
			 * Prepare the responce messages and send responce.
			 */
			wp_send_json_success(
				array(
					'cohort'          => html_entity_decode( $details['name'] ),
					'msg'             => $this->prepare_res_msg( $enroll_error, $enrolled_user, $enroll_suc, $is_csv_users ),
					'processed_users' => $proces_users,
				)
			);
		}

		/**
		 * Function to preapae the message.
		 *
		 * @param string $enroll_error Enrollment error.
		 * @param array  $enrolled_user Array of the enrolled users.
		 * @param string $enroll_suc Enroll success message.
		 * @param bool   $is_csv_users Boolean is csv input or not.
		 */
		private function prepare_res_msg( $enroll_error, $enrolled_user, $enroll_suc, $is_csv_users ) {
			ob_start();

			if ( ! empty( $enroll_suc ) && is_array( $enroll_suc ) && count( $enroll_suc ) > 0 ) {
				$msg = __( 'Users with following email ids have been enrolled successfully', 'edwiser-bridge-pro' );

				if ( $is_csv_users ) {
					?>
					<div>
						<div class="ebbp_csv_enroll_error_msg wdm_success_message">
							<i class="dashicons dashicons-dismiss wdm_success_msg_dismiss"></i>
							<?php esc_attr_e( 'Check enrolled users list ', 'edwiser-bridge-pro' ); ?> <span class="ebbp_csv_enrollment_resp_pop_up" > <?php esc_attr_e( ' here ', 'edwiser-bridge-pro' ); ?> </span> . 
						</div>

						<div class="ebbp_csv_enrollment_resp_msg_wrap">
							<div class="ebbp_csv_enrollment_resp_msg" title="<?php esc_attr_e( 'Successfully Enrolled Users List', 'edwiser-bridge-pro' ); ?>">

					<?php
				}
				?>
				<div class="wdm_success_message wdm_user_list">
					<?php
					if ( ! $is_csv_users ) {
						?>
							<i class="dashicons dashicons-dismiss wdm_success_msg_dismiss"></i>
						<?php
						$msg = __( 'User with following email id have been enrolled successfully', 'edwiser-bridge-pro' );

					}
					?>
					<span class="wdm_enroll_warning_message_lable">
						<?php echo esc_attr( $msg ); ?>
					</span>
					<?php $this->create_email_list( $enroll_suc ); ?>
				</div>
				<?php

				if ( $is_csv_users ) {
					?>
							</div>
						</div>
					</div>
					<?php
				}
			}
			if ( isset( $enrolled_user ) && count( $enrolled_user ) > 0 ) {

				$msg = __( 'User with the following email ids already enrolled in the courses', 'edwiser-bridge-pro' );

				if ( $is_csv_users ) {
					?>
					<div>
						<div class="ebbp_csv_enroll_error_msg wdm_enroll_warning_message">
							<i class="dashicons dashicons-dismiss wdm_enroll_warning_msg_dismiss"></i>
							<?php esc_attr_e( ' Check already enrolled users  ', 'edwiser-bridge-pro' ); ?> <span class="ebbp_csv_enrollment_resp_pop_up" > <?php esc_attr_e( ' here ', 'edwiser-bridge-pro' ); ?> </span> . 
						</div>
						<div class="ebbp_csv_enrollment_resp_msg_wrap">
							<div class="ebbp_csv_enrollment_resp_msg" title="<?php esc_attr_e( 'Already users List', 'edwiser-bridge-pro' ); ?>">

					<?php
				}
				?>
				<div class="wdm_enroll_warning_message wdm_user_list">
					<?php
					if ( ! $is_csv_users ) {
						?>
						<i class="dashicons dashicons-dismiss wdm_enroll_warning_msg_dismiss"></i>
						<?php

						$msg = __( 'User with the following email id already enrolled in the courses', 'edwiser-bridge-pro' );

					}
					?>
					<span class="wdm_enroll_warning_message_lable">
						<?php echo esc_attr( $msg ); ?>
					</span>
					<?php $this->create_email_list( $enrolled_user ); ?>
				</div>
				<?php
				if ( $is_csv_users ) {
					?>
							</div>
						</div>
					</div>

					<?php
				}
			}

			if ( ! empty( $enroll_error ) && is_array( $enroll_error ) && count( $enroll_error ) > 0 ) {

				$msg = __( 'Some Error occured while enrolling users with following email ids:', 'edwiser-bridge-pro' );

				if ( $is_csv_users ) {
					?>
					<div>
						<div class="ebbp_csv_enroll_error_msg wdm_error_message">
							<i class="dashicons dashicons-dismiss wdm_error_msg_dismiss"></i>
							<?php esc_attr_e( 'Unable to enroll users in group, Check users list  ', 'edwiser-bridge-pro' ); ?> <span class="ebbp_csv_enrollment_resp_pop_up" > <?php esc_attr_e( ' here ', 'edwiser-bridge-pro' ); ?> </span> . 
						</div>

						<div class="ebbp_csv_enrollment_resp_msg_wrap">
							<div class="ebbp_csv_enrollment_resp_msg" title="<?php esc_attr_e( 'Failed Enrollment Users', 'edwiser-bridge-pro' ); ?>">

					<?php
				}
				?>
				<div class="wdm_error_message wdm_user_list">
					<?php
					if ( ! $is_csv_users ) {
						?>
						<i class="dashicons dashicons-dismiss wdm_error_msg_dismiss"></i>
						<?php

						$msg = __( 'Some Error occured while enrolling users with following email id:', 'edwiser-bridge-pro' );
					}
					?>
					<span class="wdm_enroll_warning_message_lable">
						<?php echo esc_attr( $msg ); ?>
					</span>
					<?php $this->create_email_list( $enroll_error ); ?>
				</div>
				<?php
				if ( $is_csv_users ) {
					?>
						</div>
						</div>
					</div>
					<?php
				}
			}

			return ob_get_clean();
		}

		/**
		 * Function to set the csv user creation respince
		 *
		 * @param int    $user_id User id.
		 * @param string $cohort_id Cohort id.
		 * @param array  $enroll_error Array of enrollment errors.
		 * @param array  $enrolled_user Array of enrollment users.
		 * @param array  $enroll_suc Array of enrollment success.
		 */
		public function set_csv_users_response_data( $user_id, $cohort_id, $enroll_error, $enrolled_user, $enroll_suc ) {

			$existing_data = get_option( $user_id . '_' . $cohort_id );

			if ( isset( $existing_data ) && ! empty( $existing_data ) ) {
				$enroll_error  = array_merge( $existing_data['enrollment_err'], $enroll_error );
				$enrolled_user = array_merge( $existing_data['already_enrolled_user'], $enrolled_user );
				$enroll_suc    = array_merge( $existing_data['enrollment_suc'], $enroll_suc );
			}

			update_option(
				$user_id . '_' . $cohort_id,
				array(
					'enrollment_err'        => $enroll_error,
					'already_enrolled_user' => $enrolled_user,
					'enrollment_suc'        => $enroll_suc,
				)
			);
		}

		/**
		 * Function to get the users responce data.
		 *
		 * @param int    $user_id User id.
		 * @param string $cohort_id Cohort id.
		 * @param array  $enroll_error Array of enrollment errors.
		 * @param array  $enrolled_user Array of enrollment users.
		 * @param array  $enroll_suc Array of enrollment success.
		 */
		public function get_csv_users_response_data( $user_id, $cohort_id, $enroll_error, $enrolled_user, $enroll_suc ) {
			$data = get_option( $user_id . '_' . $cohort_id );
			delete_option( $user_id . '_' . $cohort_id );
			return $data;
		}

		/**
		 * Checkes whether the all the data submited is correct to create the user.
		 *
		 * @param array $data Array of post data.
		 */
		private function is_invalid_user_req_data( $data ) {
			$key_arr = array( 'mdl_cohort_id', 'firstname', 'lastname', 'email' );
			foreach ( $key_arr as $key ) {
				if ( ! $this->check_is_empty( $data, $key ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Check is the array value is present for the key.
		 *
		 * @param array  $data array of the data.
		 * @param string $key Key to check.
		 * @return true If the user array contains the value for the key ,false otherwise.
		 */
		private function check_is_empty( $data, $key ) {
			if ( isset( $data[ $key ] ) && ! empty( $data[ $key ] ) ) {
				return $data[ $key ];
			} else {
				return false;
			}
		}

		/**
		 * Currently not using this function we will remopve it.
		 * Process the user account creation request,
		 * Checks is the user account exist or not and prepeares the user object
		 * and creates the user on WordPress.
		 *
		 * @param string $email user email address.
		 * @param string $firstname user first name.
		 * @param string $lastname use last name.
		 * @return Object WP_User returns the WP_user object for the given email address.
		 */
		private function process_user_acc_creation( $email, $firstname, $lastname ) {
			$user    = null;
			$user_id = edwiserBridge\edwiser_bridge_instance()->userManager()->createWordpressUser( $email, $firstname, $lastname );

			if ( ! empty( $user_id ) && ! is_wp_error( $user_id ) ) {
				$user = get_userdata( $user_id );

				$cohort_manager = new Eb_Bp_Cohort_Manage_User();
				$cohort_manager->update_moodle_user_profile( 5, $user->ID );
			} elseif ( is_wp_error( $user_id ) && strpos( $user_id->get_error_data(), 'eb_email_exists' ) !== false ) {
				$user     = get_user_by( 'email', $email );
				$language = 'en';
				if ( isset( $general_settings['eb_language_code'] ) ) {
					$language = $general_settings['eb_language_code'];
				}

				$moodle_user = edwiserBridge\edwiser_bridge_instance()->userManager()->getMoodleUser( $email );

				if ( isset( $moodle_user['user_exists'] ) && 1 === $moodle_user['user_exists'] && is_object( $moodle_user['user_data'] ) ) {
					update_user_meta( $user->ID, 'moodle_user_id', $moodle_user['user_data']->id );
					// sync courses of an individual user when an existing moodle user is linked with a WordPress account.
					edwiserBridge\edwiser_bridge_instance()->userManager()->userCourseSynchronizationHandler( array( 'eb_synchronize_user_courses' => 1 ), $user->ID );
				} else {
					$password    = wp_generate_password();
					$user_data   = array(
						'username'  => $user->user_login,
						'password'  => $password,
						'firstname' => $firstname,
						'lastname'  => $lastname,
						'email'     => $email,
						'auth'      => 'manual',
						'lang'      => $language,
					);
					$moodle_user = edwiserBridge\edwiser_bridge_instance()->userManager()->createMoodleUser( $user_data, 0 );
					if ( isset( $moodle_user['user_created'] ) && 1 === $moodle_user['user_created'] && is_object( $moodle_user['user_data'] ) ) {
						update_user_meta( $user->ID, 'moodle_user_id', $moodle_user['user_data']->id );
						$args = array(
							'user_email' => $email,
							'username'   => $user->user_login,
							'first_name' => $firstname,
							'last_name'  => $lastname,
							'password'   => $password,
						);
						// create a new action hook with user details as argument.
						do_action( 'eb_linked_to_existing_wordpress_to_new_user', $args );
					}
				}
			}
			return $user;
		}

		/**
		 * Check is the user is enrolled for the all the users.
		 *
		 * @param array $eb_course_ids  array of the cohort corse ids.
		 * @param int   $user_id the user id for to check is the user enrolled for the courses.
		 * @return boolean true if the user is already enrolled to the all the courses, otherwise false.
		 */
		private function is_user_already_enrolled( $eb_course_ids, $user_id ) {
			global $wpdb;
			$mdl_enroll = $wpdb->prefix . 'moodle_enrollment';
			$result     = $wpdb->get_col( $wpdb->prepare( "select course_id from {$mdl_enroll} where user_id=%d", $user_id ) ); // @codingStandardsIgnoreLine
			if ( array_intersect( $eb_course_ids, $result ) === $eb_course_ids ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Currently not using this function ned to remove it
		 * Function to check if the user is enrolled successfuly or not.
		 *
		 * @param  string $email Email address.
		 * @param  array  $enrolled_user Enrolled users array.
		 * @param  array  $enrollment_err Enrollment errors.
		 */
		public function check_if_enrolled_successfully( $email, $enrolled_user, $enrollment_err ) {
			if ( ! in_array( $email, $enrolled_user, true ) && ! in_array( $email, $enrollment_err, true ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Currently not using this function Need to remove it.
		 * function to get array products having commomn courses used while updating moodleenrollment table.
		 *
		 * @param array $products Products array.
		 * @param int   $course_id searched in tghe product array.
		 */
		public function get_product_array( $products, $course_id ) {
			global $wpdb;
			$products = maybe_unserialize( $products );
			// Array for the products with associated courses.
			$prod_arr = array();
			$tbl_name = $wpdb->prefix . 'eb_moodle_course_products';
			foreach ( $products as $key => $value ) {
				unset( $value );
				$results = $wpdb->get_col( $wpdb->prepare( "SELECT moodle_post_id FROM {$tbl_name} WHERE product_id = %d", $key ) ); // @codingStandardsIgnoreLine
				if ( in_array( $course_id, $results, true ) ) {
					array_push( $prod_arr, $key );
				}
			}
			return $prod_arr;
		}

		/**
		 * Updating cohort product quantity on enrollment.
		 *
		 * @param int $cohort_id  Cohort id.
		 * @param int $rem_qty Quantity to deduct.
		 */
		public function update_bp_cohort_info_table_on_enrollment( $cohort_id, $rem_qty ) {
			global $wpdb;
			$tbl_name    = $wpdb->prefix . 'bp_cohort_info';
			$results     = $wpdb->get_var( $wpdb->prepare( "SELECT PRODUCTS FROM {$tbl_name} WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine
			$cohort_prod = maybe_unserialize( $results );
			foreach ( $cohort_prod as $prod_id => $qty ) {
				$qty                     = $qty;
				$cohort_prod[ $prod_id ] = $rem_qty;
			}
			$wpdb->update( // @codingStandardsIgnoreLine
				$tbl_name,
				array( 'PRODUCTS' => serialize( $cohort_prod ) ), // @codingStandardsIgnoreLine
				array( 'MDL_COHORT_ID' => $cohort_id )
			);
		}


		/**
		 * Function to get cohort details.
		 *
		 * @param int $mdl_cohort_id moodle cohort id.
		 */
		public function get_cohort_details( $mdl_cohort_id ) {
			global $wpdb;
			$tbl_name = $wpdb->prefix . 'bp_cohort_info';
			$results  = $wpdb->get_row( $wpdb->prepare( "SELECT MDL_COHORT_ID AS mdl_cohort_id, PRODUCTS AS products, COURSES AS courses, COHORT_NAME AS cohort_name, NAME AS name FROM {$tbl_name} WHERE mdl_cohort_id = %d", $mdl_cohort_id ), ARRAY_A ); // @codingStandardsIgnoreLine
			$prod_arr = $results['products'];
			$products = maybe_unserialize( $results['products'] );
			$courses  = maybe_unserialize( $results['courses'] );
			$products = is_array( $products ) ? array_values( $products ) : array();
			$min_qty  = min( $products );
			return array(
				'quantity'      => $min_qty,
				'name'          => '' !== $results['name'] ? $results['name'] : $results['cohort_name'],
				'courses'       => $courses,
				'products'      => $prod_arr,
				'mdl_cohort_id' => $results['mdl_cohort_id'],
			);
		}

		/**
		 * Creates the orderd list of users.
		 *
		 * @param type $email_array array of the email addreses.
		 * @version 1.1.0
		 */
		private function create_email_list( $email_array ) {
			?>
			<ol>
				<?php
				foreach ( $email_array as $email ) {
					?>
					<li>
						<?php echo esc_html( $email ); ?>
					</li>
					<?php
				}
				?>
			</ol>
			<?php
		}

		/**
		 * Function to update WordPress user role.
		 *
		 * @param int    $user_id User id.
		 * @param string $email Email addrress.
		 */
		public function update_wordpres_user_role( $user_id, $email ) {

			$cuser_id  = get_current_user_id();
			$user_info = get_userdata( $cuser_id );
			$user      = new \WP_User( $user_id );
			if ( $user_info->user_email === $email ) {
				// Get settings option.
				// check if user role assignment is enabled or not, if enabled then assign role.
				$genral_settings = get_option( 'eb_general', array() );
				if ( isset( $genral_settings['mucp_group_manager_role'] ) && 'yes' == $genral_settings['mucp_group_manager_role'] ) { // @codingStandardsIgnoreLine
					$user->add_role( 'non_editing_teacher' );
				}
			}
		}

		/**
		 * Provides the functionality to fetch the moodle course post ids with the product ids
		 *
		 * @param Array $product Array of the product ids.
		 * @since 2.0.0
		 * @return Array returns the array of the product ids with associated courses.
		 */
		private function get_product_courses( $product ) {
			global $wpdb;
			$tbl_name      = $wpdb->prefix . 'eb_moodle_course_products';
			$eb_course_ids = array();
			$stmt          = "SELECT DISTINCT `product_id`,`moodle_post_id` FROM `{$tbl_name}` WHERE `product_id` in ('" . implode( "','", $product ) . "');";
			$result        = $wpdb->get_results( $stmt, ARRAY_A ); // @codingStandardsIgnoreLine
			foreach ( $result as $rec ) {
				$eb_course_ids[ $rec['moodle_post_id'] ] = $rec['product_id'];
			}
			return $eb_course_ids;
		}

		/**
		 * Functionality to update the enrollment records on enroll in to the cohort
		 *
		 * @param int    $mdl_cohort_id mooe cohort id.
		 * @param int    $user_id User id to enroll into the course.
		 * @param int    $curr_user_id current user id.
		 * @param string $user_role Enrolled user role.
		 * @param Array  $prod_ids Array of the product cohort ids.
		 * @since 2.0.0
		 * @return boolean returns true on sucessfull DB update.
		 */
		protected function enroll_user( $mdl_cohort_id, $user_id, $curr_user_id, $user_role, $prod_ids ) {
			global $wpdb;
			$status          = false;
			$prod_ids        = maybe_unserialize( $prod_ids );
			$course_post_ids = $this->get_product_courses( array_keys( $prod_ids ) );
			foreach ( $course_post_ids as $course_id => $prod_id ) {
					$status = $wpdb->insert( // @codingStandardsIgnoreLine
						$wpdb->prefix . 'moodle_enrollment',
						array(
							'user_id'       => $user_id,
							'course_id'     => $course_id,
							'role_id'       => '5',
							'time'          => date( 'Y-m-d H:i:s' ), // @codingStandardsIgnoreLine
							'enrolled_by'   => $curr_user_id,
							'product_id'    => $prod_id,
							'mdl_cohort_id' => $mdl_cohort_id,
							'role'          => $user_role,
						),
						array(
							'%d',
							'%d',
							'%d',
							'%s',
							'%s',
							'%d',
							'%d',
							'%s',
						)
					);
			}
			return $status;
		}
	}
}

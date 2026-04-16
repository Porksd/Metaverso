<?php
/**
 * The provides the cohort course managment functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\pb;

use app\wisdmlabs\edwiserBridge as edwiserBridge;

use app\wisdmlabs\edwiserBridgePro\includes as includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * The public-facing functionality of enroll user.
 *
 * @author     WisdmLabs, India <support@wisdmlabs.com>
 */
class Edwiser_Multiple_Users_Course_Enroll_Users {

	/**
	 * Functionality to change the coohort name from enroll-student page.
	 */
	public function eb_edit_cohort_name_from_cohort_id() {
		global $wpdb;
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Unable to update group name, due to security reasons..', 'edwiser-bridge-pro' ) );
		}
		$post_data = wp_unslash( $_POST );
		if ( isset( $post_data['mdl_cohort_id'] ) && ! empty( $post_data['mdl_cohort_id'] ) && isset( $post_data['mdl_cohort_name'] ) && ! empty( $post_data['mdl_cohort_name'] ) ) {
			$cohort_id = $post_data['mdl_cohort_id'];
			$name      = $post_data['mdl_cohort_name'];

			$tblcohort_info = $wpdb->prefix . 'bp_cohort_info';

			$wpdb->update( // @codingStandardsIgnoreLine
				$tblcohort_info,
				array(
					'NAME' => $name,
				),
				array(
					'MDL_COHORT_ID' => $cohort_id,
				)
			);
			wp_send_json_success( __( 'Group name successfully updated.', 'edwiser-bridge-pro' ) );
		} else {
			wp_send_json_error( __( 'Unable to update group name.', 'edwiser-bridge-pro' ) );
		}
	}

	/**
	 * Function returns the enrolled users list.
	 */
	public function wdm_enrolled_users() {

		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}

		$post_data = wp_unslash( $_POST );
		if ( isset( $post_data['mdl_cohort_id'] ) && ! empty( $post_data['mdl_cohort_id'] ) ) {
			global $wpdb;
			$mdl_cohort_id = $post_data['mdl_cohort_id'];
			$cuser_id      = get_current_user_id();
			$avail_seats   = 0;
			$tbl_name      = $wpdb->prefix . 'bp_cohort_info';
			$result        = $wpdb->get_var( $wpdb->prepare( "SELECT PRODUCTS FROM {$tbl_name} WHERE mdl_cohort_id = %d", $mdl_cohort_id ) ); // @codingStandardsIgnoreLine
			$products      = maybe_unserialize( $result );
			$avail_seats   = min( $products );
			if ( null === $avail_seats ) {
				$avail_seats = 0;
			}
			$tbl_name = $wpdb->prefix . 'moodle_enrollment';

			/**
			 * Fixed #32173 - Backward compatibility v1.0.0
			 *
			 * @author Pandurang
			 * @since 1.0.1
			 */
			$enrolled_users = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT `user_id` FROM `{$tbl_name}` WHERE `mdl_cohort_id` = '%d'", $mdl_cohort_id ) ); // @codingStandardsIgnoreLine
			$tbl_col        = apply_filters(
				'eb_bp_enroll_user_tbl_header',
				array(
					'cb'       => '<input type="checkbox" id="ebbp_enroll_student_cb_head">',
					'name'     => __( 'Name', 'edwiser-bridge-pro' ),
					'email'    => __( 'Email Id', 'edwiser-bridge-pro' ),
					'progress' => __( 'Progress', 'edwiser-bridge-pro' ),
					'actions'  => __( 'Actions', 'edwiser-bridge-pro' ),
				)
			);

			ob_start();
			?>
			<table id='enroll-user-table'>
				<thead>
					<tr>
					<?php
					foreach ( $tbl_col as $tbl_header ) {
						echo '<th>' . wp_kses( $tbl_header, includes\bulkPurchase\eb_bp_get_allowed_html_tags() ) . '</th>';
					}
					?>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( ! empty( $enrolled_users ) ) {
						foreach ( $enrolled_users as $user ) {
							$user_data = get_userdata( $user->user_id );
							$user_role = $this->get_user_roles( $user_data );
							$tbl_data  = array(
								'cb'       => '<input data-id="' . $user->user_id . '" type="checkbox" class="ebbp_ebroll-students_cb">',
								'name'     => $user_data->first_name . ' ' . $user_data->last_name,
								'email'    => $user_data->user_email,
								'progress' => '<a> <span data-cohortid = ' . $mdl_cohort_id . ' data-userid = ' . $user->user_id . ' class="ebbp_course_progress">' . __( 'View Progress', 'edwiser-bridge-pro' ) . '</span> </a>',
								'actions'  => '<i title="' . __( 'Edit user.', 'edwiser-bridge-pro' ) . '" id="' . $user->user_id . '" class="dashicons dashicons-edit edit-enrolled-user"></i>
                                    <i title="' . __( 'Unenroll user from Group.', 'edwiser-bridge-pro' ) . '" id="' . $user->user_id . '" class="dashicons dashicons-trash bp-delete-enrolled-user"></i>',
							);
							$tbl_data  = apply_filters( 'eb_bp_enroll_user_tbl_data', $tbl_data, $mdl_cohort_id, $user->user_id );
							?>
							<tr class ="<?php echo esc_html( $user->user_id ); ?>">
							<?php
							// Here foreach is on table columns because table header should get displayed according to the array values.
							foreach ( array_keys( $tbl_col ) as $key ) {
								echo '<td>' . wp_kses( $tbl_data[ $key ], includes\bulkPurchase\eb_bp_get_allowed_html_tags() ) . '</td>';
							}
							?>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
			<?php
				$form        = ob_get_clean();
				$ass_courses = $this->wdm_enroll_user_course( $mdl_cohort_id );
				$responce    = array(
					'seats'          => $avail_seats,
					'enrolled_users' => count( $enrolled_users ),
					'html'           => $form,
					'asso_courses'   => $ass_courses,
				);
				wp_send_json_success( $responce );
		} else {
			wp_send_json_error( __( 'Invalid request data' ) );
		}
	}

	/**
	 * Function set_enroll_students_removed_users_data.
	 *
	 * @param int    $cohort_id  Cohort id.
	 * @param string $enroll_err Enrollment errors.
	 * @param string $enroll_suc Enrollment sucess.
	 * @param array  $enrolled_user array of the enrolled users.
	 */
	public function set_enroll_students_removed_users_data( $cohort_id, $enroll_err, $enroll_suc, $enrolled_user ) {

		$existing_data = get_option( 'removed_users_' . $cohort_id );

		if ( isset( $existing_data ) && ! empty( $existing_data ) ) {
			$enroll_err = $existing_data['enrollment_err'] . $enroll_err;
			$enroll_suc = $existing_data['enrollment_suc'] . $enroll_suc;

			$existing_data['enrolled_user'] = isset( $existing_data['enrolled_user'] ) && ! empty( $existing_data['enrolled_user'] ) ? $existing_data['enrolled_user'] : array();

			$enrolled_user = array_merge( $existing_data['enrolled_user'], $enrolled_user );
		}

		update_option(
			'removed_users_' . $cohort_id,
			array(
				'enrollment_err' => $enroll_err,
				'enrollment_suc' => $enroll_suc,
				'enrolled_user'  => $enrolled_user,
			)
		);
	}

	/**
	 * Function to remove the user data of cohort.
	 *
	 * @param int $cohort_id Cohort id.
	 */
	public function get_enroll_students_removed_users_data( $cohort_id ) {
		$data = get_option( 'removed_users_' . $cohort_id );
		delete_option( 'removed_users_' . $cohort_id );
		return $data;
	}

	/**
	 * Function to delete the multiple user enrollment from group.
	 */
	public function bp_delete_multiple_enrolled_user() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Unable to update group name, due to security reasons..', 'edwiser-bridge-pro' ) );
		}

		$post_data = wp_unslash( $_POST );
		if ( isset( $post_data['userId'] ) && ! empty( $post_data['userId'] ) && isset( $post_data['cohortId'] ) && ! empty( $post_data['cohortId'] ) ) {
			$users_arr     = $post_data['userId'];
			$cohort_id     = $post_data['cohortId'];
			$success_arr   = '';
			$error_arr     = '';
			$msg           = '';
			$enrolled_user = array();
			$proc_users    = count( $users_arr );
			$qty           = 0;
			foreach ( $users_arr as $single_user_id ) {
				$result = $this->bp_remove_user_from_cohort( $single_user_id, $cohort_id );

				$user_info = get_userdata( $single_user_id );

				if ( $result['status'] ) {
					$qty += $result['qty'];
					array_push( $enrolled_user, $single_user_id );
					$success_arr .= '<li>' . $user_info->user_email . '</li>';
				} else {
					$error_arr .= '<li>' . $user_info->user_email . '</li>';
				}
			}

			if ( ( $post_data['processed_users'] + $proc_users ) == $post_data['total'] ) { // @codingStandardsIgnoreLine
				$this->set_enroll_students_removed_users_data( $cohort_id, $error_arr, $success_arr, $enrolled_user );
				$saved_response = $this->get_enroll_students_removed_users_data( $cohort_id );
				$error_arr      = $saved_response['enrollment_err'];
				$success_arr    = $saved_response['enrollment_suc'];
				$enrolled_user  = $saved_response['enrolled_user'];

				if ( ! empty( $success_arr ) ) {
					$msg .= '<div>
                                <div class="ebbp_bulk_deleted_users_parent wdm_success_message">
                                    <i class="dashicons dashicons-dismiss wdm_success_msg_dismiss"></i>
                                ' . __( 'Check successfully removed users from group ', 'edwiser-bridge-pro' ) . '<span class="ebbp_bulk_deleted_users_pop_up" > ' . __( ' here ', 'edwiser-bridge-pro' ) . ' </span> . 
                                </div>
                                <div class="ebbp_bulk_deleted_users_resp_msg_wrap">
                                    <div class="ebbp_bulk_deleted_users_resp_msg" title="' . __( 'Deleted Users', 'edwiser-bridge-pro' ) . '">';
					$msg .= '<div class="wdm_success_message">
                                    <span class="wdm_enroll_warning_message_lable">
                                    ' . __( 'User with email id  ', 'edwiser-bridge-pro' ) . '
                                        <ul>
                                            ' . $success_arr . '
                                        </ul>
                                        ' . __( ' have been unenrolled successfully', 'edwiser-bridge-pro' ) . '
                                    </span>
                            </div>';
					$msg .= '</div></div></div>';
				}

				if ( ! empty( $error_arr ) ) {
					$msg .= '<div>
                                <div class="ebbp_bulk_deleted_users_parent wdm_error_message">
                                    <i class="dashicons dashicons-dismiss wdm_success_msg_dismiss"></i>
                                ' . __( 'Unable to remove some users from group, Check complete list ', 'edwiser-bridge-pro' ) . '<span class="ebbp_bulk_deleted_users_pop_up" > ' . __( ' here ', 'edwiser-bridge-pro' ) . ' </span> . 
                                </div>
                                <div class="ebbp_bulk_deleted_users_resp_msg">
                                    <div class="ebbp_bulk_deleted_users_resp_msg" title="' . __( 'Deleted Users', 'edwiser-bridge-pro' ) . '">';
					$msg .= '<div class="wdm_error_message">
                                    <span class="wdm_enroll_warning_message_lable">
                                        ' . __( 'Sorry, unable to delete user with email id ', 'edwiser-bridge-pro' ) . '
                                        <ul>
                                            ' . $error_arr . '
                                        </ul>
                                    </span>
                            </div>';
					$msg .= '</div></div></div>';
				}
			} else {

				$this->set_enroll_students_removed_users_data( $cohort_id, $error_arr, $success_arr, $enrolled_user );
			}

			wp_send_json_success(
				array(
					'status'          => 1,
					'qty'             => $qty,
					'msg'             => $msg,
					'enrolled_user'   => $enrolled_user,
					'processed_users' => $proc_users,
				)
			);

		}

	}

	/**
	 * Function to remove user from cohort.
	 *
	 * @param int $user_id User id.
	 * @param int $cohort_id Cohort id.
	 */
	public function bp_remove_user_from_cohort( $user_id, $cohort_id ) {
		$current_user_id = get_current_user_id();
		$cohort_manager  = new includes\bulkPurchase\Eb_Bp_Cohort_Manage_User();
		$result          = $cohort_manager->delete_user_from_cohort( $user_id, $cohort_id, $current_user_id );

		return $result;

	}


	/**
	 * FUnction to update pending course enrollment.
	 *
	 * @param int $user_id User id.
	 * @param int $cohort_id Cohort id.
	 */
	public function eb_update_pending_course_enrollment( $user_id, $cohort_id ) {
		$cohort_details = includes\bulkPurchase\get_cohort_details( $cohort_id );
		$courses        = $cohort_details['courses'];
		update_user_meta( $user_id, 'eb_pending_enrollment', $courses );
	}

	/**
	 * Function to delete single enrolled user.
	 */
	public function bp_delete_single_enrolled_user() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wdm_ebbp_enroll_nonce' ) ) {
			wp_send_json_error( __( 'Unable to update group name, due to security reasons..', 'edwiser-bridge-pro' ) );
		}

		$post_data = wp_unslash( $_POST );
		if ( isset( $post_data['action'] ) && ! empty( $post_data['action'] ) && 'bp_delete_enrolled_user' === $post_data['action'] && isset( $post_data['userId'] ) && ! empty( $post_data['userId'] ) && isset( $post_data['cohortId'] ) && ! empty( $post_data['cohortId'] ) ) {
			$user_id   = $post_data['userId'];
			$cohort_id = $post_data['cohortId'];

			// Adding pending un-enrollment data.
			$this->eb_update_pending_course_enrollment( $user_id, $cohort_id );

			// Call the user remove function.
			$result = $this->bp_remove_user_from_cohort( $user_id, $cohort_id );

			$user_info = get_userdata( $user_id );

			if ( $result['status'] ) {
				$msg = '<div class="wdm_success_message">
                            <i class="dashicons dashicons-dismiss wdm_success_msg_dismiss"></i>
                            <span class="wdm_enroll_warning_message_lable">
                            ' . __( 'User with email id  ', 'edwiser-bridge-pro' ) . $user_info->user_email . __( ' have been unenrolled successfully', 'edwiser-bridge-pro' ) . '
                            </span>
                        </div>';

				wp_send_json_success(
					array(
						'status' => $result['status'],
						'qty'    => $result['qty'],
						'msg'    => $msg,
					)
				);
			} else {
				$msg = '<div class="wdm_error_message">
                            <i class="dashicons dashicons-dismiss wdm_success_msg_dismiss"></i>
                            <span class="wdm_enroll_warning_message_lable">
                            ' . __( 'Sorry, unable to delete user with email id ', 'edwiser-bridge-pro' ) . $user_info->user_email . '
                            </span>
                        </div>';
				wp_send_json_error( array( 'msg' => $msg ) );
			}
		}
	}

	/**
	 * Function to Delete cohort from frontend.
	 */
	public function bp_delete_cohort_from_frontend() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		$post_data = wp_unslash( $_POST );

		if ( isset( $post_data['action'] ) && ! empty( $post_data['action'] ) && 'bp_delete_cohort' === $post_data['action'] && isset( $post_data['cohortId'] ) && ! empty( $post_data['cohortId'] ) ) {
			$cohort_id = $post_data['cohortId'];

			// unenroll all the users from the cohort.
			$cohort_user_manager = new includes\bulkPurchase\Eb_Bp_Cohort_Manage_User();
			$result              = $cohort_user_manager->delete_all_users_from_cohort( $cohort_id );
			// Delete Cohort from wordopress as well as moodle.
			$cohort_manger = new includes\bulkPurchase\Eb_Bp_Manage_Cohort();
			$result        = $cohort_manger->delete_cohort( array( $cohort_id ) );

			if ( isset( $post_data['redirect'] ) ) {
				$result = array(
					'redirect' => admin_url( 'edit.php?post_type=eb_course&page=eb-manage-groups' ),
				);
			}
			if ( $result ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( __( 'Invalid request data' ) );
			}
		}
	}

	/**
	 * Function to show all users
	 *
	 * @param object $user Wp user object.
	 */
	private function get_user_roles( $user ) {
		$user_role = $user->roles;
		$str       = '';
		$count     = count( $user_role );
		for ( $i = 0; $i < $count; $i++ ) {
			$str .= 0 !== $i ? ', ' : '';
			$str .= 'non_editing_teacher' === $user_role[ $i ] ? 'Non Editing Teacher' : $user_role[ $i ];
		}
		return $str;
	}

	/**
	 * Function to get the enroll users details.
	 */
	public function wdm_get_enroll_user_details() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		$uid       = isset( $_POST['uid'] ) ? sanitize_text_field( wp_unslash( $_POST['uid'] ) ) : '';
		$uid       = trim( $uid );
		$user_data = get_userdata( $uid );
		$roles     = $user_data->roles;
		$user_role = 'Manager';

		foreach ( $roles as $role ) {
			if ( 'subscriber' === $role ) {
				$user_role = 'student';
				break;
			}
		}

		echo wp_json_encode(
			array(
				'FirstName' => $user_data->first_name,
				'lastname'  => $user_data->last_name,
				'email'     => $user_data->user_email,
				'role'      => $user_role,
			)
		);
		die();
	}

	/**
	 * Function to enroll the user into the course.
	 *
	 * @param int $mdl_cohort_id Cohort id.
	 */
	public function wdm_enroll_user_course( $mdl_cohort_id ) {
		if ( isset( $mdl_cohort_id ) && ! empty( $mdl_cohort_id ) ) {
			global $wpdb;
			$tbl_name = $wpdb->prefix . 'bp_cohort_info';
			$result   = $wpdb->get_row( $wpdb->prepare( "SELECT PRODUCTS, NAME, COHORT_NAME FROM {$tbl_name} WHERE MDL_COHORT_ID = %d;", $mdl_cohort_id ), ARRAY_A ); // @codingStandardsIgnoreLine
			$product  = $result['PRODUCTS'];
			$product  = maybe_unserialize( $product );
			ob_start();
			?>
			<div id="wdm-asso-course-accordian" class="wdm-coho-asso-corses wdm-dialog-scroll">
				<ol type="1">
					<?php
					foreach ( array_keys( $product ) as $key ) {
						$tbl_name  = $wpdb->prefix . 'eb_moodle_course_products';
						$courses   = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT `moodle_post_id` FROM `{$tbl_name}` WHERE `product_id` = %d ", $key ) ); // @codingStandardsIgnoreLine
						$prod_name = get_the_title( $key );
						?>
							<li class="wdm_enrol-studnets_products"> <?php echo esc_html( $prod_name ); ?></li>
								<div class = "wdm_productwise_course">
									<ol type="a">
									<?php
									foreach ( $courses as $course ) {
										$course_info = get_post( $course );
										$title       = $course_info->post_title;
										?>
											<li><?php echo esc_attr( $title ); ?></li>
										<?php
									}
									?>
									</ol>
								</div>
						<?php
					}
					?>
				</ol>
			</div>
			<?php
			$responce = ob_get_clean();
			return $responce;
		} else {
			return __( 'Invalid request parameters', 'edwiser-bridge-pro' );
		}
	}

	/**
	 * Callback to update the user on WordPress and moodle.
	 */
	public function wdm_bp_edit_user() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		$post_data = wp_unslash( $_POST );
		$uid       = $this->check_data( $post_data, 'uid' );
		$fnane     = $this->check_data( $post_data, 'firstname' );
		$lname     = $this->check_data( $post_data, 'lastname' );
		$email     = $this->check_data( $post_data, 'email' );
		if ( $uid && $fnane && $lname && $email ) {
			$user_data = array(
				'ID'         => $uid,
				'first_name' => $fnane,
				'last_name'  => $lname,
				'user_email' => $email,
			);

			/**
			 * Update user on WordPress.
			 */
			$update_user = wp_update_user( $user_data );
			if ( is_wp_error( $update_user ) ) {
				wp_send_json_error( $this->prepare_res_msg( $update_user->get_error_message(), true ) );
			} else {
				$mdl_user_id = get_user_meta( trim( $post_data['uid'] ), 'moodle_user_id', true );
				$user_data   = array(
					'id'        => $mdl_user_id,
					'firstname' => $fnane,
					'lastname'  => $lname,
					'email'     => $email,
				);
				/**
				 * Update user data on moodle.
				 */
				$mdl_user_updated = edwiserBridge\edwiser_bridge_instance()->userManager()->createMoodleUser( $user_data, 1 );
				if ( 1 === $mdl_user_updated['user_updated'] ) {
					wp_send_json_success( $this->prepare_res_msg( __( 'User data has been updated successfully.', 'edwiser-bridge-pro' ), false ) );
				} else {
					wp_send_json_error( $this->prepare_res_msg( __( 'Failed to update user on moodle.', 'edwiser-bridge-pro' ), true ) );
				}
			}
		} else {
			wp_send_json_error( $this->prepare_res_msg( __( 'User data is inappropriate.', 'edwiser-bridge-pro' ), true ) );
		}
	}

	/**
	 * Function to prepare the responce message
	 *
	 * @param  string $msg Message text.
	 * @param  bool   $is_error True id the message is error message.
	 */
	private function prepare_res_msg( $msg, $is_error = false ) {
		ob_start();
		if ( ! $is_error ) {
			?>
			<div class="wdm_success_message wdm_user_list">
				<i class="dashicons dashicons-dismiss wdm_success_msg_dismiss"></i>
				<span class="wdm_enroll_warning_message_lable">
					<?php echo esc_html( $msg ); ?>
				</span>
			</div>
			<?php
		} else {
			?>
			<div class="wdm_error_message wdm_user_list">
				<i class="dashicons dashicons-dismiss wdm_error_msg_dismiss"></i>
				<span class="wdm_enroll_warning_message_lable">
					<?php echo esc_html( $msg ); ?>
				</span>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Function to check if the data is set or not to the array key.
	 *
	 * @param string $array data array.
	 * @param string $key Key name.
	 */
	private function check_data( $array, $key ) {
		if ( isset( $array[ $key ] ) && ! empty( $array[ $key ] ) ) {
			return $array[ $key ];
		}
		return false;
	}

	/**
	 * Function to perform the add to cart action on selected products.
	 */
	public function wdm_add_to_cart() {
		$session_data = array();
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		if ( WC()->session->get( 'add_product_from_enroll_page' ) ) {
			$session_data = WC()->session->get( 'add_product_from_enroll_page' );
		}
		$req_data = wp_unslash( $_POST );
		if ( isset( $req_data['mdl_cohort_id'] ) && ! empty( $req_data['mdl_cohort_id'] ) && isset( $req_data['productQuantity'] ) && ! empty( $req_data['productQuantity'] ) ) {
			global $woocommerce;
			$checkout_url   = '#';
			$cohort_id      = $req_data['mdl_cohort_id'];
			$cohort_details = array( 'cohort_id' => $req_data['mdl_cohort_id'] );
			$flag           = 0;
			foreach ( $req_data['productQuantity'] as $value ) {
				if ( $value <= 0 ) {
					$flag = 1;
				}
			}

			if ( ! $flag ) {
				foreach ( $req_data['productQuantity'] as $prod_id => $qty ) {
					$cohort_details['product_id'] = $prod_id;
					$cohort_details['quantity']   = $qty;
					$woocommerce->cart->add_to_cart(
						$prod_id,
						$qty,
						'',
						array(),
						array(
							'cohort_id'               => $cohort_id,
							'wdm_edwiser_self_enroll' => 'on',
							'Group Enrollment'        => 'yes',
							'enroll-students'         => 'yes',
							'wdm_edwiser_self_enroll_checkbox' => 'on',
						)
					);
					array_push( $session_data, $cohort_details );
				}
				$checkout_url = wc_get_checkout_url();
			}
		}
		if ( empty( $checkout_url ) ) {
			wp_send_json_error( __( 'Checkout page not found, Please contact admin', 'edwiser-bridge-pro' ) );
		}
		wp_send_json_success( $checkout_url );
	}

	/**
	 * Function to add the more product quantity to the cohort.
	 */
	public function wdm_add_more_quantity() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		$post_data = wp_unslash( $_POST );

		if ( WC()->session->get( 'eb-bp-create-same-product' ) ) {
			WC()->session->set( 'eb-bp-create-same-product', 0 );
		}
		// if ( session_status() === PHP_SESSION_NONE ) {
		// 	session_start();
		// }
		// $_SESSION['addQuantity'] = 1;
		WC()->session->set( 'addQuantity', 1 );
		$currency                = get_woocommerce_currency_symbol();
		$cohort_id               = sanitize_text_field( $post_data['mdl_cohort_id'] );
		global $wpdb;
		$tbl_name = $wpdb->prefix . 'bp_cohort_info';
		$result   = $wpdb->get_var( $wpdb->prepare( "SELECT PRODUCTS FROM {$tbl_name} WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine
		$product  = @unserialize( $result ); // @codingStandardsIgnoreLine
		if ( count( $product ) <= 0 ) {
			wp_send_json_error( __( "'Sorry, currently there are no group products available '", 'edwiser-bridge-pro' ) );
		}

		ob_start();
		?>
		<div id='add-quantity'>
			<div class="wdm-add-prod_qty">
				<div style="display: table-row;">
					<label for="wdm_new_prod_qty" style="display: table-cell;padding-right:10px;"><?php esc_attr_e( 'Add Quantity', 'edwiser-bridge-pro' ); ?>:</label>
					<input type="number" min="1" maxlength="5" name="wdm_new_prod_qty" value="0" id="wdm_new_prod_qty" style="display: table-cell;">
				</div>
			</div>
			<table id ='add-quantity-table' class="wdm-more-qty-tbl" border="0" data-cohortid='<?php echo esc_html( $cohort_id ); ?>'>
				<thead>
					<tr>
						<th class="eb_add_qty_tbl_sr_no"><?php esc_attr_e( 'Sr. No.', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_prod_name"><?php esc_attr_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_price"><?php esc_attr_e( 'Price', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_x"></th>
						<th class="eb_add_qty_tbl_qty"><?php esc_attr_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_equal"></th>
						<th class="eb_add_qty_tbl_total_price"><?php esc_attr_e( 'Price', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="wdm-dialog-scroll">
					<?php
					$cnt = 0;
					foreach ( $product as $pid => $quantity ) {
						$cnt++;
						$quantity = $quantity;
						$courses  = $this->get_prod_assoc_courses( $pid );
						?>
						<tr>
							<td class="eb_add_qty_tbl_sr_no"><?php echo esc_attr( $cnt ); ?></td>
							<td class='wdmProductNameContainer eb_add_qty_tbl_prod_name'>									
								<span class = 'product_title'><?php echo esc_html( get_the_title( $pid ) ); ?> </span>
							</td>
							<?php
							$prod_price = $this->get_prod_price( $pid );
							?>
							<td class="eb_add_qty_tbl_price">
								<span><?php echo esc_html( $currency ); ?></span>
								<span id = '<?php echo esc_attr( $pid ); ?>-per-product-price'><?php echo esc_attr( $prod_price ); ?></span>
							</td>
							<td class="eb_add_qty_tbl_x"> x </td>
							<td class="eb_add_qty_tbl_qty">
								<span class="wdm_new_qty_per_prod" id="<?php echo esc_attr( $pid ); ?>">0</span></td>
							</td>
							<td class="eb_add_qty_tbl_equal"> = </td>
							<td class="eb_add_qty_tbl_total_price">
								<div class="wdm-item-price">
									<span><?php echo esc_html( $currency ); ?></span>
									<span class = 'wdm-quantity-total add-more-quantity' id='<?php echo esc_attr( $pid ); ?>-total-price'>0</span>
								</div>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<td class="ebbp_enroll_student_total_wrap"><strong><?php esc_attr_e( 'Total', 'edwiser-bridge-pro' ); ?></strong></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td class="add-quantity-total-price-wrap">
							<span><?php echo esc_html( $currency ); ?></span>
							<span id='add-quantity-total-price'>0</span>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
		$responce = ob_get_clean();
		wp_send_json_success( $responce );
	}

	/**
	 * Function to add the new product to the array.
	 */
	public function wdm_add_new_product_to_group() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		if ( WC()->session->get( 'eb-bp-create-same-product' ) ) {
			WC()->session->set( 'eb-bp-create-same-product', 0 );
		}
		global $wpdb;
		$post_data      = wp_unslash( $_POST );
		$currency       = get_woocommerce_currency_symbol();
		$mdl_cohort_id  = isset( $_POST['mdl_cohort_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mdl_cohort_id'] ) ) : '';
		$tbl_coho_info  = $wpdb->prefix . 'bp_cohort_info';
		$result         = $wpdb->get_row( $wpdb->prepare( "SELECT PRODUCTS, NAME, COHORT_NAME FROM {$tbl_coho_info} WHERE MDL_COHORT_ID = %d", $mdl_cohort_id ), ARRAY_A ); // @codingStandardsIgnoreLine
		$cohort_prod    = maybe_unserialize( $result['PRODUCTS'] );
		$ava_qty        = max( $cohort_prod );
		$cohort_name    = $result['COHORT_NAME'];
		$cohort_mems    = $this->get_total_members( $mdl_cohort_id );
		$min_prod_qty   = $ava_qty + $cohort_mems;
		$tbl_mdl_enroll = $wpdb->prefix . 'eb_moodle_course_products';
		$all_prod       = $wpdb->get_col( "SELECT DISTINCT `product_id` FROM `{$tbl_mdl_enroll}`" ); // @codingStandardsIgnoreLine
		if ( count( $all_prod ) <= 0 ) {
			wp_send_json_error( __( 'Sorry, currently there are no group products available.', 'edwiser-bridge-pro' ) );
		}
		ob_start();
		?>
		<div id ='add-quantity-table' class="wdm-more-prod-tbl wdm-dialog-scroll" data-cohortid='<?php echo esc_attr( $mdl_cohort_id ); ?>'>
		</div>
		<div id ='bp-new-product'>
			<table id ='bp-new-product-table' class="wdm-more-prod-tbl wdm-dialog-scroll" data-cohortid='<?php echo esc_attr( $mdl_cohort_id ); ?>'>
				<thead>
					<tr>
						<th></th>
						<th><?php esc_attr_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th><?php esc_attr_e( 'Price', 'edwiser-bridge-pro' ); ?></th>
						<th></th>
						<th><?php esc_attr_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
						<th></th>
						<th><?php esc_attr_e( 'Total', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="wdm-dialog-scroll">
					<?php
					foreach ( $all_prod as $product ) {
						$price = get_post_meta( $product, '_regular_price', 1 );
						if ( 'publish' === get_post_status( $product ) && isset( $price ) && isset( $price ) && '' !== $price ) {
							$post_meta = get_post_meta( $product, 'product_options' );
							// v1.1.1.
							if ( isset( $post_meta[0]['moodle_course_group_purchase'] ) && 'on' === $post_meta[0]['moodle_course_group_purchase'] ) {
								if ( array_key_exists( $product, $cohort_prod ) ) {
									continue;
								} else {
									?>
									<tr>
										<td class = 'box'>
											<input class='wdm_selected_products' id="<?php echo esc_attr( $product ); ?>-wdm-sele-prod" type = 'checkbox' />
										</td>
										<td class='wdmProductNameContainer'>
											<ul class='wdmProductName'>
												<li class = 'product_title' data-id = "<?php echo esc_attr( $product ); ?>">
													<?php echo esc_html( get_the_title( $product ) ); ?>
												</li>
											</ul>
										</td>
										<?php $prod_price = $this->get_prod_price( $product ); ?>
										<td>
											<span><?php echo esc_html( $currency ); ?></span>
											<span id="<?php echo esc_attr( $product ); ?>-per-product-price"><?php echo esc_attr( $prod_price ); ?></span>
										</td>
										<td> x </td>
										<td style = 'text-align:center;'>
											<span class="wdm_new_qty_per_new_prod" id="<?php echo esc_attr( $product ); ?>"><?php echo esc_attr( $min_prod_qty ); ?></span></td>
										</td>
										<td> = </td>
										<td>
											<span><?php echo esc_html( $currency ); ?> </span>
											<span class = 'wdm-quantity-total add-more-product' id='<?php echo esc_attr( $product ); ?>-total-price'>0</span>
										</td>
									</tr>
									<?php
								}
							}
						}
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<td class="ebbp_enroll_student_total_wrap"><strong><?php esc_attr_e( 'Total', 'edwiser-bridge-pro' ); ?></strong></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td class="add-quantity-total-price-wrap">
							<span><?php echo esc_html( $currency ); ?></span>
							<span id='add-quantity-total-price'>0</span>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
		$current_user = wp_get_current_user();
		$cohort_name  = isset( $result['NAME'] ) && ! empty( $result['NAME'] ) ? $result['NAME'] : str_replace( $current_user->user_login . '_', '', $cohort_name );
		$responce     = array(
			'data'   => ob_get_clean(),
			'cohort' => $cohort_name,
		);
		wp_send_json_success( $responce );
	}

	/**
	 * Function to add the more product quantity to the cohort.
	 */
	public function wdm_manage_group_add_quantity() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		$post_data = wp_unslash( $_POST );

		if ( WC()->session->get( 'eb-bp-create-same-product' ) ) {
			WC()->session->set( 'eb-bp-create-same-product', 0 );
		}
		// if ( session_status() === PHP_SESSION_NONE ) {
		// 	session_start();
		// }
		// $_SESSION['addQuantity'] = 1;
		WC()->session->set( 'addQuantity', 1 );
		$currency                = get_woocommerce_currency_symbol();
		$cohort_id               = sanitize_text_field( $post_data['mdl_cohort_id'] );
		global $wpdb;
		$tbl_name = $wpdb->prefix . 'bp_cohort_info';
		$result   = $wpdb->get_var( $wpdb->prepare( "SELECT PRODUCTS FROM {$tbl_name} WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine
		$product  = @unserialize( $result ); // @codingStandardsIgnoreLine
		if ( count( $product ) <= 0 ) {
			wp_send_json_error( __( "'Sorry, currently there are no group products available '", 'edwiser-bridge-pro' ) );
		}

		ob_start();
		?>
		<div id='add-quantity' class="ebbp-add-quantity">
			<div class="wdm-add-prod_qty">
				<div style="display: table-row;">
					<label for="wdm_new_prod_qty" style="display: table-cell;padding-right:10px;"><?php esc_attr_e( 'Add Quantity', 'edwiser-bridge-pro' ); ?>:</label>
					<input type="number" min="1" maxlength="5" name="wdm_new_prod_qty" value="0" id="wdm_new_prod_qty" style="display: table-cell;">
				</div>
			</div>
			<table id ='add-quantity-table' class="wdm-more-qty-tbl" border="0" data-cohortid='<?php echo esc_html( $cohort_id ); ?>'>
				<thead>
					<tr>
						<th class="eb_add_qty_tbl_sr_no"><?php esc_attr_e( 'Sr. No.', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_prod_name"><?php esc_attr_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_price"><?php esc_attr_e( 'Price', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_qty"><?php esc_attr_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="wdm-dialog-scroll">
					<?php
					$cnt = 0;
					foreach ( $product as $pid => $quantity ) {
						$cnt++;
						$quantity = $quantity;
						$courses  = $this->get_prod_assoc_courses( $pid );
						?>
						<tr>
							<td class="eb_add_qty_tbl_sr_no"><?php echo esc_attr( $cnt ); ?></td>
							<td class='wdmProductNameContainer eb_add_qty_tbl_prod_name'>									
								<span class = 'product_title'><?php echo esc_html( get_the_title( $pid ) ); ?> </span>
							</td>
							<?php
							$prod_price = $this->get_prod_price( $pid );
							?>
							<td class="eb_add_qty_tbl_price">
								<span><?php echo esc_html( $currency ); ?></span>
								<span id = '<?php echo esc_attr( $pid ); ?>-per-product-price'><?php echo esc_attr( $prod_price ); ?></span>
							</td>
							<td class="eb_add_qty_tbl_qty">
								<span class="wdm_new_qty_per_prod" id="<?php echo esc_attr( $pid ); ?>">0</span></td>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		$responce = ob_get_clean();
		wp_send_json_success( $responce );
	}

	/**
	 * Function to add the new product to the array.
	 */
	public function wdm_manage_group_add_product() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		if ( WC()->session->get( 'eb-bp-create-same-product' ) ) {
			WC()->session->set( 'eb-bp-create-same-product', 0 );
		}
		global $wpdb;
		$post_data      = wp_unslash( $_POST );
		$currency       = get_woocommerce_currency_symbol();
		$mdl_cohort_id  = isset( $_POST['mdl_cohort_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mdl_cohort_id'] ) ) : '';
		$tbl_coho_info  = $wpdb->prefix . 'bp_cohort_info';
		$result         = $wpdb->get_row( $wpdb->prepare( "SELECT PRODUCTS, NAME, COHORT_NAME FROM {$tbl_coho_info} WHERE MDL_COHORT_ID = %d", $mdl_cohort_id ), ARRAY_A ); // @codingStandardsIgnoreLine
		$cohort_prod    = maybe_unserialize( $result['PRODUCTS'] );
		$ava_qty        = max( $cohort_prod );
		$cohort_name    = $result['COHORT_NAME'];
		$cohort_mems    = $this->get_total_members( $mdl_cohort_id );
		$min_prod_qty   = $ava_qty + $cohort_mems;
		$tbl_mdl_enroll = $wpdb->prefix . 'eb_moodle_course_products';
		$all_prod       = $wpdb->get_col( "SELECT DISTINCT `product_id` FROM `{$tbl_mdl_enroll}`" ); // @codingStandardsIgnoreLine
		if ( count( $all_prod ) <= 0 ) {
			wp_send_json_error( __( 'Sorry, currently there are no group products available.', 'edwiser-bridge-pro' ) );
		}
		ob_start();
		?>
		<div id ='add-quantity-table' class="wdm-more-prod-tbl wdm-dialog-scroll" data-cohortid='<?php echo esc_attr( $mdl_cohort_id ); ?>'>
		</div>
		<div id ='bp-new-product' class="ebbp-add-product">
			<table id ='bp-new-product-table' class="wdm-more-prod-tbl wdm-dialog-scroll" data-cohortid='<?php echo esc_attr( $mdl_cohort_id ); ?>'>
				<thead>
					<tr>
						<th></th>
						<th><?php esc_attr_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th><?php esc_attr_e( 'Price', 'edwiser-bridge-pro' ); ?></th>
						<th><?php esc_attr_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="wdm-dialog-scroll">
					<?php
					foreach ( $all_prod as $product ) {
						$price = get_post_meta( $product, '_regular_price', 1 );
						if ( 'publish' === get_post_status( $product ) && isset( $price ) && isset( $price ) && '' !== $price ) {
							$post_meta = get_post_meta( $product, 'product_options' );
							// v1.1.1.
							if ( isset( $post_meta[0]['moodle_course_group_purchase'] ) && 'on' === $post_meta[0]['moodle_course_group_purchase'] ) {
								if ( array_key_exists( $product, $cohort_prod ) ) {
									continue;
								} else {
									?>
									<tr>
										<td class = 'box'>
											<input class='wdm_selected_products' id="<?php echo esc_attr( $product ); ?>-wdm-sele-prod" type = 'checkbox' />
										</td>
										<td class='wdmProductNameContainer'>
											<ul class='wdmProductName'>
												<li class = 'product_title' data-id = "<?php echo esc_attr( $product ); ?>">
													<?php echo esc_html( get_the_title( $product ) ); ?>
												</li>
											</ul>
										</td>
										<?php $prod_price = $this->get_prod_price( $product ); ?>
										<td>
											<span><?php echo esc_html( $currency ); ?></span>
											<span id="<?php echo esc_attr( $product ); ?>-per-product-price"><?php echo esc_attr( $prod_price ); ?></span>
										</td>
										<td style = 'text-align:center;'>
											<span class="wdm_new_qty_per_new_prod" id="<?php echo esc_attr( $product ); ?>"><?php echo esc_attr( $min_prod_qty ); ?></span></td>
										</td>
									</tr>
									<?php
								}
							}
						}
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		$current_user = wp_get_current_user();
		$cohort_name  = isset( $result['NAME'] ) && ! empty( $result['NAME'] ) ? $result['NAME'] : str_replace( $current_user->user_login . '_', '', $cohort_name );
		$responce     = array(
			'data'   => ob_get_clean(),
			'cohort' => $cohort_name,
		);
		wp_send_json_success( $responce );
	}

	/**
	 * Function to remove products from group in admin dashboard.
	 *
	 * @since 2.3.8
	 */
	public function wdm_manage_group_remove_product() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		global $wpdb;
		$post_data     = wp_unslash( $_POST );
		$currency      = get_woocommerce_currency_symbol();
		$mdl_cohort_id = isset( $_POST['mdl_cohort_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mdl_cohort_id'] ) ) : '';
		$tbl_coho_info = $wpdb->prefix . 'bp_cohort_info';
		$result        = $wpdb->get_row( $wpdb->prepare( "SELECT PRODUCTS, NAME, COHORT_NAME FROM {$tbl_coho_info} WHERE MDL_COHORT_ID = %d", $mdl_cohort_id ), ARRAY_A ); // @codingStandardsIgnoreLine
		$cohort_prod   = maybe_unserialize( $result['PRODUCTS'] );
		$ava_qty       = max( $cohort_prod );
		$cohort_name   = $result['COHORT_NAME'];
		$cohort_mems   = $this->get_total_members( $mdl_cohort_id );
		$min_prod_qty  = $ava_qty + $cohort_mems;

		if ( count( $cohort_prod ) <= 0 ) {
			wp_send_json_error( __( 'Sorry, currently there are no products available.', 'edwiser-bridge-pro' ) );
		}
		ob_start();
		?>
		<div id ='add-quantity-table' class="wdm-more-prod-tbl wdm-dialog-scroll" data-cohortid='<?php echo esc_attr( $mdl_cohort_id ); ?>'>
		</div>
		<div id ='bp-new-product' class="ebbp-remove-product">
			<table id ='bp-new-product-table' class="wdm-more-prod-tbl wdm-dialog-scroll" data-cohortid='<?php echo esc_attr( $mdl_cohort_id ); ?>'>
				<thead>
					<tr>
						<th></th>
						<th><?php esc_attr_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th><?php esc_attr_e( 'Price', 'edwiser-bridge-pro' ); ?></th>
						<th><?php esc_attr_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="wdm-dialog-scroll">
					<?php
					foreach ( $cohort_prod as $product => $quantity ) {
						$price = get_post_meta( $product, '_regular_price', 1 );
						if ( 'publish' === get_post_status( $product ) && isset( $price ) && isset( $price ) && '' !== $price ) {
							$post_meta = get_post_meta( $product, 'product_options' );
							// v1.1.1.
							if ( isset( $post_meta[0]['moodle_course_group_purchase'] ) && 'on' === $post_meta[0]['moodle_course_group_purchase'] ) {
								?>
								<tr>
									<td class = 'box'>
										<input class='wdm_selected_products' id="<?php echo esc_attr( $product ); ?>-wdm-sele-prod" type = 'checkbox' />
									</td>
									<td class='wdmProductNameContainer'>
										<ul class='wdmProductName'>
											<li class = 'product_title' data-id = "<?php echo esc_attr( $product ); ?>">
												<?php echo esc_html( get_the_title( $product ) ); ?>
											</li>
										</ul>
									</td>
									<?php $prod_price = $this->get_prod_price( $product ); ?>
									<td>
										<span><?php echo esc_html( $currency ); ?></span>
										<span id="<?php echo esc_attr( $product ); ?>-per-product-price"><?php echo esc_attr( $prod_price ); ?></span>
									</td>
									<td style = 'text-align:center;'>
										<span class="wdm_new_qty_per_new_prod" id="<?php echo esc_attr( $product ); ?>"><?php echo esc_attr( $min_prod_qty ); ?></span></td>
									</td>
								</tr>
								<?php
							}
						}
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		$current_user = wp_get_current_user();
		$cohort_name  = isset( $result['NAME'] ) && ! empty( $result['NAME'] ) ? $result['NAME'] : str_replace( $current_user->user_login . '_', '', $cohort_name );
		$responce     = array(
			'data'   => ob_get_clean(),
			'cohort' => $cohort_name,
		);
		wp_send_json_success( $responce );
	}

	/**
	 * Function to remove quantity in admin dashboard
	 *
	 * @since 2.3.8
	 */
	public function wdm_manage_group_remove_quantity() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		$post_data = wp_unslash( $_POST );

		if ( WC()->session->get( 'eb-bp-create-same-product' ) ) {
			WC()->session->set( 'eb-bp-create-same-product', 0 );
		}
		// if ( session_status() === PHP_SESSION_NONE ) {
		// 	session_start();
		// }
		// $_SESSION['addQuantity'] = 1;
		WC()->session->set( 'addQuantity', 1 );
		$currency                = get_woocommerce_currency_symbol();
		$cohort_id               = sanitize_text_field( $post_data['mdl_cohort_id'] );
		global $wpdb;
		$tbl_name = $wpdb->prefix . 'bp_cohort_info';
		$result   = $wpdb->get_var( $wpdb->prepare( "SELECT PRODUCTS FROM {$tbl_name} WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine
		$product  = @unserialize( $result ); // @codingStandardsIgnoreLine
		if ( count( $product ) <= 0 ) {
			wp_send_json_error( __( "'Sorry, currently there are no group products available '", 'edwiser-bridge-pro' ) );
		}

		ob_start();
		?>
		<div id='add-quantity' class="ebbp-remove-quantity">
			<div class="wdm-add-prod_qty">
				<div style="display: table-row;">
					<label for="wdm_new_prod_qty" style="display: table-cell;padding-right:10px;"><?php esc_attr_e( 'Add Quantity', 'edwiser-bridge-pro' ); ?>:</label>
					<input type="number" min="1" maxlength="5" name="wdm_new_prod_qty" value="0" id="wdm_new_prod_qty" style="display: table-cell;">
				</div>
			</div>
			<table id ='add-quantity-table' class="wdm-more-qty-tbl" border="0" data-cohortid='<?php echo esc_html( $cohort_id ); ?>'>
				<thead>
					<tr>
						<th class="eb_add_qty_tbl_sr_no"><?php esc_attr_e( 'Sr. No.', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_prod_name"><?php esc_attr_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_price"><?php esc_attr_e( 'Price', 'edwiser-bridge-pro' ); ?></th>
						<th class="eb_add_qty_tbl_qty"><?php esc_attr_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="wdm-dialog-scroll">
					<?php
					$cnt = 0;
					foreach ( $product as $pid => $quantity ) {
						$cnt++;
						$quantity = $quantity;
						$courses  = $this->get_prod_assoc_courses( $pid );
						?>
						<tr>
							<td class="eb_add_qty_tbl_sr_no"><?php echo esc_attr( $cnt ); ?></td>
							<td class='wdmProductNameContainer eb_add_qty_tbl_prod_name'>									
								<span class = 'product_title'><?php echo esc_html( get_the_title( $pid ) ); ?> </span>
							</td>
							<?php
							$prod_price = $this->get_prod_price( $pid );
							?>
							<td class="eb_add_qty_tbl_price">
								<span><?php echo esc_html( $currency ); ?></span>
								<span id = '<?php echo esc_attr( $pid ); ?>-per-product-price'><?php echo esc_attr( $prod_price ); ?></span>
							</td>
							<td class="eb_add_qty_tbl_qty">
								<span class="wdm_new_qty_per_prod" id="<?php echo esc_attr( $pid ); ?>">0</span></td>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		$responce = ob_get_clean();
		wp_send_json_success( $responce );
	}

	/**
	 * Function to search wp users and show them to select.
	 *
	 * @since 2.3.8
	 */
	public function wdm_manage_group_search_users() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		$post_data = wp_unslash( $_POST );
		$term      = sanitize_text_field( $post_data['term'] );

		// if term is empty return all users.
		if ( empty( $term ) ) {
			$users = get_users();
		} else {
			$users = get_users(
				array(
					'search' => '*' . $term . '*',
				)
			);
		}

		// create options list from users.
		$options = '';
		foreach ( $users as $user ) {
			$options .= '<option value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->display_name ) . '</option>';
		}

		wp_send_json_success( $options );
	}

	/**
	 * Function to add products to new group.
	 *
	 * @since 2.3.8
	 */
	public function wdm_new_group_add_product() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		if ( WC()->session->get( 'eb-bp-create-same-product' ) ) {
			WC()->session->set( 'eb-bp-create-same-product', 0 );
		}
		global $wpdb;
		$post_data      = wp_unslash( $_POST );
		$currency       = get_woocommerce_currency_symbol();
		$tbl_mdl_enroll = $wpdb->prefix . 'eb_moodle_course_products';
		$all_prod       = $wpdb->get_col( "SELECT DISTINCT `product_id` FROM `{$tbl_mdl_enroll}`" ); // @codingStandardsIgnoreLine
		if ( count( $all_prod ) <= 0 ) {
			wp_send_json_error( __( 'Sorry, currently there are no group products available.', 'edwiser-bridge-pro' ) );
		}
		ob_start();
		?>
		<div id ='add-quantity-table' class="wdm-more-prod-tbl wdm-dialog-scroll">
		</div>
		<div id ='bp-new-product' class="new-group-add-product">
			<div class="wdm-add-prod_qty">
				<div style="display: table-row;">
					<label for="wdm_new_prod_qty" style="display: table-cell;padding-right:10px;"><?php esc_attr_e( 'Add Quantity', 'edwiser-bridge-pro' ); ?>:</label>
					<input type="number" min="1" maxlength="5" name="wdm_new_prod_qty" value="0" id="wdm_new_prod_qty" style="display: table-cell;">
				</div>
			</div>
			<table id ='bp-new-product-table' class="wdm-more-prod-tbl wdm-dialog-scroll" >
				<thead>
					<tr>
						<th></th>
						<th><?php esc_attr_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th><?php esc_attr_e( 'Price', 'edwiser-bridge-pro' ); ?></th>
						<th><?php esc_attr_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody class="wdm-dialog-scroll">
					<?php
					foreach ( $all_prod as $product ) {
						$price = get_post_meta( $product, '_regular_price', 1 );
						if ( 'publish' === get_post_status( $product ) && isset( $price ) && isset( $price ) && '' !== $price ) {
							$post_meta = get_post_meta( $product, 'product_options' );
							// v1.1.1.
							if ( isset( $post_meta[0]['moodle_course_group_purchase'] ) && 'on' === $post_meta[0]['moodle_course_group_purchase'] ) {
								?>
								<tr>
									<td class = 'box'>
										<input class='wdm_selected_products' id="<?php echo esc_attr( $product ); ?>-wdm-sele-prod" type = 'checkbox' />
									</td>
									<td class='wdmProductNameContainer'>
										<ul class='wdmProductName'>
											<li class = 'product_title' data-id = "<?php echo esc_attr( $product ); ?>">
												<?php echo esc_html( get_the_title( $product ) ); ?>
											</li>
										</ul>
									</td>
									<?php $prod_price = $this->get_prod_price( $product ); ?>
									<td>
										<span><?php echo esc_html( $currency ); ?></span>
										<span id="<?php echo esc_attr( $product ); ?>-per-product-price"><?php echo esc_attr( $prod_price ); ?></span>
									</td>
									<td style = 'text-align:center;'>
										<span class="wdm_new_qty_per_new_prod" id="<?php echo esc_attr( $product ); ?>">0</span></td>
									</td>
								</tr>
								<?php
							}
						}
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		$responce = array(
			'data' => ob_get_clean(),
		);
		wp_send_json_success( $responce );
	}

	/**
	 * Function to create new group
	 *
	 * @since 2.3.8
	 */
	public function wdm_create_new_group() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}
		$post_data      = wp_unslash( $_POST );
		$cohort_name    = isset( $post_data['group_name'] ) ? sanitize_text_field( $post_data['group_name'] ) : '';
		$user           = wp_get_current_user();
		$cohort_manager = isset( $post_data['cohort_manager'] ) ? sanitize_text_field( $post_data['cohort_manager'] ) : $user->ID;
		$products_data  = isset( $post_data['products'] ) ? $post_data['products'] : array();
		$products       = array();
		$courses        = array();

		foreach ( $products_data as $product_id => $quantity ) {
			$products[ $product_id ] = $quantity;

			$product_options = get_post_meta( $product_id, 'product_options', true );
			if ( ! empty( $product_options ) && isset( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
				$moodle_courses = $product_options['moodle_post_course_id'];
				if ( ! empty( $courses ) ) {
					$courses = array_unique( array_merge( $courses, $moodle_courses ), SORT_REGULAR );
				} else {
					$courses = $moodle_courses;
				}
			}
		}

		$cohort_manage = new includes\bulkPurchase\Eb_Bp_Manage_Cohort();
		$cohort_manage->update_cohort_info(
			$courses,
			'',
			$cohort_manager,
			$products,
			$cohort_name,
			false,
			$cohort_name
		);

		global $wpdb;
		$tbl_name    = $wpdb->prefix . 'bp_cohort_info';
		$result      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_name} WHERE NAME = %s AND COHORT_MANAGER = %d", $cohort_name, $cohort_manager ), ARRAY_A ); // @codingStandardsIgnoreLine
		$idnumber    = isset( $result['idnumber'] ) ? $result['idnumber'] : '';
		$cohort_name = isset( $result['COHORT_NAME'] ) && ! empty( $result['COHORT_NAME'] ) ? $result['COHORT_NAME'] : str_replace( $user->user_login . '_', '', $cohort_name );

		$response = $cohort_manage->mdl_create_cohort( $cohort_manager, $cohort_name, $idnumber );

		if ( isset( $response['success'] ) && $response['success'] ) {
			$cohort_id = $response['response_data'][0]->id;
			$this->enroll_courses_in_cohort( $cohort_id, $courses );

			$nonce    = wp_create_nonce( 'eb-bp-edit-group' );
			$url      = admin_url( 'edit.php?post_type=eb_course&page=eb-manage-groups&mdl_cohort_id=' . $cohort_id . '&eb-bp-edit-group=' . $nonce );
			$response = array(
				'msg'          => __( 'Group created successfully', 'edwiser-bridge-pro' ),
				'redirect_url' => $url,
			);

			$cohort_manager = get_user_by( 'id', $cohort_manager );
			$email_args     = array(
				'user_email' => $cohort_manager->user_email,
				'username'   => $cohort_manager->user_login,
				'first_name' => $cohort_manager->first_name,
				'last_name'  => $cohort_manager->last_name,
				'products'   => $products,
				'courses'    => $courses,
				'group_name' => isset( $post_data['group_name'] ) ? sanitize_text_field( $post_data['group_name'] ) : '',
			);

			do_action( 'eb_bp_new_group_creation', $email_args );
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( __( 'Group creation failed', 'edwiser-bridge-pro' ) );
		}

	}

	/**
	 * Function returns the product price.
	 *
	 * @param int $prod_id product id.
	 */
	private function get_prod_price( $prod_id ) {
		$product = wc_get_product( $prod_id );
		if ( null === $product || false === $product ) {
			return 0;
		}
		return $product->get_price();
	}

	/**
	 * Function to get the total cohort members.
	 *
	 * @param  int $mdl_cohort_id Cohort id.
	 */
	private function get_total_members( $mdl_cohort_id ) {
		global $wpdb;
		$tbl_name = $wpdb->prefix . 'moodle_enrollment';
		$result   = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT  user_id FROM {$tbl_name} WHERE mdl_cohort_id = %d", $mdl_cohort_id ), ARRAY_A ); // @codingStandardsIgnoreLine
		return count( $result );
	}

	/**
	 * Function to get the product associated courses.
	 *
	 * @param int $prod_id Product id.
	 */
	private function get_prod_assoc_courses( $prod_id ) {
		global $wpdb;
		$tbl_name = $wpdb->prefix . 'eb_moodle_course_products';
		$courses  = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT `moodle_post_id` FROM `{$tbl_name}` WHERE `product_id` = %d ", $prod_id ) ); // @codingStandardsIgnoreLine
		return $courses;
	}

	/**
	 * Add product in group admin side.
	 *
	 * @since 2.3.8
	 */
	public function wdm_add_product_in_group() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}

		$req_data = wp_unslash( $_POST );
		if ( isset( $req_data['mdl_cohort_id'] ) && ! empty( $req_data['mdl_cohort_id'] ) && isset( $req_data['productQuantity'] ) && ! empty( $req_data['productQuantity'] ) && isset( $req_data['type'] ) ) {
			$cohort_id = intval( $req_data['mdl_cohort_id'] );

			global $wpdb;
			$tbl_name        = $wpdb->prefix . 'bp_cohort_info';
			$result          = $wpdb->get_results( $wpdb->prepare( "SELECT PRODUCTS, COURSES FROM {$tbl_name} WHERE MDL_COHORT_ID = %d;", $cohort_id ) ); // @codingStandardsIgnoreLine
			$products        = maybe_unserialize( $result[0]->PRODUCTS );
			$courses         = maybe_unserialize( $result[0]->COURSES );
			$removed_courses = array();

			// get all the products associated with the cohort.
			$flag = 0;
			foreach ( $req_data['productQuantity'] as $product_id => $quantity ) {
				if ( $quantity <= 0 ) {
					$flag = 1;
				} else {
					$type            = $req_data['type'];
					$product_removed = false;
					if ( 'remove-quantity' === $type ) {
						$products[ $product_id ] = $products[ $product_id ] - $quantity;
						if ( $products[ $product_id ] <= 0 ) {
							unset( $products[ $product_id ] );
							$product_removed = true;
						}
					} elseif ( 'quantity' === $type || 'product' === $type ) {
						if ( isset( $products[ $product_id ] ) ) {
							$products[ $product_id ] = $products[ $product_id ] + $quantity;
						} else {
							$products[ $product_id ] = $quantity;
						}
					} elseif ( 'remove-product' === $type ) {
						unset( $products[ $product_id ] );
						$product_removed = true;
					} else {
						$products[ $product_id ] = $quantity;
					}

					$product_options = get_post_meta( $product_id, 'product_options', true );
					if ( ! empty( $product_options ) && isset( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
						$moodle_courses = $product_options['moodle_post_course_id'];
						if ( ! empty( $courses ) ) {
							if ( $product_removed ) {
								$courses = array_diff( $courses, $moodle_courses );
							} else {
								$courses = array_unique( array_merge( $courses, $moodle_courses ), SORT_REGULAR );
							}
						} else {
							if ( ! $product_removed ) {
								$courses = $moodle_courses;
							}
						}
						if ( $product_removed ) {
							$removed_courses = array_merge( $removed_courses, $moodle_courses );
						}
					}
				}
			}

			if ( ! $flag && ! empty( $products ) ) {
				$wpdb->update( // @codingStandardsIgnoreLine.
					$tbl_name,
					array(
						'PRODUCTS' => maybe_serialize( $products ),
						'COURSES'  => maybe_serialize( $courses ),
					),
					array( 'MDL_COHORT_ID' => $cohort_id )
				);
				if ( 'products' === $req_data['type'] ) {
					$this->enroll_courses_in_cohort( $cohort_id, $courses );
				}
				if ( $product_removed ) {
					$this->unenroll_cohort_from_course( $cohort_id, $removed_courses );
				}

				wp_send_json_success( __( 'Products updated successfully', 'edwiser-bridge-pro' ) );
			} else {
				wp_send_json_error( __( 'All Products can not be removed from Group', 'edwiser-bridge-pro' ) );
			}
		}
	}

	/**
	 * Update quantity in group admin side.
	 *
	 * @since 2.3.8
	 */
	public function wdm_update_quantity_in_group() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}

		$req_data = wp_unslash( $_POST );
		if ( isset( $req_data['mdl_cohort_id'] ) && ! empty( $req_data['mdl_cohort_id'] ) && isset( $req_data['quantity'] ) ) {
			$cohort_id = intval( $req_data['mdl_cohort_id'] );

			global $wpdb;
			$tbl_name = $wpdb->prefix . 'bp_cohort_info';
			$result   = $wpdb->get_results( $wpdb->prepare( "SELECT PRODUCTS, COURSES FROM {$tbl_name} WHERE MDL_COHORT_ID = %d;", $cohort_id ) ); // @codingStandardsIgnoreLine
			$products = maybe_unserialize( $result[0]->PRODUCTS );
			$quantity = intval( $req_data['quantity'] );

			// update quantity in each product.
			$flag = 0;
			foreach ( $products as $product_id => $product_quantity ) {
				if ( $quantity <= 0 ) {
					$flag = 1;
				} else {
					$products[ $product_id ] = $quantity;
				}
			}

			if ( ! $flag && ! empty( $products ) ) {
				$wpdb->update( // @codingStandardsIgnoreLine.
					$tbl_name,
					array(
						'PRODUCTS' => maybe_serialize( $products ),
					),
					array( 'MDL_COHORT_ID' => $cohort_id )
				);
				wp_send_json_success( __( 'Quantity updated successfully', 'edwiser-bridge-pro' ) );
			} else {
				wp_send_json_error( __( 'Please enter valid quantity', 'edwiser-bridge-pro' ) );
			}
		}
	}

	/**
	 * Update cohort_manager in group admin side.
	 *
	 * @since 2.3.8
	 */
	public function wdm_update_cohort_manager_in_group() {
		if ( ! isset( $_POST['nonce_gp_mng'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce_gp_mng'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
		}

		$req_data = wp_unslash( $_POST );
		if ( isset( $req_data['mdl_cohort_id'] ) && ! empty( $req_data['mdl_cohort_id'] ) && isset( $req_data['cohort_manager'] ) && ! empty( $req_data['cohort_manager'] ) ) {
			$cohort_id      = intval( $req_data['mdl_cohort_id'] );
			$cohort_manager = intval( $req_data['cohort_manager'] );

			global $wpdb;
			$tbl_name = $wpdb->prefix . 'bp_cohort_info';

			$wpdb->update( // @codingStandardsIgnoreLine.
				$tbl_name,
				array(
					'COHORT_MANAGER' => $cohort_manager,
				),
				array( 'MDL_COHORT_ID' => $cohort_id )
			);
			wp_send_json_success( __( 'Group Manager updated successfully', 'edwiser-bridge-pro' ) );
		} else {
			wp_send_json_error( __( 'Please select valid user', 'edwiser-bridge-pro' ) );
		}
	}

	/**
	 * Enroll courses in cohort.
	 *
	 * @since 2.3.8
	 *
	 * @param int   $cohort_id cohort id.
	 * @param array $courses courses to be enrolled.
	 */
	public function enroll_courses_in_cohort( $cohort_id, $courses ) {
		$eb_loader            = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();
		$eb_connection_helper = $eb_loader->connection_helper();
		$moodle_function      = 'auth_edwiserbridge_manage_cohort_enrollment';

		foreach ( $courses as $course ) {
			$course_id = get_post_meta( $course, 'moodle_course_id' );
			$eb_connection_helper->connect_moodle_with_args_helper(
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
	 * Unenroll courses in cohort.
	 *
	 * @since 2.3.8
	 *
	 * @param int   $cohort_id cohort id.
	 * @param array $courses courses to be Unenrolled.
	 */
	public function unenroll_cohort_from_course( $cohort_id, $courses ) {
		$eb_loader            = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();
		$eb_connection_helper = $eb_loader->connection_helper();
		$moodle_function      = 'auth_edwiserbridge_manage_cohort_enrollment';

		foreach ( $courses as $course ) {
			$course_id = get_post_meta( $course, 'moodle_course_id' );
			$eb_connection_helper->connect_moodle_with_args_helper(
				$moodle_function,
				array(
					'cohort' => array(
						array(
							'courseid' => $course_id[0],
							'cohortid' => $cohort_id,
							'unenroll' => 1,
						),
					),
				)
			);
		}
	}

}

new Edwiser_Multiple_Users_Course_Enroll_Users();



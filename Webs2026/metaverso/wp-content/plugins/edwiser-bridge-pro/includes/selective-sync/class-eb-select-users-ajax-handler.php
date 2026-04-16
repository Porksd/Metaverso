<?php
/**
 * Selective Synchronization courses settings.
 *
 * @link       https://edwiser.org
 * @since      1.2.0
 *
 * @package    Selective Synchronization
 * @subpackage Selective Synchronization/admin
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\selectiveSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'UserHandler' ) ) {
	/**
	 * Class handling all the user re;ated functionalities.
	 */
	class Eb_Select_Users_Ajax_Handler {

		/**
		 * Class constructor.
		 *
		 * @return void
		 */
		public function __construct() {
		}

		/**
		 * Tgis function validates ech and every condition which is mandatory to proceed the migration of all users.
		 *
		 * @return [type] [description]
		 */
		private function mandatory_check_for_all_users_migration() {

			// verify nonce.
			if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'check_select_sync_action' ) && ! isset( $_POST['offset'] ) ) { // @codingStandardsIgnoreLine
				die( esc_html__( 'Security check or invalid offset value.', 'edwiser-bridge-pro' ) );
			}

			// If the migration is started again then clean the users error array stored in the previous migration.
			if ( 0 === $_POST['offset'] ) {
				update_option( 'eb_ss_migration_error', maybe_serialize( array() ) );
			}

			// createUser variable is used to show the error messages. as the error messages should vary depending upon the link and craete users options.
			$create_user = 0;
			if ( isset( $_POST['create_users'] ) && 1 == $_POST['create_users'] ) { // @codingStandardsIgnoreLine
				$create_user = 1;
			} else {
				// return neccessary data.
				wp_send_json_error(
					array(
						'error' => 1,
						'msg'   => '<div class="eb-ss-migration-error-show"><span class="dashicons dashicons-warning"></span>' . __( 'Please enable the create users option. ', 'edwiser-bridge-pro' ) . '</div>',
					)
				);
			}

			// createUser variable is used to show the error messages. as the error messages should vary depending upon the link and craete users options.
			$link_user = 0;
			if ( isset( $_POST['link_users'] ) && 1 == $_POST['link_users'] ) { // @codingStandardsIgnoreLine
				$link_user = 1;
			}

			return array(
				'create_users' => $create_user,
				'link_users'   => $link_user,
			);
		}

		/**
		 * This handles the migration of all the users from Moodle to WordPress.
		 *
		 * ------------------IMPORTANT --------------------
		 * added extraa varible as the query result from the moodle has not removed the guest user after removing the guest user from moodle result itself then remove below variables.
		 * display_count
		 * --------------------------------------------------
		 */
		public function all_users_creation_and_linking_ajax() {
			if ( isset( $_POST['_wpnonce_field'], $_POST['display_offset'], $_POST['offset'] ) && wp_verify_nonce( wp_unslash( $_POST['_wpnonce_field'] ), 'check_select_sync_action' ) ) { // @codingStandardsIgnoreLine
				$offset            = wp_unslash( $_POST['offset'] ); // @codingStandardsIgnoreLine
				$display_offset    = wp_unslash( $_POST['display_offset'] ); // @codingStandardsIgnoreLine
				$parsed_input_data = $this->mandatory_check_for_all_users_migration();
				$link_user         = $parsed_input_data['link_users'];
				$limit             = 30; // Limit of the users willl always be 30.
				$users_response    = eb_ss_get_moodle_users_in_chunk( $offset, $limit, '' ); // get users here.
				$total_users       = $users_response['total_users'];
				$users             = $users_response['data'];
				$processed_users   = count( $users );

				/**
				 * Added below line as the query result from the moodle has not removed the gurst user after removing the guest user from moodle result itself then remove this line.
				 */
				$display_count       = $processed_users;
				$user_creation_error = array();
				$linked_users        = 0;

				// start users creation and linking.
				foreach ( $users as $user_data ) {
					if ( ! empty( $user_data['username'] ) && 'guest' === $user_data['username'] ) {
						$display_count --;
					}

					if ( ! empty( $user_data['email'] ) && ! empty( $user_data['username'] ) && 'guest' !== $user_data['username'] ) {
						$is_user_created = $this->create_and_link_user(
							$user_data['firstname'],
							$user_data['lastname'],
							$user_data['username'],
							$user_data['email'],
							$user_data['mdl_id'],
							$link_user
						);

						if ( 'error' === $is_user_created ) {
							array_push(
								$user_creation_error,
								array(
									'username' => $user_data['username'],
									'email'    => $user_data['email'],
								)
							);
						} else {
							$linked_users ++;
						}
					}
				}

				// update the users which are facing issue while migration in DB.
				if ( ! empty( $user_creation_error ) ) {
					$old_user_creation_error_array = maybe_unserialize( get_option( 'eb_ss_migration_error' ) );
					$user_creation_error           = array_merge( $old_user_creation_error_array, $user_creation_error );
					update_option( 'eb_ss_migration_error', maybe_serialize( $user_creation_error ) );
				}

				/**
				 * Commented below line as the query result from the moodle has not removed the gurst user after removing the guest user from moodle result itself then uncomment this
				 */

				/**
				 * Remove this line once the above line is uncommented.
				 */
				$total_processed_users = $display_offset + $display_count;

				// Getting arrays from the options.
				// get the option data to check if it is not empty and then process with the html generation.
				$user_creation_error = maybe_unserialize( get_option( 'eb_ss_migration_error' ) );

				$is_error = 0;
				if ( ! empty( $user_creation_error ) ) {
					$is_error = 1;
				}

				$response_msgs = $this->get_all_users_migration_error_msgs( $total_processed_users, $total_users, $is_error, $user_creation_error, $link_user );

				// return neccessary data.
				wp_send_json_success(
					array(
						'error'           => $is_error,
						'error_response'  => $response_msgs['error_response'],
						'total_users'     => $total_users,
						'processed_users' => $processed_users,
						'display_offset'  => $display_count,
						'msg'             => $response_msgs['msg'],
					)
				);
			}
		}


		/**
		 * This function generates the table for users which are facing issues while migration.
		 *
		 * @param  string $error_users_array   Array of users faced issues, this value is saved in the DB.
		 */
		public function get_users_migration_error_tbl_data( $error_users_array ) {
			$html = '';
			foreach ( $error_users_array as $value ) {
				$html .= '<tr>
							<td>
								' . $value['username'] . '
							</td>

							<td>
								' . $value['email'] . '
							</td>
						</tr>';
			}
			return $html;
		}

		/**
		 * This function generates the error message for users which are facing issues while migration.
		 *
		 * @param  string $total_processed_users no of users migration is prtocessed (it includes all successfull and non successfull users count).
		 * @param  string $total_users              No. of total users.
		 * @param  string $is_error                 This means there are some users faced difficulties while migration.
		 * @param  string $user_creation_error_array  Array of users faced difficulties.
		 * @param  string $link_user                This means the user is linked or not.
		 */
		public function get_all_users_migration_error_msgs( $total_processed_users, $total_users, $is_error, $user_creation_error_array, $link_user ) {
			$error_response = '';

			$msg = '<div class="eb-ss-erro-msgs">
						<span class="dashicons dashicons-yes-alt"></span>'
					. sprintf(
						/* translators: Enter licesing key */
						__(
							'Processed  <b> %1$s <span class="eb-ss-total-users"> / %2$s</span> </b> users',
							'edwiser-bridge-pro'
						),
						$total_processed_users,
						$total_users
					)
					. '</div>';

			$link = ! empty( $link_user ) ? __( 'and linking ', 'edwiser-bridge-pro' ) : '';

			if ( $total_processed_users >= $total_users ) {
				$msg .= '<div class="eb-ss-erro-msgs">
							<span class="dashicons dashicons-yes-alt"></span>'
								. __( 'Users creation ', 'edwiser-bridge-pro' ) . $link . __( 'completed successfully.', 'edwiser-bridge-pro' );

				if ( $is_error ) {
					$error_response = $this->get_users_migration_error_tbl_data( $user_creation_error_array );
					$msg           .= '<span class="eb-ss-migration-error-show">
								<span class="eb-ss-migration-error-show-pop-up"> ' . __( 'click here', 'edwiser-bridge-pro' ) . '</span>
								to check list of users occured error while migration.
							</span>';
				}
				$msg .= '</div>';
			}

			return array(
				'msg'            => $msg,
				'error_response' => $error_response,
			);
		}




		/**
		 * Ajax call handler for the request coming from the wp-list table bulk action.
		 */
		public function selective_users_creation_and_linking_ajax() {
			if ( isset( $_POST['_wpnonce_field'], $_POST['bulk_action'] ) && wp_verify_nonce( wp_unslash( $_POST['_wpnonce_field'] ), 'check_select_sync_action' ) ) { // @codingStandardsIgnoreLine

				// variable declaration.
				$users_created          = array();
				$already_existing_users = array();
				$user_creation_error    = array();
				$bulk_action            = $_POST['bulk_action']; // @codingStandardsIgnoreLine

				// Only linking is important because in either of the case we are creating user.
				if ( 'create_user' === $bulk_action ) {
					$link_user = 0;
				} elseif ( 'create_and_link_user' === $bulk_action ) {
					$link_user = 1;
				}

				if ( isset( $_POST['action'] ) &&
				'selective_users_sync' === $_POST['action'] &&
					isset( $_POST['users'] ) &&
					isset( $link_user ) ) {

					$users = wp_unslash( $_POST['users'] ); // @codingStandardsIgnoreLine

					foreach ( $users as $user_data ) {
						if ( ! empty( $user_data['email'] ) && ! empty( $user_data['username'] ) ) {
							$is_user_created = $this->create_and_link_user(
								$user_data['first_name'],
								$user_data['last_name'],
								$user_data['username'],
								$user_data['email'],
								$user_data['id'],
								$link_user
							);

							switch ( $is_user_created ) {
								case 'success':
									array_push( $users_created, $user_data['email'] );
									break;
								case 'already-exist':
									array_push( $already_existing_users, $user_data['email'] );
									break;
								case 'error':
									array_push( $user_creation_error, $user_data['email'] );
									break;
							}
						}
					}

					// get message response.
					$html = $this->get_selctive_users_response_msg( $users_created, $already_existing_users, $user_creation_error, $bulk_action );

					wp_send_json_success( $html );
				}
			}
		}


		/**
		 * Get response message with users array.
		 *
		 * @param  string $users_created         user created.
		 * @param  string $already_existing_users existing users.
		 * @param  string $user_creation_error    user creation error.
		 * @param  string $bulk_action    bulk action.
		 * @return string                       html content.
		 */
		public function get_selctive_users_response_msg( $users_created, $already_existing_users, $user_creation_error, $bulk_action ) {
			$html = '<div>';

			if ( ! empty( $users_created ) ) {
				$msg = __( 'Users with following email ids have been created successfully.', 'edwiser-bridge-pro' );

				if ( 'create_and_link_user' === $bulk_action ) {
					$msg = __( 'Users with following email ids have been created and linked successfully.', 'edwiser-bridge-pro' );
				}

				$html .= '<div class="eb-ss-user-creation-success eb-ss-error-users-list" style="display: block;">
							<i class="fa fa-times-circle eb-ss-msg-dismiss"></i>
							<span class="eb-ss-error-message-lable">' . $msg . '
							</span>
							<ol>';

				foreach ( $users_created as $user_email ) {
					$html .= '<li>' . $user_email . '</li>';
				}

				$html .= '</ol>';
				$html .= '</div>';
			}

			if ( ! empty( $already_existing_users ) ) {
				$msg = __( 'User with the following email ids already created.', 'edwiser-bridge-pro' );

				if ( 'create_and_link_user' === $bulk_action ) {
					$msg = __( 'Linked users with the following email ids which are already created.', 'edwiser-bridge-pro' );
				}

				$html .= '<div class="eb-ss-user-creation-warning eb-ss-error-users-list" style="display: block;">
							<i class="fa fa-times-circle eb-ss-msg-dismiss"></i>
							<span class="eb-ss-error-message-lable">' . $msg . '
							</span>
							<ol>';

				foreach ( $already_existing_users as $user_email ) {
					$html .= '<li>' . $user_email . '</li>';
				}

				$html .= '</ol>';
				$html .= '</div>';
			}

			if ( ! empty( $user_creation_error ) ) {
				$msg = __( 'Unable to create users with the following email ids.', 'edwiser-bridge-pro' );

				if ( 'create_and_link_user' === $bulk_action ) {
					$msg = __( 'Error occred while creating or linking users with the following email ids.', 'edwiser-bridge-pro' );
				}

				$html .= '<div class="eb-ss-user-creation-error eb-ss-error-users-list" style="display: block;">
							<i class="fa fa-times-circle eb-ss-msg-dismiss"></i>
							<span class="eb-ss-error-message-lable">' . $msg . '
							</span>
							<ol>';

				foreach ( $user_creation_error as $user_email ) {
					$html .= '<li>' . $user_email . '</li>';
				}

				$html .= '</ol>';
				$html .= '</div>';
			}

			$html .= '</div>';
			return $html;
		}




		/**
		 * This function creates users and link if the option provided on settings page is selected.
		 *
		 * @param  string $first_name    users firstname.
		 * @param  string $last_name     users lastname.
		 * @param  string $user_name     users username.
		 * @param  string $email        users email.
		 * @param  string $moodle_user_id users moodle users ID.
		 * @param  string $link_user     if users account should be linked to the Moodle.
		 */
		public function create_and_link_user( $first_name, $last_name, $user_name, $email, $moodle_user_id, $link_user ) {
			// email_exists is nothing but the WP user id as email_exists returns user id.
			$email_exists = email_exists( $email );
			$linked       = '';

			// Check if email is already present.
			if ( $email_exists ) {
				$old_moodle_user_id = get_user_meta( $email_exists, 'moodle_user_id', 1 );
				if ( ! $old_moodle_user_id ) {
					update_user_meta( $email_exists, 'moodle_user_id', $moodle_user_id );
				}

				if ( $link_user ) {
					$linked = $this->link_user( $email_exists );
				}
				if ( 'error' === $linked ) {
					return 'error';
				}
				// If user is already present then link users.
				return 'already-exist';
			}

			// check if username is available.
			if ( username_exists( $user_name ) ) {
				// if not available create username from email.
				$user_name = sanitize_user( current( explode( '@', $email ) ), true );

				// Ensure username is unique.
				$append            = 1;
				$initial_user_name = $user_name;

				while ( username_exists( $user_name ) ) {
					$user_name = $initial_user_name . $append;
					++$append;
				}
			}

			// Handle password creation.
			$password = wp_generate_password();

			// check validation functions.
			$role = get_option( 'default_role' );

			$wp_user_data = apply_filters(
				'eb_ss_new_user_data',
				array(
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'user_login' => $user_name,
					'user_pass'  => $password,
					'user_email' => $email,
					'role'       => $role,
				)
			);

			$wp_user_id = wp_insert_user( $wp_user_data );

			if ( is_wp_error( $wp_user_id ) ) {
				return 'error';
			}

			// Trigger user account generation mail.
			$args = array(
				'user_email' => $email,
				'username'   => $user_name,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'password'   => $password,
			);
			// create a new action hook with user details as argument.
			// do_action( 'eb_created_user', $args );
			do_action( 'eb_created_user_selective_sync', $args );

			// On success.
			/**************************
			* As we already have Moodle user Id we will just.
			*/
			do_action( 'eb_ss_after_user_creation', $wp_user_id );

			if ( $link_user ) {
				update_user_meta( $wp_user_id, 'moodle_user_id', $moodle_user_id );
				return $this->link_user( $wp_user_id );
			}

			return 'success';
		}



		/**
		 * Links user if not already linked.
		 *
		 * @param  string $user_id  users id to be linked.
		 * @return string         error or success msg.
		 */
		public function link_user( $user_id ) {
			global $wpdb;

			$mdl_user_id = get_user_meta( $user_id, 'moodle_user_id', 1 );

			$moodle_user_courses = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->course_manager()->getMoodleCourses( $mdl_user_id );

			$enrolled_courses = array(); // push user's all enrolled courses id in array.
			// enrol user to courses based on recieved data.
			if ( 1 === $moodle_user_courses['success'] ) {
				foreach ( $moodle_user_courses['response_data'] as $course_data ) {
					// get WordPress id of course.
					$existing_course_id = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->course_manager()->is_course_presynced( $course_data->id );

					// enroll user to course if course exist on WordPress ( synchronized on WordPress ).
					if ( is_numeric( $existing_course_id ) ) {
						// add enrolled courses id in array.
						$enrolled_courses[] = $existing_course_id;

						// update enrollment records.
						$this->update_record_on_wordpress( $user_id, $existing_course_id );
					}
				}
			} else {
				return 'error';
			}

			/*
			 * In this block we are unenrolling user course if a user is unenrolled from those course on moodle.
			 */
			$old_enrolled_courses = $wpdb->get_results( // @codingStandardsIgnoreLine
				"SELECT course_id
                FROM {$wpdb->prefix}moodle_enrollment
                WHERE user_id = " . $user_id, // @codingStandardsIgnoreLine
				ARRAY_A
			);

			// get user's existing enrollment record from WordPress DB.

			foreach ( $old_enrolled_courses as $existing_course ) {
				if ( ! in_array( $existing_course['course_id'], $enrolled_courses, true ) ) {
					$this->update_record_on_wordpress( $user_id, $existing_course['course_id'], 5, 1 );
				}
			}

			return 'success';
		}




		/**
		 * Links user if not already linked.
		 *
		 * @param  string $user_id  users id to be linked.
		 * @param  string $course_id  course id to be linked.
		 * @param  string $role_id  role id to be linked.
		 * @param  string $unenrol  1 or 0.
		 */
		public function update_record_on_wordpress( $user_id, $course_id, $role_id = '5', $unenrol = 0 ) {
			global $wpdb;
			// default args.
			/**
			 * Parse incoming $args into an array and merge it with $defaults.
			 */

			// the role id 5 denotes student role on moodle
			// add enrollment record in DB conditionally
			// We are using user's WordPress ID and course's WordPress ID while saving record in enrollment table.
			// Get User Course Access Count.

			if ( ! $unenrol ) {
				if ( \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->course_manager()->getMoodleCourseId( $course_id ) !== '' &&
						! $this->user_has_course_access( $user_id, $course_id ) ) {
					$wpdb->insert( // @codingStandardsIgnoreLine
						$wpdb->prefix . 'moodle_enrollment',
						array(
							'user_id'     => $user_id,
							'course_id'   => $course_id,
							'role_id'     => $role_id,
							'time'        => gmdate( 'Y-m-d H:i:s' ),
							'expire_time' => '0000-00-00 00:00:00',
							'act_cnt'     => 1,
						),
						array(
							'%d',
							'%d',
							'%d',
							'%s',
							'%s',
							'%d',
						)
					);
				}
			} else {
				// delete row if count equals zero.
				$wpdb->delete( // @codingStandardsIgnoreLine
					$wpdb->prefix . 'moodle_enrollment',
					array(
						'user_id'   => $user_id,
						'course_id' => $course_id,
					),
					array(
						'%d',
						'%d',
					)
				);
			}
		}



		/**
		 * Used to check if a user has access to a course.
		 *
		 * @since  1.0.0
		 *
		 * @param int $user_id   WordPress user id of a user.
		 * @param int $course_id WordPress course id of a course.
		 *
		 * @return bool true / false
		 */
		public function user_has_course_access( $user_id, $course_id ) {
			global $wpdb;
			$has_access = false;

			if ( '' === $user_id || '' === $course_id ) {
				return $has_access;
			}

			$tbl_name = $wpdb->prefix . 'moodle_enrollment';

			$result = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}moodle_enrollment WHERE course_id={$course_id} AND user_id={$user_id};" ); // @codingStandardsIgnoreLine

			if ( (int) $result === $user_id ) {
				$has_access = true;
			}

			return $has_access;
		}
	}
}

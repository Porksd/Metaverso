<?php
/**
 * Plugin Ajax call handler.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( '\app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Ajax_Handler' ) ) {

	/**
	 * The class handles the ajax functinality.
	 */
	class Eb_Bp_Ajax_Handler {

		/**
		 * Edwiser bridge object.
		 *
		 * @var $edwiser_bridge Object refrance.
		 */
		protected $edwiser_bridge;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->edwiser_bridge = new \app\wisdmlabs\edwiserBridge\EdwiserBridge();
		}

		/**
		 * Function to handle the sync callback action.
		 */
		public function handle_cohort_synchronization_callback() {
			$moodle_function     = 'core_cohort_get_cohorts';
			$conn_helper         = Eb_Bp_Manage_Cohort::get_connection_helper();
			$response            = $conn_helper->connect_moodle_with_args_helper( $moodle_function, array( 'cohortids' => array() ) );
			$moodle_function     = 'core_cohort_get_cohort_members';
			$get_member_function = '';

			if ( 1 === $response['success'] ) {
				foreach ( $response['response_data'] as $cohort ) {
					$cohort_id = $cohort['id'];
					$response  = $conn_helper->connect_moodle_with_args_helper( $moodle_function, array( 'cohortids' => array( $cohort_id ) ) );
					if ( 1 === $response['success'] ) {
						foreach ( $response['response_data'] as $member ) {
							$user_id    = $member['userids'];
							$response   = $conn_helper->connect_moodle_with_args_helper(
								$get_member_function,
								array(
									'criteria' => array(
										'key'   => 'id',
										'value' => $user_id,
									),
								)
							);
							$user_array = $response['response_data']['users'];
							$user_email = $user_array[0]['email'];
							$user       = get_user_by( 'email', $user_email );
						}
					}
				}
			}
		}


		/**
		 * Provides the functionality to unenroll the student from the course.
		 */
		public function ebbp_action_manage_unenrol() {
			$response = 'Unsufficiant data to unenroll user';
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ebbp_admin_nonce' ) ) {
				return;
			}

			$post_data = $_POST;
			if ( isset( $post_data['mdl_cohort_id'] ) && ! empty( $post_data['mdl_cohort_id'] ) && isset( $post_data['user_id'] ) && ! empty( $post_data['user_id'] ) && isset( $post_data['enrolled_by'] ) && ! empty( $post_data['enrolled_by'] ) ) {
				$cohort_manager = new Eb_Bp_Cohort_Manage_User();
				$response       = $cohort_manager->delete_user_from_cohort( $post_data['user_id'], $post_data['mdl_cohort_id'], $post_data['enrolled_by'] );
				if ( $response['status'] ) {
					wp_send_json_success( 'OK' );
				} else {
					wp_send_json_error( $response['message'] );
				}
			} else {
				wp_send_json_error( $response );
			}
		}

		/**
		 * Function to show the cohort details.
		 */
		public function ebbp_cohort_details() {
			$responce = 'Invalid argument passed to ajax request';
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ebbp_admin_nonce' ) ) {
				return;
			}

			$post_data = $_POST;

			if ( isset( $post_data['enrolled_by'] ) && ! empty( $post_data['enrolled_by'] ) && isset( $post_data['mdl_cohort_id'] ) && ! empty( $post_data['mdl_cohort_id'] ) ) {
				global $wpdb;
				$group_mng_id    = $post_data['enrolled_by'];
				$mdl_cohort_id   = $post_data['mdl_cohort_id'];
				$tbl_cohort_info = $wpdb->prefix . 'bp_cohort_info';
				$stmt            = "select COURSES,NAME,COHORT_NAME from $tbl_cohort_info where COHORT_MANAGER='$group_mng_id' AND MDL_COHORT_ID='$mdl_cohort_id'";
				$results         = $wpdb->get_row( $stmt, ARRAY_A ); // @codingStandardsIgnoreLine.
				$course_ids      = maybe_unserialize( $results['COURSES'] );
				$cohort_name     = $results['NAME'];
				$courses         = '';
				foreach ( $course_ids as $course ) {
					$course   = get_post( $course );
					$courses .= '<li>' . get_the_title( $course ) . '</li>';
				}
				$table_name = $wpdb->prefix . 'moodle_enrollment';
				$stmt       = "select count(distinct user_id ) from $table_name where mdl_cohort_id='$mdl_cohort_id'";
				$members    = $wpdb->get_var( $stmt ); // @codingStandardsIgnoreLine.

				$company_name = get_user_meta( $group_mng_id, 'wdm_company', true );
				if ( ! $mdl_cohort_id || empty( trim( $company_name ) ) ) {
					$company_name = get_user_meta( $group_mng_id, 'billing_company', true );
				}
				$manager_name     = get_user_profile_url( $group_mng_id );
				$current_username = get_user_profile_url( $post_data['user_id'] );
				wp_send_json_success(
					array(
						'cohort_name' => $cohort_name,
						'companyName' => $company_name,
						'manager'     => $manager_name,
						'members'     => $members,
						'courses'     => $courses,
						'currentUser' => $current_username,
					)
				);
			} else {
				wp_send_json_error( $responce );
			}
		}

		/**
		 * Function to check if the user is enrolled in the course or cohort.
		 * Check if function is unused
		 *
		 * @param int $user_id user id.
		 * @param int $course_id Course id.
		 */
		public function check_if_user_is_enrolled_in_course( $user_id, $course_id ) {
			$post_data = $_POST; // @codingStandardsIgnoreLine.
			if ( isset( $post_data['user_id'] ) && ! empty( $post_data['user_id'] ) && isset( $post_data['cohort_name'] ) && ! empty( $post_data['cohort_name'] ) ) {
				global $wpdb;
				$table_name = $wpdb->prefix;
				$result     = $wpdb->get_col( $wpdb->prepare( "select cohort_name from {$table_name} where user_id = %d and course_id = %d", $user_id, $course_id ) ); // @codingStandardsIgnoreLine.
				if ( in_array( null, $result, true ) ) {
					return wp_json_encode( array( 'success' => 1 ) );
				}
				return wp_json_encode( array( 'success' => 0 ) );
			}
		}

		/**
		 * Function to show notice on unenrollment of user
		 */
		public function show_notice_on_manage_enrollment_page() {
			if ( isset( $_GET['unenroll'] ) ) { // @codingStandardsIgnoreLine.
				?>
				<div class="mucp-notices">
					<div class="notice notice-success is-dismissible">
						<p>
							<?php esc_attr_e( 'Unenrolled Successfully', 'edwiser-bridge-pro' ); ?>
						</p>
					</div>
				</div>
				<?php
				unset( $_GET['unenroll'] ); // @codingStandardsIgnoreLine.
			}
		}
	}
}

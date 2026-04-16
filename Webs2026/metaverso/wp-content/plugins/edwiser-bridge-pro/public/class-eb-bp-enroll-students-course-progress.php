<?php
/**
 * The file contains the user progress calculation functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\pb;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Eb_Bp_Enroll_Students_Course_Progress.
 */
class Eb_Bp_Enroll_Students_Course_Progress {

	/**
	 * Contains the functionality to get cohort course progress.
	 */
	public function get_cohort_course_progress() {
		global $wpdb;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wdm_eb_gp_mng_nonce' ) ) {
			return;
		}

		$post_data = wp_unslash( $_POST );
		if ( isset( $post_data['cohort_id'] ) && ! empty( $post_data['cohort_id'] ) && isset( $post_data['user_id'] ) && ! empty( $post_data['user_id'] ) ) {
			$mdl_cohort_id  = $post_data['cohort_id'];
			$user_id        = $post_data['user_id'];
			$table_name     = $wpdb->prefix . 'bp_cohort_info';
			$cohort_courses = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT COURSES FROM {$table_name} WHERE MDL_COHORT_ID = %d;", $mdl_cohort_id ) ) ); // @codingStandardsIgnoreLine
			if ( ! empty( $cohort_courses ) ) {
				$user_course_progress = $this->get_course_progress( $user_id );

				ob_start();
				?>
				<div id="ebbp_custom_field_dialog_wrap" title="<?php esc_attr_e( 'Course Progress', 'edwiser-bridge-pro' ); ?>">
					<table class="ebbp_custom_field_tbl">
						<thead class="ebbp_custom_field_tbl_thead">
							<tr>
								<th> <?php esc_attr_e( 'Course Name', 'edwiser-bridge-pro' ); ?> </th>
								<th> <?php esc_attr_e( 'Progress', 'edwiser-bridge-pro' ); ?></th>
							</tr>
						</thead>
						<tbody class="ebbp_custom_field_tbl_body">
						<?php
						foreach ( $cohort_courses as $value ) {
							$progress = isset( $user_course_progress[ $value ] ) ? $user_course_progress[ $value ] : '0%';
							?>
							<tr>
								<td><?php echo esc_html( get_the_title( $value ) ); ?></td>
								<td> 
									<progress id="file" value="<?php echo esc_attr( ceil( $progress ) ); ?>" max="100"> <?php echo esc_attr( ceil( $progress ) ); ?>%
									</progress> <?php echo esc_attr( ceil( $progress ) ); ?> %
								</td>
							</tr>
						<?php } ?>

						</tbody>
					</table>
				</div>
				<?php
			}
			$html = ob_get_clean();
			wp_send_json_success( $html );
		}
	}

	/**
	 * Function to get course progress.
	 *
	 * @param  int $user_id user id to get the course progress.
	 */
	public function get_course_progress( $user_id ) {

		global $wpdb;

		$mdl_user_id = get_user_meta( $user_id, 'moodle_user_id', true );

		if ( $mdl_user_id ) {
			$webservice_function = 'auth_edwiserbridge_get_course_progress';
			$request_data        = array( 'user_id' => $mdl_user_id ); // prepare request data array.

			$response = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->connection_helper()->connect_moodle_with_args_helper(
				$webservice_function,
				$request_data
			);

			$course_progress_array = array();

			if ( isset( $response['success'] ) && $response['success'] ) {
				foreach ( $response['response_data'] as $value ) {
					$course_id                           = $wpdb->get_var( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_value={$value->course_id} AND meta_key = 'moodle_course_id'" ); // @codingStandardsIgnoreLine
					$course_progress_array[ $course_id ] = $value->completion;
				}
			}

			return $course_progress_array;
		}
		return 0;
	}
}

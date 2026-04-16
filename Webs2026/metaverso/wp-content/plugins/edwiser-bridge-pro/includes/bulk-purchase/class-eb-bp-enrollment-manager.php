<?php
/**
 * Handles the Enrollment functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( '\app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Enrollment_Manager' ) ) {

	/**
	 * Class manages the user enrollment functionality.
	 */
	class Eb_Bp_Enrollment_Manager {

		/**
		 * Function to add the columns in the enrollment list tabel.
		 *
		 * @param array $col_members Array of the column name.
		 */
		public function add_columns_to_manage_enroll_table( $col_members ) {
			$col_members['cohort'] = __( 'Cohort', 'edwiser-bridge-pro' );
			return $col_members;
		}

		/**
		 * Function to get the table records.
		 *
		 * @param array $tbl_rec tabel records.
		 */
		public function manage_enrollment_table_data( $tbl_rec ) {
			global $wpdb;
			$tbl_rec = array();
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}moodle_enrollment" ) ); // @codingStandardsIgnoreLine
			foreach ( $results as $result ) {
				$row                  = array();
				$row['user_id']       = $result->user_id;
				$row['user']          = get_user_profile_url( $result->user_id );
				$row['course']        = '<a href="' . esc_url( get_permalink( $result->course_id ) ) . '">' . get_the_title( $result->course_id ) . '</a>';
				$row['enrolled_date'] = $result->time;
				$row['ID']            = $result->id;
				$row['rId']           = $result->id;
				$row['course_id']     = $result->course_id;

				if ( null !== $result->mdl_cohort_id ) {
					$row['manage'] = false;
					$str           = '<div><p>' . $this->get_cohort_disp_name( $result->mdl_cohort_id ) . '</p>
                         <lable class="ebbp-cohort-details-link" data-cohort-manager="' . $result->enrolled_by . '" data-mdl-cohort-id="' . $result->mdl_cohort_id . '" data-user-id="' . $result->user_id . '" data-record-id="' . $result->id . '">' . __( 'View Details', 'edwiser-bridge-pro' ) . '</lable></div>';
				} else {
					$row['manage'] = true;
					$str           = '---';
				}
				$row['cohort']      = $str;
				$row['enrolled_by'] = $result->enrolled_by;
				$tbl_rec[]          = $row;
			}

			return $tbl_rec;
		}

		/**
		 * Function to add the data in table column row.
		 *
		 * @param array  $row Manage enrollment tabel row.
		 * @param object $result Result Object.
		 * @param string $search_text Search text term.
		 */
		public function manage_enrollment_table_data_v2( $row, $result, $search_text ) {
			if ( null !== $result->mdl_cohort_id ) {
				$row['manage'] = false;
				$str           = '<div><p>' . $this->get_cohort_name( $result->mdl_cohort_id ) . '</p>
                         <lable class="ebbp-cohort-details-link" data-cohort-manager="' . $result->enrolled_by . '" data-mdl-cohort-id="' . $result->mdl_cohort_id . '" data-user-id="' . $result->user_id . '" data-record-id="' . $result->id . '">' . __( 'View Details', 'edwiser-bridge-pro' ) . '</lable></div>';
			} else {
				$row['manage'] = true;
				$str           = '---';
			}
			$row['cohort']      = $str;
			$row['enrolled_by'] = $result->enrolled_by;
			$row                = apply_filters( 'eb_manage_student_enrollment_bp_each_row', $row, $result );

			return $row;
		}

		/**
		 * Function to get the name of the cohort.
		 *
		 * @param int $mdl_cohort_id Cohort id.
		 */
		private function get_cohort_name( $mdl_cohort_id ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( "SELECT COHORT_NAME FROM {$wpdb->prefix}bp_cohort_info where mdl_cohort_id='%d'", $mdl_cohort_id ) ); // @codingStandardsIgnoreLine
		}

		/**
		 * Function to get the cohort display name.
		 *
		 * @param int $mdl_cohort_id Cohort id.
		 */
		private function get_cohort_disp_name( $mdl_cohort_id ) {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( "SELECT NAME FROM {$wpdb->prefix}bp_cohort_info where mdl_cohort_id='%d'", $mdl_cohort_id ) ); // @codingStandardsIgnoreLine
		}

		/**
		 * Function genrates the popup.
		 */
		public function add_pop_up_data() {
			?>
			<div class="mucp-cohort-details">
				<div id='mucp-cohort-details-dialog'>
					<table border="0">
						<tbody>
							<tr>
								<td class="eb-cohort-details-lable"> <?php esc_html_e( 'Group Manager :', 'edwiser-bridge-pro' ); ?></td>
								<td id="eb-manager"></td>
							</tr>
							<tr>
								<td class="eb-cohort-details-lable"><?php esc_html_e( 'Total Group Members :', 'edwiser-bridge-pro' ); ?></td>
								<td id="eb-members"></td>
							</tr>
							<tr>
								<td class="eb-cohort-details-lable manage-enrollment-table-courses"><?php esc_html_e( 'Associated Courses :', 'edwiser-bridge-pro' ); ?></td>
								<td></td>
							</tr>
							<tr>
								<td class="eb-cohort-details-lable"></td>
								<td id="eb-courses"></td>
							</tr>
						</tbody>
					</table>
					<hr>
					<table border="0">
						<tbody>
							<tr>
								<td class="eb-cohort-details-lable"><?php esc_html_e( 'User Name :', 'edwiser-bridge-pro' ); ?></td>
								<td id="eb-current-user"></td>
							</tr>
						</tbody>
					</table>
					<hr>
					<div class="cohort-details-notice">
						<p> <i class="dashicons dashicons-warning" aria-hidden="true"></i> <?php esc_html_e( '  Unenrolling users from group this will unenroll users from all the associated courses.', 'edwiser-bridge-pro' ); ?> </p>
					</div>
				</div>
			</div>
			<?php
		}
	}
}

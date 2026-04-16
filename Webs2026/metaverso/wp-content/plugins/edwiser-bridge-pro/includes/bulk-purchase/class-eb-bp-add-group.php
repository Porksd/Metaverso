<?php
/**
 * Class to handle actioin/putput related to adding new group.
 *
 * @link http://wisdmlabs.com
 * @since 2.3.8
 *
 * @package BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

if ( ! class_exists( '\app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Add_Group' ) ) {

	/**
	 * Add New Group page.
	 */
	class Eb_Bp_Add_Group {

		/**
		 * Output the HTML for the edit group page.
		 *
		 *  * @since 2.3.8
		 */
		public function output() {

			?>
			<div class="wdm_back_button">
				<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=eb_course&page=eb-manage-groups' ) ); ?>"><?php esc_attr_e( 'Back', 'edwiser-bridge-pro' ); ?></a>
			</div>
			<div class="wdm_manage_group_header">
				<h1><?php echo esc_html__( 'Create New Group', 'edwiser-bridge-pro' ); ?></h1>
			</div>
			<div id="wdm_eb_enroll_user_page" class="wdm_manage_group_body">
			<div id="wdm-eb-enroll-msg"> <span></span> <i class="dashicons dashicons-dismiss wdm_grp_update_msg_dismiss"></i></div>
			<div id="eb_group_info" class="eb_enroll_students_tab_cont_section">
				<div class="eb_tab_subsection_new_group">
					<div id="wdm_associated_courses_container">
						<div class = "wdm_enroll_subheading">
								<?php echo esc_attr__( 'Group Name* : ', 'edwiser-bridge-pro' ); ?>
						</div>
						<div id = "wdm_group_manager">
							<input class="eb-manage-group-input" id="cohort_name" type="text" value="" name="cohort_name" required>
						</div>
					</div>
					<div id="wdm_associated_courses_container">
						<div class = "wdm_enroll_subheading">
								<?php echo esc_attr__( 'Group Manager* : ', 'edwiser-bridge-pro' ); ?>
						</div>
						<div id = "wdm_group_manager">
							<select class="eb-manage-group-input" id="ebbp_search_users" name="cohort_manager" >
								<option value=""><?php esc_attr_e( 'Select Group Manager', 'edwiser-bridge-pro' ); ?></option>
								<?php
								$users = get_users();
								foreach ( $users as $user ) {
									?>
									<option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_attr( $user->display_name ); ?></option>
									<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div id="wdm-course-button" class="eb_tab_subsection_new_group">
					<div id="wdm_associated_courses_container">
						<div class = "wdm_enroll_subheading">
							<?php echo esc_attr__( 'Select Products : ', 'edwiser-bridge-pro' ); ?>
						</div>
						<div id = "wdm_associated_courses">
							<?php
							echo esc_attr__( 'No Products selected.', 'edwiser-bridge-pro' );
							?>
						</div>
					</div>
					<ul class="course-select">
						<li class="enroll-button-grid">
							<div>
								<button class="edit-group-page-button" id="add-group-add-product-button">
									<?php esc_attr_e( 'Add Product', 'edwiser-bridge-pro' ); ?>
								</button>
							</div>
						</li>
					</ul>
					<div id="loding-icon"></div>
				</div>
				<div>
					<button id="eb-create-new-group" class="button button-primary"><?php esc_attr_e( 'Create Group', 'edwiser-bridge-pro' ); ?></button>
				</div>
				<!-- Ajax returned popup data -->
				<div id="add-quantity-popup"></div>

				<div id="wdm-eb-enroll-msg"> <span></span> <i class="dashicons dashicons-dismiss wdm_grp_update_msg_dismiss"></i></div>
			</div>
			<?php
		}

		/**
		 * Get enrolled users details
		 *
		 * @since 2.3.8
		 */
		public function get_enrolled_users_details() {
			global $wpdb;
			$avail_seats    = 0;
			$tbl_name       = $wpdb->prefix . 'bp_cohort_info';
			$result         = $wpdb->get_results( $wpdb->prepare( "SELECT PRODUCTS, 	COHORT_MANAGER FROM {$tbl_name} WHERE mdl_cohort_id = %d", $this->mdl_cohort_id ) ); // @codingStandardsIgnoreLine
			$products       = maybe_unserialize( $result[0]->PRODUCTS );
			$cohort_manager = $result[0]->COHORT_MANAGER;

			$avail_seats = min( $products );
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
			$enrolled_users = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT `user_id` FROM `{$tbl_name}` WHERE `mdl_cohort_id` = '%d'", $this->mdl_cohort_id ) ); // @codingStandardsIgnoreLine
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
			<table id='eb-enrolled-user-admin'>
				<thead>
					<tr>
					<?php
					foreach ( $tbl_col as $tbl_header ) {
						echo '<th>' . wp_kses( $tbl_header, eb_bp_get_allowed_html_tags() ) . '</th>';
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
								'progress' => '<a> <span data-cohortid = ' . $this->mdl_cohort_id . ' data-userid = ' . $user->user_id . ' class="ebbp_course_progress">' . __( 'View Progress', 'edwiser-bridge-pro' ) . '</span> </a>',
								'actions'  => '<i title="' . __( 'Edit user.', 'edwiser-bridge-pro' ) . '" id="' . $user->user_id . '" class="dashicons dashicons-edit edit-enrolled-user"></i>
                                    <i title="' . __( 'Unenroll user from Group.', 'edwiser-bridge-pro' ) . '" id="' . $user->user_id . '" class="dashicons dashicons-trash bp-delete-enrolled-user"></i>',
							);
							$tbl_data  = apply_filters( 'eb_bp_enroll_user_tbl_data', $tbl_data, $this->mdl_cohort_id, $user->user_id );
							?>
							<tr class ="<?php echo esc_html( $user->user_id ); ?>">
							<?php
							// Here foreach is on table columns because table header should get displayed according to the array values.
							foreach ( array_keys( $tbl_col ) as $key ) {
								echo '<td>' . wp_kses( $tbl_data[ $key ], eb_bp_get_allowed_html_tags() ) . '</td>';
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
			$form     = ob_get_clean();
			$responce = array(
				'seats'          => $avail_seats,
				'enrolled_users' => count( $enrolled_users ),
				'html'           => $form,
			);
			return $responce;
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

	}
}

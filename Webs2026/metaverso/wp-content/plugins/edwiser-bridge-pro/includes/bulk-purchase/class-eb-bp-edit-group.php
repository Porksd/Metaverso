<?php
/**
 * Setup plugin menus in WP admin.
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

if ( ! class_exists( '\app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Edit_Group' ) ) {

	/**
	 * Edit Group page.
	 */
	class Eb_Bp_Edit_Group {

		/**
		 * Cohort id.
		 *
		 * @since 2.3.8
		 * @var int cohort id.
		 */
		private $mdl_cohort_id;

		/**
		 * Constructor.
		 *
		 * @since 2.3.8
		 *
		 * @param int $mdl_cohort_id cohort id.
		 */
		public function __construct( $mdl_cohort_id ) {
			$this->mdl_cohort_id = $mdl_cohort_id;
		}

		/**
		 * Output the HTML for the edit group page.
		 *
		 *  * @since 2.3.8
		 */
		public function output() {
			global $wpdb;
			$user       = wp_get_current_user();
			$tbl_name   = $wpdb->prefix . 'bp_cohort_info';
			$group_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_name} WHERE MDL_COHORT_ID = %d AND SYNC='1'", $this->mdl_cohort_id ), ARRAY_A ); // @codingStandardsIgnoreLine
			$result     = $group_data;
			$group_data = $group_data[0];
			?>
			<div class="wdm_back_button">
				<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=eb_course&page=eb-manage-groups' ) ); ?>"><?php esc_attr_e( 'Back', 'edwiser-bridge-pro' ); ?></a>
			</div>
			<div class="wdm_manage_group_header">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			</div>
			<div id="wdm_eb_enroll_user_page" class="wdm_manage_group_body">
			<div id="wdm-eb-enroll-msg"> <span></span> <i class="dashicons dashicons-dismiss wdm_grp_update_msg_dismiss"></i></div>
			<div class="eb_enroll_students_tab_cont_section">
				<div id="wdm_group_details" class="eb_enrolled_students_dialog">
					<div class="eb-enroll-student-tab-container">
						<div class="eb-enroll-student-tab-full-width eb-enroll-student-tab-active" data-section="eb_enroll_students">
							<i class="dashicons dashicons-edit" aria-hidden="true"></i>
							<?php esc_attr_e( 'Group Details', 'edwiser-bridge-pro' ); ?>
						</div>
					</div>
					<div class="eb_tab_subsection">
						<form>
							<div class="eb_edit_cohort_name_section">
								<div class="eb_edit_cohort_name_sub_section">
									<?php echo esc_attr__( 'Group Name : ', 'edwiser-bridge-pro' ); ?>
								</div>
								<div class="eb_edit_cohort_name_sub_section eb_edit_cohort_name_inp_sub_section">
									<input type="hidden" id="eb_mdl_cohort_id" value="<?php echo esc_attr( $this->mdl_cohort_id ); ?>">
									<input type="text" id="eb_inpt_edit_cohort_name" name="eb_inpt_edit_cohort_name" value="<?php echo esc_attr( $group_data['NAME'] ); ?>">
								</div>
								<div class="eb_edit_cohort_name_sub_section eb_edit_cohort_name_btn_sub_section">
									<button type="button" class="button button-primary" id="eb_inpt_edit_group_name_btn" name="eb_inpt_edit_group_name_btn"><?php echo esc_attr_e( 'Update', 'edwiser-bridge-pro' ); ?></button>
									<button class="button button-secondary" id="bp-delete-cohort"><?php esc_attr_e( 'Delete Group', 'edwiser-bridge-pro' ); ?></button>
								</div>
							</div>
						</form>
						<form >
							<div class="eb_edit_cohort_manager_section">
								<div class="eb_edit_cohort_name_sub_section">
									<?php echo esc_attr__( 'Group Manager : ', 'edwiser-bridge-pro' ); ?>
								</div>
								<div class="eb_edit_cohort_name_sub_section eb_edit_cohort_name_inp_sub_section">
									<input type="hidden" id="eb_cohort_manager" value="<?php echo esc_attr( $group_data['COHORT_MANAGER'] ); ?>">
									<select class="eb-manage-group-input" id="ebbp_search_users" name="cohort_manager" >
										<option value=""><?php esc_attr_e( 'Select Group Manager', 'edwiser-bridge-pro' ); ?></option>
										<?php
										$user_info = get_userdata( $group_data['COHORT_MANAGER'] );
										$users     = get_users();
										foreach ( $users as $user ) {
											?>
											<option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_attr( $user->display_name ); ?></option>
											<?php
										}
										?>
									</select>
								</div>
								<div class="eb_edit_cohort_name_sub_section eb_edit_cohort_name_btn_sub_section">
									<button type="button" class="edit-group-page-button button button-primary" id="eb_inpt_edit_cohort_manager_btn"><?php echo esc_attr_e( 'Update', 'edwiser-bridge-pro' ); ?></button>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div id="wdm_group_details" class="eb_enrolled_students_dialog">
					<div class="eb-enroll-student-tab-container">
						<div class="eb-enroll-student-tab-full-width eb-enroll-student-tab-active" data-section="eb_enroll_students">
							<i class="dashicons dashicons-playlist-video" aria-hidden="true"></i>
							<?php esc_attr_e( 'Productwise Associated Courses', 'edwiser-bridge-pro' ); ?>
						</div>
					</div>
					<div class="eb_tab_subsection">
						<div id="wdm_associated_courses_container">
							<div id="wdm-course-button">
								<ul>
									<li class="enroll-button-grid">
										<div>
											<button class="button button-primary edit-group-page-button" id="add-product-button">
												<?php esc_attr_e( 'Add Product', 'edwiser-bridge-pro' ); ?>
											</button>
										</div>
									</li>
									<li class="enroll-button-grid">
										<div>
											<button class="button button-secondary edit-group-page-button" id="remove-product-button">
												<?php esc_attr_e( 'Remove Product', 'edwiser-bridge-pro' ); ?>
											</button>
										</div>
									</li>
								</ul>
							</div>
							<div id = "wdm_associated_courses">
								<?php
								$enroll_users       = new \app\wisdmlabs\edwiserBridgePro\pb\Edwiser_Multiple_Users_Course_Enroll_Users();
								$accociated_courses = $enroll_users->wdm_enroll_user_course( $this->mdl_cohort_id );

								echo $accociated_courses; // @codingStandardsIgnoreLine
								?>
							</div>
						</div>
					</div>
				</div>
				<div id="ebbp_csv_users_progress_wrap" class="eb-lading-parent-wrap">
					<div class="ebbp_csv_users_progress">
						<span> <span id="ebbp_csv_processed_users_count"> 0 </span> / <span id="ebbp_csv_total_users_count"> </span> <?php esc_attr_e( '   Users processed.', 'edwiser-bridge-pro' ); ?> </span>
						<div id="ebbp_csv_users_progress_percent"> </div>
					</div> 
				</div>
				<div id="ebbp_csv_users_progress_wrap" class="eb-lading-parent-wrap">
					<div class="ebbp_csv_users_progress">
						<span> <span id="ebbp_csv_processed_users_count"> 0 </span> / <span id="ebbp_csv_total_users_count"> </span> <?php esc_attr_e( '   Users processed.', 'edwiser-bridge-pro' ); ?> </span>
						<div id="ebbp_csv_users_progress_percent"> </div>
					</div> 
				</div>
				<div id='wdm_eb_message'>
					<div class="wdm_select_course_msg">
						<i class='dashicons dashicons-dismiss wdm_select_course_msg_dismiss'></i>
						<lable class='wdm_enroll_warning_message_lable'>
							<?php
							esc_attr_e( 'Please select Group', 'edwiser-bridge-pro' );
							?>
					</div>
				</div>
				<div id="wdm-eb-enroll-msg"> <span></span> <i class="dashicons dashicons-dismiss wdm_grp_update_msg_dismiss"></i></div>
				<form name="wdm_eb_enroll_user" id ="wdm_eb_enroll_user" method="POST" enctype="multipart/form-data">
					<div>
						<!-- Div to add ajax returned content and show it in pop-up -->
						<div id="add-quantity-popup"></div>
					</div>

					<!-- Associated courses -->
					<div class="eb_enrolled_students_dialog">
						<div class="eb-enroll-student-tab-container">
							<div class="eb-enroll-student-tab-full-width eb-enroll-student-tab-active" data-section="eb_enroll_students">
								<i class="dashicons dashicons-admin-users" aria-hidden="true"></i>
								<?php esc_attr_e( 'Enrollment Details', 'edwiser-bridge-pro' ); ?>
							</div>
							<?php

							/**
							 * Data section of the div should match the id of the content div.
							 */
							do_action( 'eb_add_tab_on_enroll_students_page' );
							?>
						</div>
						<?php
						$group_details = $this->get_enrolled_users_details();
						?>
						<div class="eb-enroll-student-tab-content">
							<!-- enroll users section -->
							<div id="eb_enroll_students" class="eb_enroll_students_tab_cont_section">
								<div>
									<div id='wdm_avaliable_reg'>
										<div class = "wdm_seats">
											<span ><?php esc_attr_e( 'Enrolled Users : ', 'edwiser-bridge-pro' ); ?><span class = "wdm_seats_enrolled_users"> <?php echo esc_attr( $group_details['enrolled_users'] ); ?> </span></span>
											<div class="wdm_available_seats">
												<span><?php esc_attr_e( 'Available Seats :', 'edwiser-bridge-pro' ); ?></span>
												<input type="number" name="quantity" class="wdm_seats_available" value="<?php echo esc_attr( $group_details['seats'] ); ?>">
												<button id="eb-add-quantity-to-group" class="button button-primary edit-group-page-button"><?php esc_attr_e( 'Update', 'edwiser-bridge-pro' ); ?></button>
											</div>
										</div>
									</div>

									<div id="wdm_enroll_div">
										<div id="enroll-new-user-btn-div">
											<button id='enroll-new-user' class="edit-group-page-button">
												<?php esc_attr_e( 'Enroll User', 'edwiser-bridge-pro' ); ?>
											</button>

											<button id="enroll-multiple-users" class="edit-group-page-button">
												<?php esc_attr_e( 'Enroll Multiple Users', 'edwiser-bridge-pro' ); ?>
											</button>

										</div>

										<div id="wdm_eb_upload_csv" class="eb_hide">
											<div>
												<div>
													<input id="wdm_user_csv" name="wdm_user_csv" type="file" class="file" accept=".csv" data-show-preview="false" data-show-upload="true">
												</div>
												<div>
													<a id="wdm_csv_link" href="<?php echo esc_url( EB_PRO_PLUGIN_URL . 'public/upload_users_sample.csv' ); ?>">
														<?php esc_html_e( 'Download Sample CSV', 'edwiser-bridge-pro' ); ?>
													</a>
												</div>
											</div>
										</div>
										<div title='Enroll User' id='enroll-user-form-pop-up'></div>
									</div>
								</div>

								<!-- Enrolled users section -->
								<div>
									<!-- FROM 2.1.0 -->
									<div id="wdm_user_delete_msg"></div>

									<!-- LIST OF ALL ENROLLED USERS -->
									<div class='wdm_enrolled_users'>
										<?php
										echo $group_details['html']; // @codingStandardsIgnoreLine
										?>
									</div>
								</div>
							</div>
						</div>

						<?php

						/**
						 * Plese make sure id of the tab should match the data-section of the tab div.
						 */
						do_action( 'eb_add_tab_content_on_enroll_students_page' );
						?>
					</div>
				</form>
				<div title="<?php esc_attr_e( 'Enroll Users', 'edwiser-bridge-pro' ); ?>" id="enroll-user-form-csv"></div>
				<div id="enroll-user-form-pop-up">
					<div id="enroll_user-pop-up">
						<div id="enroll_user_form-msg"></div>
						<form  id="enroll_user-form" method="POST" enctype="multipart/form-data">
							<div class="enroll_user-row">
								<label><?php esc_attr_e( 'First name *', 'edwiser-bridge-pro' ); ?></label>
								<input class="wdm-enrol-form-input" id="wdm_enroll_fname" type='text' name='firstname[]' placeholder='<?php esc_attr_e( 'Enter First name', 'edwiser-bridge-pro' ); ?>' value="" required/>
							</div>
							<div class="enroll_user-row">
								<label><?php esc_attr_e( 'Last name *', 'edwiser-bridge-pro' ); ?></label>
								<input class="wdm-enrol-form-input" id="wdm_enroll_lname" type='text' name='lastname[]' placeholder='<?php esc_attr_e( 'Enter Last name', 'edwiser-bridge-pro' ); ?>' value="" required/>
							</div>
							<div class="enroll_user-row">
								<label><?php esc_attr_e( 'Email Address *', 'edwiser-bridge-pro' ); ?></label>
								<input class="wdm-enrol-form-input" id="wdm_enroll_email" type='email' name='email[]' placeholder='<?php esc_attr_e( 'Enter Email Address', 'edwiser-bridge-pro' ); ?>' value="" required/>
							</div>
							<input  id='enroll_user_course' name='edb_course_product_name' type='hidden'/>
						</form>
						<div id="popup-loding-icon" class="loader pop-up-loader"></div>
					</div>
				</div>
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

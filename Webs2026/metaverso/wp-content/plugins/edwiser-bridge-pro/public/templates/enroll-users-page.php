<?php
/**
 * Bulk Purchase Public Module
 * This class is responsible for SSO module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for SSO module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( is_user_logged_in() ) {
	global $wpdb;
	$user     = wp_get_current_user();
	$tbl_name = $wpdb->prefix . 'bp_cohort_info';
	$result   = $wpdb->get_results( $wpdb->prepare( "SELECT ID, NAME, COHORT_NAME, MDL_COHORT_ID, PRODUCTS FROM {$tbl_name} WHERE COHORT_MANAGER = %d AND SYNC='1'", $user->ID ), ARRAY_A ); // @codingStandardsIgnoreLine
	?>
	<div id="wdm_eb_enroll_user_page">
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
				<div>
					<div id="wdm-course-button">
							<div>
								<span class="wdm_eb_lable">
								<?php
								esc_attr_e( 'Select Group:', 'edwiser-bridge-pro' );
								?>
								</span>
								<select id="edb_course_product_name" name="edb_course_product_name">
									<option value="0">
										<?php esc_attr_e( 'Select Group', 'edwiser-bridge-pro' ); ?>
									</option>
									<?php
									foreach ( $result as $row ) {
										$products_qty = maybe_unserialize( $row['PRODUCTS'] );
										$products_qty = array_values( $products_qty );
										$products_qty = min( $products_qty );
										?>
										<option data-qty= "<?php echo esc_html( $products_qty ); ?>"  data-name="
															<?php
																echo ! empty( $row['NAME'] ) ? esc_html( $row['NAME'] ) : esc_html( str_replace( $user->user_login . '_', '', $row['COHORT_NAME'] ) );
															?>
										" value="<?php echo esc_html( $row['MDL_COHORT_ID'] ); ?>">
										<?php
											echo ! empty( $row['NAME'] ) ? esc_html( $row['NAME'] ) . '<span> (' . esc_html( $products_qty ) . ') </span>' : esc_html( str_replace( $user->user_login . '_', '', $row['COHORT_NAME'] ) ) . '<span> (' . esc_html( $products_qty ) . ') </span>';
										?>
										</option>
										<?php
									}
									?>
								</select>


									<ul class="course-select">
										<?php
										/**
										 * Add quatity and add view associated course.
										 *
										 * @since 1.1.0
										 */
										?>
										<li class="enroll-button-grid">
											<div>
												<button class="enroll-student-page-button" id="add-product-button">
													<?php esc_attr_e( 'Add Product', 'edwiser-bridge-pro' ); ?>
												</button>
											</div>
										</li>
										<li class="enroll-button-grid">
											<div>
												<button class="enroll-student-page-button" id="add-quantity-button">
													<?php esc_attr_e( 'Add Quantity', 'edwiser-bridge-pro' ); ?>
												</button>
											</div>
										</li>
										<li class="enroll-button-grid">
											<div>
												<button class="enroll-student-page-button" id="bp-delete-cohort">
													<?php esc_attr_e( 'Delete Group', 'edwiser-bridge-pro' ); ?>
												</button>
											</div>
										</li>
									</ul>
								<div id="loding-icon"></div>
							</div>
					</div>
				</div>
				<!-- Div to add ajax returned content and show it in pop-up -->
				<div id="add-quantity-popup"></div>
			</div>


			<!-- Associated courses -->
			<div id = "wdm_group_details">
				<div class="eb-enroll-student-tab-container">
					<div class="eb-enroll-student-tab eb-enroll-student-tab-active" data-section="eb_enroll_students">
						<i class="dashicons dashicons-admin-users" aria-hidden="true"></i>
						<?php esc_attr_e( 'Enrollment Details', 'edwiser-bridge-pro' ); ?>
					</div>

					<div class="eb-enroll-student-tab" data-section="eb_group_info">
						<i class="dashicons dashicons-edit" aria-hidden="true"></i>
						<?php esc_attr_e( 'Group Details', 'edwiser-bridge-pro' ); ?>
					</div>
					<?php

					/**
					 * Data section of the div should match the id of the content div.
					 */
					do_action( 'eb_add_tab_on_enroll_students_page' );
					?>
				</div>
				<div class="eb-enroll-student-tab-content">
					<!-- enroll users section -->
					<div id="eb_enroll_students" class="eb_enroll_students_tab_cont_section">
						<div>
							<div id='wdm_avaliable_reg'>
								<div class = "wdm_seats">
									<?php
									esc_attr_e( 'Enrolled Users ( Available Seats ) :', 'edwiser-bridge-pro' );
									?>
									<span class = "wdm_seats_enrolled_users"> 0 </span> ( <span class = "wdm_seats_available"> 0 </span> )
								</div>
							</div>

							<div id="wdm_enroll_div">
								<div id="enroll-new-user-btn-div">
									<button id='enroll-new-user'>
										<?php esc_attr_e( 'Enroll User', 'edwiser-bridge-pro' ); ?>
									</button>

									<button id="enroll-multiple-users">
										<?php esc_attr_e( 'Enroll Multiple Users', 'edwiser-bridge-pro' ); ?>
									</button>

								</div>
								<!-- <div id="wdm_eb_upload_csv" class="eb_hide">
									<input id="wdm_users_csv_input" name="wdm_users_csv_input" type="file" accept=".csv">
									<input name="wdm_user_csv_btn" id="wdm_user_csv_btn" type="button" value="Upload File">
									<a id="wdm_csv_link" href="<?php echo esc_html( EB_PRO_PLUGIN_URL ) . 'public/upload_users_sample.csv'; ?>">
										<?php esc_attr_e( 'Download Sample CSV', 'edwiser-bridge-pro' ); ?>
									</a>
								</div> -->

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
							<div class='wdm_enrolled_users'></div>
						</div>
					</div>

					<!-- edit cohort name section -->
					<div id="eb_group_info" class="eb_hidden_tab_content eb_enroll_students_tab_cont_section">
						<div>
							<div class="eb_tab_subsection">
								<form>
									<div class="eb_edit_cohort_name_section">
										<div class="eb_edit_cohort_name_sub_section">
											<?php echo esc_attr__( 'Group Name : ', 'edwiser-bridge-pro' ); ?>
										</div>
										<div class="eb_edit_cohort_name_sub_section eb_edit_cohort_name_inp_sub_section">
											<input type="text" id="eb_inpt_edit_cohort_name" name="eb_inpt_edit_cohort_name">
										</div>
										<div class="eb_edit_cohort_name_sub_section eb_edit_cohort_name_btn_sub_section">
											<button type="button" id="eb_inpt_edit_cohort_name_btn" name="eb_inpt_edit_cohort_name_btn"><?php echo esc_attr_e( 'Update', 'edwiser-bridge-pro' ); ?></button>
										</div>
									</div>
								</form>
							</div>
						</div>

						<!-- EB associated courses section-->
						<div>
							<div id = "wdm_associated_courses_container">
								<div class = "wdm_enroll_subheading">
									<?php echo esc_attr__( 'Productwise Associated Courses', 'edwiser-bridge-pro' ); ?>
								</div>
								<div id = "wdm_associated_courses">
								</div>
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
	</div>
	<?php
} else {
	/*
	 * Show Login Request Message if user is not logged in.
	 * @author Pandurang
	 * @since 1.0.1
	 */
	?>
	<div class="wdmebbp-wrapper-login-req alert alert-warning">
		<span>
			<?php esc_attr_e( 'Login required to enroll users!', 'edwiser-bridge-pro' ); ?>
		</span>
		<a class="btn btn-info" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
			<?php esc_attr_e( 'Sign in', 'edwiser-bridge-pro' ); ?>
		</a>
	</div>
	<?php
}

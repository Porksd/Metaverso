<?php
/**
 * Bulk Purchase Module
 * This class is responsible for Bulk Purchase module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for Bulk Purchase module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$post_data = wp_unslash( $_POST );
if ( ! wp_verify_nonce( $post_data['wdm_eb_user_csv_nonce_field'], 'wdm_eb_user_csv_nonce' ) ) {
	wp_send_json_error( __( 'Security check failed try reloading page', 'edwiser-bridge-pro' ) );
}

$csv_file         =  isset( $_FILES['wdm_users_csv_input']['tmp_name'] ) ? $_FILES['wdm_users_csv_input']['tmp_name'] : '' ;  // @codingStandardsIgnoreLine
$required_headers = array( 'First Name', 'Last Name', 'Email' );
$csv_f            = fopen( $csv_file, 'r' ); // @codingStandardsIgnoreLine
$csv_first_line   = fgets( $csv_f ); // get first line of csv file.
fclose( $csv_f ); // @codingStandardsIgnoreLine

$found_headers = str_getcsv( trim( $csv_first_line ), ',', '"' ); // parse to array.
// check the headers of file.

if ( $found_headers !== $required_headers ) {
	wp_send_json_error(
		__(
			'Invalid CSV file data please upload correct file or check the sample CSV for correct format',
			'edwiser-bridge-pro'
		)
	);
}

$getfile = fopen( $csv_file, 'r' ); // @codingStandardsIgnoreLine
if ( false !== $getfile ) {
	$data = fgetcsv( $getfile, 1000, ',' );
	// Check if the number of record is more than the products available.
	$cohort_id = wp_unslash( $post_data['mdl_cohort_id'] );

	global $wpdb;
	$tbl_name  = $wpdb->prefix . 'bp_cohort_info';
	$result    = $wpdb->get_var( $wpdb->prepare( "SELECT PRODUCTS FROM {$tbl_name} WHERE MDL_COHORT_ID = %d", $cohort_id ) ); // @codingStandardsIgnoreLine
	$products  = maybe_unserialize( $result );
	$min_qty   = @min( $products ); // @codingStandardsIgnoreLine
	$cuser_id  = get_current_user_id();
	$tbl_name  = $wpdb->prefix . 'moodle_enrollment';
	$fp        = file( $_FILES['wdm_users_csv_input']['tmp_name'], FILE_SKIP_EMPTY_LINES ); // @codingStandardsIgnoreLine
	$rec_count = count( $fp ) - 1;

	if ( $rec_count > $min_qty ) {
		wp_send_json_error( __( 'Insufficient quantity available please add more quantity or reduce CSV records', 'edwiser-bridge-pro' ) );
	}
	ob_start();
	?>
	<div id = 'wdm_csv_error_message'></div>
	<div class="eb_csv_enroll_tbl">
		<div class="eb_csv_tbl_header" style="display: flex;">
			<div class="eb_csv_tbl_column">
				<label  class='lbl_first_name'>
					<?php esc_attr_e( 'First Name ', 'edwiser-bridge-pro' ); ?>
				</label>
			</div>
			<div class="eb_csv_tbl_column">
				<label  class='lbl_last_name'>
					<?php esc_attr_e( 'Last name ', 'edwiser-bridge-pro' ); ?>
				</label>
			</div>
			<div class="eb_csv_tbl_column">
				<label  class='lbl_email'>
					<?php esc_attr_e( 'Email ID ', 'edwiser-bridge-pro' ); ?>
				</label>
			</div>
		</div>
		<div class="ebbp_csv_form_tbl_content">
	<?php

	while ( false !== ( $data = fgetcsv( $getfile, 1000, ',' ) ) ) { // @codingStandardsIgnoreLine
		$num    = count( $data );
		$result = $data;
		$str    = implode( ',', $result );
		$slice  = explode( ',', $str );

		do_action( 'get_user_data' );
		?>
		<div class='wdm_new_user'>
			<div class="eb_csv_tbl_column">
				<input type=text class='txt_fname' name='firstname[]' value="<?php echo esc_attr( $slice[0] ); ?>">
			</div>
			<div class="eb_csv_tbl_column">
				<input type=text class='txt_lname' name='lastname[]' value="<?php echo esc_attr( $slice[1] ); ?>">
			</div>
			<div class="eb_csv_tbl_column">
				<input type=text class='txt_email' name='email[]' value="<?php echo esc_html( $slice[2] ); ?>">
			</div>
			<div>
				<i id="1083" class="dashicons dashicons-trash wdm_remove_user"></i>
			</div>
		</div>
		<?php
		do_action( 'display_user_data' );
	}
	?>
		</div>
	</div>
	<?php

	$data = ob_get_clean();
	wp_send_json_success( $data );
}

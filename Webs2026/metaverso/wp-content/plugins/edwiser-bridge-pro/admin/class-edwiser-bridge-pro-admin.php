<?php

/**
 * The admin specific functionality of the plugin.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

use app\wisdmlabs\edwiserBridgePro\includes as inlucdes;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Edwiser_Bridge_Pro_Admin
{

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Modules data array.
	 * This array contains all the modules data.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      array    $modules_data    The array containing all the modules data.
	 */
	protected $modules_data;

	/**
	 * Instance of the class.
	 *
	 * @since 3.0.0
	 * @access   protected
	 * @var Edwiser_Bridge_Pro_Admin The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Main CustomFields Instance
	 *
	 * Ensures only one instance of Edwiser_Bridge_Pro is loaded or can be loaded.
	 *
	 * @since 3.0.0
	 * @static
	 * @return Edwiser_Bridge_Pro_Admin - Main instance
	 */
	public static function instance()
	{

		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function __construct()
	{
		global $eb_pro_plugin_data;
		$this->plugin_name  = $eb_pro_plugin_data['plugin_slug'];
		$this->version      = $eb_pro_plugin_data['plugin_version'];
		$this->modules_data = get_option('eb_pro_modules_data');

		add_action('admin_enqueue_scripts', array($this, 'eb_enqueue_update_modal_assets'));
		add_action('admin_footer', array($this, 'eb_output_update_modal_html'));
		add_action('wp_ajax_eb_dismiss_update_modal', array($this, 'eb_dismiss_update_modal'));
		add_action('wp_ajax_eb_update_enroll_students_page', array($this, 'eb_update_enroll_students_page'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles()
	{

		wp_enqueue_style(
			'eb-pro-admin-css',
			EB_PRO_PLUGIN_URL . 'admin/assets/css/edwiser-bridge-pro-admin.css',
			array(),
			$this->version,
			'all'
		);
		wp_enqueue_style(
			'eb-pro-datatable-css',
			EB_PRO_PLUGIN_URL . 'admin/assets/css/datatable.css',
			array(),
			$this->version,
			'all'
		);

		/**
		 * CSS specific to selective sync module.
		 */
		if (isset($this->modules_data['selective_sync']) && 'active' === $this->modules_data['selective_sync']) {
			wp_enqueue_style(
				'select-admin-css',
				EB_PRO_PLUGIN_URL . 'admin/assets/css/eb-pro-selective-sync-admin.css',
				array(),
				$this->version,
				'all'
			);
		}

		/**
		 * CSS specific to SSO module.
		 */
		if (isset($this->modules_data['sso']) && 'active' === $this->modules_data['sso']) {
			wp_enqueue_style(
				'sso-admin-css',
				EB_PRO_PLUGIN_URL . 'admin/assets/css/eb-pro-sso-admin.css',
				array(),
				$this->version
			);
		}

		/**
		 * CSS specific to Woo Integration module.
		 */
		if (isset($this->modules_data['woo_integration']) && 'active' === $this->modules_data['woo_integration']) {
			wp_enqueue_style(
				'woo-int-admin-css',
				EB_PRO_PLUGIN_URL . 'admin/assets/css/bridge-woocommerce-admin.css',
				array(),
				$this->version,
				'all'
			);
		}

		/**
		 * CSS specific to Bulk Purchase module.
		 */
		if (isset($this->modules_data['bulk_purchase']) && 'active' === $this->modules_data['bulk_purchase']) {
			wp_enqueue_style(
				'bulk-purchase-admin-css',
				EB_PRO_PLUGIN_URL . 'admin/assets/css/edwiser-multiple-users-course-purchase-admin.css',
				array(),
				$this->version,
				'all'
			);
			wp_enqueue_style(
				'bulk-purchase-admin-jquery-ui',
				EB_PRO_PLUGIN_URL . 'admin/assets/css/jquery-ui.min.css',
				array(),
				$this->version,
				'all'
			);
			// enqueue style from public folder.
			wp_enqueue_style(
				'bulk-purchase-admin-public',
				EB_PRO_PLUGIN_URL . 'public/assets/css/edwiser-multiple-users-course-purchase-public.css',
				array(),
				$this->version,
				'all'
			);

			wp_enqueue_style(
				'wdm_front_end_css',
				EB_PRO_PLUGIN_URL . 'public/assets/css/edwiser-frontend-style.css',
				array(),
				$this->version,
				'all'
			);

			if ('eb-manage-groups' === filter_input(INPUT_GET, 'page')) {
				wp_enqueue_style('wdm_bootstrap_css', EB_PRO_PLUGIN_URL . 'public/assets/css/bootstrap.min.css', array(), $this->version);

				wp_enqueue_style('bootstrap_file_input_min_css', EB_PRO_PLUGIN_URL . 'public/assets/css/fileinput.min.css', array(), '1.0.2', 'all');
			}
		}

		/**
		 * CSS specific to Custom Fields module.
		 */
		if (isset($this->modules_data['custom_fields']) && 'active' === $this->modules_data['custom_fields']) {
			wp_enqueue_style(
				'edwiser_custom_fields_admin',
				EB_PRO_PLUGIN_URL . 'admin/assets/css/edwiser-custom-field-admin.css',
				array(),
				$this->version,
				'all'
			);
			
			// Admin custom fields profile styling
			wp_enqueue_style(
				'eb_admin_custom_fields',
				EB_PRO_PLUGIN_URL . 'admin/assets/css/eb-admin-custom-fields.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * Common JS for all modules.
		 */
		wp_enqueue_script(
			'eb-pro-admin-js',
			EB_PRO_PLUGIN_URL . 'admin/assets/js/edwiser-bridge-pro-admin.js',
			array(
				'jquery',
			),
			$this->version,
			1
		);
		wp_enqueue_script(
			'eb-pro-datatable-js',
			EB_PRO_PLUGIN_URL . 'admin/assets/js/jquery.dataTables.js',
			array(
				'jquery',
			),
			$this->version,
			1
		);

		/**
		 * JS specific to selective sync module.
		 */
		if (isset($this->modules_data['selective_sync']) && 'active' === $this->modules_data['selective_sync']) {
			wp_enqueue_script(
				'columnfilter-datatable-js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/jquery.dataTables.columnFilter.js',
				array(
					'eb-pro-datatable-js',
				),
				$this->version,
				1
			);

			wp_register_script(
				'eb-ss-button-datatable-js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/dataTables_buttons_min.js',
				array(
					'eb-pro-datatable-js',
				),
				$this->version,
				1
			);

			wp_register_script(
				'eb-ss-buttons-html5-datatable-js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/buttons.html5.min.js',
				array(
					'eb-pro-datatable-js',
				),
				$this->version,
				1
			);

			wp_register_script(
				'eb-ss-button-print-datatable-js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/buttons.print.min.js',
				array(
					'eb-pro-datatable-js',
				),
				$this->version,
				1
			);

			wp_register_script(
				'select-admin-js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/eb-select-sync.js',
				array(
					'jquery',
					'eb-pro-datatable-js',
					'edwiserbridge',
					'columnfilter-datatable-js',
				),
				$this->version,
				1
			);
		}

		/**
		 * JS specific to SSO module.
		 */
		if (isset($this->modules_data['sso']) && 'active' === $this->modules_data['sso']) {
			wp_enqueue_script( // @codingStandardsIgnoreLine
				'eb-pro-sso-admin-js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/eb-pro-sso-admin.js',
				array('jquery'),
				$this->version
			);

			$nonce = wp_create_nonce('ebsso-verify-key');
			$data  = array(
				'ajaxurl'     => admin_url('admin-ajax.php'),
				'nonce'       => $nonce,
				'invalid_url' => __('Entered URL is invalid, Please check URL again.', 'edwiser-bridge-pro'),
				'empty_url'   => __('Please enter URL.', 'edwiser-bridge-pro'),
				'select_role' => __('Please select user role first.', 'edwiser-bridge-pro'),
			);
			wp_localize_script(
				'eb-pro-sso-admin-js',
				'ebssoAdSet',
				$data
			);

			// Showing alert message to only admin if secret key is not matched.
			if (is_user_logged_in() && current_user_can('manage_options')) {
				if (isset($_GET['wdm_moodle_error']) && 'wdm_moodle_error' === $_GET['wdm_moodle_error']) { // @codingStandardsIgnoreLine
					wp_enqueue_script( // @codingStandardsIgnoreLine
						'eb_sso_blockUI_js',
						EB_PRO_PLUGIN_URL . 'admin/assets/js/jquery.blockUI.js',
						array('jquery')
					);
					wp_register_script('eb_sso_moodle_js', false, array('jquery', 'eb_sso_blockUI_js')); // @codingStandardsIgnoreLine

					$data = array(
						'error_message' => __('Please set the same secret key on WordPress as well as on Moodle', 'edwiser-bridge-pro'),
					);
					wp_localize_script('eb_sso_moodle_js', 'eb_sso_data', $data);
					wp_enqueue_script('eb_sso_moodle_js', '', array('jquery', 'eb_sso_blockUI_js')); // @codingStandardsIgnoreLine
				}
			}
		}

		/**
		 * JS specific to Woo integration module.
		 */
		if (isset($this->modules_data['woo_integration']) && 'active' === $this->modules_data['woo_integration']) {
			wp_register_script( // @codingStandardsIgnoreLine
				'admin_product_js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/bridge-woocommerce-product.js',
				array('jquery'),
				$this->version
			);

			wp_localize_script(
				'admin_product_js',
				'adminProduct',
				array(
					'placeholder' => __('Select any course', 'edwiser-bridge-pro'),
				)
			);

			wp_enqueue_script(
				'woo-int-admin-js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/bridge-woocommerce-admin.js',
				array('jquery'),
				$this->version,
				false
			);

			wp_enqueue_script(
				'eb_wi_custom_fields',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/bridge-woocommerce-custom-fields.js',
				array('jquery', 'jquery-ui-dialog', 'jquery-ui-sortable'),
				$this->version,
				false
			);

			wp_localize_script(
				'eb_wi_custom_fields',
				'wi_custom_fields',
				array(
					'dialog_save_btn'              => __('Save Changes', 'edwiser-bridge-pro'),
					'dialog_cancel_btn'            => __('Cancel', 'edwiser-bridge-pro'),
					'dialog_option_value'          => __('Option Value', 'edwiser-bridge-pro'),
					'dialog_option_text'           => __('Option Text', 'edwiser-bridge-pro'),
					'dialog_field_name_validation' => __('Field name should be unique and not empty.', 'edwiser-bridge-pro'),
				)
			);

			wp_localize_script(
				'woo-int-admin-js',
				'adminStrings',
				array(
					'singleTrashWarning' => __('Some users are enrolled to this course. By trashing they will be unenrolled. Do you still want to continue?', 'edwiser-bridge-pro'),
					'bulkTrashWarning'   => __('Some users are enrolled in the selected courses. By trashing they will be unenrolled. Do you still want to continue?', 'edwiser-bridge-pro'),
				)
			);

			wp_register_script( // @codingStandardsIgnoreLine
				'admin_refund_js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/bridge-woocommerce-refund.js',
				array('jquery'),
				$this->version
			);
		}

		/**
		 * JS specific to Bulk Purchase module.
		 */
		if (isset($this->modules_data['bulk_purchase']) && 'active' === $this->modules_data['bulk_purchase']) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script(
				'ebbp_admin_js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/edwiser-multiple-users-course-purchase-admin.js',
				array('jquery'),
				$this->version,
				false
			);

			wp_localize_script(
				'ebbp_admin_js',
				'ebbpAdmin',
				array(
					'nonce_admin' => wp_create_nonce('ebbp_admin_nonce'),
				)
			);

			wp_enqueue_script(
				'ebbp_migration',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/edwiser-multiple-users-course-purchase-migrate.js',
				array('jquery'),
				$this->version,
				false
			);

			wp_register_script(
				'bp_admin_refund_js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/edwiser-multiple-users-course-purchase-refund.js',
				array('jquery'),
				$this->version,
				false
			);

			wp_localize_script(
				'bp_admin_refund_js',
				'ebbpRefund',
				array(
					'nonce_refund' => wp_create_nonce('ebbp_refund_nonce'),
				)
			);

			if ('eb-manage-groups' === filter_input(INPUT_GET, 'page')) {
				wp_enqueue_script('bootstrap_canvas_js', EB_PRO_PLUGIN_URL . 'public/assets/js/plugins/canvas-to-blob.min.js', array('jquery'), $this->version, false);

				wp_enqueue_script('bootstrap_fileinput_min_js', EB_PRO_PLUGIN_URL . 'public/assets/js/fileinput/fileinput.min.js', array('jquery'), $this->version, false);
			}

			wp_enqueue_script(
				'bp_manage_group_js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/edwiser-multiple-user-course-purchase-manage-group.js',
				array('jquery'),
				$this->version,
				false
			);

			$res = wp_localize_script(
				'bp_manage_group_js',
				'ebbpManageGroup',
				array(
					'addNewUser'           => __('Add New User', 'edwiser-bridge-pro'),
					'removeUser'           => __('Remove User', 'edwiser-bridge-pro'),
					'removeUserFromGroup'  => __('Remove User from Group?', 'edwiser-bridge-pro'),
					'removeUserConetnt'    => __('Are you sure you want to remove user from group ?', 'edwiser-bridge-pro'),
					'deleteCohort'         => __('Are you sure you want to delete this group ?', 'edwiser-bridge-pro'),
					'deleteCohortBtn'      => __('Delete Group', 'edwiser-bridge-pro'),
					'deleteCohortContent'  => __('This will unenroll all the users from group and also from the courses assigned to the group.', 'edwiser-bridge-pro'),
					'enroll'               => __('Enroll', 'edwiser-bridge-pro'),
					'enterFirstName'       => __('Enter First Name : * ', 'edwiser-bridge-pro'),
					'enterLastName'        => __('Enter Last name : * ', 'edwiser-bridge-pro'),
					'enterEmailName'       => __('Enter E-mail ID : * ', 'edwiser-bridge-pro'),
					'mandatoryMsg'         => __('All fields marked with * are mandatory.', 'edwiser-bridge-pro'),
					'slctValidFile'        => __('Please select a valid CSV file. Required headers are <b>First Name</b>, <b>Last Name</b>, <b>Username</b> and <b>Email</b>.', 'edwiser-bridge-pro'),
					'invalidEmailId'       => __('Invalid Email ID:', 'edwiser-bridge-pro'),
					'user'                 => __('user', 'edwiser-bridge-pro'),
					'youCanEnrollOnly'     => __('You can enroll only', 'edwiser-bridge-pro'),
					'uploadFileFirst'      => __('Please upload CSV file first.', 'edwiser-bridge-pro'),
					'wdm_user_import_file' => EB_PRO_PLUGIN_URL . 'public/edwiser-multiple-users-course-purchase-upload-csv.php',
					'ajax_url'             => admin_url() . 'admin-ajax.php',
					'remove_url'           => EB_PRO_PLUGIN_URL . 'admin/assets/public/images/Remove-icon.png',
					'edit_user'            => __('Update User Data', 'edwiser-bridge-pro'),
					'emptyTable'           => __('Sorry, No users Enrolled Yet', 'edwiser-bridge-pro'),
					'emptyTableProducts'   => __('Sorry, No products available', 'edwiser-bridge-pro'),
					'enterQuantity'        => __('Please enter quantity', 'edwiser-bridge-pro'),
					'associatedCourse'     => __('Associated Courses', 'edwiser-bridge-pro'),
					'enrollUser'           => __('Enroll User', 'edwiser-bridge-pro'),
					'enrollNewUser'        => __('Enroll New User', 'edwiser-bridge-pro'),
					'cancel'               => __('Cancel', 'edwiser-bridge-pro'),
					'addProduct'           => __('Add Product', 'edwiser-bridge-pro'),
					'removeProduct'        => __('Remove Product', 'edwiser-bridge-pro'),
					'addQuantity'          => __('Add Quantity', 'edwiser-bridge-pro'),
					'removeQuantity'       => __('Remove Quantity', 'edwiser-bridge-pro'),
					'ok'                   => __('OK', 'edwiser-bridge-pro'),
					'addQuantityInGrp'     => __('Add Quantity In Group', 'edwiser-bridge-pro'),
					'addNewProductsIn'     => __('Add New Products In Group', 'edwiser-bridge-pro'),
					'removeProductTitle'   => __('Remove Products From Group', 'edwiser-bridge-pro'),
					'removeQuantityTitle'  => __('Remove Quantity From Group', 'edwiser-bridge-pro'),
					'saveChanges'          => __('Save Changes', 'edwiser-bridge-pro'),
					'close'                => __('Close', 'edwiser-bridge-pro'),
					'insufficientQty'      => __('Insufficient Quantity. Please Add more quantity', 'edwiser-bridge-pro'),
					'select_action'        => __('Please select the action.', 'edwiser-bridge-pro'),
					'select_action_lbl'    => __('Select Action', 'edwiser-bridge-pro'),
					'select_delete_users'  => __('Please select user to delete', 'edwiser-bridge-pro'),
					'apply'                => __('Apply', 'edwiser-bridge-pro'),
					'error'                => __('Error', 'edwiser-bridge-pro'),
					'first'                => __('First', 'edwiser-bridge-pro'),
					'last'                 => __('Last', 'edwiser-bridge-pro'),
					'previous'             => __('Previous', 'edwiser-bridge-pro'),
					'next'                 => __('Next', 'edwiser-bridge-pro'),
					'remove'               => __('Remove', 'edwiser-bridge-pro'),
					'search'               => __('Search:', 'edwiser-bridge-pro'),
					'courseprogress'       => __('Course Progress:', 'edwiser-bridge-pro'),
					'infoEmpty'            => __('No entries to show', 'edwiser-bridge-pro'),
					'info'                 => __('Showing from', 'edwiser-bridge-pro') . ' _START_ ' . __(' to ', 'edwiser-bridge-pro') . '_END_ ' . __('from', 'edwiser-bridge-pro') . ' _TOTAL_',
					'nonce_csv_enroll'     => wp_create_nonce('wdm_eb_user_csv_nonce'),
					'nonce_gp_mng'         => wp_create_nonce('wdm_eb_gp_mng_nonce'),
					'nonce_bp_enroll'      => wp_create_nonce('wdm_ebbp_enroll_nonce'),
					'errorGroupName'       => __('Group Name is required', 'edwiser-bridge-pro'),
					'errorManagerName'     => __('Group Manager Name is required', 'edwiser-bridge-pro'),
					'errorNoProduct'       => __('Please select at least one product (Quantity may be zero)', 'edwiser-bridge-pro'),
					'productRemoveError'   => __('All Products can not be removed', 'edwiser-bridge-pro'),

				)
			);
		}

		/**
		 * JS specific to Custom Fields module.
		 */
		if (isset($this->modules_data['custom_fields']) && 'active' === $this->modules_data['custom_fields']) {
			wp_enqueue_script(
				'edwiser_custom_fields_admin',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/edwiser-custom-field-admin.js',
				array(
					'jquery',
					'jquery-ui-dialog',
					'jquery-ui-sortable',
				),
				$this->version,
				false
			);

			wp_localize_script(
				'edwiser_custom_fields_admin',
				'eb_custom_fields',
				array(
					'ajax_url'                     => admin_url('admin-ajax.php'),
					'nonce'                        => wp_create_nonce('eb_cf_dialog_nonce'),
					'dialog_save_btn'              => __('Save Changes', 'edwiser-bridge-pro'),
					'dialog_delete_btn'            => __('Delete', 'edwiser-bridge-pro'),
					'dialog_cancel_btn'            => __('Cancel', 'edwiser-bridge-pro'),
					'dialog_option_value'          => __('Option Value', 'edwiser-bridge-pro'),
					'dialog_option_text'           => __('Option Text', 'edwiser-bridge-pro'),
					'dialog_field_name_validation' => __('Field name should be unique and not empty.', 'edwiser-bridge-pro'),
					'dialog_option_validation'     => __('Option value and text should not be empty.', 'edwiser-bridge-pro'),
					'dialog_success_btn'           => __('Saved', 'edwiser-bridge-pro'),
				)
			);
		}
	}

	/**
	 * Add "Selective Sync" tab in course synchronization after "User"
	 *
	 * @param  array $section List of section in synchronize tab.
	 * @return  array $section Modified array with "Product" tab.
	 * @since 1.0.2
	 */
	public function multiple_course_synchronization_section($section)
	{
		$section = array_merge(
			array_slice($section, 0, 1),
			array('select_sync' => __('Selective Courses', 'edwiser-bridge-pro')),
			array_slice($section, 1, null)
		);

		return $section;
	}

	/**
	 * Add "Selective Sync" tab in settings.
	 *
	 * @param  array $settings  List of section.
	 * @return array $settings Modified array.
	 * @since 1.0.2
	 */
	public function add_selective_synch_tab($settings)
	{
		/*
		 * Class showing the settings page.
		 * @since    1.2.0
		 */
		$settings[] = include_once EB_PRO_PLUGIN_PATH . 'admin/settings/class-selective-synch-settings.php';
		return $settings;
	}

	/**
	 * Add "SSO" tab in settings.
	 *
	 * @param  array $settings  List of section.
	 * @return array $settings Modified array.
	 * @since 1.0.2
	 */
	public function sso_settings($settings)
	{
		/*
		 * Class showing the settings page.
		 * @since    1.2.0
		 */
		$settings[] = include EB_PRO_PLUGIN_PATH . '/admin/settings/class-sso-settings.php';
		return $settings;
	}

	/**
	 * Add "Woo Integration" tab in settings.
	 *
	 * @param  array $settings  List of section.
	 * @return array $settings Modified array.
	 * @since 1.0.2
	 */
	public function woo_int_settings($settings)
	{
		$settings[] = include EB_PRO_PLUGIN_PATH . 'admin/settings/class-bridge-woocommerce-settings.php';

		return $settings;
	}

	/**
	 * Add "Products" tab in "Synchronization" after "Course".
	 *
	 * @param array $section List of section in synchronize tab.
	 * @return $section array Modified array with "Product" tab
	 * @since 1.0.2
	 */
	public function woo_int_product_sync_section($section)
	{
		if (count($section) > 1) {
			$result = array_merge(
				array_slice($section, 0, 1),
				array('product_data' => __('Products', 'edwiser-bridge-pro')),
				array_slice($section, 1, null)
			);
		} else {
			$result = array('product_data' => __('Products', 'edwiser-bridge-pro'));
		}

		return $result;
	}

	/**
	 * Add "Templates" tab in Settings
	 * @param  array $settings List of section.
	 * @return array $settings Modified array.
	 */
	public function add_templates_tab($settings)
	{
		// $settings[] = include_once EB_PRO_PLUGIN_PATH . 'admin/settings/class-eb-pro-elementor-template-settings.php';
		return $settings;
	}

	/**
	 * Add fields in "Products" tab.
	 *
	 * @param array  $settings List of settings fields.
	 * @param string $current_section Gives current displayed section.
	 *
	 * @return $settings array Modified array with settings for Product section
	 * @since 1.0.2
	 */
	public function woo_int_product_sync_settings($settings, $current_section)
	{
		if ('product_data' === $current_section) {
			$settings = apply_filters(
				'bridge_woo_product_synchronization_settings',
				array(
					array(
						'title' => __('Synchronize Products', 'edwiser-bridge-pro'),
						'type'  => 'title',
						'id'    => 'product_synchronization_options',
					),
					array(
						'title'           => __('WooCommerce Synchronization Options', 'edwiser-bridge-pro'),
						'desc'            => __('Create courses as products.', 'edwiser-bridge-pro'),
						'id'              => 'bridge_woo_synchronize_product_create',
						'default'         => 'no',
						'type'            => 'checkbox',
						'checkboxgroup'   => 'start',
						'show_if_checked' => 'yes',
						'autoload'        => false,
					),
					array(
						'desc'            => __('Update courses as products.', 'edwiser-bridge-pro'),
						'id'              => 'bridge_woo_synchronize_product_update',
						'default'         => 'no',
						'type'            => 'checkbox',
						'checkboxgroup'   => '',
						'show_if_checked' => 'yes',
						'autoload'        => false,
					),
					array(
						'desc'            => __('Publish synchronized products.', 'edwiser-bridge-pro'),
						'id'              => 'bridge_woo_synchronize_product_publish',
						'default'         => 'no',
						'type'            => 'checkbox',
						'checkboxgroup'   => '',
						'show_if_checked' => 'yes',
						'autoload'        => false,
					),
					array(
						'desc'            => __('Synchronize categories.', 'edwiser-bridge-pro'),
						'id'              => 'bridge_woo_synchronize_product_categories',
						'default'         => 'no',
						'type'            => 'checkbox',
						'checkboxgroup'   => '',
						'show_if_checked' => 'yes',
						'autoload'        => false,
					),
					array(
						'title'    => '',
						'desc'     => '',
						'id'       => 'bridge_woo_synchronize_product_button',
						'default'  => 'Start Synchronization',
						'type'     => 'button',
						'desc_tip' => false,
						'class'    => 'button secondary',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'product_synchronization_options',
					),
				)
			);

			// Enqueue Script.

			$nonce = wp_create_nonce('check_product_sync_action');

			wp_enqueue_script('synchronization_handler', EB_PRO_PLUGIN_URL . 'admin/assets/js/bridge-woocommerce-synchronize.js', array('jquery'), $this->version, false);

			wp_localize_script(
				'synchronization_handler',
				'bridge_woo_product_obj',
				array(
					'product_sync_nonce'          => $nonce,
					'admin_ajax_path'             => admin_url('admin-ajax.php'),
					'alt_text'                    => __('Loading...', 'edwiser-bridge-pro'),
					'select_least_option_message' => __('Please select proper options.', 'edwiser-bridge-pro'),
				)
			);
		}

		return $settings;
	}

	/**
	 * Unenroll check status.
	 */
	public function unenrol_check_status()
	{
		check_ajax_referer('wi_refund_unenrol', 'security');
		$checked  = isset($_POST['unenrol']) && 'checked' === $_POST['unenrol'] ? 'checked' : '';
		$order_id = absint($_POST['order_id']); // @codingStandardsIgnoreLine

		$order = wc_get_order($order_id);
		$order->update_meta_data('wi_refund_checked', $checked);
		$order->save();

		$response_data['status'] = 'updated';
		wp_send_json_success($response_data);
	}

	/**
	 * Unenroll update html.
	 */
	public function unenrol_update_html()
	{
		check_ajax_referer('wi_refund_unenrol', 'security');

		$order_id = absint($_POST['order_id']); // @codingStandardsIgnoreLine

		$order                    = wc_get_order($order_id);
		$is_processed             = $order->get_meta('_is_processed', true);
		$response_data['display'] = empty($is_processed) ? 'false' : 'true';

		wp_send_json_success($response_data);
	}

	/**
	 * Html content for refund.
	 *
	 * @param object $order order object.
	 */
	public function refund_html_content($order)
	{
		$enrolled_courses = array();
		$order_id         = $order->get_id();
		$user_id          = $order->get_user_id();

		$is_for_someone_else = $order->get_meta('_order_for_someone_else', true);
		if ('yes' === $is_for_someone_else) {
			$user_email = $order->get_meta('_recipient_email', true);
			$user       = get_user_by('email', $user_email);
			$user_id    = $user->ID;
		}

		$order_manager = new inlucdes\wooInt\Bridge_Woocommerce_Order_Manager($this->plugin_name, $this->version);

		$courses = $order_manager->get_moodle_course_ids_for_order($order);

		foreach ((array) $courses as $course_id) {
			if (\app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->enrollmentManager()->userHasCourseAccess($user_id, $course_id)) {
				$enrolled_courses[] = $course_id;
			}
		}

		// The order does not contain courses in which the user is enrolled.
		if (! count($enrolled_courses)) {
			return;
		}

		$order->update_meta_data('wi_refund_checked', '');
		$order->save();
		$checked = $order->get_meta('wi_refund_checked', true);

		ob_start();
?>
		<div class="wi-refund-wrapper">
			<table class="wc-order-totals">
				<tr title="<?php esc_attr_e('You cannot rollback this action!', 'edwiser-bridge-pro'); ?>">
					<td class="label">
						<label for="wi_unenrol"><?php esc_attr_e('Unenroll from purchased courses?', 'edwiser-bridge-pro'); ?></label>
					</td>
					<td class="total">
						<input type="checkbox" class="text" id="wi_unenrol" name="wi_unenrol" <?php echo esc_attr($checked); ?> />
						<div class="clear"></div>
					</td>
				</tr>
			</table>
			<input type="hidden" id="wi_order_id" name="wi_order_id" value="<?php echo esc_attr($order_id); ?>" />
			<?php wp_nonce_field('wi_refund_unenrol', 'wi_refund_unenrol'); ?>
			<div class="clear"></div>
		</div>
	<?php
		$html = ob_get_clean();

		wp_localize_script(
			'admin_refund_js',
			'wiRefund',
			array(
				'order' => $order,
				'html'  => $html,
			)
		);

		wp_enqueue_script('admin_refund_js');
	}

	/**
	 * Order refunded.
	 *
	 * @param int $order_id order id.
	 * @param int $refund_id refund id.
	 */
	public function order_refunded($order_id, $refund_id)
	{
		$order    = wc_get_order($order_id);

		$order_manager = new inlucdes\wooInt\Bridge_Woocommerce_Order_Manager($this->plugin_name, $this->version);
		$courses       = $order_manager->get_moodle_course_ids_for_order($order);

		// Does not contain course product.
		if (! count($courses)) {
			return;
		}
		$checked = $order->get_meta('wi_refund_checked', true);
		if ('checked' === $checked) {
			$order_manager->handle_order_cancel($order_id);
			wp_localize_script(
				'admin_refund_js',
				'wiRefunded',
				array('display' => false)
			);
		}

		do_action('wooint_order_refunded', $order_id, $refund_id, $checked, $courses);
	}

	/**
	 * Function to add contains enrolment class to trash action
	 *
	 * @param array  $actions actions array.
	 * @param object $post post object.
	 */
	public function add_contains_enrolment($actions, $post)
	{
		if ('eb_course' === $post->post_type && isset($actions['trash'])) {
			global $wpdb;
			$enrols = $wpdb->get_row("SELECT user_id FROM {$wpdb->prefix}moodle_enrollment WHERE course_id={$post->ID}", ARRAY_A); // @codingStandardsIgnoreLine

			if (isset($enrols) && is_array($enrols) && count($enrols)) {
				$actions['trash'] = str_replace('submitdelete', 'submitdelete contains_enrolment', $actions['trash']);
			}
		}
		return $actions;
	}

	/**
	 * Function to update enable redirect to yes by default on init
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function update_settings()
	{
		$setting_woo_integration = get_option('eb_woo_int_settings', array());
		$setting_keys            = array_keys($setting_woo_integration);
		if (! in_array('wi_enable_redirect', $setting_keys, true)) {
			$setting_woo_integration['wi_enable_redirect'] = 'yes';
		}

		if (! in_array('wi_enable_asso_courses', $setting_keys, true)) {
			$setting_woo_integration['wi_enable_asso_courses'] = 'yes';
		}

		if (! in_array('wi_on_subscription_expiration', $setting_keys, true)) {
			$setting_woo_integration['wi_on_subscription_expiration'] = 'do-nothing';
		}

		if (! in_array('wi_on_membership_expired', $setting_keys, true)) {
			$setting_woo_integration['wi_on_membership_expired'] = 'do-nothing';
		}

		if (! in_array('wi_on_membership_cancelled', $setting_keys, true)) {
			$setting_woo_integration['wi_on_membership_cancelled'] = 'do-nothing';
		}

		update_option('eb_woo_int_settings', $setting_woo_integration);
	}

	/**
	 * Bulk purchase settings.
	 *
	 * @since 1.1.0
	 * @param array $settings The array of the settings.
	 */
	public function eb_general_settings($settings)
	{
		if (is_array($settings)) {
			$last = $settings[count($settings) - 1];
			unset($settings[count($settings) - 1]);

			$mucp_settings[] = array(
				'type' => 'sectionend',
				'id'   => 'refund_options',
			);

			$mucp_settings[] = array(
				'title' => __('Edwiser Group Purchase', 'edwiser-bridge-pro'),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'edwiser_group_purchase',
			);

			$mucp_settings[] = array(
				'title'   => __('Enroll Students Page', 'edwiser-bridge-pro'),
				'desc'    => '<br />' . __('Select the page having shortcode [bridge_woo_enroll_users] or block.', 'edwiser-bridge-pro'),
				'id'      => 'mucp_group_enrol_page_id',
				'type'    => 'single_select_page',
				'default' => '',
				'css'     => 'min-width:300px;',
				'args'    => array(
					'show_option_none'  => __('- Select a page -', 'edwiser-bridge-pro'),
					'option_none_value' => '',
				),
			);
			$mucp_settings[] = array(
				'title'   => __('Group Purchase Label', 'edwiser-bridge-pro'),
				'desc'    => '<br />' . __('Group purchase checkbox label on woocommerce single product page', 'edwiser-bridge-pro'),
				'id'      => 'mucp_group_pur_lbl',
				'type'    => 'text',
				'default' => __('Enable Group Purchase', 'edwiser-bridge-pro'),
				'css'     => 'min-width:300px;',
			);

			$mucp_settings[] = array(
				'title'    => __('Group Manager Role', 'edwiser-bridge-pro'),
				'desc'     => __('This will assign Non Editing Teacher role to Group purchaser only If he enroll himself in the group. ( Role assignment will happen only on the WordPress side and not on Moodle. )', 'edwiser-bridge-pro'),
				'id'       => 'mucp_group_manager_role',
				'default'  => 'no',
				'type'     => 'checkbox',
				'autoload' => true,
			);

			$mucp_settings[] = array(
				'type' => 'sectionend',
				'id'   => 'edwiser_group_purchase',
			);

			$mucp_settings = (array) apply_filters('mucp_settings', $mucp_settings);

			foreach ($mucp_settings as $the_setting) {
				$settings[] = $the_setting;
			}
			$settings[] = $last;
		}
		return $settings;
	}


	/**
	 * Functionality to show shortcodes description on the edwiser admin settings
	 */
	public function add_shortcode_desc()
	{
		ob_start();
	?>
		<div class='eb-shortcode-doc-wpra'>
			<h3> <?php esc_attr_e('Bulk Purchase Shortcode Options', 'edwiser-bridge-pro'); ?></h3>
			<div class='eb-shortcode-doc'>
				<h4>[bridge_woo_enroll_users]</h4>
				<div class='eb-shortcode-doc-desc'>
					<p>
						<?php esc_attr_e('This shortcode shows all groups purchased by a user and also the users enrolled in it. This page also provides you to change cohort name and see all the associated courses to the group.', 'edwiser-bridge-pro'); ?>
					</p>
				</div>
			</div>
		</div>
<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue custom modal assets if update modal should be shown
	 */
	public function eb_enqueue_update_modal_assets()
	{
		if (is_admin() && get_option('eb_pro_show_enroll_students_update_modal')) {
			wp_enqueue_style(
				'eb-pro-update-modal-css',
				EB_PRO_PLUGIN_URL . 'admin/assets/css/eb-pro-update-modal.css',
				array(),
				$this->version
			);
			wp_enqueue_script(
				'eb-pro-update-modal-js',
				EB_PRO_PLUGIN_URL . 'admin/assets/js/eb-pro-update-modal.js',
				array('jquery'),
				$this->version,
				true
			);
			wp_localize_script(
				'eb-pro-update-modal-js',
				'EBProUpdateModal',
				array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce'    => wp_create_nonce('eb_pro_update_modal_nonce')
				)
			);
		}
	}

	/**
	 * Output the update modal HTML in the admin footer if needed
	 */
	public function eb_output_update_modal_html()
	{
		static $modal_shown = false;
		if ($modal_shown) {
			return;
		}
		$modal_shown = true;
		include plugin_dir_path(__FILE__) . 'partials/eb-pro-update-modal.php';
	}

	/**
	 * AJAX handler to clear the update modal transient
	 */
	public function eb_dismiss_update_modal()
	{
		check_ajax_referer('eb_pro_update_modal_nonce', 'nonce');
		delete_option('eb_pro_show_enroll_students_update_modal');
		wp_send_json_success();
	}

	public function eb_update_enroll_students_page()
	{
		check_ajax_referer('eb_pro_update_modal_nonce', 'nonce');

		$eb_general = get_option('eb_general');
		$woo_gutenberg_pages = get_option('eb_woo_gutenberg_pages', array());

		$eb_general['mucp_group_enrol_page_id'] = $woo_gutenberg_pages['eb_pro_enroll_students_page_id'];;
		update_option('eb_general', $eb_general);

		delete_option('eb_pro_show_enroll_students_update_modal');
		wp_send_json_success();
	}
}

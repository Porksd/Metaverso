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

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

use app\wisdmlabs\edwiserBridgePro\admin as admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Eb_Pro_Bulk_Purchase {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      Eb_Pro_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

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
	 * Instance of the class.
	 *
	 * @since 3.0.0
	 * @access   protected
	 * @var Eb_Pro_Bulk_Purchase The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Main CustomFields Instance
	 *
	 * Ensures only one instance of Edwiser_Bridge_Pro is loaded or can be loaded.
	 *
	 * @since 3.0.0
	 * @static
	 * @return Eb_Pro_Bulk_Purchase - Main instance
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
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
	 * @since    1.0.0
	 * @param    string $loader The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 */
	public function __construct( $loader ) {
		global $eb_pro_plugin_data;
		$this->plugin_name = $eb_pro_plugin_data['plugin_slug'];
		$this->version     = $eb_pro_plugin_data['plugin_version'];
		$this->loader      = $loader;
		$this->define_constants();
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_email_hooks();
		$this->define_system_hooks();
	}

	/**
	 * Define the constants for the plugin.
	 *
	 * @since    3.0.0
	 */
	private function define_constants() {

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Files are included on the basis of which modules are enabled.
	 * Create an instance of the loader which will be used to register the hooks.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// If user is not admin then load frontend dependencies.
		if ( ! is_admin() ) {
			$this->load_frontend_dependencies();
		}

		// Email template manager.
		require_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-email-template-manager.php';
		// Cohort manager functoinality.
		require_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-manage-cohort.php';
		// Migraton functionality for versoin 1.0.2 to 2.0.0.
		require_once EB_PRO_PLUGIN_PATH . 'public/class-edwiser-multiple-users-course-enroll-users.php';

		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/eb-bp-functions.php';
		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-admin-notices.php';

		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-user-manager.php';

		new \app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_User_Manager();

		/**
		 * Class resopnsible for the manage enrollment table modifications.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-enrollment-manager.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'admin/class-edwiser-bulk-purchase-product-settings.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'public/class-edwiser-multiple-users-course-purchase-public.php';

		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-self-enroll.php';

		include_once 'class-eb-bp-ajax-handler.php';

		include_once 'class-eb-bp-manage-cohort.php';
		include_once 'class-eb-bp-cohort-manage-user.php';

		include_once 'emails/class-eb-bp-emailer.php';

		include_once EB_PRO_PLUGIN_PATH . 'admin/class-eb-bp-users-refund-manager.php';

		include_once EB_PRO_PLUGIN_PATH . 'public/class-eb-bp-enroll-students-course-progress.php';

		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-manage-groups.php';
		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-group-table.php';
		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-edit-group.php';
		include_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-bp-add-group.php';
	}

	/**
	 * Load the required frontend dependencies for this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_frontend_dependencies() {
		/**
		 * Tha classes responsible for defining shortcodes & templates.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'public/class-edwiser-enroll-multiple-user-shortcode.php';
		include_once EB_PRO_PLUGIN_PATH . 'public/shortcodes/class-eb-shortcode-enroll-users.php';
		include_once EB_PRO_PLUGIN_PATH . 'public/class-edwiser-multiple-users-course-enroll-users.php';
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new admin\Edwiser_Bridge_Pro_Admin();

		$refund_manager = new admin\Eb_Bp_Users_Refund_Manager();
		$this->loader->add_action( 'woocommerce_order_item_add_line_buttons', $refund_manager, 'refund_html_content' );
		$this->loader->add_action( 'wp_ajax_bp_save_refund_data', $refund_manager, 'save_refund_data' );
		$this->loader->add_action( 'woocommerce_order_refunded', $refund_manager, 'refund_handler', 10, 2 );

		/**
		 * Class responsible for the modification in the manage enrollment table.
		 */
		$mng_enroll = new \app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Enrollment_Manager();

		/**
		 * Filter to add more columns to the wp list table of manage enrollment.
		 */
		$this->loader->add_filter( 'edwiser_add_colomn_to_manage_enrollment', $mng_enroll, 'add_columns_to_manage_enroll_table', 10 );

		$eb_version = get_option( 'eb_current_version' );

		// If Eb version 1.4.7 then use this otherwise use other hook.
		if ( version_compare( $eb_version, '1.4.7' ) <= 0 ) {
			$this->loader->add_filter( 'eb_manage_student_enrollment_table_data', $mng_enroll, 'manage_enrollment_table_data', 10 );
		} else {
			$this->loader->add_filter( 'eb_manage_student_enrollment_each_row', $mng_enroll, 'manage_enrollment_table_data_v2', 10, 3 );
		}

		/**
		 * Class object to handle the ajax callback
		 *
		 * @since 1.2.0
		 */
		$admin_ajax_init = new Eb_Bp_Ajax_Handler();

		/**
		 * Cohort class object to handle the cohort callbacks.
		 *
		 * @since 1.2.0
		 */
		$cohort_manager = new Eb_Bp_Manage_Cohort();

		/**
		 * Action to enque style and JS
		 *
		 * @since 1.0.0
		 */
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		/*
		 * Functionality to show shortcodes description on the edwiser admin settings
		 */
		$this->loader->add_action( 'eb_after_shortcode_doc', $plugin_admin, 'add_shortcode_desc' );
		/**
		 * Action to handle the user unenrollment
		 *
		 * @since 1.2.0
		 */
		$this->loader->add_action( 'wp_ajax_mucp_unenrol_user', $admin_ajax_init, 'ebbp_action_manage_unenrol' );

		/**
		 * Action to show cohort details
		 *
		 * @since 1.2.0
		 */
		$this->loader->add_action( 'wp_ajax_mucp_cohort_details', $admin_ajax_init, 'ebbp_cohort_details' );

		/**
		 * Action to make the company filed compalsoty and save the company details on the woocomerce checkout.
		 *
		 * @since 1.2.0
		 */
		$this->loader->add_action( 'user_register', $cohort_manager, 'update_user_profile' );
		/**
		 * Action to add the general setting option in the EB settings page
		 *
		 * @since 1.2.0
		 */
		$this->loader->add_filter( 'eb_general_settings', $plugin_admin, 'eb_general_settings', 111 );

		/**
		 * Action to add the email template and tempalate constants.
		 *
		 * @since 1.2.0
		 */
		$email_tmpl_manag = new Eb_Bp_Email_Template_Manager();
		$this->loader->add_filter( 'eb_email_templates_list', $email_tmpl_manag, 'eb_templates_list', 111 );
		$this->loader->add_filter( 'eb_email_template_constant', $email_tmpl_manag, 'eb_templates_constants', 111 );
		$this->loader->add_filter( 'eb_emailtmpl_content_before', $email_tmpl_manag, 'email_template_parser', 110 );

		/*
		 * Backend product settings
		 */
		$product_settings_handler = new admin\Edwiser_Bulk_Purchase_Product_Settings();
		$this->loader->add_action( 'wdm_display_fields', $product_settings_handler, 'wdm_display_group_purchase_fields', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $product_settings_handler, 'wdm_save_group_purchase_field', 20, 2 );
		$this->loader->add_action( 'save_post', $product_settings_handler, 'wdm_save_group_purchase_field', 20 );

		/**
		 * Admin menu.
		 */
		$eb_bp_menu = new admin\Eb_Pro_Menu();
		$this->loader->add_action( 'admin_menu', $eb_bp_menu, 'eb_bp_menu' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new \app\wisdmlabs\edwiserBridgePro\pb\Edwiser_Multiple_Users_Course_Purchase_Public(
			$this->plugin_name,
			$this->version
		);

		$user_enrollment_manager = new \app\wisdmlabs\edwiserBridgePro\pb\Edwiser_Multiple_Users_Course_Enroll_Users();

		/*
		* |------------------------------------------
		* | enroll-students page ajax calls
		* |------------------------------------------
		*/

		$this->loader->add_action( 'wp_ajax_get_user_bulk_course_details', $user_enrollment_manager, 'wdm_enrolled_users' );
		$this->loader->add_action( 'wp_ajax_get_enrol_user_details', $user_enrollment_manager, 'wdm_get_enroll_user_details' );
		$this->loader->add_action( 'wp_ajax_get_enrol_user_course', $user_enrollment_manager, 'wdm_enroll_user_course' );
		$this->loader->add_action( 'wp_ajax_edit_user', $user_enrollment_manager, 'wdm_bp_edit_user' );
		$this->loader->add_action( 'wp_ajax_ebbp_add_to_cart', $user_enrollment_manager, 'wdm_add_to_cart' );
		$this->loader->add_action( 'wp_ajax_ebbp_add_quantity', $user_enrollment_manager, 'wdm_add_more_quantity' );
		$this->loader->add_action( 'wp_ajax_ebbp_add_new_product', $user_enrollment_manager, 'wdm_add_new_product_to_group' );
		$this->loader->add_action( 'wp_ajax_ebbp_edit_cohort_name', $user_enrollment_manager, 'eb_edit_cohort_name_from_cohort_id' );
		$this->loader->add_action( 'wp_ajax_bp_delete_enrolled_user', $user_enrollment_manager, 'bp_delete_single_enrolled_user' );
		$this->loader->add_action( 'wp_ajax_bp_delete_multiple_enrolled_user', $user_enrollment_manager, 'bp_delete_multiple_enrolled_user' );

		$this->loader->add_action( 'wp_ajax_bp_delete_cohort', $user_enrollment_manager, 'bp_delete_cohort_from_frontend' );

		/**
		 * Manage group page ajax calls.
		 */
		$this->loader->add_action( 'wp_ajax_ebbp_manage_group_add_product', $user_enrollment_manager, 'wdm_manage_group_add_product' );
		$this->loader->add_action( 'wp_ajax_ebbp_manage_group_add_quantity', $user_enrollment_manager, 'wdm_manage_group_add_quantity' );
		$this->loader->add_action( 'wp_ajax_ebbp_manage_group_remove_product', $user_enrollment_manager, 'wdm_manage_group_remove_product' );
		$this->loader->add_action( 'wp_ajax_ebbp_manage_group_remove_quantity', $user_enrollment_manager, 'wdm_manage_group_remove_quantity' );
		$this->loader->add_action( 'wp_ajax_ebbp_manage_group_search_users', $user_enrollment_manager, 'wdm_manage_group_search_users' );
		$this->loader->add_action( 'wp_ajax_ebbp_new_group_add_product', $user_enrollment_manager, 'wdm_new_group_add_product' );
		$this->loader->add_action( 'wp_ajax_ebbp_create_new_group', $user_enrollment_manager, 'wdm_create_new_group' );
		$this->loader->add_action( 'wp_ajax_ebbp_add_product_in_group', $user_enrollment_manager, 'wdm_add_product_in_group' );
		$this->loader->add_action( 'wp_ajax_ebbp_update_quantity_in_group', $user_enrollment_manager, 'wdm_update_quantity_in_group' );
		$this->loader->add_action( 'wp_ajax_ebbp_update_cohort_manager', $user_enrollment_manager, 'wdm_update_cohort_manager_in_group' );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// class-edwiser-multiple-users-course-purchase-public.php HOOKS.
		$this->loader->add_action( 'init', '\app\wisdmlabs\edwiserBridgePro\pb\Edwiser_Enroll_Multiple_User_Shortcode', 'init', 999 );

		$this->loader->add_action( 'pre_post_update', $plugin_public, 'prevent_product_edit' );
		$this->loader->add_action( 'admin_init', $plugin_public, 'woo_prod_edit_warning' );
		$this->loader->add_action( 'before_delete_post', $plugin_public, 'product_delete_precheck' );
		$this->loader->add_action( 'woocommerce_before_cart_table', $plugin_public, 'show_checkbox_on_cart_page' );
		$this->loader->add_action( 'wp_ajax_check_for_different_products', $plugin_public, 'check_for_different_products' );
		$this->loader->add_action( 'wp_ajax_nopriv_check_for_different_products', $plugin_public, 'check_for_different_products' );

		// v1.1.1
		// Adding filter to display custom message to cart summary page.
		$this->loader->add_filter( 'woocommerce_cart_item_name', $plugin_public, 'show_grouped_product_message', 10, 2 );
		$this->loader->add_action( 'woocommerce_after_order_notes', $plugin_public, 'bp_cohort_name_fields' );
		$this->loader->add_action( 'woocommerce_checkout_process', $plugin_public, 'bp_check_mandatory_fields' );
		$this->loader->add_filter( 'woocommerce_update_cart_action_cart_updated', $plugin_public, 'update_single_group_creation', 10, 1 );
		$this->loader->add_filter( 'woocommerce_cart_item_removed', $plugin_public, 'bp_handle_cart_item_removal', 10, 2 );

		// thankyou message for non bulk purchase orders.
		$this->loader->add_filter( 'woocommerce_thankyou_order_received_text', $plugin_public, 'wdm_order_received_thank_you_message', 100, 2 );

		/**
		 * Cohort class object to handle the cohort callbacks.
		 *
		 * @since 1.2.0
		 */
		$cohort_manager = new Eb_Bp_Manage_Cohort();
		$this->loader->add_action( 'woocommerce_checkout_order_processed', $cohort_manager, 'handle_order_placed', 10 );
		$this->loader->add_action( 'woocommerce_store_api_checkout_order_processed', $cohort_manager, 'handle_order_placed', 10 );

		/**
		 * Hook to save cart item meta into the order meta.
		 *
		 * @since 2.1.0
		 */
		$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $cohort_manager, 'save_cart_item_meta_into_order_meta', 10, 4 );

		$course_progress_handler = new \app\wisdmlabs\edwiserBridgePro\pb\Eb_Bp_Enroll_Students_Course_Progress();
		$this->loader->add_action( 'wp_ajax_get_cohort_course_progress', $course_progress_handler, 'get_cohort_course_progress', 10 );

		$mng_enroll = new \app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Enrollment_Manager();
		$this->loader->add_action( 'eb_before_manage_user_enrollment_table', $mng_enroll, 'add_pop_up_data' );


		// Add modal for Typeform survey on group management pages
		add_action('wp_footer', array($this, 'eb_pro_add_group_manage_tf_modal'));
		add_action('wp_ajax_eb_pro_group_manage_tf_submit', array($this, 'eb_pro_group_manage_tf_submit'));
	}

	/**
	 * Set the flag that the form was submitted
	 */
	public function eb_pro_group_manage_tf_submit()
	{
		// Set the flag that the form was submitted
		update_option('eb_pro_group_manage_tf_modal_submitted', true);
		wp_send_json_success('Typeform submission recorded');
	}

	/**
	 * Add Typeform modal HTML to footer
	 */
	public function eb_pro_add_group_manage_tf_modal()
	{
		// Check if user is admin
		if (!current_user_can('manage_options')) {
			return;
		}

		// Check if modal has been shown before
		if (get_option('eb_pro_group_manage_tf_modal_submitted')) {
			return;
		}

		global $post;
		if ( strpos($post->post_content, 'wp:edwiser-bridge-pro/group-management') !== false ) {
			?>
			<div id="eb-pro-typeform-modal" class="eb-pro-typeform-modal" style="display: none;">
			<div class="eb-pro-typeform-modal-content">
				<span class="eb-pro-typeform-modal-close">&times;</span>
				<div class="eb-pro-typeform-container">				
      <div data-tf-live="01K09MJ4Z8BAHWV8AFY33Y0D22" data-tf-on-submit="ebTypeformSubmitted"></div>
					<script src="https://embed.typeform.com/next/embed.js"></script>
					<script>
					  function ebTypeformSubmitted() {
						// Hit your AJAX endpoint to set the PHP option
						fetch('<?php echo admin_url("admin-ajax.php"); ?>?action=eb_pro_group_manage_tf_submit', {
							method: "GET",
							credentials: "same-origin"
						})
						.then((res) => res.json())
						.then((data) => {
							console.log("Submission saved to WP:", data);
						})
						.catch((err) => {
							console.error("Failed to save submission:", err);
						});
					  }
					</script>
				</div>
			</div>
		</div>
		<?php
		}
	}

	/**
	 * Register all of the hooks related to the email functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_email_hooks() {
		$plugin_emailer = new Eb_Bp_Emailer();
		$this->loader->add_action( 'eb_bp_bulk_purchase_email', $plugin_emailer, 'send_bulk_purchase_email', 10, 1 );
		$this->loader->add_action( 'eb_bp_new_user_to_cohort', $plugin_emailer, 'send_cohort_enrollment_email', 10, 1 );
		$this->loader->add_action( 'eb_bp_remove_user_from_cohort', $plugin_emailer, 'send_cohort_unenrollment_email', 10, 1 );
		$this->loader->add_action( 'eb_bp_cohort_delete', $plugin_emailer, 'bp_send_cohort_delete_email', 10, 1 );
		$this->loader->add_action( 'eb_bp_bulk_purchase_refund', $plugin_emailer, 'bp_send_group_refund_email', 10, 1 );
		$this->loader->add_action( 'eb_bp_new_group_creation', $plugin_emailer, 'bp_send_group_creation_email', 10, 1 );
		/************************************************
		- When bulk purchase product is purchased without checking bulk purchase product checkbox then the normal woo-int mail should go.
		- but the mail added in woo-int don't have any hook added to send it.
		- so temporarily addin hook for only bulk purchase.

		IMP
		replace it with the hook in woo-int which will be added for mail eb_emailtmpl_woocommerce_moodle_course_notifn

		**********************************************
		*/

		$this->loader->add_action( 'eb_bp_send_normal_enrollemnt_mail', $plugin_emailer, 'send_course_enrollment_email' );
	}

	/**
	 * Define system hooks in this function.
	 *
	 * @since 3.0.0
	 */
	private function define_system_hooks() {

		/**
		 * Class object to handle the ajax callback.
		 *
		 * @since 1.2.0
		 */
		$admin_ajax_init = new Eb_Bp_Ajax_Handler();
		$this->loader->add_action( 'wp_ajax_handle_cohort_synchronization', $admin_ajax_init, 'handle_cohort_synchronization_callback' );
		$this->loader->add_action( 'wp_ajax_mucp_get_course_details', $admin_ajax_init, 'check_if_user_is_enrolled_in_course' );
		$this->loader->add_action( 'eb_before_manage_user_enrollment_table', $admin_ajax_init, 'show_notice_on_manage_enrollment_page' );

		$this->loader->add_filter( 'check_group_purchase', $this, 'wdm_check_group_purchase', 10, 2 );
		$this->loader->add_filter( 'is_order_group_purchase', $this, 'wdm_check_group_purchase_for_order', 10, 2 );
		$this->loader->add_filter( 'eb_reset_email_tmpl_content', $this, 'wdm_parse_email_template', 10, 2 );

		/*
			* Enroll self file Hooks.
			*/
		$mng_enroll = new \app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Self_Enroll();

		$this->loader->add_filter( 'eb_emailtmpl_content', $mng_enroll, 'eb_email_tmpl_content', 111 );
		$this->loader->add_action( 'woocommerce_before_add_to_cart_button', $mng_enroll, 'wdm_ld_woocommerce_before_add_to_cart_button', 10 );

		// removing this as bulk purchase enable button have no efect when checked on shop page.
		// Store the custom field.
		$this->loader->add_filter( 'woocommerce_add_cart_item_data', $mng_enroll, 'wdm_ld_add_cart_item_custom_data_save', 10, 2 );
		$this->loader->add_filter( 'woocommerce_get_cart_item_from_session', $mng_enroll, 'get_cart_items_from_session', 1, 3 );
		// Hook to add cart item meta  to the order item meta.

		$this->loader->add_action( 'woocommerce_new_order_item', $mng_enroll, 'wdm_add_values_to_order_item_meta', 1, 2 );

		// $this->loader->add_action( 'woocommerce_order_status_completed', $mng_enroll, 'wdm_save_product_qty', 99, 1 );
		$this->loader->add_action( 'woocommerce_order_status_changed', $mng_enroll, 'wdm_save_product_qty', 99, 3 );
		$this->loader->add_filter( 'woocommerce_order_items_meta_display', $mng_enroll, 'translate_enrolled_self', 11, 2 );

		// translate group enrollment meta key and value.
		$this->loader->add_filter( 'woocommerce_order_item_display_meta_key', $mng_enroll, 'eb_translate_order_item_meta_key', 11, 3 );
		$this->loader->add_filter( 'woocommerce_order_item_display_meta_value', $mng_enroll, 'eb_translate_order_item_meta_value', 11, 3 );
		$this->loader->add_filter( 'woocommerce_order_item_get_formatted_meta_data', $mng_enroll, 'eb_hide_unwanted_order_item_meta', 11, 2 );

	}

	/**
	 * Call back for the reset email template content of ebbp email tmpl.
	 *
	 * @param array $args array of the tmpl config.
	 */
	public function wdm_parse_email_template( $args ) {
		$email_tmpl_pars = new Eb_Bp_Email_Template_Manager();
		return $email_tmpl_pars->handle_template_restore( $args );
	}

	/**
	 * Check if group purchase is enabled for the product.
	 *
	 * @since  1.0.0
	 *
	 * @param array $group_purchase group_purchase.
	 * @param array $product_id product_id.
	 */
	public function wdm_check_group_purchase( $group_purchase, $product_id ) {
		unset( $group_purchase );
		$product_options = get_post_meta( $product_id, 'product_options', true );
		if ( isset( $product_options['moodle_course_group_purchase'] ) ) {
			return $product_options['moodle_course_group_purchase'];
		}
		return 'off';
	}

	/**
	 * Check if group purchase is enabled for the product.
	 *
	 * @since  2.3.8
	 *
	 * @param array $group_purchase group_purchase.
	 * @param int   $item_id item_id in order.
	 */
	public function wdm_check_group_purchase_for_order( $group_purchase, $item_id ) {
		unset( $group_purchase );
		$group_purchase = wc_get_order_item_meta( $item_id, 'Group Enrollment' );
		if ( isset( $group_purchase ) ) {
			return $group_purchase;
		}
		return 'no';
	}
}

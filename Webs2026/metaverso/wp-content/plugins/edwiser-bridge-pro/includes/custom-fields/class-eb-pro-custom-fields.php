<?php
/**
 * Selective Sync Module
 * This class is responsible for selective sync module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for selective sync module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\customFields;

use app\wisdmlabs\edwiserBridgePro\admin as admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Eb_Pro_Custom_Fields {
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
	 * @var Eb_Pro_Custom_Fields The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Main CustomFields Instance
	 *
	 * Ensures only one instance of Edwiser_Bridge_Pro is loaded or can be loaded.
	 *
	 * @since 3.0.0
	 * @static
	 * @return Eb_Pro_Custom_Fields - Main instance
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
		if ( ! is_admin() ) {
			$this->load_frontend_dependencies();
		}

		/**
		 * Class to initiate custom fields menu under edwiser bridge.
		 */
		require_once EB_PRO_PLUGIN_PATH . 'admin/class-eb-pro-menu.php';

		require_once EB_PRO_PLUGIN_PATH . 'includes/custom-fields/class-edwiser-custom-field-handler.php';

		/**
		 * Class to display custom fields on frontend.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/custom-fields/class-edwiser-custom-field-frontend-handler.php';

		/**
		 * Class for custom fields validation.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/custom-fields/class-edwiser-custom-field-validation.php';

		/**
		 * Class for custom field save and sync.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/custom-fields/class-edwiser-custom-field-sync-handler.php';

		/**
		 * Class for displaying custom fields on admin user profile page.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/custom-fields/class-edwiser-custom-field-admin-profile.php';


	}

	/**
	 * Load the required frontend dependencies for this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_frontend_dependencies() {

	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new admin\Edwiser_Bridge_Pro_Admin();

		$custom_field_menu = new admin\Eb_Pro_Menu();
		// add custom fields menu under edwiser bridge menu.
		$this->loader->add_action( 'admin_menu', $custom_field_menu, 'custom_field_menu' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// admin page handler for custom fields.
		$custom_field_handler = new Edwiser_Custom_Field_Handler();
		// Add ajax action to save custom fields.
		$this->loader->add_action( 'wp_ajax_eb_cf_save_data', $custom_field_handler, 'save_custom_fields' );
		// Add ajax action to delete custom fields.
		$this->loader->add_action( 'wp_ajax_eb_cf_delete_field', $custom_field_handler, 'delete_custom_fields' );
		// Add ajax action for bulk action.
		$this->loader->add_action( 'wp_ajax_eb_cf_bulk_action', $custom_field_handler, 'handle_bulk_actions' );

		// initiate frontend handler.
		$custom_field_frontend_handler = new Edwiser_Custom_Field_Frontend_Handler();
		// show custom field on checkout page.
		$this->loader->add_action( 'woocommerce_after_order_notes', $custom_field_frontend_handler, 'eb_show_fields_on_checkout_page' );
		// show custom field on woocommerce.
		$this->loader->add_action( 'woocommerce_register_form', $custom_field_frontend_handler, 'eb_show_fields_on_woo_reg_page' );
		// show custom field on woocommerce edit account page.
		$this->loader->add_action( 'woocommerce_edit_account_form', $custom_field_frontend_handler, 'eb_show_fields_on_woo_my_accnt_page' );
		// show custom field on edwiser registration page.
		$this->loader->add_action( 'eb_register_form', $custom_field_frontend_handler, 'eb_show_fields_on_edwiser_reg_page' );
		// show custom field on edwiser user account page.
		$this->loader->add_action( 'eb_edit_user_profile', $custom_field_frontend_handler, 'eb_show_fields_on_edwiser_user_accnt_page' );

		// Add custom field validation.
		$custom_field_validation = new Edwiser_Custom_Field_Validation();
		// Add validation on checkout page.
		$this->loader->add_action( 'woocommerce_checkout_process', $custom_field_validation, 'eb_validate_custom_field_on_checkout_page' );
		// Add validation on woo registration page.
		$this->loader->add_action( 'woocommerce_registration_errors', $custom_field_validation, 'eb_validate_custom_field_on_woo_reg_page', 10, 3 );
		// Validation on my-account page.
		$this->loader->add_action( 'woocommerce_save_account_details_errors', $custom_field_validation, 'eb_validate_custom_field_on_my_accnt_page', 10, 2 );
		// Validation on edwiser registration page.
		$this->loader->add_action( 'eb_process_registration_errors', $custom_field_validation, 'eb_validate_custom_field_on_eb_reg_page', 10, 4 );
		// Validation on user account page.
		// NOT WORKING
		// $this->loader->add_action( 'eb_save_account_details_required_fields', $custom_field_validation, 'eb_validate_custom_field_on_user_accnt_page', 10, 1 ); // @codingStandardsIgnoreLine

		// save and sync custom fields.
		$custom_field_sync = new Edwiser_Custom_Field_Sync_Handler();

		// save and sync custom fields on checkout page.
		$this->loader->add_action( 'wi_woo_checkout_customer_user_created', $custom_field_sync, 'eb_sync_custom_field_on_checkout_page', 10, 1 );

		// save and sync custom fields on woo-reg, my-account, eb-reg, user-account page.
		$this->loader->add_action( 'eb_moodle_user_profile_details', $custom_field_sync, 'eb_sync_custom_field', 10, 2 );

		// if user is not linked then save custom fields in user meta from my-account.
		$this->loader->add_action( 'woocommerce_save_account_details', $custom_field_sync, 'eb_save_custom_field_in_user_meta', 10, 1 );

		// if user is not linked then save custom fields in user meta from user-account.
		$this->loader->add_action( 'eb_save_account_details', $custom_field_sync, 'eb_save_custom_field_in_user_meta', 10, 1 );

		// save custom fields from moodle to WordPress.
		$this->loader->add_action( 'eb_user_created_from_moodle', $custom_field_sync, 'eb_cf_save_custom_fields_data_from_moodle', 10, 2 );

		// Initialize admin profile handler for displaying custom fields on user profile pages.
		$custom_field_admin_profile = new Edwiser_Custom_Field_Admin_Profile();

		// sync custom fields when a user account is linked to moodle.
		$this->loader->add_action( 'eb_user_updated_from_moodle', $custom_field_sync, 'eb_cf_save_custom_fields_data_from_moodle', 10, 2 );
		$this->loader->add_action( 'eb_linked_to_existing_wordpress_user', $custom_field_sync, 'eb_sync_custom_field_on_user_link', 10, 1 );
		$this->loader->add_action( 'eb_linked_to_existing_wordpress_to_new_user', $custom_field_sync, 'eb_sync_custom_field_on_user_link', 10, 1 );
	}
}

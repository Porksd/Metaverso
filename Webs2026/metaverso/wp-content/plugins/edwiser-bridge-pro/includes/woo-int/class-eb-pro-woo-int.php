<?php

/**
 * Woo Integration Module
 * This class is responsible for Woo Integration module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for Woo Integration module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\wooInt;

use app\wisdmlabs\edwiserBridgePro\admin as admin;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Eb_Pro_Woo_Int
{
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
	 * @var Eb_Pro_Woo_Int The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Main CustomFields Instance
	 *
	 * Ensures only one instance of Edwiser_Bridge_Pro is loaded or can be loaded.
	 *
	 * @since 3.0.0
	 * @static
	 * @return Eb_Pro_Woo_Int - Main instance
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
	 * @since    1.0.0
	 * @param    string $loader The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 */
	public function __construct($loader)
	{
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
	private function define_constants()
	{
		// Woocommerce version.
		if (! defined('WOOCOMMERCE_VERSION')) {
			define('WOOCOMMERCE_VERSION', $this->get_woocommerce_version_number());
		}

		// Woocommerce Subscriptions version.
		if (! defined('WOOINT_WCS_VER')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			if (is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
				include_once WP_PLUGIN_DIR . '/woocommerce-subscriptions/woocommerce-subscriptions.php';
				define('WOOINT_WCS_VER', \WC_Subscriptions::$version);
			}
		}
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
	private function load_dependencies()
	{
		// If user is not admin then load frontend dependencies.
		if (! is_admin()) {
			$this->load_frontend_dependencies();
		}

		/**
		 * File to load all the functions used multiple times in whole wooocommerce-integration plugin.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woo-functions.php';

		/**
		 * File responsible for cart page content override
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woo-template-override.php';

		/**
		 * File responsible to perform all membership related functionalities.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woo-membership-handler.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		include_once EB_PRO_PLUGIN_PATH . 'public/class-bridge-woocommerce-public.php';

		/*
			*The class responsible for defining all actions that occur in both for
			* course Product syncrhonization
			*/
		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woocommerce-course.php';

		/*
			*The class responsible for defining all actions that occur for Product meta fields & other operation
			*/

		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woocommerce-product-manager.php';

		/*
			*The class responsible for adding a moodle course filter in woocommerce dropdown
			*/

		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woocommerce-product-filter.php';

		/*
			*The class responsible for defining all actions that occur Order completion & other operation
			*/

		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woocommerce-order-manager.php';

		/*
			*The class responsible for defining all actions that occur for AJAX
			*/

		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woocommerce-ajax.php';

		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woo-email-template-manager.php';

		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woo-my-account-compatibility.php';

		/*
			* Edwiser associated Products Api.
			*/
		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woo-product-api.php';

		/**
		 * Cart AJAX for blocks
		 */
		require_once EB_PRO_PLUGIN_PATH . 'includes/api/class-eb-pro-blocks-cart-ajax.php';

		/**
		 * Group Management AJAX for blocks
		 */
		require_once EB_PRO_PLUGIN_PATH . 'includes/api/class-eb-pro-blocks-group-management-ajax.php';
	}

	/**
	 * Load the required frontend dependencies for this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_frontend_dependencies()
	{
		/**
		 * Tha classes responsible for defining shortcodes & templates
		 */
		include_once EB_PRO_PLUGIN_PATH . 'public/class-bridge-woocommerce-shortcodes.php';
		include_once EB_PRO_PLUGIN_PATH . 'public/shortcodes/class-bridge-woocommerce-shortcode-associated-courses.php';
		include_once EB_PRO_PLUGIN_PATH . 'public/shortcodes/class-bridge-woocommerce-shortcode-single-cart-checkout.php';

		/**
		 * Core functions for APIs
		 */
		require_once EB_PRO_PLUGIN_PATH . 'includes/eb-pro-core-functions.php';

		/**
		 * Blocks api
		 */
		require_once EB_PRO_PLUGIN_PATH . 'includes/api/class-eb-pro-blocks-cart-api.php';
		require_once EB_PRO_PLUGIN_PATH . 'includes/api/class-eb-pro-blocks-shop-api.php';
		require_once EB_PRO_PLUGIN_PATH . 'includes/api/class-eb-pro-blocks-checkout-api.php';
		require_once EB_PRO_PLUGIN_PATH . 'includes/api/class-eb-pro-blocks-group-management-api.php';
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new admin\Edwiser_Bridge_Pro_Admin();

		$this->loader->add_action('admin_init', $plugin_admin, 'update_settings');

		$this->loader->add_filter('eb_get_settings_pages', $plugin_admin, 'woo_int_settings', 10, 1);

		$this->loader->add_filter('post_row_actions', $plugin_admin, 'add_contains_enrolment', 10, 2);

		$email_tmpl_manag = new Bridge_Woo_Email_Template_Manager();
		$this->loader->add_filter('eb_email_templates_list', $email_tmpl_manag, 'eb_templates_list', 111);
		$this->loader->add_filter('eb_email_template_constant', $email_tmpl_manag, 'eb_templates_constants', 111);
		$this->loader->add_filter('eb_emailtmpl_content_before', $email_tmpl_manag, 'email_template_parser', 111);
		$this->loader->add_filter('eb_reset_email_tmpl_content', $email_tmpl_manag, 'wdm_parse_email_template', 10, 2);

		// Add product moodle course in woocommerce product filter dropdown.

		$prod_filter = new Bridge_Woocommerce_Product_Filter($this->plugin_name, $this->version);

		// Add Associated Courses Column.
		$this->loader->add_filter('manage_product_posts_columns', $prod_filter, 'add_custom_column');

		$this->loader->add_action('manage_product_posts_custom_column', $prod_filter, 'add_custom_column_value', 10, 2);

		$this->loader->add_action('wp_ajax_unenrol_check_status', $plugin_admin, 'unenrol_check_status');
		$this->loader->add_action('wp_ajax_unenrol_update_html', $plugin_admin, 'unenrol_update_html');
		$this->loader->add_action('woocommerce_order_item_add_line_buttons', $plugin_admin, 'refund_html_content', 10, 1);
		$this->loader->add_action('woocommerce_order_refunded', $plugin_admin, 'order_refunded', 10, 2);

		// Add Product synchronization setting.

		$this->loader->add_filter('eb_get_sections_synchronization', $plugin_admin, 'woo_int_product_sync_section', 10, 1);

		$this->loader->add_filter('eb_get_settings_synchronization', $plugin_admin, 'woo_int_product_sync_settings', 10, 2);

		// Products Meta fields and other operation.

		$prod_manager_plugin = new Bridge_Woocommerce_Product_Manager($this->plugin_name, $this->version);

		$this->loader->add_action('save_post', $prod_manager_plugin, 'handle_post_options_save', 10);
		$this->loader->add_action('before_delete_post', $prod_manager_plugin, 'handle_post_options_delete', 10, 1);

		$this->loader->add_filter('woocommerce_product_data_tabs', $prod_manager_plugin, 'bridge_woo_add_tab', 10, 1);
		$this->loader->add_action('woocommerce_product_data_panels', $prod_manager_plugin, 'bridge_woo_add_data_panel');
		$this->loader->add_action('woocommerce_product_after_variable_attributes', $prod_manager_plugin, 'bridge_woo_add_product_meta_variation', 10, 3);

		$this->loader->add_action('woocommerce_save_product_variation', $prod_manager_plugin, 'bridge_woo_save_variation_meta', 10, 2);

		// Enroll User on order status change.

		$order_manager_plugin = new Bridge_Woocommerce_Order_Manager($this->plugin_name, $this->version);

		/**
		 * One hook handles all statues
		 *
		 * @since 1.1.3
		 */
		$this->loader->add_action('woocommerce_order_status_changed', $order_manager_plugin, 'wc_order_status_changed', 10, 3);

		// membership hooks.

		$membership_handler = new Bridge_Woo_Membership_Handler($this->plugin_name, $this->version);
		$this->loader->add_action('wc_memberships_user_membership_status_changed', $membership_handler, 'handle_membsership_status_change', 10, 3);

		// Below is to add membership_id column in the moodle_enrollment table.
		$this->loader->add_action('admin_init', $membership_handler, 'add_membership_column_in_moodle_enrollment');

		/*Membership Hooks end*/

		$this->loader->add_filter('pre_option_woocommerce_enable_guest_checkout', $order_manager_plugin, 'disable_guest_checkout', 10, 1);

		// Create / Link Moodle User.
		$this->loader->add_action('woocommerce_checkout_order_processed', $order_manager_plugin, 'create_moodle_user_for_created_customer', 1, 2);
		$this->loader->add_action('woocommerce_store_api_checkout_order_processed', $order_manager_plugin, 'create_moodle_user_for_created_customer', 1, 2);

		// $this->loader->add_action('woocommerce_checkout_order_processed', $order_manager_plugin, 'create_moodle_user_for_created_customer', 1, 2);

		$this->loader->add_filter('eb_filter_moodle_password', $order_manager_plugin, 'add_user_submitted_password', 10, 1);

		// WCS is active.
		if (defined('WOOINT_WCS_VER')) {
			if (version_compare(WOOINT_WCS_VER, '2.0', '>=')) {
				$this->loader->add_action('woocommerce_subscription_status_updated', $order_manager_plugin, 'wcs_status_updated', 111, 3);
			} else {
				/**
				 * Legacy hooks.
				 *
				 * @deprecated 1.1.3 Use woocommerce_subscription_status_updated.
				 */
				$this->loader->add_action('activated_subscription', $order_manager_plugin, 'handle_activated_subscription', 10, 2);
				$this->loader->add_action('cancelled_subscription', $order_manager_plugin, 'handle_cancelled_subscription', 10, 2);
				$this->loader->add_action('subscription_expired', $order_manager_plugin, 'handle_cancelled_subscription', 10, 2);
				$this->loader->add_action('subscription_put_on-hold', $order_manager_plugin, 'handle_cancelled_subscription', 10, 2);
			}
		}

		// Show purchase for someone else data
		$this->loader->add_action('woocommerce_admin_order_data_after_billing_address', $order_manager_plugin, 'show_purchase_for_someone_else_data', 10, 1);


		// load Elementor widgets.

		$this->loader->add_action('elementor/widgets/register', $this, 'register_elementor_widgets');

		$this->loader->add_action('elementor/elements/categories_registered', $this, 'add_elementor_widget_categories');

		add_filter('loop_shop_per_page', array($this, 'eb_pro_shop_page_per_page'), 999, 1);

		// create default woocommerce gutenberg pages.
		$this->loader->add_action('admin_init', $this, 'eb_pro_check_and_create_default_pages');

		// Add custom post state for Enroll Students page
		$this->loader->add_filter('display_post_states', $this, 'add_gutenberg_post_state', 10, 2);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$template_override = new Bridge_Woo_Template_Override($this->plugin_name, $this->version);
		$template_pages    = get_option('eb_woo_gutenberg_pages', array());
		// Add shop page override
		if (isset($template_pages['eb_pro_shop_page_id']) && ! empty($template_pages['eb_pro_shop_page_id']) && get_option('eb_pro_enable_shop_override', false)) {
			$this->loader->add_filter('template_include', $template_override, 'override_shop_template', 99);
			$this->loader->add_action('eb_pro_shop_before_content', $template_override, 'shop_wrapper_start');
			$this->loader->add_action('eb_pro_shop_after_content', $template_override, 'shop_wrapper_end');
		}
		// Add single product page override
		if (isset($template_pages['eb_pro_single_product_page_id']) && ! empty($template_pages['eb_pro_single_product_page_id']) && get_option('eb_pro_enable_single_product_override', false)) {
			$this->loader->add_filter('template_include', $template_override, 'override_single_product_template', 99);
			$this->loader->add_action('eb_pro_single_product_before_content', $template_override, 'single_product_wrapper_start');
			$this->loader->add_action('eb_pro_single_product_after_content', $template_override, 'single_product_wrapper_end');
			$this->loader->add_action('storefront_after_footer', $template_override, 'remove_storefront_sticky_single_add_to_cart', 998);
		}
		// Add cart page override
		if (isset($template_pages['eb_pro_cart_page_id']) && ! empty($template_pages['eb_pro_cart_page_id']) && get_option('eb_pro_enable_cart_override', false)) {
			$this->loader->add_filter('woocommerce_locate_template', $template_override, 'override_cart_template', 10, 3);
			$this->loader->add_filter('template_include', $template_override, 'override_cart_page_template', 99);

			// Disable default WooCommerce cart actions to prevent duplicate elements
			// add_action('init', array($template_override, 'disable_default_cart_actions'), 20);
		}
		if (isset($template_pages['eb_pro_thank_you_page_id']) && ! empty($template_pages['eb_pro_thank_you_page_id']) && get_option('eb_pro_enable_thank_you_override', false)) {
			$this->loader->add_filter('woocommerce_thankyou_order_received_text', $template_override, 'thank_you_order_received_text', 90, 2);
			$this->loader->add_filter('wc_get_template', $template_override, 'override_woocommerce_order_received_template', 90, 5);
		} else {
			$this->loader->add_filter('woocommerce_thankyou_order_received_text', $template_override, 'thank_you_order_received_text_old', 90, 2);
		}
		if (! is_admin()) {
			$this->loader->add_action('init', '\app\wisdmlabs\edwiserBridgePro\pb\Bridge_Woocommerce_Shortcodes', 'init', 99);
		}

		$plugin_public = new \app\wisdmlabs\edwiserBridgePro\pb\Bridge_Woocommerce_Public($this->plugin_name, $this->version);

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		// Display associated courses on single product page as well as in Order email.

		$this->loader->add_action('woocommerce_before_add_to_cart_form', $plugin_public, 'display_product_related_courses', 10);
		$this->loader->add_action(
			'woocommerce_grouped_product_list_before_price',
			$plugin_public,
			'grouped_product_display_associated_courses',
			10,
			1
		);
		$this->loader->add_action('woocommerce_email_after_order_table', $plugin_public, 'send_associated_courses_in_email', 10, 3);

		// To mark Create an account? checkbox as checked and then hide the option for non-logged user who have edwiser products in cart
		// Added this hook to include all checkout page css.
		$this->loader->add_filter('woocommerce_is_checkout', $plugin_public, 'is_single_cart_checkout', 111, 1);

		// Hook to rdirect to the single checkout page if cart is updated from the same page.
		// Hook :: woocommerce_get_cart_url.
		$this->loader->add_filter('woocommerce_get_cart_url', $plugin_public, 'eb_get_one_click_checkout_url', 111, 1);

		$this->loader->add_filter(
			'eb_user_orders',
			$plugin_public,
			'add_woocomerce_orders_to_user_account_page',
			10,
			1
		);

		$eb_general = get_option('eb_woo_int_settings', array());

		$buy_now_enabled = isset($eb_general['wi_enable_buynow']) && 'yes' === $eb_general['wi_enable_buynow'] ? true : false;

		if ($buy_now_enabled) {
			$this->loader->add_action(
				'woocommerce_after_add_to_cart_button',
				$plugin_public,
				'product_page_after_add_to_cart'
			);

			$this->loader->add_action(
				'woocommerce_after_shop_loop_item',
				$plugin_public,
				'shop_page_after_add_to_cart',
				11
			);

			$this->loader->add_filter(
				'woocommerce_add_to_cart_redirect',
				$plugin_public,
				'buy_now_redirect',
				10,
				1
			);
		}

		$purchase_for_someone_else = isset($eb_general['wi_enable_purchase_for_someone_else']) && 'yes' === $eb_general['wi_enable_purchase_for_someone_else'] ? true : false;

		if ($purchase_for_someone_else) {
			$this->loader->add_action(
				'woocommerce_before_order_notes',
				$plugin_public,
				'wi_add_purchase_for_someone_else_input_fields'
			);

			$this->loader->add_action(
				'woocommerce_checkout_process',
				$plugin_public,
				'wi_validate_purchase_for_someone_else_input_fields'
			);
		}

		// subscription meta box.
		$this->loader->add_action(
			'woocommerce_subscriptions_product_options_pricing',
			$plugin_public,
			'wi_add_expiry_options_to_subscription_product'
		);

		$this->loader->add_action(
			'woocommerce_variable_subscription_pricing',
			$plugin_public,
			'wi_add_expiry_options_to_variable_subscription_product',
			9,
			3
		);


		// Woocommerce My-account page Hooks.
		$woo_my_account_operations = new Bridge_Woo_My_Account_Compatibility();
		$this->loader->add_action(
			'woocommerce_created_customer',
			$woo_my_account_operations,
			'my_account_page_user_creation',
			10,
			3
		);

		$this->loader->add_action(
			'woocommerce_save_account_details',
			$woo_my_account_operations,
			'wi_my_account_user_profile_update',
			10,
			1
		);

		$this->loader->add_action(
			'woocommerce_save_account_details_errors',
			$woo_my_account_operations,
			'validate_my_account_page_fields',
			10,
			2
		);

		$this->loader->add_action(
			'woocommerce_register_form_start',
			$woo_my_account_operations,
			'wi_add_name_fields_woo_account_registration',
			10
		);

		$this->loader->add_action(
			'woocommerce_registration_errors',
			$woo_my_account_operations,
			'wi_validate_name_fields',
			10,
			3
		);

		$this->loader->add_action(
			'init',
			$woo_my_account_operations,
			'wi_add_my_courses_endpoint',
			99
		);

		$this->loader->add_action(
			'query_vars',
			$woo_my_account_operations,
			'wi_add_my_courses_query_vars',
			10,
			1
		);

		$this->loader->add_action(
			'woocommerce_account_menu_items',
			$woo_my_account_operations,
			'wi_add_my_courses_link_my_account',
			10,
			1
		);

		$this->loader->add_action(
			'woocommerce_account_eb_my_courses_endpoint',
			$woo_my_account_operations,
			'wi_add_my_courses_content',
			10
		);

		$this->loader->add_action(
			'wp_loaded',
			$woo_my_account_operations,
			'wi_flush_rewrite_rules',
			10
		);

		/*
		 * Edwiser Product API related Hooks
		 */
		$api_handler = new Bridge_Woo_Product_API();
		$this->loader->add_action('rest_api_init', $api_handler, 'wi_get_edwiser_products_list');

		/**
		 * Gutenburg Block
		 */
		// $this->loader->add_action( 'init', $this, 'register_checkout_page_block' );

		// $this->loader->add_action( 'enqueue_block_editor_assets', $this, 'enqueue_block_editor_assets' );

		// $this->loader->add_action( 'eb_pro_checkout_page_block_hook', $this, 'eb_pro_checkout_page_block_hook_action' );

		// Add modal for Typeform survey on shop/product pages
		add_action('wp_footer', array($this, 'eb_pro_add_woo_tf_modal'));
		add_action('wp_ajax_eb_pro_woo_tf_submit', array($this, 'eb_pro_woo_tf_submit'));

	}

	/**
	 * Set the flag that the form was submitted
	 */
	public function eb_pro_woo_tf_submit()
	{
		// Set the flag that the form was submitted
		update_option('eb_pro_woo_tf_modal_submitted', true);
		wp_send_json_success('Typeform submission recorded');
	}

	/**
	 * This function returns installed woocommerce version number.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	public function get_woocommerce_version_number()
	{
		// If get_plugins() isn't available, require it.
		if (! function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Create the plugins folder and file variables.
		$plugin_folder = get_plugins('/woocommerce');
		$plugin_file   = 'woocommerce.php';

		// If the plugin version number is set, return it.
		if (isset($plugin_folder[$plugin_file]['Version'])) {
			return $plugin_folder[$plugin_file]['Version'];
		} else {
			// Otherwise return null.
			return null;
		}
	}

	/**
	 * Register Elementor widgets.
	 *
	 * @since 3.0.2
	 */
	public function register_elementor_widgets($widgets_manager)
	{

		// load elementor widgets.
		include_once EB_PRO_PLUGIN_PATH . 'public/widgets/class-eb-pro-shop-page-widget.php';
		include_once EB_PRO_PLUGIN_PATH . 'public/widgets/class-eb-pro-product-page-widget.php';
		include_once EB_PRO_PLUGIN_PATH . 'public/widgets/class-eb-pro-related-product-widget.php';

		$widgets_manager->register(new \app\wisdmlabs\edwiserBridgePro\pb\widgets\EB_Pro_Shop_Page_Widget());
		$widgets_manager->register(new \app\wisdmlabs\edwiserBridgePro\pb\widgets\EB_Pro_Product_Page_Widget());
		$widgets_manager->register(new \app\wisdmlabs\edwiserBridgePro\pb\widgets\EB_Pro_Related_Product_Widget());
	}

	/**
	 * Elementor widget category.
	 */
	public function add_elementor_widget_categories($elements_manager)
	{

		$elements_manager->add_category(
			'eb-pro-widgets',
			[
				'title' => esc_html__('Edwiser Bridge Pro', 'textdomain'),
				'icon' => 'fa fa-plug',
			]
		);
	}

	public function eb_pro_shop_page_per_page($woo_per_page)
	{
		$eb_shop_page = get_option('eb_pro_elementor_shop_page_template_id');
		// check if this template is published and set as shop page in settings
		$is_page_shop = false;
		if ('publish' == get_post_status($eb_shop_page)) {
			$conditions = get_post_meta($eb_shop_page, '_elementor_conditions', true);
			if (! empty($conditions)) {
				foreach ($conditions as $condition) {
					if (strpos($condition, 'shop_page') !== false) {
						$is_page_shop = true;
						break;
					}
				}
			}
		}

		$per_page = get_option('eb_pro_shop_page_product_per_page');
		if ($per_page && $is_page_shop) {
			return $per_page;
		} else {
			return $woo_per_page;
		}
	}

	/**
	 * Register the checkout page block.
	 *
	 * @since 3.0.7
	 */
	public function register_checkout_page_block()
	{
		// Check if the block editor is active.		
		if (function_exists('register_block_type')) {
			register_block_type('edwiser-bridge-pro/eb-pro-checkout-page-block', array(
				'editor_script' => 'eb-pro-checkout-page-block-script',
				'render_callback' => array($this, 'eb_pro_checkout_page_block_render_callback'),
			));
		}
	}

	/**
	 * Render the checkout page block.
	 *
	 * @since 3.0.7
	 */
	public function eb_pro_checkout_page_block_render_callback()
	{
		// Check if the checkout page is set in the plugin settings.
		ob_start();
		echo '<h3>Additional Details</h3>';
		do_action('woocommerce_after_order_notes', WC()->checkout);
		return ob_get_clean();
	}

	/**
	 * Enqueue the checkout page block assets.
	 */
	public function enqueue_block_editor_assets()
	{
		wp_enqueue_script(
			'eb-pro-checkout-page-block-script',
			EB_PRO_PLUGIN_URL . 'public/assets/js/eb-pro-checkout-page-block.js',
			array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-hooks'),
			EB_PRO_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Block action hook.
	 */
	public function eb_pro_checkout_page_block_hook_action()
	{
		echo 'This Block is used for adding additional functionality of Edwiser Bridge PRO to the checkout page.';
	}

	/**
	 * Check if WooCommerce integration is enabled and create default pages if needed
	 */
	public function eb_pro_check_and_create_default_pages()
	{
		$module_data = get_option('eb_pro_modules_data');
		$woo_integration_enabled = (isset($module_data['woo_integration']) && 'active' === $module_data['woo_integration']) ? true : false;

		// Check if WooCommerce integration is enabled
		if ($woo_integration_enabled) {
			$this->eb_pro_create_woocom_default_pages();
		}
	}

	/**
	 * Create default WooCommerce integration pages when integration is enabled
	 */
	public function eb_pro_create_woocom_default_pages()
	{
		$woo_gutenberg_pages = get_option('eb_woo_gutenberg_pages', array());

		// Create Shop Page
		if (!isset($woo_gutenberg_pages['eb_pro_shop_page_id']) || empty($woo_gutenberg_pages['eb_pro_shop_page_id'])) {
			$shop_page_id = self::eb_pro_create_page(
				'Edwiser - Shop',
				'<!-- wp:edwiser-bridge-pro/shop -->
<div class="wp-block-edwiser-bridge-pro-shop"><div id="eb-shop" data-use-background-image="true" data-background-color="#162324" data-page-title="Shop" data-products-per-page="8" data-allow-sort="true" data-default-sort-order="popularity" data-show-result-count="true" data-default-card-layout="grid" data-show-category="true" data-show-course-description="true" data-show-ratings="true" data-show-enrolled="true" data-show-view="true" data-show-breadcrumb="true" data-title-color="#fff"></div></div>
<!-- /wp:edwiser-bridge-pro/shop -->',
				'publish'
			);
			$woo_gutenberg_pages['eb_pro_shop_page_id'] = $shop_page_id;
		}

		// Create Single Product Page
		if (!isset($woo_gutenberg_pages['eb_pro_single_product_page_id']) || empty($woo_gutenberg_pages['eb_pro_single_product_page_id'])) {
			$products = get_posts(array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			));

			if (!empty($products)) {
				$product_id = $products[0]->ID;
			} else {
				$product_id = 0;
			}

			$single_product_page_id = self::eb_pro_create_page(
				'Edwiser - Single Product',
				'<!-- wp:edwiser-bridge-pro/single-product -->
<div class="wp-block-edwiser-bridge-pro-single-product"><div id="eb-product-desc" data-show-category="true" data-show-ratings="true" data-show-created="true" data-show-course-access="true" data-show-enrolled="true" data-show-associated-courses="true" data-show-related-courses="true"></div></div>
<!-- /wp:edwiser-bridge-pro/single-product -->',
				'publish'
			);

			update_post_meta($single_product_page_id, 'productId', $product_id);

			$woo_gutenberg_pages['eb_pro_single_product_page_product_id'] = $product_id;
			$woo_gutenberg_pages['eb_pro_single_product_page_id'] = $single_product_page_id;
		}

		// Create Cart Page
		if (!isset($woo_gutenberg_pages['eb_pro_cart_page_id']) || empty($woo_gutenberg_pages['eb_pro_cart_page_id'])) {
			$cart_page_id = self::eb_pro_create_page(
				'Edwiser - Cart',
				'<!-- wp:edwiser-bridge-pro/cart -->
<div class="wp-block-edwiser-bridge-pro-cart"><div id="eb-cart"></div></div>
<!-- /wp:edwiser-bridge-pro/cart -->',
				'publish'
			);
			$woo_gutenberg_pages['eb_pro_cart_page_id'] = $cart_page_id;
		}

		// Create Checkout Page
		if (!isset($woo_gutenberg_pages['eb_pro_checkout_page_id']) || empty($woo_gutenberg_pages['eb_pro_checkout_page_id'])) {
			$checkout_page_id = self::eb_pro_create_page(
				'Edwiser - Checkout',
				'<!-- wp:edwiser-bridge-pro/legacy-checkout -->
<div class="wp-block-edwiser-bridge-pro-legacy-checkout"><div class="eb-legacy-checkout__wrapper">[woocommerce_checkout]</div></div>
<!-- /wp:edwiser-bridge-pro/legacy-checkout -->',
				'publish'
			);
			$woo_gutenberg_pages['eb_pro_checkout_page_id'] = $checkout_page_id;
		}

		// Create Thank You Page
		if (!isset($woo_gutenberg_pages['eb_pro_thank_you_page_id']) || empty($woo_gutenberg_pages['eb_pro_thank_you_page_id'])) {
			$thank_you_page_id = self::eb_pro_create_page(
				'Edwiser - Thank you for purchase',
				'<!-- wp:edwiser-bridge-pro/thank-you -->
<div class="wp-block-edwiser-bridge-pro-thank-you"><div id="eb-thank-you" data-show-thank-you-image="true" data-show-you-may-like-courses="true" data-thank-you-image-alt="Thank you"></div></div>
<!-- /wp:edwiser-bridge-pro/thank-you -->',
				'publish'
			);
			$woo_gutenberg_pages['eb_pro_thank_you_page_id'] = $thank_you_page_id;
		}

		// Create Enroll Students Page
		if (!isset($woo_gutenberg_pages['eb_pro_enroll_students_page_id']) || empty($woo_gutenberg_pages['eb_pro_enroll_students_page_id'])) {
			$enroll_students_page_id = self::eb_pro_create_page(
				'Enroll Students - New',
				'<!-- wp:edwiser-bridge-pro/group-management -->
<div class="wp-block-edwiser-bridge-pro-group-management"><div id="eb-group-management" data-custom-title="Enroll Students" data-hide-title="true"></div></div>
<!-- /wp:edwiser-bridge-pro/group-management -->',
				'publish'
			);
			$woo_gutenberg_pages['eb_pro_enroll_students_page_id'] = $enroll_students_page_id;
			if ($enroll_students_page_id) {
				update_post_meta($enroll_students_page_id, '_eb_pro_page_state', 'Gutenberg');
			}
		}

		$woo_gutenberg_pages['default_pages_created'] = 'yes';
		update_option('eb_woo_gutenberg_pages', $woo_gutenberg_pages);
	}

	/**
	 * Helper function to create a default page
	 *
	 * @param string $title The page title
	 * @param string $content The page content
	 * @param string $status The page status
	 * @return int The page ID
	 */
	private function eb_pro_create_page($title, $content, $status = 'draft')
	{
		// Check if page already exists with the same title
		$existing_page_query = new \WP_Query(
			array(
				'post_type'              => 'page',
				'title'                  => $title,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
			)
		);

		// If page exists, return its ID
		if ($existing_page_query->have_posts()) {
			return $existing_page_query->posts[0]->ID;
		}

		// Create the page
		$page_args = array(
			'post_title'     => $title,
			'post_content'   => $content,
			'post_status'    => $status,
			'post_type'      => 'page',
		);

		$page_id = wp_insert_post($page_args, true);

		// Add custom meta to identify this page as created by our plugin
		if (!is_wp_error($page_id)) {
			update_post_meta($page_id, '_eb_pro_created_page', 'yes');
		}

		return is_wp_error($page_id) ? 0 : $page_id;
	}

	/**
	 * Add Typeform modal HTML to footer
	 */
	public function eb_pro_add_woo_tf_modal()
	{
		// Only show on shop or product pages
		if (!is_shop() && !is_product()) {
			return;
		}

		// Check if user is admin
		if (!current_user_can('manage_options')) {
			return;
		}

		// Check if any override settings are enabled
		$override_settings = array(
			'eb_pro_enable_shop_override',
			'eb_pro_enable_single_product_override',
			'eb_pro_enable_cart_override',
			'eb_pro_enable_thank_you_override',
			'eb_pro_enable_checkout_override'
		);

		$has_enabled = false;
		foreach ($override_settings as $setting) {
			if (get_option($setting)) {
				$has_enabled = true;
				break;
			}
		}

		if (!$has_enabled) {
			return;
		}

		// Check if modal has been shown before
		if (get_option('eb_pro_woo_tf_modal_submitted')) {
			return;
		}

?>
		<div id="eb-pro-typeform-modal" class="eb-pro-typeform-modal" style="display: none;">
			<div class="eb-pro-typeform-modal-content">
				<span class="eb-pro-typeform-modal-close">&times;</span>
				<div class="eb-pro-typeform-container">
					<div data-tf-live="01JRG6RPKB3VX6XW64Y50JRR67" data-tf-on-submit="ebTypeformSubmitted"></div>
					<script src="https://embed.typeform.com/next/embed.js"></script>
					<script>
					  function ebTypeformSubmitted() {
						// Hit your AJAX endpoint to set the PHP option
						fetch('<?php echo admin_url("admin-ajax.php"); ?>?action=eb_pro_woo_tf_submit', {
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
		// update_option('eb_pro_typeform_modal_shown', true);
	}

	/**
	 * Add custom post state for Enroll Students - New page.
	 */
	public function add_gutenberg_post_state($post_states, $post)
	{
		if ('page' === $post->post_type) {
			$state = get_post_meta($post->ID, '_eb_pro_page_state', true);
			if ($state === 'Gutenberg') {
				$post_states['eb_pro_gutenberg'] = __('Gutenberg', 'edwiser-bridge-pro');
			}
		}
		return $post_states;
	}
}

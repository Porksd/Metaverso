<?php

/**
 * Cart Page Override Class
 * This class is responsible for overriding the WooCommerce cart page template.
 *
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\wooInt;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Bridge_Woo_Template_Override
{
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string The current version of this plugin.
	 */
	private $version;
	/**
	 * Template pages
	 *
	 * @since    1.0.0
	 *
	 * @var array Template pages.
	 */
	private $template_pages;

	/**
	 * Constructor
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->template_pages    = get_option('eb_woo_gutenberg_pages', array());
	}

	/**
	 * Remove Storefront sticky single add to cart(This is blocking the plugin's single product page override from working).
	 */
	public function remove_storefront_sticky_single_add_to_cart()
	{
		remove_action('storefront_after_footer', 'storefront_sticky_single_add_to_cart', 999);
	}

	/**
	 * Override cart template
	 * 
	 * @param string $template      Default template path
	 * @param string $template_name Template name
	 * @param string $template_path Template path
	 * @return string Modified template path
	 */
	public function override_cart_template($template, $template_name, $template_path)
	{
		if (has_shortcode(get_the_content(), 'bridge_woo_single_cart_checkout')) {
			return $template;
		}
		// Only override cart template
		if ($template_name === 'cart/cart.php') {
			$page_id = $this->template_pages['eb_pro_cart_page_id'];
			$page = get_post($page_id);

			if ($page && !is_wp_error($page)) {
				// Get the custom template path
				$custom_template = EB_PRO_PLUGIN_PATH . 'templates/woocommerce/cart/cart.php';

				// Check if custom template exists
				if (file_exists($custom_template)) {
					return $custom_template;
				}
			}
		}
		return $template;
	}

	/**
	 * Override cart page template
	 * 
	 * @param string $template The template path
	 * @return string Modified template path
	 */
	public function override_cart_page_template($template)
	{
		if (is_cart() && !has_shortcode(get_the_content(), 'bridge_woo_single_cart_checkout')) {
			$page_id = $this->template_pages['eb_pro_cart_page_id'];
			$page    = get_post($page_id);
			if ($page && !is_wp_error($page)) {
				// Get the custom page template
				$custom_template = EB_PRO_PLUGIN_PATH . 'templates/woocommerce/cart/page-cart.php';
				// Check if custom template exists
				if (file_exists($custom_template)) {
					return $custom_template;
				}
			}
		}
		return $template;
	}

	/**
	 * Check if WooCommerce integration is enabled and create default pages if needed
	 */
	public function override_shop_template($template)
	{
		if (is_shop()) {
			$page_id = $this->template_pages['eb_pro_shop_page_id'];
			$page = get_post($page_id);

			if ($page && !is_wp_error($page)) {
				$plugin_template = EB_PRO_PLUGIN_PATH . 'templates/woocommerce/shop.php';
				if (file_exists($plugin_template)) {
					return $plugin_template;
				}
			}
		}
		return $template;
	}

	/**
	 * Start shop wrapper for theme compatibility
	 */
	public function shop_wrapper_start()
	{
		$template = get_option('template');

		switch ($template) {
			case 'Divi':
				echo '<div id="content-area" class="clearfix">';
				break;
			case 'flatsome':
				echo '<div class="large-9 col">';
				break;
			case 'astra':
				echo '<div id="primary" class="content-area primary">';
				break;
			case 'storefront':
				echo '<div id="primary" class="content-area">';
				break;
			default:
				echo '<div class="content-area">';
				break;
		}
	}

	/**
	 * End shop wrapper for theme compatibility
	 */
	public function shop_wrapper_end()
	{
		echo '</div>';
	}

	/**
	 * Thank you page order received text.
	 *
	 * @param string $msg message.
	 * @param object $order order.
	 */
	public function thank_you_order_received_text($msg, $order)
	{
		if (! empty($order)) {
			$page_id = get_option('eb_woo_gutenberg_pages', array())['eb_pro_thank_you_page_id'];
			$page = get_post($page_id);
			if ($page && !is_wp_error($page)) {
				$msg = apply_filters('the_content', $page->post_content);
			}

			return $msg;
		}
	}

	public function thank_you_order_received_text_old($msg, $order)
	{
		$order_manager = new \app\wisdmlabs\edwiserBridgePro\includes\wooInt\Bridge_Woocommerce_Order_Manager($this->plugin_name, $this->version);
		$courses       = (array) $order_manager->get_moodle_course_ids_for_order($order);
		$setting       = get_option('eb_general', array());
		$url           = isset($setting['eb_my_courses_page_id']) ? get_permalink($setting['eb_my_courses_page_id']) : null;
		// Get the setting to check if redirection is enabled or not.
		$setting_woo_integration = get_option('eb_woo_int_settings', array());

		if (count($courses) && $url && 'yes' === $setting_woo_integration['wi_enable_redirect']) {
			ob_start();
?>
			<br />
			<span id="wi-thanq-wrapper">
				<span class="msg">
					<?php
					printf(
						__('You will be redirected to %s within next %s seconds.', 'edwiser-bridge-pro'), // @codingStandardsIgnoreLine
						'<a href="' . esc_url($url) . '">' . __('My Courses Page', 'edwiser-bridge-pro') . '</a>', // @codingStandardsIgnoreLine
						'<span id="wi-countdown">10</span>'
					);
					?>
				</span>
				<a style="cursor: pointer; font-weight: bold" id="wi-cancel-redirect" data-wi-auto-redirect="on"><?php esc_attr_e('Cancel', 'edwiser-bridge-pro'); ?></a>
			</span>
<?php
			$msg .= ob_get_clean();
		}
		return $msg;
	}

	/**
	 * Override the default WooCommerce order received template.
	 *
	 * @param string $template template.
	 * @param string $template_name template name.
	 * @param string $args arguments.
	 * @param string $template_path template path.
	 * @param string $default_path default path.
	 */
	public function override_woocommerce_order_received_template($template, $template_name, $args, $template_path, $default_path)
	{
		if ('checkout/order-received.php' === $template_name) {
			$eb_template = EB_PRO_PLUGIN_PATH . 'templates/woocommerce/order-received.php';
			if (file_exists($eb_template)) {
				$template = $eb_template;
			}
		}
		return $template;
	}

	/**
	 * Override single product template
	 * 
	 * @param string $template The template path
	 * @return string Modified template path
	 */
	public function override_single_product_template($template)
	{
		if (is_product()) {
			$page_id = $this->template_pages['eb_pro_single_product_page_id'];
			$page = get_post($page_id);

			if ($page && !is_wp_error($page)) {
				$plugin_template = EB_PRO_PLUGIN_PATH . 'templates/woocommerce/single-product.php';
				if (file_exists($plugin_template)) {
					return $plugin_template;
				}
			}
		}
		return $template;
	}

	/**
	 * Start single product wrapper for theme compatibility
	 */
	public function single_product_wrapper_start()
	{
		$template = get_option('template');

		switch ($template) {
			case 'Divi':
				echo '<div id="content-area" class="clearfix">';
				break;
			case 'flatsome':
				echo '<div class="large-9 col">';
				break;
			case 'astra':
				echo '<div id="primary" class="content-area primary">';
				break;
			case 'storefront':
				echo '<div id="primary" class="content-area">';
				break;
			default:
				echo '<div class="content-area">';
				break;
		}
	}

	/**
	 * End single product wrapper for theme compatibility
	 */
	public function single_product_wrapper_end()
	{
		echo '</div>';
	}

	/**
	 * Disable WooCommerce default cart actions to prevent duplicate cart elements
	 */
	public function disable_default_cart_actions()
	{
		// Remove cart actions that create duplicate elements
		remove_action('woocommerce_before_cart', 'woocommerce_output_all_notices', 10);
		remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
		remove_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10);
		remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);

		// Remove all cart hooks to prevent duplication
		remove_all_actions('woocommerce_before_cart');
		remove_all_actions('woocommerce_before_cart_table');
		remove_all_actions('woocommerce_before_cart_contents');
		remove_all_actions('woocommerce_cart_contents');
		remove_all_actions('woocommerce_after_cart_contents');
		remove_all_actions('woocommerce_after_cart_table');
		remove_all_actions('woocommerce_cart_collaterals');
		remove_all_actions('woocommerce_after_cart');
	}
}

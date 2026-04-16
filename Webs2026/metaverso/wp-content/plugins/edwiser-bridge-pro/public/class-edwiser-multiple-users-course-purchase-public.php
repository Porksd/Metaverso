<?php

/**
 * The Core plugin file.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\pb;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
/**
 * The public-facing functionality of the plugin.
 *
 * @link  www.wisdmlabs.com
 * @since 1.0.0
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     WisdmLabs, India <support@wisdmlabs.com>
 */
class Edwiser_Multiple_Users_Course_Purchase_Public
{


	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 *
	 * @var string The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 *
	 * @var string The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		include_once dirname(plugin_dir_path(__FILE__)) . '/public/class-edwiser-enroll-multiple-user-shortcode.php';
		add_action('woocommerce_update_cart_action_cart_updated', array($this, 'check_group_purchase'), 10, 1);
	}

	/**
	 * We have some data on cart page like create one group for all product, which needs to be deleted if the products are removed from cart.
	 * Below is the function responsible to handle this condition .
	 * Hook: woocommerce_cart_item_removed
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_item_key cart_item_key.
	 * @param string $cart     cart.
	 */
	public function bp_handle_cart_item_removal($cart_item_key, $cart)
	{
		// Check if there are any Bulk products if yes then proceed if not then uncheck 'Add all product in same group'.
		global $woocommerce;
		$items    = $woocommerce->cart->get_cart();
		$quantity = array();
		foreach ($items as $item => $values) {

			$product_id = $values['product_id'];

			$_product = wc_get_product($values['product_id']);

			if ($_product && $_product->is_type('variable') && isset($values['variation_id'])) {
				// The line item is a variable product, so consider its variation.
				$product_id = $values['variation_id'];
			}

			$item = $item;
			if (isset($values['wdm_edwiser_self_enroll']) && 'on' === $values['wdm_edwiser_self_enroll']) {
				$quantity[$product_id] = $values['quantity'];
			}
		}

		// unset session 'eb-bp-create-same-product' value if count is less than 2 as if there is only 1 product then no need to set create same group option as it will by default create 1 group.
		if (count($quantity) < 2) {
			// $_SESSION['createDifferentGroup'] = 1;
			WC()->session->set('createDifferentGroup', 1);
			WC()->session->set('eb-bp-create-same-product', 0);
		}
	}




	/**
	 * Function provides the functionality to update single group creation.
	 *
	 * @param bool $update Should update or not.
	 */
	public function update_single_group_creation($update)
	{
		// if ( session_status() === PHP_SESSION_NONE ) {
		// 	session_start();
		// }
		$createDifferentGroup = WC()->session->get('createDifferentGroup');
		if (! empty($createDifferentGroup) && 0 === $createDifferentGroup) {
			if (! $this->cart_item_qty_eql()) {
				// $_SESSION['createDifferentGroup'] = 1;
				WC()->session->set('createDifferentGroup', 1);
				WC()->session->set('eb-bp-create-same-product', 0);
			}
		}
		return $update;
	}

	/**
	 * Provides the functioanlity to check if the product is purchased by one
	 * or more than one user then prevent form deleting the product and display
	 * the warning message to the user.
	 *
	 * @param integer $prod_id product post id which is going to delete.
	 *
	 * @since 1.0.1
	 */
	public function product_delete_precheck($prod_id)
	{
		$post = get_post($prod_id);
		if ('product' === $post->post_type && $this->is_product_purchased($prod_id)) {
			$edit_post_url = admin_url('edit.php?post_status=trash&post_type=product&eb_edit_warning=delete');
			wp_safe_redirect($edit_post_url);
			die();
		}
	}

	/**
	 * Function adds the cohort name field on the woo checkout apage.
	 *
	 * @param object $checkout woo checkout page object.
	 */
	public function bp_cohort_name_fields($checkout)
	{
		global $woocommerce;
		$flag  = 0;
		$count = 0;
		if (! $woocommerce->cart) {
			return;
		}

		$items = $woocommerce->cart->get_cart();

		if (WC()->session->get('eb-bp-create-same-product')) {
			$flag = WC()->session->get('eb-bp-create-same-product');
		}

		ob_start();
		wp_nonce_field('eb_woo_checkout_nonce', 'eb_woo_checkout_nonce');
?>
		<div id="my_custom_checkout_field" class="woocommerce-billing-fields">
			<?php
			if ($flag) {
				$is_bulk_product = 0;
			?>
				<h3> <?php esc_attr_e('Group Name', 'edwiser-bridge-pro'); ?> </h3>
			<?php
				woocommerce_form_field(
					'cohort_name',
					array(
						'type'        => 'text',
						'class'       => array('my-field-class form-row-wide'),
						'required'    => true,
						'label'       => __('Group Name', 'edwiser-bridge-pro'),
						'placeholder' => __('Enter Group Name', 'edwiser-bridge-pro'),
					),
					$checkout->get_value('cohort_name')
				);
				$count = 1;
			} else {
			?>
				<h3> <?php esc_attr_e('Group Name', 'edwiser-bridge-pro'); ?> </h3>
			<?php
				foreach ($items as $item) {
					$_product = wc_get_product($item['product_id']);
					if ($_product && $_product->is_type('variable') && isset($item['variation_id'])) {
						$prod_id = $item['variation_id'];
					} else {
						$prod_id = $item['product_id'];
					}
					if (isset($item['wdm_edwiser_self_enroll']) && 'no' !== $item['wdm_edwiser_self_enroll']) {
						if (isset($item['enroll-students']) && 'yes' === $item['enroll-students']) {
							continue;
						}
						$pord_meta = get_post_meta($prod_id, 'product_options', true);
						if (isset($pord_meta['moodle_course_group_purchase']) && 'on' === $pord_meta['moodle_course_group_purchase'] && $item['quantity'] >= 1) {
							woocommerce_form_field(
								'diff_cohort_name[' . $prod_id . ']',
								array(
									'type'        => 'text',
									'class'       => array('my-field-class form-row-wide'),
									'required'    => true,
									'label'       => __('Group Name for ', 'edwiser-bridge-pro') . get_the_title($prod_id) . __(' ( ', 'edwiser-bridge-pro') . $item['quantity'] . __(' ) product', 'edwiser-bridge-pro'),
									'placeholder' => __('Enter Group Name', 'edwiser-bridge-pro'),
								),
								$checkout->get_value($prod_id)
							);
							$count = 1;
						}
					}
				}
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();

		if ($count) {
			echo $html; // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Show notice on checkoutr page if  any of the group name field is missing.
	 *
	 * @param int $order_id Cohort id.
	 */
	public function bp_check_mandatory_fields($order_id)
	{
		$post_data = wp_unslash($_POST); // @codingStandardsIgnoreLine
		if (isset($post_data['diff_cohort_name']) && ! empty($post_data['diff_cohort_name'])) {
			foreach ($post_data['diff_cohort_name'] as $value) {
				if (empty($value)) {
					wc_add_notice(__('Please enter group name in Group Name fields.', 'edwiser-bridge-pro'), 'error');
					break;
				}
			}
		}

		if (isset($post_data['cohort_name'])) {
			if (empty($post_data['cohort_name'])) {
				wc_add_notice(__('Please enter group name in Group Name fields.', 'edwiser-bridge-pro'), 'error');
			}
		}
	}




	/**
	 * Function to set the value of different group checkbox in session.
	 */
	public function check_for_different_products()
	{
		// if ( session_status() === PHP_SESSION_NONE ) {
		// 	session_start();
		// }
		if (isset($_POST['single_group'])) { // @codingStandardsIgnoreLine
			if ($_POST['single_group']) { // @codingStandardsIgnoreLine
				// First function check all products quantity.
				$same_qty_check = $this->cart_item_qty_eql();

				// Second function checks if the products are added from the enroll-studnets page.
				$enroll_stud_prod_check = $this->check_enroll_stud_prod_on_gp_creation();

				if ($same_qty_check && $enroll_stud_prod_check['result']) {
					// $_SESSION['createDifferentGroup'] = 0;
					WC()->session->set('createDifferentGroup', 0);
					WC()->session->set('eb-bp-create-same-product', 1);

					// Checking if the reuse quantity after unenrollment option is checked in backend and if not then show notice.
					$result = $this->check_ebbp_prod_opt_reuse_qty();
					if (isset($result['status']) && $result['status']) {
						$msg = __(' Successfully enabled single group creation for group products.', 'edwiser-bridge-pro');
					} else {
						wp_send_json_success(
							array(
								'status' => 0,
								'msg'    => $result['msg'],
							)
						);
					}
				} else {
					if (! $same_qty_check) {
						wp_send_json_error(__('To create same group for all the purchased group products, Please make their quantities same and update your cart.', 'edwiser-bridge-pro'));
					} else {
						wp_send_json_error($enroll_stud_prod_check['msg']);
					}
				}
			} else {
				// $_SESSION['createDifferentGroup'] = 1;
				WC()->session->set('createDifferentGroup', 1);
				WC()->session->set('eb-bp-create-same-product', 0);
				$msg = __(' Successfully disabled single group creation for group products.', 'edwiser-bridge-pro');
			}
			wp_send_json_success(
				array(
					'status' => 1,
					'msg'    => $msg,
				)
			);
		}
	}


	/**
	 * Check if the cart item quantity is same.
	 */
	private function cart_item_qty_eql()
	{
		global $woocommerce;
		$items    = $woocommerce->cart->get_cart();
		$quantity = array();

		foreach ($items as $item => $values) {
			$product_id = $values['product_id'];

			$_product = wc_get_product($values['product_id']);

			if ($_product && $_product->is_type('variable') && isset($values['variation_id'])) {
				// The line item is a variable product, so consider its variation.
				$product_id = $values['variation_id'];
			}

			$item = $item;
			if ('on' === $values['wdm_edwiser_self_enroll']) {
				$quantity[$product_id] = $values['quantity'];
			}
		}

		if (1 !== count(array_unique($quantity))) {
			return false;
		}
		return true;
	}



	/**
	 * Check if any product is added from enroll-students page as the cart should not contain the products added from the enroll-students page.
	 */
	private function check_enroll_stud_prod_on_gp_creation()
	{
		global $woocommerce;
		$items  = $woocommerce->cart->get_cart();
		$result = 1;
		$msg    = __('To create same group for all the purchased group products, Please remove products added from the enroll-students page and those are : ', 'edwiser-bridge-pro');
		$msg   .= '<ui>';
		foreach ($items as $values) {
			if (isset($values['enroll-students']) && 'yes' === $values['enroll-students']) {
				$msg   .= '<li>' . get_the_title($values['product_id']);
				$result = 0;
			}
		}
		$msg .= '</ul>';
		return array(
			'result' => $result,
			'msg'    => $msg,
		);
	}




	/**
	 * Checking if the reuse quantity after unenrollment option is checked in backend and if not then show notice
	 */
	private function check_ebbp_prod_opt_reuse_qty()
	{
		global $woocommerce;
		$items                  = $woocommerce->cart->get_cart();
		$prod_without_reuse_qty = array();

		foreach ($items as $item => $values) {
			$item         = $item;
			$prod_options = get_post_meta($values['product_id'], 'product_options', 1);

			if (! isset($prod_options['bp_reuse_quantity']) || 'on' !== $prod_options['bp_reuse_quantity']) {
				array_push($prod_without_reuse_qty, $values['product_id']);
			}
		}

		if (count($prod_without_reuse_qty) >= 1 && count($prod_without_reuse_qty) !== count($items)) {
			$msg  = __("Created group will not allow you to reuse the quantity if the users are unenrolled from group as there are some products which don't allow you to reuse quantity which are listed below:", 'edwiser-bridge-pro');
			$msg .= '<ul>';
			foreach ($prod_without_reuse_qty as $prod_id) {
				$msg .= '<li>' . get_the_title($prod_id);
			}
			$msg .= '</ul>';
			return array(
				'status' => '0',
				'msg'    => $msg,
			);
		}
		return array('status' => '1');
	}

	/**
	 * Function to show checkbox on the cart page.
	 */
	public function show_checkbox_on_cart_page()
	{
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();
		$flag  = 0;
		foreach ($items as $item => $values) {
			$item     = $item;
			$prod_id  = $values['product_id'];
			$_product = wc_get_product($prod_id);
			if ($_product && $_product->is_type('variable') && isset($values['variation_id'])) {
				// The line item is a variable product, so consider its variation.
				$prod_id = $values['variation_id'];
			}

			$product_options = get_post_meta($prod_id, 'product_options', true);
			if (isset($product_options['moodle_course_group_purchase']) && 'on' === $product_options['moodle_course_group_purchase'] && isset($values['wdm_edwiser_self_enroll']) && 'on' === $values['wdm_edwiser_self_enroll']) {
				$flag++;
			}
		}

		if ($flag > 1) {
			$checked = '';
			if (WC()->session->get('eb-bp-create-same-product')) {
				$checked = 'checked';
			}
		?>
			<div>
				<div class="wdm-diff-prod-qty-error wdm-hide">
					<i class="dashicons dashicons-warning" aria-hidden="true"></i>
					<span id="wdm-diff-prod-qty-error-msg"></span>
				</div>
				<div class="wdm-diff-prod-qty-success wdm-hide">
					<i class="dashicons dashicons-dismiss" aria-hidden="true"></i>
					<span id="wdm-diff-prod-qty-success-msg"></span>
				</div>
				<div class="wdm-cartp-group-chk-box">
					<input id="mucp-cart-group-checkbox" type="checkbox" name="mucp-group-checkbox" title="<?php esc_attr_e('This will allow to create the same product for all the courses products', 'edwiser-bridge-pro'); ?>" <?php echo esc_html($checked); ?>>
					<label><?php esc_attr_e('Add all product in same group', 'edwiser-bridge-pro'); ?></label>
				</div>
			</div>
		<?php
		}

		WC()->session->set('cart-bulk-product-count', $flag);
		// $_SESSION['cart-bulk-product-count'] = $flag;
	}

	/**
	 * Provides the functioanlity to display that product is grouped
	 * product if group purchase is enabled in single cart page.
	 *
	 * @param string $product_get_title product title.
	 * @param object $cart_item cart item object.
	 */
	public function show_grouped_product_message($product_get_title, $cart_item)
	{
		$title = __('Group Purchase Enabled', 'edwiser-bridge-pro');
		if (strpos($product_get_title, 'wdm-bulk-purchase-message') !== false) {
			return $product_get_title;
		}
		if (isset($cart_item['wdm_edwiser_self_enroll']) && 'no' !== $cart_item['wdm_edwiser_self_enroll']) {
			return sprintf('%s <div><span class = "wdm-bulk-purchase-message">%s</span></div>', $product_get_title, $title);
		} else {
			return $product_get_title;
		}
	}

	/**
	 * Function check if the current purchase is group purchase or not
	 *
	 * @param  mixed $cart_updated cart update param.
	 */
	public function check_group_purchase($cart_updated)
	{
		global $woocommerce;
		$items    = $woocommerce->cart->get_cart();
		$quantity = array();
		foreach ($items as $item => $values) {
			$product_id = $values['product_id'];

			$_product = wc_get_product($values['product_id']);
			$pmeta    = get_post_meta($product_id, 'product_options', true);

			if (isset($pmeta['moodle_course_group_purchase']) && 'on' === $pmeta['moodle_course_group_purchase']) {
				if (isset($woocommerce->cart->cart_contents[$item]['wdm_edwiser_self_enroll_checkbox'])) {
					$checkbox = $woocommerce->cart->cart_contents[$item]['wdm_edwiser_self_enroll_checkbox'];
				} else {
					$checkbox = 'no';
				}

				if (isset($values['quantity']) && $values['quantity'] > 1) {
					$woocommerce->cart->cart_contents[$item]['wdm_edwiser_self_enroll'] = (isset($values['quantity']) && $values['quantity'] > 1) ? 'on' : 'no';
				} else {
					$woocommerce->cart->cart_contents[$item]['wdm_edwiser_self_enroll'] = (isset($checkbox) && 'on' === $checkbox) ? 'on' : 'no';
				}
			}
			if ($cart_updated) {
				WC()->cart->set_session();
			}
		}
	}

	/**
	 * Provides the functioanlity to display the admin notice if the eb course
	 * related product is get deleted or edit and if the course has associated
	 * remaining qunatity one or more than one.
	 *
	 * @since 1.0.1
	 */
	public function woo_prod_edit_warning()
	{

		if (isset($_GET['eb_edit_warning']) && isset($_GET['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'eb-product-edit-nonce')) {
			$edit = 'edit' === $_GET['eb_edit_warning'] ? __('change associated courses.', 'edwiser-bridge-pro') : __('delete the product permanently.', 'edwiser-bridge-pro');
		?>
			<div id="eb_edit_warning" class="notice notice-warning is-dismissible">
				<p>
					<?php
					printf(
						/* translators: Course action.*/
						esc_html__('This product is purchased by more than one user can\'t %s', 'edwiser-bridge-pro'),
						esc_html($edit)
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Provides the functioanlity to check if the product is purchased by one or
	 * more than one user and if the product is purchased by one or more than
	 * one user then prevent post update and redirect user to product edit page.
	 *
	 * @param Integer $post post id which is goin to edit.
	 *
	 * @since 1.0.1
	 */
	public function prevent_product_edit($post)
	{
		$post_data = wp_unslash($_POST); // @codingStandardsIgnoreLine
		if (isset($post_data['action']) && 'editpost' === $post_data['action']) {
			if (isset($post_data['post_ID'])) {
				$post_id = $post_data['post_ID'];
				if ('product' === $post_data['post_type']) {
					if ($this->is_product_purchased($post_id)) {
						$old_prod_course = get_post_meta($post_id, 'product_options', true);
						$old_prod_course = isset($old_prod_course['moodle_post_course_id']) ? $old_prod_course['moodle_post_course_id'] : false;
						$new_prod_course = isset($post_data['product_options']['moodle_post_course_id']) ? $post_data['product_options']['moodle_post_course_id'] : false;
						if ($old_prod_course !== $new_prod_course) {
							$nonce         = wp_create_nonce('eb-product-edit-nonce');
							$edit_post_url = admin_url("post.php?post=$post_id&action=edit&eb_edit_warning=edit&nonce=$nonce");
							// Prevent changing the database records and return to eddit page.
							wp_safe_redirect($edit_post_url);
							die();
						}
					}
				}
			}
		}
		unset($post);
	}

	/**
	 * Provides the functioanlity to check if the product is purchased by one
	 * or more than one user.
	 *
	 * @param Integer $prod_id the product id to check is the number of
	 * availabler sites are not less than one.
	 * @return boolean true if the product avaiulable sites quantity is not
	 * less than zero. otherwise returns false.
	 *
	 * @since 1.0.1
	 */
	private function is_product_purchased($prod_id)
	{
		global $wpdb;
		$query    = "SELECT meta_value FROM  $wpdb->usermeta WHERE  `meta_key`='group_products' ";
		$result   = $wpdb->get_results($query); // @codingStandardsIgnoreLine
		$products = array();
		foreach ($result as $value) {
			$courses  = @unserialize($value->meta_value); // @codingStandardsIgnoreLine
			$products = $this->product_quantity($products, $courses);
		}
		if (array_key_exists($prod_id, $products) && $products[$prod_id] > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Provides the functionality to add the product quantity to check is the
	 * product quantity is more than zero.
	 *
	 * @param array $old_product Array of old products.
	 * @param array $new_product Array of new products.
	 */
	private function product_quantity($old_product, $new_product)
	{
		foreach ($new_product as $key => $value) {
			if (array_key_exists($key, $old_product)) {
				$old_product[$key] += $new_product[$key];
			} else {
				$old_product[$key] = $new_product[$key];
			}
			$value = $value;
		}
		return $old_product;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles()
	{
		/**
		 *Data tables libarary for enroll users.
		 *
		 * @author krunal
		 * @since 1.1.0
		 */
		wp_enqueue_style('jquery_dataTables_min', EB_PRO_PLUGIN_URL . 'public/assets/css/jquery_dataTables_min.css', array(), $this->version);
		wp_enqueue_style('responsive_dataTables_min', EB_PRO_PLUGIN_URL . 'public/assets/css/responsive_dataTables_min.css', array(), $this->version);
		wp_enqueue_style('buttons_dataTables_min', EB_PRO_PLUGIN_URL . 'public/assets/css/buttons_dataTables_min.css', array(), $this->version);
		wp_enqueue_style('select_dataTables_min', EB_PRO_PLUGIN_URL . 'public/assets/css/select_dataTables_min.css', array(), $this->version);
		global $post;
		if (null !== $post && (property_exists($post, 'post_content') && has_shortcode($post->post_content, 'bridge_woo_enroll_users')) || is_singular('product')) {
			wp_enqueue_style('wdm_bootstrap_css', EB_PRO_PLUGIN_URL . 'public/assets/css/bootstrap.min.css', array(), $this->version);

			wp_enqueue_style('bootstrap_file_input_min_css', EB_PRO_PLUGIN_URL . 'public/assets/css/fileinput.min.css', array(), '1.0.2', 'all');

			wp_enqueue_style('dashicons');
			wp_enqueue_style($this->plugin_name, EB_PRO_PLUGIN_URL . 'public/assets/css/edwiser-multiple-users-course-purchase-public.css', array(), '1.0.2', 'all');
		}

		wp_enqueue_style('wdm_front_end_css', EB_PRO_PLUGIN_URL . 'public/assets/css/edwiser-frontend-style.css', array(), $this->version);
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts()
	{
		/**
		 * Performance issue - Loaded js on shortcode page and on single product page. Edit condition if you want load in other cases.
		 *
		 * @author Pandurang
		 * @since 1.0.1
		 */
		/**
		 * Data tables libarary for enroll users.
		 *
		 * @author krunal
		 * @since 1.1.0
		 */
		wp_enqueue_script('jquery_dataTables_min', EB_PRO_PLUGIN_URL . 'public/assets/js/jquery_dataTables_min.js', array(), '3.3.4', true);
		wp_enqueue_script('dataTables_responsive_min', EB_PRO_PLUGIN_URL . 'public/assets/js/dataTables_responsive_min.js', array(), '3.3.4', true);
		wp_enqueue_script('dataTables_responsive_min', EB_PRO_PLUGIN_URL . 'public/assets/js/dataTables_fixedColumns.min.js', array(), '3.3.1', true);

		wp_enqueue_script('dataTables_select_min', EB_PRO_PLUGIN_URL . 'public/assets/js/dataTables_select_min.js', array(), '3.3.4', true);

		wp_enqueue_script('eb-pro-bulk-purchase-js', EB_PRO_PLUGIN_URL . 'public/assets/js/edwiser-multiple-users-course-purchase-public.js', array('jquery'), $this->version, false);

		wp_register_script('eb-pro-bulk-purchase-enroll-students', EB_PRO_PLUGIN_URL . 'public/assets/js/edwiser-multiple-users-course-purchse-enroll-students.js', array('jquery', 'jquery-ui-accordion'), $this->version, false);

		wp_localize_script(
			'eb-pro-bulk-purchase-js',
			'ebbpPublic',
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
				'remove_url'           => EB_PRO_PLUGIN_URL . 'public/assets/images/Remove-icon.png',
				'edit_user'            => __('Update User Data', 'edwiser-bridge-pro'),
				'emptyTable'           => __('Sorry, No users Enrolled Yet', 'edwiser-bridge-pro'),
				'emptyTableProducts'   => __('Sorry, No products available', 'edwiser-bridge-pro'),
				'enterQuantity'        => __('Please enter quantity', 'edwiser-bridge-pro'),
				'associatedCourse'     => __('Associated Courses', 'edwiser-bridge-pro'),
				'enrollUser'           => __('Enroll User', 'edwiser-bridge-pro'),
				'enrollNewUser'        => __('Enroll New User', 'edwiser-bridge-pro'),
				'cancel'               => __('Cancel', 'edwiser-bridge-pro'),
				'proctocheckout'       => __('Proceed to checkout', 'edwiser-bridge-pro'),
				'ok'                   => __('OK', 'edwiser-bridge-pro'),
				'addQuantity'          => __('Add Quantity In Group', 'edwiser-bridge-pro'),
				'addNewProductsIn'     => __('Add New Products In Group', 'edwiser-bridge-pro'),
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
			)
		);

		global $post;

		if (null !== $post && (property_exists($post, 'post_content') && has_shortcode($post->post_content, 'bridge_woo_enroll_users')) || is_singular('product')) {
			wp_enqueue_script('jquery');

			wp_register_script('bootstrap_min_js', EB_PRO_PLUGIN_URL . 'public/assets/js/bootstrap.min.js', array(), '3.3.4', true);

			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-dialog', '', array('bootstrap_min_js')); // @codingStandardsIgnoreLine

			// for jquery ui.

			wp_enqueue_script('bootstrap_canvas_js', EB_PRO_PLUGIN_URL . 'public/assets/js/plugins/canvas-to-blob.min.js', array('jquery'), $this->version, false);

			wp_enqueue_script('bootstrap_fileinput_min_js', EB_PRO_PLUGIN_URL . 'public/assets/js/fileinput/fileinput.min.js', array('jquery'), $this->version, false);

			// For jquery ui.
			wp_enqueue_script('eb-pro-bulk-purchase-js');
		}
	}


	/**
	 * Max quantity limt check
	 *
	 * @param int    $qty purchase Quantity.
	 * @param object $product woo product object.
	 */
	public function wdm_woocommerce_quantity_input_max($qty, $product)
	{
		$post_meta = get_post_meta($product->id, 'product_options', true);
		if (isset($post_meta['moodle_post_course_id'])) {
			if ('no' === $post_meta['moodle_course_group_purchase']) {
				return 1;
			}
		}
		return $qty;
	}

	/**
	 * Function checks if the product is availabel in cart or not.
	 *
	 * @param int $product_id Product id.
	 */
	private function check_cart_product($product_id)
	{
		global $woocommerce;
		foreach ($woocommerce->cart->get_cart() as $val) {
			$product = $val['data'];
			if ($product_id === $product->id) {
				update_post_meta($product->id, '_sold_individually', 'yes');
				return false;
			}
		}
		return true;
	}


	/**
	 * Thankyou message for non bulk purchase orders.
	 *
	 * @since 2.3.8
	 *
	 * @param string $msg thank you message.
	 * @param object $order woo order object.
	 */
	public function wdm_order_received_thank_you_message($msg, $order)
	{
		// check if msg already have thank you message from woo-int or other plugins.
		if (strpos($msg, 'wi-thanq-wrapper') !== false) {
			return $msg;
		}
		if (! empty($order)) {
			$items                     = $order->get_items();
			$order_id                  = $order->get_id();
			$non_bulk_purchase_producd = 0;
			foreach ($items as $item_id => $item) {
				$bulk_order   = wc_get_order_item_meta($item_id, 'Group Enrollment');
				$item_prod_id = wc_get_order_item_meta($item_id, '_product_id');

				if ('no' === $bulk_order && 'on' === apply_filters('check_group_purchase', 'off', $item_prod_id)) {
					$non_bulk_purchase_producd++;
				}
			}

			$setting = get_option('eb_general', array());
			$url     = isset($setting['eb_my_courses_page_id']) ? get_permalink($setting['eb_my_courses_page_id']) : null;
			// Get the setting to check if redirection is enabled or not.
			$setting_woo_integration = get_option('eb_woo_int_settings', array());

			if ($non_bulk_purchase_producd && $url && isset($setting_woo_integration['wi_enable_redirect']) && 'yes' === $setting_woo_integration['wi_enable_redirect'] && !get_option('eb_pro_enable_thank_you_override', false)) {
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
					<button id="wi-cancel-redirect" data-wi-auto-redirect="on"><?php esc_html_e('Cancel', 'edwiser-bridge-pro'); ?></button>
				</span>
<?php
				$msg .= ob_get_clean();
			}
			return $msg;
		}
	}
}

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

use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

/**
 * Woo int My Account Page Handler
 */
class Bridge_Woo_My_Account_Compatibility
{

	/**
	 * My account page user creation request handled here.
	 *
	 * @param int $user_id User Id.
	 * @param int $new_customer_data Data of the new customer which we are registering.
	 * @param int $password_generated Password generated.
	 */
	public function my_account_page_user_creation($user_id, $new_customer_data, $password_generated)
	{
		if (! check_value_set($_POST, 'woocommerce-register-nonce')) {
			return;
		}
		$eb_woo_settings = get_option('eb_woo_int_settings', array());
		if (check_value_set($_POST, 'woocommerce-process-checkout-nonce')) {
			return;
		}

		if (check_value_set($eb_woo_settings, 'wi_enable_my_account_user_creation') && 'yes' === $eb_woo_settings['wi_enable_my_account_user_creation']) {
			if (check_value_set($_POST, 'eb_first_name') && check_value_set($_POST, 'eb_last_name')) { // @codingStandardsIgnoreLine
				wp_update_user(
					array(
						'ID'         => $user_id, // this is the ID of the user you want to update.
						'first_name' => $_POST['eb_first_name'], // @codingStandardsIgnoreLine
						'last_name'  => $_POST['eb_last_name'], // @codingStandardsIgnoreLine
					)
				);
			}

			$user      = get_userdata($user_id);
			$user_data = array(
				'firstname' => isset($_POST['eb_first_name']) ? $_POST['eb_first_name'] : $user->first_name, // added first name retrival from user object in case request is coming from checkout page @codingStandardsIgnoreLine
				'lastname'  => isset($_POST['eb_last_name']) ? $_POST['eb_last_name'] : $user->last_name, // added last name retrival from user object in case request is coming from checkout page @codingStandardsIgnoreLine
				'password'  => $new_customer_data['user_pass'],
				'username'  => $user->user_login,
				'email'     => $user->user_email,
				'auth'      => 'manual',
			);

			// check if the email is already created or not if already created then just send the link email.
			$user_linked    = 0;
			$edwiser_bridge = new EdwiserBridge();
			if ($this->is_mdl_email_available($user->user_email)) {
				$user_linked = 1;
				$moodle_user = $edwiser_bridge->userManager()->createMoodleUser($user_data);
				if (isset($moodle_user['user_created']) && 1 === $moodle_user['user_created'] && is_object($moodle_user['user_data'])) {
					update_user_meta($user_id, 'moodle_user_id', $moodle_user['user_data']->id);
				}
			} else {
				$edwiser_bridge->userManager()->linkMoodleUser($user);
			}

			if (isset($moodle_user['user_created']) && 1 == $moodle_user['user_created'] && is_object($moodle_user['user_data'])) { // @codingStandardsIgnoreLine
				update_user_meta($user_id, 'moodle_user_id', $moodle_user['user_data']->id);

				if ($user_linked) {
					$args = array(
						'user_email' => $user_data['email'],
						'username'   => $moodle_user['user_data']->username,
						'first_name' => $user_data['firstname'],
						'last_name'  => $user_data['lastname'],
						'password'   => $user_data['password'],
					);
					// create a new action hook with user details as argument.
					do_action('eb_linked_to_existing_wordpress_to_new_user', $args);
				}
			}
		}
	}



	/**
	 * Add First & Last Name to My Account Register Form - WooCommerce
	 */
	public function wi_add_name_fields_woo_account_registration()
	{
		$eb_woo_settings = get_option('eb_woo_int_settings', array());
		if (check_value_set($eb_woo_settings, 'wi_enable_my_account_user_creation') && 'yes' === $eb_woo_settings['wi_enable_my_account_user_creation']) {
?>
			<p class="form-row form-row-first">
				<label for="eb_first_name"><?php esc_html_e('First name', 'edwiser-bridge-pro'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="eb_first_name" id="eb_first_name" value="<?php if (! empty($_POST['eb_first_name'])) esc_attr_e($_POST['eb_first_name']); // @codingStandardsIgnoreLine 
																										?>" />
			</p>

			<p class="form-row form-row-last">
				<label for="eb_last_name"><?php esc_html_e('Last name', 'edwiser-bridge-pro'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="eb_last_name" id="eb_last_name" value="<?php if (! empty($_POST['eb_last_name'])) esc_attr_e($_POST['eb_last_name']); // @codingStandardsIgnoreLine 
																									?>" />
			</p>

			<div class="clear"></div>
<?php
		}
	}

	/**
	 * Validate name fields i.e check if the fields entered are non empty on the woocommerce my-account registration page.
	 *
	 * @param WP_Error $errors  Contains the errors.
	 * @param string   $username Username.
	 * @param string   $email   Email.
	 */
	public function wi_validate_name_fields($errors, $username, $email)
	{

		if (isset($_POST['eb_first_name']) && empty($_POST['eb_first_name'])) { // @codingStandardsIgnoreLine
			$errors->add('first_name_error', __(' First name is required!', 'edwiser-bridge-pro'));
		}
		if (isset($_POST['eb_last_name']) && empty($_POST['eb_last_name'])) { // @codingStandardsIgnoreLine
			$errors->add('last_name_error', __(' Last name is required!.', 'edwiser-bridge-pro'));
		}
		return $errors;
	}




	/**
	 * This function handles user profile field update on the Moodle site.
	 *
	 * @param int $user_id User id.
	 */
	public function wi_my_account_user_profile_update($user_id)
	{

		$eb_woo_settings = get_option('eb_woo_int_settings', array());
		if (check_value_set($eb_woo_settings, 'wi_enable_my_account_field_update') && 'yes' === $eb_woo_settings['wi_enable_my_account_field_update']) {

			$moodle_user_id = get_user_meta($user_id, 'moodle_user_id', true); // get moodle user id.

			// if moodle user id is not set then return.
			if (empty($moodle_user_id)) {
				return;
			}

			/*
				* Password Update conditions will come here
				*/
			$user_data = array(
				'id'        => $moodle_user_id, // moodle user id.
				'firstname' => $_POST['account_first_name'], // @codingStandardsIgnoreLine
				'lastname'  => $_POST['account_last_name'], // @codingStandardsIgnoreLine
			);

			$user = get_userdata($user_id);

			// If the password and email is changed then only add those fields.
			if (check_value_set($_POST, 'password_1')) { // @codingStandardsIgnoreLine
				$user_data['password'] = ! empty($_POST['password_1']) ? $_POST['password_1'] : ''; // @codingStandardsIgnoreLine
			}

			if (check_value_set($_POST, 'account_email')) { // @codingStandardsIgnoreLine
				$user_data['email'] = $_POST['account_email']; // @codingStandardsIgnoreLine
			}

			$edwiser_bridge = new EdwiserBridge();
			$edwiser_bridge->userManager()->createMoodleUser($user_data, 1);
		}
	}


	/**
	 * Validate my account page fields for the profile fields update.
	 *
	 * @param array $args            Contains the arguments.
	 * @param array $user_form_data  Contains the user form data.
	 */
	public function validate_my_account_page_fields(&$args, &$user_form_data)
	{

		$user = get_userdata($user_form_data->ID);

		// check if the email is available.
		if ($user->user_email !== $user_form_data->user_email && ! $this->is_mdl_email_available($user_form_data->user_email)) {

			wc_add_notice('<strong>' . __('Email', 'edwiser-bridge-pro') . '</strong> ' . __(' already exist on Moodle please use different email.', 'edwiser-bridge-pro'), 'error');
		}
	}

	/**
	 * Check if the user email exists on the Moodle site before updating it.
	 *
	 * @param string $email Email.
	 */
	public function is_mdl_email_available($email)
	{

		$edwiser_bridge_instance = new EdwiserBridge();
		$email                   = sanitize_user($email); // get sanitized username.
		$webservice_function     = 'core_user_get_users_by_field';

		// prepare request data array.
		$request_data = array(
			'field'  => 'email',
			'values' => array($email),
		);
		$response     = $edwiser_bridge_instance->connection_helper()->connect_moodle_with_args_helper($webservice_function, $request_data);

		// return true only if username is available.
		if (1 === $response['success'] && empty($response['response_data'])) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Below are the steps to add new tab in the woocommerce my-account page.
	 */
	public function wi_add_my_courses_endpoint()
	{
		add_rewrite_endpoint('eb_my_courses', EP_ROOT | EP_PAGES);
	}

	/**
	 * Add a new query var.
	 *
	 * @param array $vars Array of query vars.
	 */
	public function wi_add_my_courses_query_vars($vars)
	{
		$vars[] = 'eb_my_courses';
		return $vars;
	}

	/**
	 * Link My Courses tab to the my account page.
	 *
	 * @param array $items Array of items.
	 */
	public function wi_add_my_courses_link_my_account($items)
	{
		$eb_woo_settings = get_option('eb_woo_int_settings', array());
		$new_items       = array();
		if (check_value_set($eb_woo_settings, 'wi_show_my_courses_on_my_account') && 'yes' === $eb_woo_settings['wi_show_my_courses_on_my_account']) {

			foreach ($items as $key => $value) {
				if ('customer-logout' === $key) {
					$new_items['eb_my_courses'] = esc_html__('My Courses', 'edwiser-bridge-pro');
				}
				$new_items[$key] = $value;
			}
		} else {
			$new_items = $items;
		}

		return $new_items;
	}

	/**
	 * Helper: Check if a block exists in given content.
	 *
	 * @param array  $blocks     Parsed blocks.
	 * @param string $block_name Full block name (namespace/block).
	 * @return bool
	 */
	private function eb_contains_block($blocks, $block_name)
	{
		foreach ($blocks as $block) {
			if ($block['blockName'] === $block_name) {
				return true;
			}
			if (! empty($block['innerBlocks']) && $this->eb_contains_block($block['innerBlocks'], $block_name)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add content to the my courses tab.
	 */
	public function wi_add_my_courses_content()
	{
		// get selected my courses page content.
		$eb_general_option = get_option('eb_general', array());
		$my_courses_page_id  = isset($eb_general_option['eb_my_courses_page_id']) ? intval($eb_general_option['eb_my_courses_page_id']) : 0;

		// If a "My Courses" page is selected.
		if ($my_courses_page_id) {
			$content = get_post($my_courses_page_id);

			if ($content) {
				$block_name = 'edwiser-bridge/my-courses';

				$has_block = has_block($block_name, $my_courses_page_id);

				// If has_block() failed, double-check using parse_blocks().
				if (! $has_block) {
					$has_block = $this->eb_contains_block(parse_blocks($content->post_content), $block_name);
				}

				if ($has_block) {
					// Render Gutenberg blocks.
					echo do_blocks($content->post_content);
				} else {
					// Render as shortcode.
					echo do_shortcode($content->post_content);
				}
			}
		} else {
			// echo the default shortcode.
			echo do_shortcode('[eb_my_courses my_courses_wrapper_title="My Courses" recommended_courses_wrapper_title="Recommended Courses" number_of_recommended_courses="4" ]');
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public function wi_flush_rewrite_rules()
	{
		flush_rewrite_rules();
	}
}

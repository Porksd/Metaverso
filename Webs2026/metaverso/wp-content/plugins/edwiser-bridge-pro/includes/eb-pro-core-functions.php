<?php

/**
 * Common functions.
 *
 * @link       https://edwiser.org
 * @since      1.0.0
 * @package    Edwiser Bridge
 */

namespace app\wisdmlabs\edwiserBridgePro;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (! function_exists('eb_get_user_enrolled_courses')) {
	function eb_get_user_enrolled_courses($user_id = null)
	{
		global $wpdb;
		$user_id = ! is_numeric($user_id) ? get_current_user_id() : (int) $user_id;

		$result = $wpdb->get_results($wpdb->prepare("SELECT course_id FROM {$wpdb->prefix}moodle_enrollment WHERE user_id=%d;", $user_id)); // @codingStandardsIgnoreLine
		$courses = array();
		foreach ($result as $key => $course) {
			$courses[] = $course->course_id;
		}

		return $courses;
	}
}

if (! function_exists('eb_get_course_enrolled_stundents')) {
	function eb_get_course_enrolled_stundents($courses = [])
	{
		global $wpdb;

		$enrolled_students = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}moodle_enrollment WHERE course_id IN ( %s )", implode(',', $courses)));

		return $enrolled_students;
	}
}

if (! function_exists('eb_get_course_buy_link')) {
	function eb_get_course_buy_link($product = null)
	{
		$eb_general = get_option('eb_woo_int_settings', array());
		$buy_now_enabled = isset($eb_general['wi_enable_buynow']) && 'yes' === $eb_general['wi_enable_buynow'];

		if (!$buy_now_enabled || null === $product || !$product->is_purchasable()) {
			return '';
		}

		$buy_now_text = isset($eb_general['wi_buy_now_text']) && !empty($eb_general['wi_buy_now_text'])
			? __($eb_general['wi_buy_now_text'], 'edwiser-bridge-pro')
			: __('Buy Now', 'edwiser-bridge-pro');

		if ('simple' === $product->get_type()) {
			$url = add_query_arg('add-to-cart', $product->get_id(), '');
			$url = add_query_arg('quantity', 1, $url);
			$url = add_query_arg('wi_buy_now', true, $url);

			return array(
				'url' => $url,
				'text' => __($buy_now_text, 'edwiser-bridge-pro')
			);
		}

		return '';
	}
}

if (! function_exists('eb_get_cart_from_session')) {
	function eb_get_cart_from_session()
	{
		global $wpdb;

		$woo_session = $_COOKIE['wp_woocommerce_session_' . md5(site_url())] ?? null;

		if (!$woo_session) return [];

		if (strpos($woo_session, '||') !== false) {
			$woo_session_key = explode('||', $woo_session)[0];
		} else {
			$woo_session_key = explode('|', $woo_session)[0];
		}

		$session_data = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
				$woo_session_key
			)
		);

		$cart_items = array();
		if ($session_data) {
			$session_value = maybe_unserialize($session_data);
			if (isset($session_value['cart'])) {
				$cart_items = maybe_unserialize($session_value['cart']);
			}
		}

		return $cart_items;
	}
}

if (! function_exists('eb_get_create_same_group')) {
	function eb_get_create_same_group()
	{
		global $wpdb;

		$woo_session = $_COOKIE['wp_woocommerce_session_' . md5(site_url())] ?? null;
		$woo_session_key = explode('||', $woo_session)[0];

		$session_data = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
				$woo_session_key
			)
		);

		$session_value = array();
		$create_same_group = false;
		if ($session_data) {
			$session_value = maybe_unserialize($session_data);
			if (isset($session_value['eb-bp-create-same-product'])) {
				$create_same_group = $session_value['eb-bp-create-same-product'] === 1;
			}
		}

		return $create_same_group;
	}
}

if (! function_exists('wdm_eb_get_user_suspended_status')) {
	/**
	 * Status.
	 *
	 * @param text $user_id user_id.
	 * @param text $course_id course_id.
	 */
	function wdm_eb_get_user_suspended_status($user_id, $course_id)
	{
		global $wpdb;
		$suspended = 0;

		if ('' === $user_id || '' === $course_id) {
			return $suspended;
		}

		// check if user has access to course.
		$suspended = $wpdb->get_var( // @codingStandardsIgnoreLine
			$wpdb->prepare(
				"SELECT suspended
				FROM {$wpdb->prefix}moodle_enrollment
				WHERE course_id=%d
				AND user_id=%d;",
				$course_id,
				$user_id
			)
		);

		return $suspended;
	}
}

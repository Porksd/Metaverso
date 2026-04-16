<?php

use function app\wisdmlabs\edwiserBridgePro\eb_get_cart_from_session;
use function app\wisdmlabs\edwiserBridgePro\eb_get_create_same_group;
use app\wisdmlabs\edwiserBridgePro\includes\wooInt as wooInt;

if (!defined('ABSPATH')) {
    exit;
}

class EdwiserBridgeBlocksPro_Checkout_API
{
    // API namespace
    private const API_NAMESPACE = 'eb/api/v1';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'eb_register_checkout_routes'));
    }

    /**
     * Register API routes.
     */
    public function eb_register_checkout_routes()
    {
        register_rest_route(self::API_NAMESPACE, '/checkout/meta', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_get_checkout_meta'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/checkout/html', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_get_checkout_html'),
            'permission_callback' => '__return_true',
        ));
    }

    public function eb_get_checkout_meta()
    {
        $current_user_id = get_current_user_id();

        $formatted_custom_fields = [];

        $custom_fields = get_option('edwiser_custom_fields');
        $modules_data = get_option('eb_pro_modules_data');

        if (!empty($custom_fields)) {
            foreach ($custom_fields as $name => $field_details) {
                if (isset($modules_data['woo_integration']) && 'active' === $modules_data['woo_integration'] && isset($field_details['enabled']) && "1" === $field_details['enabled'] && isset($field_details['checkout']) && "1" === $field_details['checkout']) {
                    $field_value = get_user_meta($current_user_id, $name, true);
                    if (empty($field_value)) {
                        $field_value = $field_details['default-val'];
                    }

                    $field_data = array(
                        'type' => $field_details['type'],
                        'value' => $field_value,
                        'required' => isset($field_details['required']) && $field_details['required'] === "1",
                        'label' => $field_details['label'],
                        'placeholder' => isset($field_details['placeholder']) ? $field_details['placeholder'] : '',
                        'class' => isset($field_details['class']) ? $field_details['class'] : '',
                        'id' => 'eb_cf_' . esc_attr($name),
                        'name' => $name
                    );

                    if ($field_details['type'] === 'select' && !empty($field_details['options'])) {
                        $formatted_options = array();
                        foreach ($field_details['options'] as $option_value => $option_label) {
                            $formatted_options[] = array(
                                'label' => $option_label,
                                'value' => $option_value
                            );
                        }
                        $field_data['options'] = $formatted_options;
                    }

                    if ($field_details['type'] === 'checkbox') {
                        $field_data['checked'] = in_array($field_value, ['on', 'true', true, '1', 1], true);
                        $field_data['value'] = in_array($field_value, ['on', 'true', true, '1', 1], true);
                    }

                    $formatted_custom_fields[] = $field_data;
                }
            }
        }


        $privacy_policy_text = '';
        if (function_exists('wc_replace_policy_page_link_placeholders')) {
            $raw_policy_text = get_option('woocommerce_checkout_privacy_policy_text', '');
            $privacy_policy_text = wc_replace_policy_page_link_placeholders($raw_policy_text);
        }

        $account_url = wc_get_page_permalink('myaccount');
        $checkout_url = wc_get_checkout_url();
        $login_url = add_query_arg(
            'redirect_to',
            urlencode($checkout_url),
            $account_url
        );

        // Groups name
        $groups = [];

        $cart_items = eb_get_cart_from_session();
        $create_same_group = eb_get_create_same_group();

        if ($create_same_group) {
            $groups[] = array(
                'id' => 'cohort_name',
                'type'        => 'text',
                'class'       => array('my-field-class form-row-wide'),
                'required'    => true,
                'label'       => __('Group Name', 'edwiser-bridge-pro'),
                'placeholder' => __('Enter Group Name', 'edwiser-bridge-pro'),
            );
        } else {
            foreach ($cart_items as $item) {
                $product = wc_get_product($item['product_id']);
                if ($product && $product->is_type('variable') && isset($item['variation_id'])) {
                    $product_id = $item['variation_id'];
                } else {
                    $product_id = $item['product_id'];
                }

                if (isset($item['wdm_edwiser_self_enroll']) && 'no' !== $item['wdm_edwiser_self_enroll']) {
                    if (isset($item['enroll-students']) && 'yes' === $item['enroll-students']) {
                        continue;
                    }

                    $product_meta = get_post_meta($product_id, 'product_options', true);

                    if (isset($product_meta['moodle_course_group_purchase']) && 'on' === $product_meta['moodle_course_group_purchase'] && $item['quantity'] >= 1) {
                        $groups[] = array(
                            'id' => $product_id,
                            'type'        => 'text',
                            'class'       => array('my-field-class form-row-wide'),
                            'required'    => true,
                            'label'       => __('Group Name for ', 'edwiser-bridge-pro') . html_entity_decode(get_the_title($product_id)) . __(' (', 'edwiser-bridge-pro') . $item['quantity'] . ')',
                            'placeholder' => __('Enter Group Name', 'edwiser-bridge-pro'),
                        );
                    }
                }
            }
        }

        // payments
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        $payment_options = [];

        if (!empty($available_gateways)) {
            foreach ($available_gateways as $gateway_id => $gateway) {
                ob_start();
                $gateway->payment_fields(); // this echoes HTML form fields
                $fields_html = ob_get_clean();

                $payment_options[] = [
                    'id' => $gateway->id,
                    'title' => $gateway->get_title(),
                    'description' => $gateway->get_description(),
                    'has_fields' => $gateway->has_fields(),
                    'enabled' => $gateway->enabled === 'yes',
                    'fields' => $fields_html,
                    'icon' => $gateway->get_icon(),
                    'method_title' => $gateway->method_title,
                ];
            }
        }

        $eb_woo_int_settings = get_option('eb_woo_int_settings', array());
        $enable_gift_purchase = isset($eb_woo_int_settings['wi_enable_purchase_for_someone_else']) && "yes" === $eb_woo_int_settings['wi_enable_purchase_for_someone_else'];

        if ($enable_gift_purchase) {
            $all_linked_products = true;
            foreach ($cart_items as $item) {
                $courses = wooInt\get_wp_courses_from_product_id($item['variation_id'] !== 0 ? $item['variation_id'] : $item['product_id']);
                if (
                    count($courses) === 0 ||
                    (isset($item['wdm_edwiser_self_enroll']) && 'no' !== $item['wdm_edwiser_self_enroll'])
                ) {
                    $all_linked_products = false;
                    break;
                }
            }
            // Only enable gift purchase if all products are simple
            $enable_gift_purchase = $all_linked_products;
        }

        return new WP_REST_Response(array(
            'groups' => $groups,
            'create_same_group' => $create_same_group,
            'custom_fields' => $formatted_custom_fields,
            'place_order_btn_text' => get_option('woocommerce_subscriptions_order_button_text'),
            'privacy_policy' => $privacy_policy_text,
            'login_url' => $login_url,
            'enable_guest_checkout' => get_option('woocommerce_enable_guest_checkout') === 'yes',
            'enable_login' => get_option('woocommerce_enable_checkout_login_reminder') === 'yes',
            'enable_signup' => get_option('woocommerce_enable_signup_and_login_from_checkout') === 'yes',
            'enable_subscriptions_signup' => get_option('woocommerce_enable_signup_from_checkout_for_subscriptions') === 'yes',
            'cart_page_url' => get_permalink(get_option('woocommerce_cart_page_id')),
            'coupons_enabled' => get_option('woocommerce_enable_coupons') === 'yes',
            'includes_tax' => get_option('woocommerce_tax_display_cart') === 'incl',
            'single_tax_total' => get_option('woocommerce_tax_total_display') === 'single',
            'payment_options' => $payment_options,
            'payment_gateways' => $available_gateways,
            'enable_gift_purchase' => $enable_gift_purchase
        ));
    }

    public function eb_get_checkout_html()
    {
        // Initialize WooCommerce session
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        // Initialize customer
        if (!WC()->customer) {
            WC()->customer = new WC_Customer(get_current_user_id(), true);
        }

        // Initialize cart
        if (!WC()->cart) {
            WC()->cart = new WC_Cart();
            WC()->cart->get_cart();
        }

        // Capture output
        ob_start();
        echo do_shortcode('[woocommerce_checkout]');
        $styles = array(
            array(
                'path' => 'assets/css/woocommerce-layout.css',
                'media' => 'all'
            ),
            array(
                'path' => 'assets/css/woocommerce-smallscreen.css',
                'media' => 'only screen and (max-width: ' . apply_filters('woocommerce_style_smallscreen_breakpoint', '768px') . ')'
            ),
            array(
                'path' => 'assets/css/woocommerce.css',
                'media' => 'all'
            ),
            array(
                'path' => 'assets/css/woocommerce-blocktheme.css',
                'media' => 'all',
                'condition' => wc_current_theme_is_fse_theme() // Only load for FSE themes
            )
        );

        foreach ($styles as $style) {
            // Skip if condition exists and is false
            if (isset($style['condition']) && !$style['condition']) {
                continue;
            }

            echo '<link rel="stylesheet" href="' . plugins_url($style['path'], WC_PLUGIN_FILE) . '" media="' . $style['media'] . '" />' . "\n";
        }
        $checkout_html = ob_get_clean();

        return new WP_REST_Response(array(
            'success' => true,
            'html' => $checkout_html,
        ), 200);
    }
}

new EdwiserBridgeBlocksPro_Checkout_API();

<?php
if (!defined('ABSPATH')) {
    exit;
}

class EdwiserBridgeBlocksPro_GroupManagement_AJAX
{
    public function __construct()
    {
        add_action('wp_ajax_eb_add_quantity', array($this, 'eb_add_quantity'));
        add_action('wp_ajax_nopriv_eb_add_quantity', array($this, 'eb_add_quantity'));

        add_action('wp_ajax_eb_add_product', array($this, 'eb_add_product'));
        add_action('wp_ajax_nopriv_eb_add_product', array($this, 'eb_add_product'));

        add_action('wp_ajax_eb_add_to_cart', array($this, 'eb_add_to_cart'));
        add_action('wp_ajax_nopriv_eb_add_to_cart', array($this, 'eb_add_to_cart'));
    }

    public function eb_add_quantity()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wdm_eb_gp_mng_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed try reloading page', 'edwiser-bridge-pro')
            ));
        }

        // Validate required parameters
        if (!isset($_POST['cohort_id']) || empty($_POST['cohort_id'])) {
            wp_send_json_error(array(
                'message' => __('Cohort ID is required.', 'edwiser-bridge-pro')
            ));
        }

        // Set WooCommerce session data
        if (WC()->session->get('eb-bp-create-same-product')) {
            WC()->session->set('eb-bp-create-same-product', 0);
        }
        WC()->session->set('addQuantity', 1);

        $currency = get_woocommerce_currency_symbol();
        $cohort_id = sanitize_text_field($_POST['cohort_id']);

        // Get products from database
        global $wpdb;
        $tbl_name = $wpdb->prefix . 'bp_cohort_info';
        $result = $wpdb->get_var($wpdb->prepare("SELECT PRODUCTS FROM {$tbl_name} WHERE MDL_COHORT_ID = %d", $cohort_id));
        $products = @unserialize($result);

        if (!$products || count($products) <= 0) {
            wp_send_json_error(array(
                'message' => __('Sorry, currently there are no group products available', 'edwiser-bridge-pro')
            ));
        }

        $products_data = array();

        foreach ($products as $product_id => $quantity) {
            $product = wc_get_product($product_id);

            if (null === $product || false === $product) {
                $prod_price = 0;
            } else {
                $prod_price = $product->get_price();
            }

            $products_data[] = array(
                'product_id' => $product_id,
                'product_name' => esc_html(get_the_title($product_id)),
                'price' => $prod_price,
            );
        }

        // Send success response
        wp_send_json_success(array(
            'cohort_id' => $cohort_id,
            'currency_symbol' => html_entity_decode($currency),
            'products' => $products_data,
        ));
    }

    public function eb_add_product()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wdm_eb_gp_mng_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed try reloading page', 'edwiser-bridge-pro')
            ));
        }

        // Validate required parameters
        if (!isset($_POST['cohort_id']) || empty($_POST['cohort_id'])) {
            wp_send_json_error(array(
                'message' => __('Cohort ID is required.', 'edwiser-bridge-pro')
            ));
        }

        // Set WooCommerce session data
        if (WC()->session->get('eb-bp-create-same-product')) {
            WC()->session->set('eb-bp-create-same-product', 0);
        }

        global $wpdb;
        $currency = get_woocommerce_currency_symbol();
        $cohort_id = sanitize_text_field($_POST['cohort_id']);

        // Get cohort information
        $tbl_coho_info = $wpdb->prefix . 'bp_cohort_info';
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT PRODUCTS, NAME, COHORT_NAME FROM {$tbl_coho_info} WHERE MDL_COHORT_ID = %d",
                $cohort_id
            ),
            ARRAY_A
        );

        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Cohort not found.', 'edwiser-bridge-pro')
            ));
        }

        $cohort_prod = maybe_unserialize($result['PRODUCTS']);
        $ava_qty = max($cohort_prod);
        $cohort_name = $result['COHORT_NAME'];
        $cohort_mems = $this->get_total_members($cohort_id);
        $min_prod_qty = $ava_qty + $cohort_mems;

        // Get all available products
        $tbl_mdl_enroll = $wpdb->prefix . 'eb_moodle_course_products';
        $all_prod = $wpdb->get_col("SELECT DISTINCT `product_id` FROM `{$tbl_mdl_enroll}`");

        if (count($all_prod) <= 0) {
            wp_send_json_error(array(
                'message' => __('Sorry, currently there are no group products available.', 'edwiser-bridge-pro')
            ));
        }

        $available_products = array();

        foreach ($all_prod as $product_id) {
            $price = get_post_meta($product_id, '_regular_price', true);

            if ('publish' === get_post_status($product_id) && isset($price) && '' !== $price) {
                $post_meta = get_post_meta($product_id, 'product_options');

                // Check if product supports group purchase
                if (isset($post_meta[0]['moodle_course_group_purchase']) && 'on' === $post_meta[0]['moodle_course_group_purchase']) {
                    // Skip if product already exists in cohort
                    if (array_key_exists($product_id, $cohort_prod)) {
                        continue;
                    }

                    $prod_price = $this->get_prod_price($product_id);

                    $available_products[] = array(
                        'product_id' => $product_id,
                        'product_name' => esc_html(get_the_title($product_id)),
                        'price' => $prod_price,
                        'min_quantity' => $min_prod_qty,
                        'total_price' => 0
                    );
                }
            }
        }

        $current_user = wp_get_current_user();
        $display_cohort_name = isset($result['NAME']) && !empty($result['NAME'])
            ? $result['NAME']
            : str_replace($current_user->user_login . '_', '', $cohort_name);

        // Send success response
        wp_send_json_success(array(
            'cohort_id' => $cohort_id,
            'cohort_name' => $display_cohort_name,
            'currency_symbol' => html_entity_decode($currency),
            'min_quantity' => $min_prod_qty,
            'available_products' => $available_products
        ));
    }

    /**
     * Get total members for a cohort
     */
    private function get_total_members($cohort_id)
    {
        global $wpdb;
        $tbl_name = $wpdb->prefix . 'moodle_enrollment';
        $result   = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT  user_id FROM {$tbl_name} WHERE mdl_cohort_id = %d", $cohort_id), ARRAY_A);
        return count($result);
    }

    /**
     * Get product price
     */
    private function get_prod_price($product_id)
    {
        $product = wc_get_product($product_id);
        if (null === $product || false === $product) {
            return 0;
        }
        return $product->get_price();
    }

    public function eb_add_to_cart()
    {
        // Validate nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wdm_eb_gp_mng_nonce')) {
            wp_send_json_error(__('Security check failed. Please reload the page.', 'edwiser-bridge-pro'));
        }

        $cohort_id = isset($_POST['cohort_id']) ? sanitize_text_field($_POST['cohort_id']) : '';
        $products = array();
        if (isset($_POST['products'])) {
            $products = json_decode(stripslashes($_POST['products']), true);
            if (!is_array($products)) {
                $products = array();
            }
        }

        if (empty($cohort_id) || empty($products)) {
            wp_send_json_error(__('Cohort ID and product quantities are required.', 'edwiser-bridge-pro'));
        }

        global $woocommerce;
        $cohort_details = array('cohort_id' => $cohort_id);

        $session_data = array();
        if (WC()->session->get('add_product_from_enroll_page')) {
            $session_data = WC()->session->get('add_product_from_enroll_page');
        }

        $has_invalid_quantity = false;

        // Validate all quantities
        foreach ($products as $quantity) {

            if ((int)$quantity <= 0) {
                $has_invalid_quantity = true;
                break;
            }
        }

        if ($has_invalid_quantity) {
            wp_send_json_error(__('All product quantities must be greater than zero.', 'edwiser-bridge-pro'));
        }

        // Add each product to cart
        foreach ($products as $product_id => $quantity) {
            $cohort_details['product_id'] = $product_id;
            $cohort_details['quantity']   = $quantity;
            $woocommerce->cart->add_to_cart(
                $product_id,
                $quantity,
                '',
                array(),
                array(
                    'cohort_id'               => $cohort_id,
                    'wdm_edwiser_self_enroll' => 'on',
                    'Group Enrollment'        => 'yes',
                    'enroll-students'         => 'yes',
                    'wdm_edwiser_self_enroll_checkbox' => 'on',
                )
            );
            array_push($session_data, $cohort_details);
        }

        $checkout_url = wc_get_checkout_url();

        if (empty($checkout_url)) {
            wp_send_json_error(__('Checkout page not found. Please contact admin.', 'edwiser-bridge-pro'));
        }

        wp_send_json_success($checkout_url);
    }
}

new EdwiserBridgeBlocksPro_GroupManagement_AJAX();

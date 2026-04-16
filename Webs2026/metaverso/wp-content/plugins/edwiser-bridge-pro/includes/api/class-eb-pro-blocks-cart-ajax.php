<?php
if (!defined('ABSPATH')) {
    exit;
}

class EdwiserBridgeBlocksPro_Cart_AJAX
{
    public function __construct()
    {
        // Register AJAX handlers for both logged-in and non-logged-in users
        add_action('wp_ajax_eb_group_purchase_status', array($this, 'eb_get_group_purchase_status'));
        add_action('wp_ajax_nopriv_eb_group_purchase_status', array($this, 'eb_get_group_purchase_status'));

        add_action('wp_ajax_eb_update_group_purchase_status', array($this, 'eb_update_group_purchase_status'));
        add_action('wp_ajax_nopriv_eb_update_group_purchase_status', array($this, 'eb_update_group_purchase_status'));
    }

    /**
     * Get group purchase status via AJAX
     */
    public function eb_get_group_purchase_status()
    {
        // Check nonce for security
        check_ajax_referer('eb_ajax_nonce', 'security');

        $same_group_purchase_enabled = false;

        if (WC()->session && WC()->session->get('eb-bp-create-same-product')) {
            $same_group_purchase_enabled = true;
        } elseif (isset($_SESSION['createDifferentGroup']) && $_SESSION['createDifferentGroup'] == 0) {
            $same_group_purchase_enabled = true;
        }

        wp_send_json_success(array(
            'same_group_purchase_enabled' => $same_group_purchase_enabled,
        ));
    }

    /**
     * Update group purchase status via AJAX
     */
    public function eb_update_group_purchase_status()
    {
        // Check nonce for security
        check_ajax_referer('eb_ajax_nonce', 'security');

        if (!isset($_POST['single_group']) || !in_array($_POST['single_group'], array('0', '1'), true)) {
            wp_send_json_error(array(
                'message' => __('Invalid parameter', 'edwiser-bridge-pro')
            ));
        }

        $single_group = absint($_POST['single_group']);

        if ($single_group) {
            // Check if cart quantities are equal for group products
            $same_qty_check = $this->cart_item_qty_eql();

            // Check if there are products from enroll-students page
            $enroll_stud_prod_check = $this->check_enroll_stud_prod_on_gp_creation();

            if ($same_qty_check && $enroll_stud_prod_check['result']) {
                $_SESSION['createDifferentGroup'] = 0;
                if (WC()->session) {
                    WC()->session->set('eb-bp-create-same-product', 1);
                }

                // Check if reuse quantity option is enabled for all products
                $result = $this->check_ebbp_prod_opt_reuse_qty();

                if (isset($result['status']) && $result['status']) {
                    $msg = __('Successfully enabled single group creation for group products.', 'edwiser-bridge-pro');
                    $status = true;
                } else {
                    wp_send_json_error(array(
                        'message' => $result['msg'],
                        'type' => 'warning'
                    ));
                }
            } else {
                if (!$same_qty_check) {
                    wp_send_json_error(array(
                        'message' => __('To create same group for all the purchased group products, please make their quantities same and update your cart.', 'edwiser-bridge-pro')
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => $enroll_stud_prod_check['msg']
                    ));
                }
            }
        } else {
            $_SESSION['createDifferentGroup'] = 1;
            if (WC()->session) {
                WC()->session->set('eb-bp-create-same-product', 0);
            }
            $msg = __('Successfully disabled single group creation for group products.', 'edwiser-bridge-pro');
            $status = true;
        }

        wp_send_json_success(array(
            'message' => $msg
        ));
    }

    private function cart_item_qty_eql()
    {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();
        $quantity = array();

        foreach ($items as $item => $values) {
            $product_id = $values['product_id'];

            $_product = wc_get_product($values['product_id']);

            if ($_product && $_product->is_type('variable') && isset($values['variation_id'])) {
                // The line item is a variable product, so consider its variation
                $product_id = $values['variation_id'];
            }

            if (isset($values['wdm_edwiser_self_enroll']) && 'on' === $values['wdm_edwiser_self_enroll']) {
                $quantity[$product_id] = $values['quantity'];
            }
        }

        if (1 !== count(array_unique($quantity)) && !empty($quantity)) {
            return false;
        }
        return true;
    }

    /**
     * Check if any product is added from enroll-students page
     */
    private function check_enroll_stud_prod_on_gp_creation()
    {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();
        $result = 1;
        $msg = __('To create same group for all the purchased group products, please remove products added from the enroll-students page and those are: ', 'edwiser-bridge-pro');
        $msg .= '<ul>';

        foreach ($items as $values) {
            if (isset($values['enroll-students']) && 'yes' === $values['enroll-students']) {
                $msg .= '<li>' . get_the_title($values['product_id']) . '</li>';
                $result = 0;
            }
        }

        $msg .= '</ul>';

        return array(
            'result' => $result,
            'msg' => $msg,
        );
    }

    /**
     * Check if reuse quantity after unenrollment option is enabled for all products
     */
    private function check_ebbp_prod_opt_reuse_qty()
    {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();
        $prod_without_reuse_qty = array();

        foreach ($items as $item => $values) {
            $prod_options = get_post_meta($values['product_id'], 'product_options', 1);

            if (!isset($prod_options['bp_reuse_quantity']) || 'on' !== $prod_options['bp_reuse_quantity']) {
                array_push($prod_without_reuse_qty, $values['product_id']);
            }
        }

        if (count($prod_without_reuse_qty) >= 1 && count($prod_without_reuse_qty) !== count($items)) {
            $msg = __("Created group will not allow you to reuse the quantity if the users are unenrolled from group as there are some products which don't allow you to reuse quantity which are listed below:", 'edwiser-bridge-pro');
            $msg .= '<ul>';
            foreach ($prod_without_reuse_qty as $prod_id) {
                $msg .= '<li>' . get_the_title($prod_id) . '</li>';
            }
            $msg .= '</ul>';
            return array(
                'status' => '0',
                'msg' => $msg,
            );
        }
        return array('status' => '1');
    }
}

new EdwiserBridgeBlocksPro_Cart_AJAX();

// Add nonce to the page for AJAX security
add_action('wp_enqueue_scripts', function () {
    wp_localize_script('jquery', 'eb_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eb_ajax_nonce')
    ));
});

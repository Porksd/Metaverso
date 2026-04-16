<?php

use function app\wisdmlabs\edwiserBridgePro\eb_get_cart_from_session;
use app\wisdmlabs\edwiserBridgePro\includes\wooInt as wooInt;

if (!defined('ABSPATH')) {
    exit;
}

class EdwiserBridgeBlocksPro_Cart_API
{
    // API namespace
    private const API_NAMESPACE = 'eb/api/v1';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'eb_register_cart_routes'));
    }

    /**
     * Register API routes.
     */
    public function eb_register_cart_routes()
    {
        register_rest_route(self::API_NAMESPACE, '/cart/products-meta', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_get_products_meta'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/cart/meta', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_get_cart_meta'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/cart/countries', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_get_countries'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/cart/validate-postcode', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_validate_postcode'),
            'permission_callback' => '__return_true',
            'args' => array(
                'country' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'postcode' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    /**
     * Retrieves metadata for specified WooCommerce products (returns an array of objects containing product's category, and * group purchase status)
     *
     * @param WP_REST_Request $request REST request containing product IDs.
     * @return WP_REST_Response Response with product metadata including ID, category, and group purchase status.
     */
    public function eb_get_products_meta($request)
    {
        $params = $request->get_json_params();

        if (!isset($params['productIds']) || !is_array($params['productIds'])) {
            return new WP_REST_Response(array(
                'message' => __("Product id's are missing", 'edwiser-bridge-pro')
            ), 400);
        }

        // Sanitize the product IDs (ensure they're integers)
        $product_ids = array_map(function ($item) {
            return [
                'id' => absint($item['id']),
                'key' => sanitize_text_field($item['key']),
            ];
        }, $params['productIds']);

        $products_meta = array();

        $cart_items = eb_get_cart_from_session();

        foreach ($product_ids as $pair) {
            $product_id = $pair['id'];
            $cart_item_key = $pair['key'];

            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            $product_options = get_post_meta($product->get_id(), 'product_options', true);

            $categories = [];

            // Check if this is a variation
            if ('subscription_variation' === $product->get_type() || "variation" === $product->get_type()) {
                // Get the parent product
                $parent_id = $product->get_parent_id();
                $parent_product = wc_get_product($parent_id);

                if ($parent_product) {
                    // Get categories from the parent product
                    $categories = wp_get_post_terms($parent_id, 'product_cat');
                }
            } else {
                // This is a simple product, get categories directly
                $categories = wp_get_post_terms($product->get_id(), 'product_cat');
            }
            $category = !empty($categories) ? $categories[0]->name : '';

            $self_enroll_enabled = (
                isset($cart_items[$cart_item_key]['wdm_edwiser_self_enroll']) &&
                $cart_items[$cart_item_key]['wdm_edwiser_self_enroll'] === 'on'
            );
            $courses = wooInt\get_wp_courses_from_product_id($product->get_id());

            // Format product data
            $products_meta[] = array(
                'id' => $product->get_id(),
                'key' => $cart_item_key,
                'category' => html_entity_decode(__($category, 'edwiser-bridge-pro'), ENT_QUOTES, 'UTF-8'),
                'group_purchase_enabled' => isset($product_options['moodle_course_group_purchase']),
                'self_enroll_enabled' => $self_enroll_enabled,
                'non_course_product' => count($courses) === 0
            );
        }

        return new WP_REST_Response(
            $products_meta,
            200
        );
    }

    /**
     * Retrieves the URLs for the WooCommerce shop page and checkout page.
     *
     * @return WP_REST_Response Response containing shop and checkout page URLs.
     */
    public function eb_get_cart_meta()
    {
        return new WP_REST_Response(array(
            'shop_page_url' => get_permalink(get_option('woocommerce_shop_page_id')),
            'checkout_url' =>  wc_get_checkout_url(),
            'coupons_enabled' => wc_coupons_enabled(),
            'prices_include_tax' => get_option('woocommerce_prices_include_tax'), // yes or no
            'tax_display' => get_option('woocommerce_tax_display_cart'), // 'excl' or 'incl'
            'includes_tax' => get_option('woocommerce_tax_display_cart') === 'incl',
            'single_tax_total' => get_option('woocommerce_tax_total_display') === 'single', // 'itemized' or 'single'
            'enable_shipping_calculator' => get_option('woocommerce_enable_shipping_calc') === 'yes'
        ), 200);
    }

    function eb_get_countries()
    {
        $wc_countries = new WC_Countries();
        $countries = $wc_countries->get_countries();
        $states = $wc_countries->get_states();
        $locale = $wc_countries->get_country_locale();
        $default_address_fields = $wc_countries->get_default_address_fields();

        $formatted_countries = array();

        foreach ($countries as $code => $name) {
            $country_data = array(
                'code' => $code,
                'name' => $name,
                'states' => array(),
                'locale' => array()
            );

            // Add states if available for this country
            if (isset($states[$code]) && !empty($states[$code])) {
                foreach ($states[$code] as $state_code => $state_name) {
                    $country_data['states'][] = array(
                        'code' => $state_code,
                        'name' => $state_name
                    );
                }
            }

            // Add locale if available for this country
            if (isset($locale[$code])) {
                $country_locale = $default_address_fields;

                foreach ($locale[$code] as $field_key => $field_props) {
                    if (isset($country_locale[$field_key])) {
                        $country_locale[$field_key] = array_merge($country_locale[$field_key], $field_props);
                    } else {
                        $country_locale[$field_key] = $field_props;
                    }
                }

                $country_data['locale'] = $country_locale;
            } else {
                $country_data['locale'] = $default_address_fields;
            }

            $formatted_countries[] = $country_data;
        }

        return new WP_REST_Response($formatted_countries, 200);
    }

    /**
     * Validate postal code against country-specific rules
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    function eb_validate_postcode($request)
    {
        $country = $request->get_param('country');
        $postcode = $request->get_param('postcode');

        // Call WooCommerce's internal validation function
        $wc_validation_result = WC_Validation::is_postcode($postcode, $country);

        if ($wc_validation_result) {
            return new WP_REST_Response(array(
                'valid' => true,
                'message' => __('Postal code is valid', 'edwiser-bridge-pro')
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Invalid postal code format for the selected country', 'edwiser-bridge-pro')
            ), 400);
        }
    }
}

new EdwiserBridgeBlocksPro_Cart_API();

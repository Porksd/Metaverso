<?php
if (!defined('ABSPATH')) {
    exit;
}

use app\wisdmlabs\edwiserBridgePro\includes\wooInt as wooInt;
use app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase as bulkPurchase;
use app\wisdmlabs\edwiserBridge as eb;

/**
 * Shop API class for handling product-related REST API endpoints.
 *
 * Provides functionality for retrieving product metadata, product details,
 * submitting product reviews, and managing related product information.
 *
 * @since 1.0.0
 */
class EdwiserBridgeBlocksPro_Shop_API
{
    // API namespace
    private const API_NAMESPACE = 'eb/api/v1/shop';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'eb_register_shop_routes'));
    }

    /**
     * Register API routes.
     */
    public function eb_register_shop_routes()
    {

        register_rest_route(self::API_NAMESPACE, '/products-meta', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_get_products_meta'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/product/(?P<id>\d+)', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_get_product_data'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/submit-review', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_product_review'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/order/(?P<key>\w+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_get_order_details'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handles product review submission for both logged-in and guest users.
     *
     * Validates review parameters, creates a new comment/review for a product,
     * and sets the review status to pending. Supports different data handling
     * for authenticated and unauthenticated users.
     *
     * @param WP_REST_Request $request The REST API request containing review data.
     * @return array|WP_Error Response with review submission status or error details.
     */
    public function eb_product_review($request)
    {
        $params = $request->get_params();
        $is_user_logged_in = is_user_logged_in();
        $current_user = wp_get_current_user();
        $product_id = $params['product_id'];

        if (empty($product_id) || empty($params['review'])) {
            return new WP_Error('missing_fields', 'Required fields are missing', array('status' => 400));
        }

        // Check if star ratings are enabled
        $ratings_enabled = wc_review_ratings_enabled();

        // Check if ratings are required
        $ratings_required = 'yes' === get_option('woocommerce_review_rating_required', 'yes');

        // Validate rating if enabled
        if ($ratings_enabled) {
            // If ratings are required but not provided
            if ($ratings_required && empty($params['rating'])) {
                return new WP_Error('missing_rating', 'Rating is required', array('status' => 400));
            }
        }

        // guest users require name and email
        if (!$is_user_logged_in && (empty($params['reviewer']) || empty($params['reviewer_email']))) {
            return new WP_Error('missing_user_info', 'Name and email are required for guest reviews', array('status' => 400));
        }

        $email = $is_user_logged_in ? $current_user->user_email : $params['reviewer_email'];
        $user_id = $is_user_logged_in ? $current_user->ID : 0;

        // Check if "verified owners only" is enabled
        $verified_owners_only = 'yes' === get_option('woocommerce_review_rating_verification_required', 'no');

        // Check if the user has purchased the product
        $customer_bought_product = wc_customer_bought_product($email, $user_id, $product_id);

        if ($verified_owners_only && !$customer_bought_product) {
            return new WP_Error(
                'not_verified_owner',
                'Only verified owners can leave reviews for this product',
                array('status' => 403)
            );
        }

        $comment_approved = $customer_bought_product ? 1 : 0;

        $comment_data = array(
            'comment_post_ID' => $product_id,
            'comment_content' => $params['review'],
            'comment_type'    => 'review',
            'comment_parent'  => 0,
            'comment_approved' => $comment_approved,
        );

        if ($is_user_logged_in) {
            $comment_data['user_id'] = $current_user->ID;
            $comment_data['comment_author'] = $current_user->display_name;
            $comment_data['comment_author_email'] = $current_user->user_email;
        } else {
            $comment_data['user_id'] = 0;
            $comment_data['comment_author'] = $params['reviewer'];
            $comment_data['comment_author_email'] = $params['reviewer_email'];
        }

        $comment_id = wp_insert_comment($comment_data);

        if (!$comment_id) {
            return new WP_Error('comment_failed', 'Failed to submit review', array('status' => 500));
        }

        // Only add rating meta if ratings are enabled and a rating was provided
        if ($ratings_enabled && isset($params['rating'])) {
            add_comment_meta($comment_id, 'rating', $params['rating']);
        }

        // Mark as verified purchaser if they bought the product
        if ($customer_bought_product) {
            add_comment_meta($comment_id, 'verified', 1);
        }

        if (function_exists('wc_get_product') && wc_get_product($product_id)) {
            update_comment_meta($comment_id, 'rating', $params['rating']);
        }

        $message = $comment_approved ?
            'Review submitted successfully' :
            'Review submitted successfully and is pending approval';

        return array(
            'success' => true,
            'message' => $message,
            'comment_id' => $comment_id
        );
    }

    /**
     * Retrieves metadata for multiple products, including enrollment status and purchase information.
     *
     * This method processes an array of product IDs and returns comprehensive metadata for each product,
     * including whether the current user is enrolled, number of enrolled students, and purchase links.
     *
     * @param WP_REST_Request $request The REST API request object containing an array of product IDs.
     * @return WP_REST_Response A REST response with an array of product metadata.
     */
    public function eb_get_products_meta($request)
    {
        $params = $request->get_json_params();

        if (!isset($params['productIds']) || !is_array($params['productIds'])) {
            return new WP_REST_Response(array(
                'message' => "Product id's are missing"
            ), 400);
        }

        // Sanitize the product IDs (ensure they're integers)
        $product_ids = array_map('absint', $params['productIds']);

        $products_meta = array();

        $user_id = apply_filters('determine_current_user', false);
        wp_set_current_user($user_id);

        $enrolled_courses = \app\wisdmlabs\edwiserBridgePro\eb_get_user_enrolled_courses($user_id);

        foreach ($product_ids as $product_id) {
            $courses = wooInt\get_wp_courses_from_product_id($product_id);
            $is_enrolled = !empty(array_intersect($courses, $enrolled_courses));
            $enrolled_students = \app\wisdmlabs\edwiserBridgePro\eb_get_course_enrolled_stundents($courses);
            $buy = \app\wisdmlabs\edwiserBridgePro\eb_get_course_buy_link(wc_get_product($product_id));

            $product = wc_get_product($product_id);
            $product_type = $product->get_type();
            $show_enrollments = count($courses) === 1
                && $enrolled_students > 0
                && !in_array($product_type, ['grouped', 'variable', 'variable-subscription', 'external']);

            $product_meta = array(
                'id' => $product_id,
                'is_enrolled' => $is_enrolled,
                'enrolled_students' => $enrolled_students,
                'buy' => $buy,
                'cart_url' => wc_get_cart_url(),
                'show_enrollments' => $show_enrollments,
            );

            if ('subscription' === $product->get_type()) {
                $product_meta['subscription_add_to_cart_text'] = __(get_option('woocommerce_subscriptions_add_to_cart_button_text'), 'edwiser-bridge-pro');
            }

            $products_meta[] = $product_meta;
        }

        return new WP_REST_Response(
            $products_meta,
            200
        );
    }

    /**
     * Retrieves comprehensive product details for a given product ID.
     *
     * This method fetches detailed product information including associated courses,
     * enrollment status, purchase options, and product-specific metadata.
     *
     * @param WP_REST_Request $request The REST API request object containing the product ID.
     * @return WP_REST_Response A REST response with detailed product information.
     */
    public function eb_get_product_data($request)
    {
        $product_id = $request->get_param('id');

        if (empty($product_id)) {
            return new WP_REST_Response(array(
                'message' => "Invalid product ID"
            ), 400);
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_REST_Response(array(
                'message' => "Product not found"
            ), 404);
        }

        $uncategorized_term = get_term_by('slug', 'uncategorized', 'product_cat');

        $product_options = get_post_meta($product_id, 'product_options', true);

        // Check if reviews are enabled
        $reviews_enabled = $product->get_reviews_allowed() && 'yes' === get_option('woocommerce_enable_reviews', 'yes');

        $group_purchase_enabled = isset($product_options['moodle_course_group_purchase']);
        $courses = wooInt\get_wp_courses_from_product_id($product_id);

        $response_data = array(
            'id' => intval($product_id),
            'date_created' => $product->get_date_created()
                ? date_i18n('F Y', strtotime($product->get_date_created()->date('Y-m-d')))
                : '',
            'cart_url' => wc_get_cart_url(),
            'shop_page_url' => get_permalink(get_option('woocommerce_shop_page_id')),
            'buy' => \app\wisdmlabs\edwiserBridgePro\eb_get_course_buy_link(wc_get_product($product_id)),
            'group_purchase_enabled' => $group_purchase_enabled,
            'related_courses' => $this->get_related_products($product_id),
            'reviews_enabled' => $reviews_enabled,
            'non_course_product' => count($courses) === 0
        );

        if ($group_purchase_enabled) {
            $eb_general = get_option('eb_general', array());
            $response_data['group_purchase_label'] = isset($eb_general['mucp_group_pur_lbl']) ? __($eb_general['mucp_group_pur_lbl'], 'edwiser-bridge-pro') : __('Enable Group Purchase', 'edwiser-bridge-pro');
        }

        if ($uncategorized_term) {
            $response_data['uncategorized_id'] = $uncategorized_term->term_id;
        }

        // check star rating is enabled if reviews are enabled
        if ($reviews_enabled) {
            $response_data['star_ratings_enabled'] = wc_review_ratings_enabled();
            $response_data['verified_owner_labels_enabled'] = 'yes' === get_option('woocommerce_review_rating_verification_label', 'yes');
            $response_data['ratings_optional'] = 'yes' === get_option('woocommerce_review_rating_required', 'yes') ? false : true;
        }

        // Add associated courses data
        $courses = wooInt\get_wp_courses_from_product_id($product_id);
        $response_data['show_course_details'] = !empty($courses);
        $response_data['associated_courses'] = $this->get_associated_courses_data($courses);

        // Add enrolled students count
        $enrolled_students = \app\wisdmlabs\edwiserBridgePro\eb_get_course_enrolled_stundents($courses);
        $response_data['enrolled_students'] = $enrolled_students;

        // Add course expiry information if applicable
        if (count($courses) === 1) {
            $course_options = get_post_meta($courses[0], 'eb_course_options', true);
            if (isset($course_options['course_expirey']) && 'yes' === $course_options['course_expirey']) {
                $response_data['course_expires_after_days'] = $course_options['num_days_course_access'] ?? 0;
            }
        }

        // Add variable product data if applicable
        if ('variable' === $product->get_type() || 'variable-subscription' === $product->get_type()) {
            $response_data['course_variations'] = $this->get_variations_data($product);
        }

        // Add grouped product data if applicable
        if ('grouped' === $product->get_type()) {
            $response_data['grouped_products'] = $this->get_grouped_products_data($product);
        }

        if ('subscription' === $product->get_type() || 'variable-subscription' === $product->get_type()) {
            $response_data['subscription_add_to_cart_text'] = __(get_option('woocommerce_subscriptions_add_to_cart_button_text'), 'edwiser-bridge-pro');
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Retrieves and processes order details for a given order ID.
     *
     * This method fetches order information, including line items, associated courses,
     * and recommended courses. It returns a structured response with order metadata,
     * product details, and course recommendations.
     *
     * @param WP_REST_Request $request The REST API request object containing the order ID.
     * @return WP_REST_Response A REST response with order details and recommended courses.
     */
    public function eb_get_order_details($request)
    {
        $setting = get_option('eb_general', array());
        $course_page_url     = isset($setting['eb_courses_page_id']) ? get_permalink($setting['eb_courses_page_id']) : null;
        $user_id = apply_filters('determine_current_user', false);
        wp_set_current_user($user_id);

        $current_user = wp_get_current_user();

        if (!$current_user || $current_user->ID === 0) {
            return new WP_REST_Response(array(
                'message' => "User not logged in",
                'course_page_url' => $course_page_url
            ), 401);
        }

        $order_key = $request->get_param('key');

        if (empty($order_key)) {
            return new WP_REST_Response(array(
                'message' => "Invalid order key",
                'course_page_url' => $course_page_url
            ), 400);
        }

        $order_id = wc_get_order_id_by_order_key($order_key);
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_REST_Response(array(
                'message' => "Order not found",
                'course_page_url' => $course_page_url
            ), 404);
        }

        if ($order->get_customer_id() !== $current_user->ID) {
            return new WP_REST_Response(array(
                'message' => "You do not have permission to view this order",
                'course_page_url' => $course_page_url
            ), 403);
        }

        $my_course_page_url     = isset($setting['eb_my_courses_page_id']) ? get_permalink($setting['eb_my_courses_page_id']) : null;
        $setting_woo_integration = get_option('eb_woo_int_settings', array());
        $redirect_after_success = isset($setting_woo_integration['wi_enable_redirect']) && 'yes' === $setting_woo_integration['wi_enable_redirect'];

        $order_data = array(
            'id' => $order_id,
            'date_created' => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : null,
            'my_course_page_url' => $my_course_page_url,
            'course_page_url' => $course_page_url,
            'shop_page_url' => get_permalink(get_option('woocommerce_shop_page_id')),
            // 'redirect_after_success' => $redirect_after_success,
            'items' => array(),
            'recommended_courses' => array(),
        );

        // Tracking courses to get recommendations for
        $course_ids = array();

        $bulk_purchase_product = 0;
        $product_with_courses = false;

        // Add order items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            $bulk_order   = wc_get_order_item_meta($item_id, 'Group Enrollment');
            $item_prod_id = wc_get_order_item_meta($item_id, '_product_id');

            if ('yes' === $bulk_order && 'on' === apply_filters('check_group_purchase', 'off', $item_prod_id)) {
                $bulk_purchase_product++;
            }

            $product_category = null;
            if ($product) {
                $product_categories = get_the_terms($product->get_id(), 'product_cat');
                if ($product_categories && !is_wp_error($product_categories)) {
                    $product_category = array(
                        'id' => $product_categories[0]->term_id,
                        'name' => $product_categories[0]->name,
                        'slug' => $product_categories[0]->slug
                    );
                }
            }

            $courses = wooInt\get_wp_courses_from_product_id($product->get_id());
            $course_expires_after_days = 0;

            if (!empty($courses)) {
                $course_ids[] = $courses[0];
                $course_options = get_post_meta($courses[0], 'eb_course_options', true);
                if (isset($course_options['course_expirey']) && 'yes' === $course_options['course_expirey']) {
                    $course_expires_after_days = $course_options['num_days_course_access'] ?? 0;
                }

                $product_with_courses = true;
            }

            $thumbnail = null;
            $alt_text = null;
            if ($product) {
                $image_id = $product->get_image_id();
                if ($image_id) {
                    $thumbnail = wp_get_attachment_image_src($image_id, 'full');
                    $thumbnail = $thumbnail ? $thumbnail[0] : null;
                    $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                }
            }

            $order_item = array(
                'name' => $item->get_name(),
                'product_id' => $item->get_product_id(),
                'thumbnail' => $thumbnail,
                'alt_text' => $alt_text,
                'permalink' => $product ? get_permalink($product->get_id()) : null,
                'category' => $product_category,
            );

            // Only add course_expires_after_days if a course exists
            if (!empty($courses) && count($courses) === 1) {
                $order_item['course_expires_after_days'] = $course_expires_after_days;
            }

            $order_data['items'][] = $order_item;
        }

        $order_data['redirect_after_success'] = $redirect_after_success && $my_course_page_url && $bulk_purchase_product == 0 && $product_with_courses;

        // Get recommended courses
        if (!empty($course_ids)) {
            $recommended_courses = [];
            $enrolled_courses = \app\wisdmlabs\edwiserBridgePro\eb_get_user_enrolled_courses();

            $total_categories = count($course_ids);
            $courses_per_category = min(3, floor(6 / $total_categories));
            if ($courses_per_category == 0) $courses_per_category = 1;

            foreach ($course_ids as $course_id) {
                $course_options = get_post_meta($course_id, 'eb_course_options', true);

                $query_args = [];

                $course_data = apply_filters('eb_content_course_before', $course_id, [], false);
                $query_args = [
                    'post_type'      => 'eb_course',
                    'post_status'    => 'publish',
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'numberposts'    => $courses_per_category,
                    'post__not_in'   => [$course_id],
                    'tax_query'      => [
                        [
                            'taxonomy' => 'eb_course_cat',
                            'field'    => 'tag_ID',
                            'terms'    => array_keys($course_data['categories'] ?? []),
                        ],
                    ],
                ];

                // Specific recommended courses
                if (!empty($course_options['enable_recmnd_courses_single_course'])) {
                    $query_args = [
                        'post_type'      => 'eb_course',
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'post__in'       => $course_options['enable_recmnd_courses_single_course'],
                        'post__not_in'   => [$course_id],
                        'numberposts'    => $courses_per_category,
                    ];
                }

                // If valid query args, get recommended courses
                if (!empty($query_args)) {
                    $courses = get_posts($query_args);

                    foreach ($courses as $rec_course) {
                        $is_enrolled = in_array($rec_course->ID, $enrolled_courses);
                        $rec_course_data = apply_filters('eb_content_course_before', $rec_course->ID, [], $is_enrolled);

                        preg_match('/^(\D+)\s*(\d+(\.\d+)?)/', $rec_course_data['course_price_formatted'] ?? '$ 0', $matches);
                        $currency = isset($matches[1]) ? trim($matches[1]) : '$';
                        $amount = isset($matches[2]) ? floatval($matches[2]) : 0;

                        $recommended_courses[] = [
                            'id'        => $rec_course->ID,
                            'title'     => __($rec_course->post_title, 'edwiser-bridge-pro'),
                            'link'      => get_permalink($rec_course->ID),
                            'excerpt'   => !empty($rec_course_data['short_description']) ? __($rec_course_data['short_description'], 'edwiser-bridge-pro') : wp_strip_all_tags(html_entity_decode(__($rec_course->post_content, 'edwiser-bridge-pro'))),
                            'category'  => !empty($rec_course_data['categories']) ? reset($rec_course_data['categories']) : __('Uncategorized', 'edwiser-bridge-pro'),
                            'thumbnail' => $rec_course_data['thumb_url'] ?? '',
                            'createdAt' => $rec_course->post_date,
                            'suspended' => \app\wisdmlabs\edwiserBridgePro\wdm_eb_get_user_suspended_status($user_id, $rec_course->ID) == true,
                            'price'     => [
                                'amount'        => $amount,
                                'currency'      => $currency,
                                'type'          => $rec_course_data['course_price_type'] ?? '',
                                'enrolled'      => $rec_course_data['is_eb_my_courses'] ?? false,
                                'originalAmount' => null,
                            ],
                        ];
                    }
                }
            }

            $order_data['recommended_courses'] = array_slice($recommended_courses, 0, 6);
        }

        return new WP_REST_Response($order_data, 200);
    }

    /**
     * Retrieves data for published courses from a list of course IDs.
     *
     * @param array $courses List of course IDs to retrieve data for.
     * @return array An array of course data including ID, title, and permalink for published courses.
     */
    private function get_associated_courses_data($courses)
    {
        $eb_general = get_option('eb_woo_int_settings', array());
        $show_associated_courses = isset($eb_general['wi_enable_asso_courses']) && 'yes' === $eb_general['wi_enable_asso_courses'];

        if (!$show_associated_courses) {
            return [];
        }

        $courses_data = array();

        if (!empty($courses)) {
            foreach ($courses as $course_id) {
                if ('publish' === get_post_status($course_id)) {
                    $courses_data[] = array(
                        'id' => $course_id,
                        'title' => __(get_the_title($course_id), 'edwiser-bridge-pro'),
                        'link' => get_permalink($course_id)
                    );
                }
            }
        }

        return $courses_data;
    }

    /**
     * Retrieves and formats variations data for a variable product.
     *
     * Extracts detailed information about product variations, including price,
     * stock status, attributes, associated courses, and group purchase options.
     *
     * @param WC_Product $product The variable product object to retrieve variations for.
     * @return array An associative array containing variations data and product attributes.
     */
    private function get_variations_data($product)
    {
        $variations_data = array();
        $available_variations = $product->get_available_variations();
        $attributes = $product->get_variation_attributes();

        if (!empty($available_variations)) {
            foreach ($available_variations as $variation) {
                $variation_id = $variation['variation_id'];
                $variation_obj = wc_get_product($variation_id);

                $product_options = get_post_meta($variation_id, 'product_options', true);

                $image_data = array();
                if (!empty($variation['image']) && $variation['image']['srcset'] !== false) {
                    $image = $variation['image'];
                    $image_data = array(
                        'id' => isset($image['attachment_id']) ? $image['attachment_id'] : 0,
                        'alt' => isset($image['alt']) ? $image['alt'] : '',
                        'name' => isset($image['title']) ? $image['title'] : basename($image['src']),
                        'thumbnail' => $image['src'],
                        'src' => $image['full_src']
                    );
                }

                $variation_data = array(
                    'id' => $variation_id,
                    'price_html' => $variation_obj->get_price_html(),
                    'attributes' => $variation['attributes'],
                    'is_in_stock' => $variation_obj->is_in_stock(),
                    'associated_courses' => array(),
                    'group_purchase_enabled' => isset($product_options['moodle_course_group_purchase']),
                );

                if (!empty($image_data)) {
                    $variation_data['image'] = $image_data;
                }

                // Get associated courses for this variation
                if (!empty($product_options) && isset($product_options['moodle_post_course_id']) && is_array($product_options['moodle_post_course_id'])) {
                    foreach ($product_options['moodle_post_course_id'] as $course_id) {
                        if ('publish' === get_post_status($course_id)) {
                            $variation_data['associated_courses'][] = array(
                                'id' => $course_id,
                                'title' => get_the_title($course_id),
                                'link' => get_permalink($course_id)
                            );
                        }
                    }
                }

                $variations_data[] = $variation_data;
            }
        }

        return array(
            'variations' => $variations_data,
            'attributes' => $attributes
        );
    }

    /**
     * Retrieves and formats data for grouped products.
     *
     * Fetches child products of a grouped product and extracts their
     * essential details such as ID, name, price, and permalink.
     *
     * @param WC_Product $product The parent grouped product object.
     * @return array An array of formatted grouped product data.
     */
    private function get_grouped_products_data($product)
    {
        $grouped_products_data = array();
        $grouped_products = $product->get_children();

        if (!empty($grouped_products)) {
            foreach ($grouped_products as $grouped_product_id) {
                $grouped_product = wc_get_product($grouped_product_id);

                $courses = wooInt\get_wp_courses_from_product_id($grouped_product_id);
                $product_options = get_post_meta($grouped_product_id, 'product_options', true);

                if ($grouped_product) {
                    $grouped_products_data[] = array(
                        'id' => $grouped_product_id,
                        'name' => __($grouped_product->get_name(), 'edwiser-bridge-pro'),
                        'price_html' => $grouped_product->get_price_html(),
                        'link' => get_permalink($grouped_product_id),
                        'associated_courses' => $this->get_associated_courses_data($courses),
                        'type' => $grouped_product->get_type(),
                        'on_sale' => $grouped_product->is_on_sale(),
                        'group_purchase_enabled' => isset($product_options['moodle_course_group_purchase']),
                    );
                }
            }
        }

        return $grouped_products_data;
    }

    /**
     * Retrieves and formats related products for a given product.
     *
     * Fetches related products, processes their details including categories,
     * images, course information, and enrollment status.
     *
     * @param int $product_id The ID of the product to find related products for.
     * @return array An array of formatted related product data.
     */
    private function get_related_products($product_id)
    {
        // get related product IDs
        $related_products_ids = wc_get_related_products($product_id, 4);

        $formatted_products = [];

        $user_id = apply_filters('determine_current_user', false);
        wp_set_current_user($user_id);

        $enrolled_courses = \app\wisdmlabs\edwiserBridgePro\eb_get_user_enrolled_courses();

        // Loop through each related product ID
        foreach ($related_products_ids as $related_id) {
            $product = wc_get_product($related_id);

            if (!$product) {
                continue;
            }

            $categories = [];
            $terms = get_the_terms($product->get_id(), 'product_cat');
            if ($terms && !is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $categories[] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'link' => get_term_link($term)
                    ];
                }
            }

            $image = [];
            $image_id = $product->get_image_id();
            if ($image_id) {
                $image_data = wp_get_attachment_image_src($image_id, 'full');
                $thumbnail = wp_get_attachment_image_src($image_id, 'thumbnail');

                if ($image_data) {
                    $image = [
                        'id' => $image_id,
                        'src' => $image_data[0],
                        'thumbnail' => $thumbnail[0],
                        'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                    ];
                }
            }

            $courses = wooInt\get_wp_courses_from_product_id($product->get_id());
            $is_enrolled = !empty(array_intersect($courses, $enrolled_courses));
            $enrolled_students = \app\wisdmlabs\edwiserBridgePro\eb_get_course_enrolled_stundents($courses);
            $buy = \app\wisdmlabs\edwiserBridgePro\eb_get_course_buy_link($product);

            $formatted_product = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'slug' => $product->get_slug(),
                'parent' => $product->get_parent_id(),
                'type' => $product->get_type(),
                'permalink' => get_permalink($related_id),
                'short_description' => $product->get_short_description(),
                'description' => $product->get_description(),
                'on_sale' => $product->is_on_sale(),
                'price_html' => $product->get_price_html(),
                'average_rating' => $product->get_average_rating(),
                'review_count' => $product->get_review_count(),
                'image' => $image,
                'categories' => $categories,
                'is_purchasable' => $product->is_purchasable(),
                'add_to_cart' => [
                    'text' => __('Add to cart'),
                    'url' => '?add-to-cart=' . $product->get_id(),
                ],
                'is_enrolled' => $is_enrolled,
                'enrolled_students' => $enrolled_students,
                'buy' => $buy,
                'cart_url' => wc_get_cart_url(),
            ];

            $formatted_products[] = $formatted_product;
        }

        return $formatted_products;
    }
}

new EdwiserBridgeBlocksPro_Shop_API();

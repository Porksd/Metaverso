<?php
/**
 * Product Page Widget
 *
 * @package Edwiser Bridge Pro
 * @since 3.0.2
 */

namespace app\wisdmlabs\edwiserBridgePro\pb\widgets;

use app\wisdmlabs\edwiserBridgePro\includes\wooInt as wooInt;
use app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase as bulkPurchase;
use app\wisdmlabs\edwiserBridge as eb;
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
/**
 * Shop Page Widget
*/
class EB_Pro_Product_Page_Widget extends \Elementor\Widget_Base {
    
    /**
    * Get widget name.
    *
    * @since 3.0.2
    * @access public
    * @return string Widget name.
    */
    public function get_name() {
        return 'eb-pro-product-page-widget';
    }
    
    /**
    * Get widget title.
    *
    * @since 3.0.2
    * @access public
    * @return string Widget title.
    */
    public function get_title() {
        return __( 'Edwiser Bridge Pro Product Page', 'edwiser-bridge-pro' );
    }
    
    /**
    * Get widget icon.
    *
    * @since 3.0.2
    * @access public
    * @return string Widget icon.
    */
    public function get_icon() {
        return 'eicon-product-page';
    }
    
    /**
    * Get widget categories.
    *
    * @since 3.0.2
    * @access public
    * @return array Widget categories.
    */
    public function get_categories() {
        return [ 'eb-pro-widgets' ];
    }
    
    /**
    * Register widget controls.
    *
    * @since 3.0.2
    * @access protected
    */
    protected function _register_controls() {
        $this->start_controls_section(
                'content_section',
                [
                'label' => __( 'Content', 'edwiser-bridge-pro' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );
        
        // get any woo product
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 1,
        );
        $products = get_posts( $args );
        if( ! empty( $products ) ) {
			$product_id = $products[0]->ID;
		} else {
			$product_id = 0;
		}
        $this->add_control(
                'product_id',
                [
                'label'       => __( 'Product ID', 'edwiser-bridge-pro' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'input_type'  => 'number',
                'placeholder' => __( 'Enter product ID', 'edwiser-bridge-pro' ),
                'default'     => $product_id,
                ]
        );
        // add control to show/hide product category breadcrumb
        $this->add_control(
                'show_product_category',
                [
                'label'        => __( 'Show Category', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                ]
        );

        // add control to show ratings in the product page
        $this->add_control(
                'show_product_ratings',
                [
                'label'        => __( 'Show Ratings', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                ]
        );

        // show 'Last Updated' date
        $this->add_control(
                'show_last_updated',
                [
                'label'        => __( 'Show \'Created\'', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                ]
        );

        // show 'course acces'
        $this->add_control(
                'show_course_access',
                [
                'label'        => __( 'Show \'Course Expiry\'', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                ]
        );

        // show enrolled students
        $this->add_control(
                'show_enrolled_students',
                [
                'label'        => __( 'Show \'Enrolled\'', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                ]
        );

        // show associated courses
        $this->add_control(
                'show_associated_courses',
                [
                'label'        => __( 'Show \'Associated Courses\'', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                ]
        );

        // add a information link to configure more settings
        $this->add_control(
                'more_settings',
                [
                'label' => '',
                'type'  => \Elementor\Controls_Manager::RAW_HTML,
                'raw'   => __( 'To configure ‘Buy now’ button click', 'edwiser-bridge-pro') . ' <a href="' . admin_url( 'admin.php?page=eb-settings&tab=woo_int_settings' ) . '" target="_blank" style="color:#FA9941;">' . __( 'here', 'edwiser-bridge-pro' ) . '</a>',
                ]
        );

    
        $this->end_controls_section();
    }
    
    /**
    * Render widget output on the frontend.
    *
    * @since 3.0.2
    * @access protected
    */
    protected function render() {
        $settings = $this->get_settings_for_display();

        global $post, $product;
        $product_id = $post->ID;

        // check if this is a product page
        if ( ! is_product() ) {
            // check product id in settings else get it from the current product
            if ( ! empty( $settings['product_id'] ) ) {
                $product_id = $settings['product_id'];
            }
        }

        // get product data
        ?>
        <div class="eb-pro-product-page-widget">
            <!-- product category  breadcrumb -->
            <?php
            $product = wc_get_product( $product_id );

            $slash      = '<span class="slash"> / </span>';
            $categories = get_the_terms( $product_id, 'product_cat' );
            ?>
            <div class="eb-pro-product-category-breadcrumb">
                <?php
                echo '<span class="categories">' . __( 'All Courses', 'edwiser-bridge-pro' ) . '</span>';
                foreach ( $categories as $category ) {
                    $category_link = wc_get_page_permalink( 'shop' ) . '?category=' . $category->term_id;
                    echo $slash . '<span class="categories"><a href="' . $category_link . '">' . $category->name . '</a></span>';
                }
                echo $slash . '<span class="product-title">' . get_the_title( $product_id ) . '</span>';
                ?>
            </div>
            <div class="eb-pro-product-container">
                <div class="eb-pro-product-wrap">
                    <div class="eb-pro-product-page-body">
                        <div class="eb-pro-product-page-header">
                            <h1 class="product-title"><?php echo $product->get_title(); ?></h1>
                            <div class="additional-details">
                                <?php if ( isset( $settings['show_product_category'] ) && 'yes' === $settings['show_product_category'] ) { ?>
                                <div class="category">
                                    <span class="category-label"><?php _e( 'Category', 'edwiser-bridge-pro' ); ?></span>
                                    <!-- <span class="category-value"><?php echo wc_get_product_category_list( $product_id, ', ', '', '' ); ?></span> -->
                                    <span class="category-value">
                                        <?php
                                        $separator = '';
                                        foreach ( $categories as $category ) {
                                            $category_link = wc_get_page_permalink( 'shop' ) . '?category=' . $category->term_id;
                                            echo $separator . '<a href="' . $category_link . '">' . $category->name . '</a>';
                                            $separator = ', ';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <?php } ?>
                                <?php if ( isset( $settings['show_product_ratings'] ) && 'yes' === $settings['show_product_ratings'] ) { ?>
                                <div class="product-reviews">
                                    <?php
                                    if ( 'yes' === get_option( 'woocommerce_enable_review_rating' ) ) {
                                    ?>
                                        <span class="review-title"><?php _e( 'Review', 'edwiser-bridge-pro' ); ?></span>
                                        <div class="review-detail">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="Star">
                                                <path id="Vector" d="M11.622 15C11.4397 15 11.2574 14.9561 11.0961 14.8611L8.07376 13.2019C8.02468 13.1727 7.96858 13.1727 7.91949 13.2019L4.89718 14.8611C4.34321 15.1681 3.656 14.9415 3.3685 14.3641C3.24929 14.1302 3.21422 13.8671 3.2563 13.6112L3.83131 10.1028C3.83832 10.0589 3.8243 10.0005 3.78222 9.95661L1.34193 7.47146C0.893146 7.01829 0.886133 6.27275 1.3209 5.80495C1.49621 5.61491 1.7206 5.49796 1.97304 5.46142L5.34597 4.94977C5.40207 4.94246 5.44414 4.90592 5.47219 4.85475L6.97984 1.6606C7.25332 1.07586 7.93352 0.834658 8.4945 1.11972C8.7189 1.23667 8.90122 1.42671 9.01342 1.6606L10.5211 4.85475C10.5421 4.90592 10.5912 4.94246 10.6473 4.94977L14.0272 5.46142C14.6443 5.55644 15.0791 6.1558 14.9879 6.79901C14.9529 7.05484 14.8336 7.29604 14.6583 7.47877L12.211 9.95661C12.169 9.99316 12.1549 10.0516 12.1619 10.1101L12.737 13.6186C12.8421 14.2618 12.4284 14.8757 11.8113 14.9854C11.7482 14.9927 11.6851 15 11.622 15Z" fill="#F98012"/>
                                            </g>
                                        </svg>
                                        <?php
                                        echo '<span class="rating">' . round( $product->get_average_rating(), 1 ) . ' (' . $product->get_rating_count() . ')</span>';
                                        ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="eb-pro-product-page-thumbnails-wrapper">
                            <div class="product-main-image">
                                <?php
                                if( has_post_thumbnail( $product_id ) ) {
                                    echo get_the_post_thumbnail( $product_id, 'full' );
                                } else {
                                    $default_img = EB_PRO_PLUGIN_URL . 'public/assets/images/course-bg.png';
                                    echo '<img src="' . $default_img . '" alt="Course Image" />';
                                }
                                ?>
                            </div>
                            <?php
                            $attachment_ids = $product->get_gallery_image_ids();
                            if ( $attachment_ids ) {
                                ?>
                                <div class="product-small-images">
                                    <?php
                                    if( has_post_thumbnail( $product_id ) ) {
                                        $thumb_img = get_the_post_thumbnail( $product_id, 'full', array( 'class' => 'selected' ) );
                                    } else {
                                        $default_img = EB_PRO_PLUGIN_URL . 'public/assets/images/course-bg.png';
                                        $thumb_img = '<img class="selected" src="' . $default_img . '" alt="Course Image" />';
                                    }
                                    echo '<div class="product-small-image">' . $thumb_img . '</div>';
                                    foreach ( $attachment_ids as $attachment_id ) {
                                        echo '<div class="product-small-image" data-image-id="' . $attachment_id . '">' . wp_get_attachment_image( $attachment_id, 'thumbnail' ) . '</div>';
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="eb-pro-product-page-sidebar">
                        <div class="product-price-section">
                            <!-- Sale label -->
                            <?php
                                if ( $product->is_on_sale() ) {
                                    echo '<span class="eb-pro-product-sale-badge">' . esc_html__( 'Sale', 'edwiser-bridge-pro' ) . '</span>';
                                }
                            ?>
                            <span class="price"><?php echo $product->get_price_html(); ?></span>
                        </div>
                        <?php
                        $is_variable = false;
                        if( 'variable' === $product->get_type() || 'variable-subscription' === $product->get_type() ) {
                            $is_variable = true;
                            $available_variations = $product->get_available_variations();
                            $attributes = $product->get_variation_attributes();
                            $attribute_keys  = array_keys( $attributes );
                            $selected_attributes = $product->get_default_attributes();
                            $variations_json = wp_json_encode( $available_variations );
                            $variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

                            // enqueue variation scripts
                            wp_enqueue_script( 'wc-add-to-cart-variation' );
                        }
                        if ( $product->is_purchasable() || 'grouped' === $product->get_type() ) {
                            ?>
                            <div class="add-to-cart-section">
                                <form class="<?php echo $is_variable ? 'variations_form ' : ''; ?>cart" action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product_id ); ?>" <?php echo $is_variable ? 'data-product_variations="' . $variations_attr . '"': ''; ?>>
                                    <?php

                                    if( $is_variable ) {
                                        ?>
                                        <?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
                                            <p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
                                        <?php else : ?>
                                            <table class="variations" cellspacing="0" role="presentation">
                                                <tbody>
                                                    <?php foreach ( $attributes as $attribute_name => $options ) : ?>
                                                        <tr>
                                                            <th class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label></th>
                                                            <td class="value">
                                                                <?php
                                                                    wc_dropdown_variation_attribute_options(
                                                                        array(
                                                                            'options'   => $options,
                                                                            'attribute' => $attribute_name,
                                                                            'product'   => $product,
                                                                        )
                                                                    );
                                                                    echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : '';
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif;
                                        ?>
                                        <div class="single_variation_wrap">
                                            <div class="single_variation"></div>
                                        <?php
                                        }

                                        $modules_data = get_option( 'eb_pro_modules_data' );
                                        if ( isset( $modules_data['bulk_purchase'] ) && 'active' === $modules_data['bulk_purchase'] ) {
                                            $bulk_purchase = new bulkPurchase\Eb_Bp_Self_Enroll();
                                            $bulk_purchase->wdm_ld_woocommerce_before_add_to_cart_button();
                                        }

                                        // check product is grouped product
                                        if ( 'grouped' === $product->get_type() ) {
                                            $grouped_products = $product->get_children();
                                            if ( ! empty( $grouped_products ) ) {
                                                // show quantity and product name and price for each product
                                                foreach ( $grouped_products as $grouped_product_id ) {
                                                    $grouped_product = wc_get_product( $grouped_product_id );
                                                    ?>
                                                    <div class="grouped-product">
                                                        <div class="grouped-product-name">
                                                            <a href="<?php echo esc_url( get_permalink( $grouped_product_id ) ); ?>" target="_blank"><?php echo esc_html( $grouped_product->get_name() ); ?></a>
                                                        </div>
                                                        <div class="grouped-product-price">
                                                            <?php echo $grouped_product->get_price_html(); ?>
                                                        </div>
                                                        <div class="grouped-product-quantity quantity-wrap">
                                                            <button class="quantity-minus">-</button>
                                                            <input type="number" class="quantity-input" value="0" min="0" name="quantity[<?php echo $grouped_product_id; ?>]" title="Qty" size="4" pattern="[0-9]*" inputmode="numeric">
                                                            <button class="quantity-plus">+</button>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                                <input type="hidden" name="add-to-cart" value="<?php echo absint( $product_id ); ?>">
                                                <?php
                                            }
                                        } else{
                                        ?>
                                            <div class="quantity-wrap">
                                                <button class="quantity-minus">-</button>
                                                <input type="number" class="quantity-input" value="1" min="1" name="quantity" title="Qty" size="4" pattern="[0-9]*" inputmode="numeric">
                                                <button class="quantity-plus">+</button>
                                                <a href="<?php echo wc_get_cart_url(); ?>" class="view-cart-btn"><?php _e( 'View Cart', 'edwiser-bridge-pro' ); ?></a>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <div class="cart-cta-wrap">
                                            <?php
                                            $cart_products = WC()->cart->get_cart();
                                            $cart_product_ids = array();
                                            foreach ( $cart_products as $cart_product ) {
                                                $cart_product_ids[] = $cart_product['product_id'];
                                            }
                                            $in_cart = in_array( get_the_ID(), $cart_product_ids ) ? true : false;
                                            $cart_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 19 19" fill="none">
                                                <path d="M1.97949 3.36458C1.97949 3.20711 2.04205 3.05608 2.1534 2.94473C2.26475 2.83338 2.41577 2.77083 2.57324 2.77083H3.01499C3.76708 2.77083 4.21833 3.2767 4.47562 3.74695C4.64741 4.06045 4.7717 4.42383 4.86908 4.75316C4.89541 4.75108 4.92182 4.75003 4.94824 4.74999H14.8425C15.4996 4.74999 15.9746 5.37858 15.7941 6.01112L14.3469 11.0849C14.2172 11.54 13.9427 11.9404 13.565 12.2256C13.1873 12.5107 12.727 12.665 12.2537 12.6651H7.54491C7.06792 12.6651 6.60413 12.5085 6.22482 12.2193C5.84551 11.9301 5.57168 11.5243 5.44541 11.0643L4.84374 8.86983L3.84624 5.50683L3.84545 5.50049C3.72195 5.05162 3.60637 4.63124 3.43378 4.31774C3.26833 4.01295 3.13533 3.95833 3.01578 3.95833H2.57324C2.41577 3.95833 2.26475 3.89577 2.1534 3.78442C2.04205 3.67307 1.97949 3.52205 1.97949 3.36458ZM5.99562 8.58166L6.59016 10.75C6.70891 11.1791 7.0992 11.4776 7.54491 11.4776H12.2537C12.4688 11.4776 12.6781 11.4075 12.8498 11.2779C13.0215 11.1484 13.1463 10.9664 13.2053 10.7595L14.5804 5.93749H5.21345L5.98453 8.5397L5.99562 8.58166ZM8.70866 15.0417C8.70866 15.4616 8.54184 15.8643 8.24491 16.1612C7.94798 16.4582 7.54525 16.625 7.12533 16.625C6.7054 16.625 6.30267 16.4582 6.00574 16.1612C5.70881 15.8643 5.54199 15.4616 5.54199 15.0417C5.54199 14.6217 5.70881 14.219 6.00574 13.9221C6.30267 13.6251 6.7054 13.4583 7.12533 13.4583C7.54525 13.4583 7.94798 13.6251 8.24491 13.9221C8.54184 14.219 8.70866 14.6217 8.70866 15.0417ZM7.52116 15.0417C7.52116 14.9367 7.47946 14.836 7.40522 14.7618C7.33099 14.6875 7.23031 14.6458 7.12533 14.6458C7.02034 14.6458 6.91966 14.6875 6.84543 14.7618C6.7712 14.836 6.72949 14.9367 6.72949 15.0417C6.72949 15.1466 6.7712 15.2473 6.84543 15.3216C6.91966 15.3958 7.02034 15.4375 7.12533 15.4375C7.23031 15.4375 7.33099 15.3958 7.40522 15.3216C7.47946 15.2473 7.52116 15.1466 7.52116 15.0417ZM14.2503 15.0417C14.2503 15.4616 14.0835 15.8643 13.7866 16.1612C13.4896 16.4582 13.0869 16.625 12.667 16.625C12.2471 16.625 11.8443 16.4582 11.5474 16.1612C11.2505 15.8643 11.0837 15.4616 11.0837 15.0417C11.0837 14.6217 11.2505 14.219 11.5474 13.9221C11.8443 13.6251 12.2471 13.4583 12.667 13.4583C13.0869 13.4583 13.4896 13.6251 13.7866 13.9221C14.0835 14.219 14.2503 14.6217 14.2503 15.0417ZM13.0628 15.0417C13.0628 14.9367 13.0211 14.836 12.9469 14.7618C12.8727 14.6875 12.772 14.6458 12.667 14.6458C12.562 14.6458 12.4613 14.6875 12.3871 14.7618C12.3129 14.836 12.2712 14.9367 12.2712 15.0417C12.2712 15.1466 12.3129 15.2473 12.3871 15.3216C12.4613 15.3958 12.562 15.4375 12.667 15.4375C12.772 15.4375 12.8727 15.3958 12.9469 15.3216C13.0211 15.2473 13.0628 15.1466 13.0628 15.0417Z" fill="white"/>
                                                </svg>';
                                            $added_in_cart = '<svg class="item-in-cart" width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="Icons"><path id="Path (Stroke)" fill-rule="evenodd" clip-rule="evenodd" d="M3.54911 8.04633C3.83686 7.76231 4.30341 7.76231 4.59116 8.04633L6.66663 10.0775L11.4088 5.37966C11.6965 5.09564 12.1631 5.09564 12.4508 5.37966C12.7386 5.66368 12.7386 6.12416 12.4508 6.40818L7.18765 11.6203C6.8999 11.9043 6.43335 11.9043 6.1456 11.6203L3.54911 9.07485C3.26135 8.79083 3.26135 8.33035 3.54911 8.04633Z" fill="white"/></g></svg>';
                                            if( $in_cart ) {
                                                $cart_icon = '<span class="item-in-cart-wrap">' . $cart_icon . $added_in_cart . '</span>';
                                            }
                                            if( ! $is_variable ){
                                                $args = array(
                                                    'quantity' => 1,
                                                );
                                                echo apply_filters( 'woocommerce_loop_add_to_cart_link', // WPCS: XSS ok.
                                                    sprintf( '<button type="submit" class="%s">%s</button>',
                                                        'eb-pro-cart-cta',
                                                        $cart_icon . esc_html( $product->single_add_to_cart_text() )
                                                    ),
                                                    $product, $args );
                                                
                                                global $eb_pro_plugin_data;
                                                $public_class = new \app\wisdmlabs\edwiserBridgePro\pb\Bridge_Woocommerce_Public( $eb_pro_plugin_data['plugin_slug'], $eb_pro_plugin_data['plugin_version'] );
                                                $args = array( 'product' => $product, 'class' => 'eb-pro-cart-cta' );
                                                echo str_replace( 'button ', '', $public_class :: get_buy_now_button( $args ) );
                                            } else {
                                                ?>
                                                <button type="submit" class="eb-pro-cart-cta" disabled><?php echo $cart_icon . esc_html( $product->single_add_to_cart_text() ); ?></button>
                                                <input type="hidden" name="add-to-cart" value="<?php echo absint( $product_id ); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo absint( $product_id ); ?>">
                                                <input type="hidden" name="variation_id" class="variation_id" value="">
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <?php
                                        if( $is_variable ) {
                                            ?>
                                        
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </form>                
                            </div>
                            <?php
                        }
                        ?>
                        <?php
                            global $wpdb;
                            if( ! $is_variable ) {
                                $courses = $courses = wooInt\get_wp_courses_from_product_id( $product_id );
                                if ( ! empty( $courses ) && 'yes' === $settings['show_associated_courses'] ) {
                                    ?>
                                    <div class="associated-courses">
                                        <h2 class="associated-courses-title"><?php _e( 'Associated Courses', 'edwiser-bridge-pro' ); ?></h2>
                                        <div class="associated-courses-list">
                                            <?php
                                            foreach ( $courses as $course_id ) {
                                                $course = get_post( $course_id );
                                                ?>
                                                <div class="associated-course">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" viewBox="0 0 14 15" fill="none">
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M4.85289 11.3945C4.60438 11.1427 4.60438 10.7345 4.85289 10.4827L7.79685 7.5L4.85289 4.5173C4.60438 4.26551 4.60438 3.85729 4.85289 3.6055C5.10141 3.35372 5.50433 3.35372 5.75284 3.6055L9.14678 7.0441C9.3953 7.29589 9.3953 7.70411 9.14678 7.9559L5.75284 11.3945C5.50433 11.6463 5.10141 11.6463 4.85289 11.3945Z" fill="#C7660E"/>
                                                    </svg>
                                                    <a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $course_id ) ); ?></a>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                if ( ! empty( $available_variations ) ) {
                                    foreach ( $available_variations as $single_variation ) {
                                        $return          = '';
                                        $variation_id    = $single_variation['variation_id'];
                                        $product_options = get_post_meta( $variation_id, 'product_options', true );
                
                                        if ( ! empty( $product_options ) ) {
                                            if ( isset( $product_options['moodle_post_course_id'] ) && is_array( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
                                                ?>
                                                <div class="associated-courses" style="display:none;" data-variation-id="<?php echo esc_attr( $variation_id ); ?>">
                                                    <h2 class="associated-courses-title"><?php _e( 'Associated Courses', 'edwiser-bridge-pro' ); ?></h2>
                                                    <div class="associated-courses-list">
                                                        <?php
                                                        foreach ( $product_options['moodle_post_course_id'] as $single_course_id ) {
                                                            if ( 'publish' === get_post_status( $single_course_id ) ) {
                                                                ?>
                                                                <div class="associated-course">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" viewBox="0 0 14 15" fill="none">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M4.85289 11.3945C4.60438 11.1427 4.60438 10.7345 4.85289 10.4827L7.79685 7.5L4.85289 4.5173C4.60438 4.26551 4.60438 3.85729 4.85289 3.6055C5.10141 3.35372 5.50433 3.35372 5.75284 3.6055L9.14678 7.0441C9.3953 7.29589 9.3953 7.70411 9.14678 7.9559L5.75284 11.3945C5.50433 11.6463 5.10141 11.6463 4.85289 11.3945Z" fill="#C7660E"/>
                                                                    </svg>
                                                                    <a href="<?php echo esc_url( get_permalink( $single_course_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $single_course_id ) ); ?></a>
                                                                </div>
                                                                <?php
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                    }
                                }
                            }
                            
                            if( ! $is_variable && count( $courses ) === 1 ) {
                                $date_created = $product->get_date_created();
                                $enrolled_students = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}moodle_enrollment WHERE course_id IN ( %s )", implode( ',', $courses ) ) );
                                $additoinal_details = array(
                                    'date_created'      => array(
                                        'label' => __( 'Created', 'edwiser-bridge-pro' ),
                                        'value' => $date_created->date( 'Y-m-d' ),
                                        'icon'  => '<svg width="20" height="22" viewBox="0 0 20 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="Group 1">
                                                        <path id="Vector (Stroke)" fill-rule="evenodd" clip-rule="evenodd" d="M4.89711 0.753952C5.31133 0.753952 5.64711 1.08974 5.64711 1.50395V2.00343H13.8931V1.50395C13.8931 1.08974 14.2288 0.753952 14.6431 0.753952C15.0573 0.753952 15.3931 1.08974 15.3931 1.50395V2.00343H15.8426C17.8378 2.00343 19.4414 3.6204 19.4414 5.60224V17.625C19.4526 19.622 17.8343 21.236 15.8426 21.236H3.81756C1.82229 21.236 0.21875 19.6191 0.21875 17.6372V5.60224C0.21875 3.61868 1.834 2.00343 3.81756 2.00343H4.14711V1.50395C4.14711 1.08974 4.4829 0.753952 4.89711 0.753952ZM4.14711 3.50343H3.81756C2.66243 3.50343 1.71875 4.44711 1.71875 5.60224V7.24125H17.9414V5.60224C17.9414 4.4454 17.006 3.50343 15.8426 3.50343H15.3931V4.06289C15.3931 4.4771 15.0573 4.81289 14.6431 4.81289C14.2288 4.81289 13.8931 4.4771 13.8931 4.06289V3.50343H5.64711V4.06289C5.64711 4.4771 5.31133 4.81289 4.89711 4.81289C4.4829 4.81289 4.14711 4.4771 4.14711 4.06289V3.50343ZM17.9414 8.74125H1.71875V17.6372C1.71875 18.7941 2.65415 19.736 3.81756 19.736H15.8426C17.0086 19.736 17.9487 18.7925 17.9414 17.632L17.9414 17.6272V8.74125ZM4.93678 16.3977C4.93678 15.9835 5.27257 15.6477 5.68678 15.6477H13.9733C14.3875 15.6477 14.7233 15.9835 14.7233 16.3977C14.7233 16.812 14.3875 17.1477 13.9733 17.1477H5.68678C5.27257 17.1477 4.93678 16.812 4.93678 16.3977Z" fill="#008B91"/>
                                                        </g>
                                                    </svg>',
                                    ),
                                    'enrolled_students' => array(
                                        'label' => __( 'Enrolled Students', 'edwiser-bridge-pro' ),
                                        'value' => $enrolled_students,
                                        'icon'  => '<svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="Frame" clip-path="url(#clip0_422_3333)"><g id="Group">
                                                            <path id="Vector" fill-rule="evenodd" clip-rule="evenodd" d="M6.23141 1.37545C4.82621 1.37545 3.69703 2.50233 3.69703 3.88607C3.69703 5.26981 4.83457 6.39669 6.23141 6.39669C7.63662 6.39669 8.7658 5.26981 8.7658 3.88607C8.7658 2.50233 7.62825 1.37545 6.23141 1.37545ZM2.30855 3.88607C2.30855 1.74003 4.06506 0 6.23141 0C8.39777 0 10.1543 1.74003 10.1543 3.88607C10.1543 6.03211 8.39777 7.77214 6.23141 7.77214C4.06506 7.77214 2.30855 6.03211 2.30855 3.88607ZM4.13197 10.8545L3.96468 10.8793C2.47584 11.1113 1.38011 12.3874 1.38011 13.8788C1.38011 14.2931 1.71468 14.6245 2.1329 14.6245H10.3132C10.7314 14.6245 11.066 14.2931 11.066 13.8788C11.066 12.3874 9.97026 11.1113 8.48141 10.8793L8.31413 10.8545C6.93401 10.6308 5.52045 10.6308 4.13197 10.8545ZM3.9145 9.4956C5.44517 9.25531 7.00929 9.25531 8.53996 9.4956L8.70725 9.52046C10.8652 9.86018 12.4545 11.7079 12.4545 13.8788C12.4545 15.0471 11.5009 16 10.3132 16H2.14126C0.953532 16 0 15.0471 0 13.8788C0 11.7079 1.58922 9.86846 3.74721 9.52046L3.9145 9.4956Z" fill="#008B91"/>
                                                            <path id="Vector_2" fill-rule="evenodd" clip-rule="evenodd" d="M11.0742 0.687727C11.0742 0.306577 11.3837 0 11.7685 0C13.9348 0 15.6913 1.74003 15.6913 3.88607C15.6913 6.03211 13.9348 7.77214 11.7685 7.77214C11.3837 7.77214 11.0742 7.46556 11.0742 7.08441C11.0742 6.70326 11.3837 6.39669 11.7685 6.39669C13.1737 6.39669 14.3028 5.26981 14.3028 3.88607C14.3028 2.50233 13.1653 1.37545 11.7685 1.37545C11.3837 1.37545 11.0742 1.06059 11.0742 0.687727ZM12.2201 10.175C12.2201 9.79389 12.5296 9.48731 12.9144 9.48731H13.8093C13.9599 9.48731 14.1021 9.4956 14.2527 9.52046C16.4107 9.86018 17.9999 11.7079 17.9999 13.8788C17.9999 15.0471 17.0463 16 15.8586 16H13.9683C13.5835 16 13.274 15.6934 13.274 15.3123C13.274 14.9311 13.5835 14.6245 13.9683 14.6245H15.8502C16.2685 14.6245 16.603 14.2931 16.603 13.8788C16.603 12.3874 15.5073 11.1113 14.0185 10.8793C13.9432 10.8711 13.8679 10.8628 13.7926 10.8628H12.8976C12.5296 10.8628 12.2201 10.5562 12.2201 10.175Z" fill="#008B91"/>
                                                        </g></g>
                                                        <defs><clipPath id="clip0_422_3333"><rect width="18" height="16" fill="white"/></clipPath></defs>
                                                    </svg>',
                                        
                                    )
                                );

                                // controls for additional details
                                if ( isset( $settings['show_last_updated'] ) && 'yes' !== $settings['show_last_updated'] ) {
                                    unset( $additoinal_details['date_created'] );
                                }
                                if ( isset( $settings['show_enrolled_students'] ) && 'yes' !== $settings['show_enrolled_students'] ) {
                                    unset( $additoinal_details['enrolled_students'] );
                                }
                                if( count( $courses ) === 1 && isset( $settings['show_course_access'] ) && 'yes' === $settings['show_course_access'] ) {
                                    $course_options = get_post_meta( $courses[0], 'eb_course_options', true );
                                    if ( isset( $course_options['course_expirey'] ) && 'yes' === $course_options['course_expirey'] ) {
                                        $additoinal_details['course_expirey'] = array(
                                            'label' => __( 'Course Expiry', 'edwiser-bridge-pro' ),
                                            'value' => $course_options['num_days_course_access'] . ' ' . __( 'Days', 'edwiser-bridge-pro' ),
                                            'icon'  => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="Group">
                                                        <path id="Vector" d="M13.5629 11.7661L10.7743 9.67465V5.41442C10.7743 4.98606 10.4281 4.63981 9.99971 4.63981C9.57135 4.63981 9.2251 4.98606 9.2251 5.41442V10.062C9.2251 10.306 9.33975 10.5361 9.53494 10.6817L12.6333 13.0055C12.7727 13.11 12.9354 13.1604 13.0973 13.1604C13.3335 13.1604 13.5659 13.0543 13.7178 12.8498C13.975 12.5081 13.9053 12.0225 13.5629 11.7661Z" fill="#008B91"/>
                                                        <path id="Vector_2" d="M10 0C4.48566 0 0 4.48566 0 10C0 15.5143 4.48566 20 10 20C15.5143 20 20 15.5143 20 10C20 4.48566 15.5143 0 10 0ZM10 18.4508C5.34082 18.4508 1.54918 14.6592 1.54918 10C1.54918 5.34082 5.34082 1.54918 10 1.54918C14.66 1.54918 18.4508 5.34082 18.4508 10C18.4508 14.6592 14.6592 18.4508 10 18.4508Z" fill="#008B91"/>
                                                        </g>
                                                    </svg>',
                                        );
                                    
                                    }
                                }
                                $additoinal_details = apply_filters( 'eb_pro_product_page_additional_details', $additoinal_details, $product_id );
                                foreach ( $additoinal_details as $key => $value ) {
                                    ?>
                                    <div class="additional-detail">
                                        <span class="detail-label">
                                            <?php echo $value['icon']; ?>
                                            <?php echo $value['label']; ?>
                                        </span>
                                        <span class="detail-value"><?php echo $value['value']; ?></span>
                                    </div>
                                    <?php
                                }
                            }
                        ?>
                    </div>
                    <div class="eb-pro-product-page-details">
                        <!-- tabs for overview and review -->
                        <div class="detail-tabs">
                            <?php
                            // check if this product have description
                            $description = $product->get_description();
                            if ( ! empty( $description ) ) {
                                ?>
                                <div class="tab overview-tab active" data-tab="overview">
                                    <span class="tab-title"><?php _e( 'Overview', 'edwiser-bridge-pro' ); ?></span>
                                </div>
                                <?php
                            }
                            if ( 'yes' === get_option( 'woocommerce_enable_reviews' ) ) {
                                ?>
                                <div class="tab review-tab <?php echo empty( $description ) ? 'active' : ''; ?>" data-tab="review">
                                    <span class="tab-title"><?php _e( 'Review', 'edwiser-bridge-pro' ); ?>(<?php echo $product->get_review_count(); ?>)</span>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <!-- overview content -->
                        <?php
                        if ( ! empty( $description ) ) {
                            ?>
                            <div class="tab-content overview-content active" data-tab="overview">
                                <!-- show the product description -->
                                <h2 class="overview-title"><?php _e( 'Overview', 'edwiser-bridge-pro' ); ?></h2>
                                <div class="product-description">
                                    <?php echo $product->get_description(); ?>
                                </div>
                            </div>
                            <?php
                        }
                        if ( 'yes' === get_option( 'woocommerce_enable_reviews' ) ) {
                            ?>
                            <!-- review content -->
                            <div class="tab-content review-content <?php echo empty( $description ) ? 'active' : ''; ?>" data-tab="review">
                                <!-- show the product reviews -->
                                <?php
                                // get the reviews
                                $reviews = get_comments( array(
                                    'post_id' => $product_id,
                                    'status'  => 'approve',
                                    'type'    => 'review',
                                ) );
                                if ( empty( $reviews ) ) {
                                    echo '<p class="no-reviews">' . __( 'No reviews yet.', 'edwiser-bridge-pro' ) . '</p>';
                                } else {
                                    ?>
                                    <div class="eb-pro-product-page-all-reviews-wrap">
                                        <div class="overall-reviews">
                                            <?php
                                            // check if rating is enabled
                                            if ( 'yes' === get_option( 'woocommerce_enable_review_rating' ) ) {
                                                ?>
                                                <div class="avg-review-count">
                                                    <span class="avg-rating"><?php echo round( $product->get_average_rating(), 1 ); ?></span>
                                                    <span class="avg-rating-title"><?php _e( 'Course rating', 'edwiser-bridge-pro' ); ?></span>
                                                    <!-- stars -->
                                                    <div class="avg-rating-stars">
                                                        <?php
                                                        $rating = $product->get_average_rating();
                                                        $rating = round( $rating );
                                                        for ( $i = 1; $i <= 5; $i++ ) {
                                                            if ( $i <= $rating ) {
                                                                echo '<svg width="22" height="22" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <g id="Star">
                                                                        <path id="Vector" d="M11.872 15C11.6897 15 11.5074 14.9561 11.3461 14.8611L8.32376 13.2019C8.27468 13.1727 8.21858 13.1727 8.16949 13.2019L5.14718 14.8611C4.59321 15.1681 3.906 14.9415 3.6185 14.3641C3.49929 14.1302 3.46422 13.8671 3.5063 13.6112L4.08131 10.1028C4.08832 10.0589 4.0743 10.0005 4.03222 9.95661L1.59193 7.47146C1.14315 7.01829 1.13613 6.27275 1.5709 5.80495C1.74621 5.61491 1.9706 5.49796 2.22304 5.46142L5.59597 4.94977C5.65207 4.94246 5.69414 4.90592 5.72219 4.85475L7.22984 1.6606C7.50332 1.07586 8.18352 0.834658 8.7445 1.11972C8.9689 1.23667 9.15122 1.42671 9.26342 1.6606L10.7711 4.85475C10.7921 4.90592 10.8412 4.94246 10.8973 4.94977L14.2772 5.46142C14.8943 5.55644 15.3291 6.1558 15.2379 6.79901C15.2029 7.05484 15.0836 7.29604 14.9083 7.47877L12.461 9.95661C12.419 9.99316 12.4049 10.0516 12.4119 10.1101L12.987 13.6186C13.0921 14.2618 12.6784 14.8757 12.0613 14.9854C11.9982 14.9927 11.9351 15 11.872 15Z" fill="#F98012"/>
                                                                        </g>
                                                                    </svg>';
                                                            } else {
                                                                echo '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <g id="Star">
                                                                        <path id="Vector" d="M15.9802 20.625C15.7296 20.625 15.4789 20.5647 15.2571 20.434L11.1014 18.1526C11.0339 18.1124 10.9568 18.1124 10.8893 18.1526L6.73363 20.434C5.97191 20.8562 5.027 20.5446 4.63168 19.7506C4.46777 19.429 4.41956 19.0672 4.47741 18.7155L5.26805 13.8913C5.27769 13.831 5.25841 13.7506 5.20056 13.6903L1.84516 10.2733C1.22808 9.65015 1.21843 8.62503 1.81623 7.98181C2.05728 7.72051 2.36582 7.5597 2.71293 7.50945L7.35071 6.80593C7.42785 6.79588 7.4857 6.74563 7.52426 6.67528L9.59728 2.28333C9.97332 1.47931 10.9086 1.14766 11.6799 1.53961C11.9885 1.70042 12.2392 1.96172 12.3934 2.28333L14.4665 6.67528C14.4954 6.74563 14.5629 6.79588 14.64 6.80593L19.2874 7.50945C20.1359 7.6401 20.7337 8.46422 20.6084 9.34864C20.5602 9.7004 20.3963 10.0321 20.1552 10.2833L16.7902 13.6903C16.7323 13.7406 16.713 13.821 16.7227 13.9014L17.5133 18.7255C17.6579 19.6099 17.0891 20.4541 16.2406 20.6049C16.1538 20.615 16.067 20.625 15.9802 20.625Z" fill="white" stroke="#F98012" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                                        </g>
                                                                    </svg>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                    <span class="avg-rating-count"><?php echo round( $product->get_average_rating(), 1 ); ?> (<?php echo $product->get_rating_count() . ' ' . __( 'Rating', 'edwiser-bridge-pro' ); ?>)</span>
                                                </div>
                                                <div class="review-details">
                                                    <?php
                                                    for ( $i = 5; $i >= 1; $i-- ) {
                                                        $rating_count = $product->get_rating_count( $i );
                                                        $total_rating = ( $product->get_rating_count() > 0 ) ? $product->get_rating_count() : 1;
                                                        ?>
                                                        <div class="rating-row" data-rating="<?php echo $i; ?>">
                                                            <div class="rating-bar">
                                                                <div class="rating-bar-fill" style="width: <?php echo ( $rating_count / $total_rating ) * 100; ?>%"></div>
                                                            </div>
                                                            <div class="rating-stars">
                                                                <?php
                                                                for ( $j = 1; $j <= 5; $j++ ) {
                                                                    if ( $j <= $i ) {
                                                                        ?>
                                                                        <?php
                                                                        echo '<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                            <g id="Star">
                                                                            <path id="Vector" d="M11.872 15C11.6897 15 11.5074 14.9561 11.3461 14.8611L8.32376 13.2019C8.27468 13.1727 8.21858 13.1727 8.16949 13.2019L5.14718 14.8611C4.59321 15.1681 3.906 14.9415 3.6185 14.3641C3.49929 14.1302 3.46422 13.8671 3.5063 13.6112L4.08131 10.1028C4.08832 10.0589 4.0743 10.0005 4.03222 9.95661L1.59193 7.47146C1.14315 7.01829 1.13613 6.27275 1.5709 5.80495C1.74621 5.61491 1.9706 5.49796 2.22304 5.46142L5.59597 4.94977C5.65207 4.94246 5.69414 4.90592 5.72219 4.85475L7.22984 1.6606C7.50332 1.07586 8.18352 0.834658 8.7445 1.11972C8.9689 1.23667 9.15122 1.42671 9.26342 1.6606L10.7711 4.85475C10.7921 4.90592 10.8412 4.94246 10.8973 4.94977L14.2772 5.46142C14.8943 5.55644 15.3291 6.1558 15.2379 6.79901C15.2029 7.05484 15.0836 7.29604 14.9083 7.47877L12.461 9.95661C12.419 9.99316 12.4049 10.0516 12.4119 10.1101L12.987 13.6186C13.0921 14.2618 12.6784 14.8757 12.0613 14.9854C11.9982 14.9927 11.9351 15 11.872 15Z" fill="#F98012"/>
                                                                            </g>
                                                                        </svg>';
                                                                    } else {
                                                                        echo '<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                            <g id="Star">
                                                                            <path id="Vector" d="M11.872 15C11.6897 15 11.5074 14.9561 11.3461 14.8611L8.32376 13.2019C8.27468 13.1727 8.21858 13.1727 8.16949 13.2019L5.14718 14.8611C4.59321 15.1681 3.906 14.9415 3.6185 14.3641C3.49929 14.1302 3.46422 13.8671 3.5063 13.6112L4.08131 10.1028C4.08832 10.0589 4.0743 10.0005 4.03222 9.95661L1.59193 7.47146C1.14315 7.01829 1.13613 6.27275 1.5709 5.80495C1.74621 5.61491 1.9706 5.49796 2.22304 5.46142L5.59597 4.94977C5.65207 4.94246 5.69414 4.90592 5.72219 4.85475L7.22984 1.6606C7.50332 1.07586 8.18352 0.834658 8.7445 1.11972C8.9689 1.23667 9.15122 1.42671 9.26342 1.6606L10.7711 4.85475C10.7921 4.90592 10.8412 4.94246 10.8973 4.94977L14.2772 5.46142C14.8943 5.55644 15.3291 6.1558 15.2379 6.79901C15.2029 7.05484 15.0836 7.29604 14.9083 7.47877L12.461 9.95661C12.419 9.99316 12.4049 10.0516 12.4119 10.1101L12.987 13.6186C13.0921 14.2618 12.6784 14.8757 12.0613 14.9854C11.9982 14.9927 11.9351 15 11.872 15Z" fill="#D9E7E8"/>
                                                                            </g>
                                                                        </svg>';
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                            <span><?php echo round( ( $rating_count / $total_rating ) * 100, 2); ?> %</span>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="product-reviews">
                                            <?php
                                            foreach ( $reviews as $review ) {
                                                $rating = get_comment_meta( $review->comment_ID, 'rating', true );
                                                ?>
                                                <div class="product-review">
                                                    <div class="review-header">
                                                        <div class="author-thumb">
                                                            <?php echo get_avatar( $review->comment_author_email, 48 ); ?>
                                                        </div>
                                                        <div class="author-details">
                                                            <span class="author-name"><?php echo $review->comment_author; ?></span>
                                                            <span class="review-date">
                                                                <?php echo get_comment_date( 'l, F j, Y, g:i a', $review->comment_ID ); ?>
                                                                <?php if ( ! empty( $rating ) && 'yes' === get_option( 'woocommerce_enable_review_rating' ) ) {
                                                                    ?>
                                                                    <span class="review-count">
                                                                        <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                            <g id="Star">
                                                                            <path id="Vector" d="M11.872 15C11.6897 15 11.5074 14.9561 11.3461 14.8611L8.32376 13.2019C8.27468 13.1727 8.21858 13.1727 8.16949 13.2019L5.14718 14.8611C4.59321 15.1681 3.906 14.9415 3.6185 14.3641C3.49929 14.1302 3.46422 13.8671 3.5063 13.6112L4.08131 10.1028C4.08832 10.0589 4.0743 10.0005 4.03222 9.95661L1.59193 7.47146C1.14315 7.01829 1.13613 6.27275 1.5709 5.80495C1.74621 5.61491 1.9706 5.49796 2.22304 5.46142L5.59597 4.94977C5.65207 4.94246 5.69414 4.90592 5.72219 4.85475L7.22984 1.6606C7.50332 1.07586 8.18352 0.834658 8.7445 1.11972C8.9689 1.23667 9.15122 1.42671 9.26342 1.6606L10.7711 4.85475C10.7921 4.90592 10.8412 4.94246 10.8973 4.94977L14.2772 5.46142C14.8943 5.55644 15.3291 6.1558 15.2379 6.79901C15.2029 7.05484 15.0836 7.29604 14.9083 7.47877L12.461 9.95661C12.419 9.99316 12.4049 10.0516 12.4119 10.1101L12.987 13.6186C13.0921 14.2618 12.6784 14.8757 12.0613 14.9854C11.9982 14.9927 11.9351 15 11.872 15Z" fill="#F98012"/>
                                                                            </g>
                                                                        </svg>
                                                                        <?php echo $rating; ?>
                                                                    </span>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="review-content">
                                                        <?php echo $review->comment_content; ?>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div class="eb-pro-product-page-reviews-wrap">
                                    <div id="review_form">
                                        <?php
                                        $commenter    = wp_get_current_commenter();
                                        $comment_form = array(
                                            /* translators: %s is product title */
                                            'title_reply'         => ! empty( $reviews ) ? esc_html__( 'Add a review', 'edwiser-bridge-pro' ) : sprintf( esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'edwiser-bridge-pro' ), get_the_title() ),
                                            /* translators: %s is product title */
                                            'title_reply_to'      => esc_html__( 'Leave a Reply to %s', 'edwiser-bridge-pro' ),
                                            'title_reply_before'  => '<span id="reply-title" class="comment-reply-title">',
                                            'title_reply_after'   => '</span>',
                                            'comment_notes_after' => '',
                                            'label_submit'        => esc_html__( 'Submit', 'edwiser-bridge-pro' ),
                                            'logged_in_as'        => '',
                                            'comment_field'       => '',
                                        );

                                        $name_email_required = (bool) get_option( 'require_name_email', 1 );
                                        $fields              = array(
                                            'author' => array(
                                                'label'    => __( 'Name', 'edwiser-bridge-pro' ),
                                                'type'     => 'text',
                                                'value'    => $commenter['comment_author'],
                                                'required' => $name_email_required,
                                            ),
                                            'email'  => array(
                                                'label'    => __( 'Email', 'edwiser-bridge-pro' ),
                                                'type'     => 'email',
                                                'value'    => $commenter['comment_author_email'],
                                                'required' => $name_email_required,
                                            ),
                                        );

                                        $comment_form['fields'] = array();

                                        foreach ( $fields as $key => $field ) {
                                            $field_html  = '<p class="comment-form-' . esc_attr( $key ) . '">';
                                            $field_html .= '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] );

                                            if ( $field['required'] ) {
                                                $field_html .= '&nbsp;<span class="required">*</span>';
                                            }

                                            $field_html .= '</label><input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $field['value'] ) . '" size="30" ' . ( $field['required'] ? 'required' : '' ) . ' /></p>';

                                            $comment_form['fields'][ $key ] = $field_html;
                                        }

                                        $account_page_url = wc_get_page_permalink( 'myaccount' );
                                        if ( $account_page_url ) {
                                            /* translators: %s opening and closing link tags respectively */
                                            $comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'edwiser-bridge-pro' ), '<a href="' . esc_url( $account_page_url ) . '">', '</a>' ) . '</p>';
                                        }

                                        if ( wc_review_ratings_enabled() ) {
                                            $is_required = ( wc_review_ratings_required() ) ? 'required' : '';
                                            $comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__( 'Add your ratings', 'edwiser-bridge-pro' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" ' . $is_required . '>
                                                <option value="">' . esc_html__( 'Rate&hellip;', 'edwiser-bridge-pro' ) . '</option>
                                                <option value="5">' . esc_html__( 'Perfect', 'edwiser-bridge-pro' ) . '</option>
                                                <option value="4">' . esc_html__( 'Good', 'edwiser-bridge-pro' ) . '</option>
                                                <option value="3">' . esc_html__( 'Average', 'edwiser-bridge-pro' ) . '</option>
                                                <option value="2">' . esc_html__( 'Not that bad', 'edwiser-bridge-pro' ) . '</option>
                                                <option value="1">' . esc_html__( 'Very poor', 'edwiser-bridge-pro' ) . '</option>
                                            </select></div>';
                                        }

                                        $comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Add your review', 'edwiser-bridge-pro' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';

                                        comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
}

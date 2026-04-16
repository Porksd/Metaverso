<?php
/**
 * Shop Page Widget for Elementor
 *
 * @package Edwiser Bridge Pro
 * @since 3.0.2
 */

namespace app\wisdmlabs\edwiserBridgePro\pb\widgets;

use app\wisdmlabs\edwiserBridgePro\includes\wooInt as wooInt;
use app\wisdmlabs\edwiserBridge as eb;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shop Page Widget
 */
class EB_Pro_Shop_Page_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name() {
        return 'eb-pro-shop-page-widget';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title() {
        return __( 'Edwiser Bridge Pro Shop Page', 'edwiser-bridge-pro' );
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-products';
    }

    /**
     * Get widget categories.
     *
     * @return array
     */
    public function get_categories() {
        return [ 'eb-pro-widgets' ];
    }

    /**
     * Register widget controls.
     */
    protected function _register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __( 'Content', 'edwiser-bridge-pro' ),
            ]
        );

        // add page Settings label
        $this->add_control(
            'page_settings',
            [
                'label'       => __( 'Page Settings', 'edwiser-bridge-pro' ),
                'type'        => \Elementor\Controls_Manager::RAW_HTML,
                'label_block' => true,
                'raw'         => ''
            ]
        );

        $this->add_control(
            'title',
            [
                'label'       => __( 'Title', 'edwiser-bridge-pro' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => true,
                'default'     => __( 'Shop', 'edwiser-bridge-pro' ),
            ]
        );

        // option to add background color/ gradiant /image to the title 
        $this->add_control(
            'title_style',
            [
                'label'       => __( 'Title Style', 'edwiser-bridge-pro' ),
                'type'        => \Elementor\Controls_Manager::HEADING,
                'label_block' => true,
            ]
        );

        // keep ither background image or background color 
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name'     => 'title_background',
                'label'    => __( 'Background', 'edwiser-bridge-pro' ),
                'types'    => [ 'classic', 'gradient' ],
                'selector' => '{{WRAPPER}} .eb-pro-page-header',
                'fields_options' => [
                    'image' => [
                        'default' => ['url'	=>EB_PRO_PLUGIN_URL . 'public/assets/images/shop-bg.png'],
                    ],
                    'size' => ['default' => 'cover'],
                    'position' => ['default' => 'center center'],
                    'repeat' => ['default' => 'no-repeat'],
                    'background' => ['default' => 'classic'],
                ],
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label'       => __( 'Products Per Page', 'edwiser-bridge-pro' ),
                'type'        => \Elementor\Controls_Manager::NUMBER,
                'label_block' => true,
                'default'     => 12,
            ]
        );

        // Allow order
        $this->add_control(
            'allow_order',
            [
                'label'        => __( 'Allow Sort', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'       => __( 'Sort By', 'edwiser-bridge-pro' ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'label_block' => true,
                'default'     => 'date-desc',
                'options'     => [
                    'date-desc'  => __( 'Date', 'edwiser-bridge-pro' ),
                    'price' => __( 'Price: low to high', 'edwiser-bridge-pro' ),
                    'price-desc' => __( 'Price: high to low', 'edwiser-bridge-pro' ),
                    'rating-desc'  => __( 'Rating', 'edwiser-bridge-pro' ),
                    'popularity' => __( 'Popularity', 'edwiser-bridge-pro' ),
                ],
            ]
        );

        // Show result count
        $this->add_control(
            'show_result_count',
            [
                'label'        => __( 'Show Result Count', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        // default card layout
        $this->add_control(
            'default_layout',
            [
                'label'       => __( 'Default Layout', 'edwiser-bridge-pro' ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'label_block' => true,
                'default'     => 'grid',
                'options'     => [
                    'grid' => __( 'Grid', 'edwiser-bridge-pro' ),
                    'list' => __( 'List', 'edwiser-bridge-pro' ),
                ],
            ]
        );

        $this->add_control(
            'course_card_settings',
            [
                'label'       => __( 'Course Card Settings', 'edwiser-bridge-pro' ),
                'type'        => \Elementor\Controls_Manager::RAW_HTML,
                'label_block' => true,
                'raw'         => ''
            ]
        );

        // Show category
        $this->add_control(
            'show_category',
            [
                'label'        => __( 'Show Category', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        // show course description
        $this->add_control(
            'show_description',
            [
                'label'        => __( 'Show Short Description', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        // show rating
        $this->add_control(
            'show_rating',
            [
                'label'        => __( 'Show Rating', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        // show 'Enrolled'
        $this->add_control(
            'show_enrolled',
            [
                'label'        => __( 'Show \'Enrolled\'', 'edwiser-bridge-pro' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'edwiser-bridge-pro' ),
                'label_off'    => __( 'No', 'edwiser-bridge-pro' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        // show view button
        $this->add_control(
            'show_view_button',
            [
                'label'        => __( 'Show View Button', 'edwiser-bridge-pro' ),
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
     */
    protected function render() {
        global $post;
        $settings = $this->get_settings_for_display();

        update_option( 'eb_pro_shop_page_product_per_page', $settings['per_page'] );

        $order_by_value = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : $settings['orderby'];
        $category_by    = isset( $_GET['category'] ) ? wc_clean( wp_unslash( $_GET['category'] ) ) : 'all';

        $order_by_value = explode( '-', $order_by_value );
        $order_by       = $order_by_value[0];
        $order          = isset( $order_by_value[1] ) ? $order_by_value[1] : 'asc';

        // to be used again in the template
        $order_by_value = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : $settings['orderby'];

        // get page number
        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $settings['per_page'],
            'orderby'        => $order_by,
            'order'          => $order,
        ];

        if( 'price' === $order_by ) {
            $args['meta_key'] = '_price';
            $args['orderby'] = 'meta_value_num';
        }

        if( 'popularity' === $order_by ) {
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
        }

        $args['paged'] = $paged;


        if ( 'rating' === $order_by ) {
            $args['meta_key'] = '_wc_average_rating';
            $args['orderby'] = 'meta_value_num';
        }

        // add category filter
        if ( 'all' !== $category_by ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_by,
                ],
            ];
        }

        $products = new \WP_Query( $args );

        // total products without pagination
        $total_products = $products->found_posts;

        ?>

        <!-- storefront theme compatibility -->
        <style>
            .storefront-breadcrumb{ 
                display: none !important;
            }
        </style>
        <div class="eb-pro-shop-container">
            <?php
            // check if there is add to cart notice 
            if ( wc_notice_count( 'success' ) > 0 ) {
                wc_clear_notices();
            }
            ?>
            <div class="eb-pro-page-header">
                <span><a href="<?php echo get_site_url(); ?>"><?php echo __( 'Home', 'edwiser-bridge-pro' ); ?></a> / <?php echo $settings['title']; ?></span>
                <h1 class="eb-pro-shop-page-title"><?php echo $settings['title']; ?></h1>
            </div>
            <div class="eb-pro-shop-header">
                <div>
                <?php if ( 'yes' === $settings['show_result_count'] ) : ?>
                    <p class="eb-pro-shop-heading"><?php echo __( 'We have found', 'edwiser-bridge-pro' ); ?> <?php echo esc_html( $total_products ); ?> <?php echo __( 'Courses for you', 'edwiser-bridge-pro' ); ?></p>
                <?php endif; ?>
                </div>
                <div class="eb-pro-shop-filters">
                    <form class="eb-pro-shop-category-filter" method="get" action="<?php echo wc_get_page_permalink( 'shop' ); ?>">
                        <select name="category" class="category">
                            <?php
                            $categories = get_terms( 'product_cat' );
                            echo '<option value="all"' . ( 'all' === $category_by ? ' selected' : '' ) . '> ' . __( 'All Categories', 'edwier-bridge-pro' ) . '</option>';
                            foreach ( $categories as $category ) {
                                echo '<option value="' . esc_attr( $category->term_id ) . '" ' . ( $category->term_id == $category_by ? ' selected' : '' ) . '>' . esc_html( $category->name ) . '</option>';
                            }
                            ?>
                        </select>
                    </form>
                    <?php if ( 'yes' === $settings['allow_order'] ) : ?>
                        <form class="eb-pro-shop-orderby-filter" method="get" action="<?php echo wc_get_page_permalink( 'shop' ); ?>">
                            <select name="orderby" class="orderby">
                                <option value="popularity" <?php selected( $order_by_value, 'popularity' ); ?>><?php echo __( 'Sort by popularity', 'edwiser-bridge-pro' ); ?></option>
                                <option value="rating-desc" <?php selected( $order_by_value, 'rating-desc' ); ?>><?php echo __( 'Sort by average rating', 'edwiser-bridge-pro' ); ?></option>
                                <option value="date-desc" <?php selected( $order_by_value, 'date-desc' ); ?>><?php echo __( 'Sort by newness', 'edwiser-bridge-pro' ); ?></option>
                                <option value="price" <?php selected( $order_by_value, 'price' ); ?>><?php echo __( 'Sort by price: low to high', 'edwiser-bridge-pro' ); ?></option>
                                <option value="price-desc" <?php selected( $order_by_value, 'price-desc' ); ?>><?php echo __( 'Sort by price: high to low', 'edwiser-bridge-pro' ); ?></option>
                            </select>
                        </form>
                    <?php endif; ?>
                    <!-- grid and list icons -->
                    <?php
                    $card_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.79638 3.55925C4.11469 3.55925 3.55937 4.11457 3.55937 4.79626V7.90437C3.55937 8.58606 4.11469 9.14137 4.79638 9.14137H7.90449C8.58618 9.14137 9.14149 8.58606 9.14149 7.90437V4.79626C9.14149 4.11457 8.58618 3.55925 7.90449 3.55925H4.79638ZM2.00012 4.79626C2.00012 3.25342 3.25354 2 4.79638 2H7.90449C9.44733 2 10.7007 3.25342 10.7007 4.79626V7.90437C10.7007 9.44721 9.44733 10.7006 7.90449 10.7006H4.79638C3.25354 10.7006 2.00012 9.44721 2.00012 7.90437V4.79626ZM13.2995 4.79626C13.2995 3.25342 14.5529 2 16.0958 2H19.2039C20.7467 2 22.0001 3.25342 22.0001 4.79626V7.90437C22.0001 9.44721 20.7467 10.7006 19.2039 10.7006H16.0958C14.5529 10.7006 13.2995 9.44721 13.2995 7.90437V4.79626ZM16.0958 3.55925C15.4141 3.55925 14.8588 4.11457 14.8588 4.79626V7.90437C14.8588 8.58606 15.4141 9.14137 16.0958 9.14137H19.2039C19.8856 9.14137 20.4409 8.58606 20.4409 7.90437V4.79626C20.4409 4.11457 19.8856 3.55925 19.2039 3.55925H16.0958ZM2.00012 16.0956C2.00012 14.5528 3.25354 13.2994 4.79638 13.2994H7.90449C9.44733 13.2994 10.7007 14.5528 10.7007 16.0956V19.2037C10.7007 20.7466 9.44733 22 7.90449 22H4.79638C3.25354 22 2.00012 20.7466 2.00012 19.2037V16.0956ZM4.79638 14.8586C4.11469 14.8586 3.55937 15.4139 3.55937 16.0956V19.2037C3.55937 19.8854 4.11469 20.4407 4.79638 20.4407H7.90449C8.58618 20.4407 9.14149 19.8854 9.14149 19.2037V16.0956C9.14149 15.4139 8.58618 14.8586 7.90449 14.8586H4.79638ZM13.2995 16.0956C13.2995 14.5528 14.5529 13.2994 16.0958 13.2994H19.2039C20.7467 13.2994 22.0001 14.5528 22.0001 16.0956V19.2037C22.0001 20.7466 20.7467 22 19.2039 22H16.0958C14.5529 22 13.2995 20.7466 13.2995 19.2037V16.0956ZM16.0958 14.8586C15.4141 14.8586 14.8588 15.4139 14.8588 16.0956V19.2037C14.8588 19.8854 15.4141 20.4407 16.0958 20.4407H19.2039C19.8856 20.4407 20.4409 19.8854 20.4409 19.2037V16.0956C20.4409 15.4139 19.8856 14.8586 19.2039 14.8586H16.0958Z" fill="#0041C9"/>
                                </svg>';
                    $list_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 20 15" fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.99951 1.51067C4.99951 1.05723 5.32071 0.689651 5.71693 0.689651H18.1521C18.5483 0.689651 18.8695 1.05723 18.8695 1.51067C18.8695 1.96411 18.5483 2.33169 18.1521 2.33169H5.71693C5.32071 2.33169 4.99951 1.96411 4.99951 1.51067Z" fill="#385B5C"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.99951 7.51067C4.99951 7.05723 5.32071 6.68965 5.71693 6.68965H18.1521C18.5483 6.68965 18.8695 7.05723 18.8695 7.51067C18.8695 7.96411 18.5483 8.33169 18.1521 8.33169H5.71693C5.32071 8.33169 4.99951 7.96411 4.99951 7.51067Z" fill="#385B5C"/>
                                    <path d="M1.49973 2.99945C2.328 2.99945 2.99945 2.328 2.99945 1.49973C2.99945 0.67145 2.328 0 1.49973 0C0.67145 0 0 0.67145 0 1.49973C0 2.328 0.67145 2.99945 1.49973 2.99945Z" fill="#385B5C"/>
                                    <path d="M1.49973 8.99945C2.328 8.99945 2.99945 8.328 2.99945 7.49973C2.99945 6.67145 2.328 6 1.49973 6C0.67145 6 0 6.67145 0 7.49973C0 8.328 0.67145 8.99945 1.49973 8.99945Z" fill="#385B5C"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.99951 13.821C4.99951 13.3676 5.32071 13 5.71693 13H18.1521C18.5483 13 18.8695 13.3676 18.8695 13.821C18.8695 14.2745 18.5483 14.642 18.1521 14.642H5.71693C5.32071 14.642 4.99951 14.2745 4.99951 13.821Z" fill="#385B5C"/>
                                    <path d="M1.49973 14.9995C2.328 14.9995 2.99945 14.328 2.99945 13.4997C2.99945 12.6715 2.328 12 1.49973 12C0.67145 12 0 12.6715 0 13.4997C0 14.328 0.67145 14.9995 1.49973 14.9995Z" fill="#385B5C"/>
                                </svg>';
                    if ( 'list' === $settings['default_layout'] ) {
                        $card_svg = str_replace( 'fill="#0041C9"', 'fill="#385B5C"', $card_svg );
                        $list_svg = str_replace( 'fill="#385B5C"', 'fill="#0041C9"', $list_svg );
                    }
                    ?>
                    <div class="eb-pro-shop-view">
                        <a class="eb-pro-shop-grid-view">
                            <?php echo $card_svg; ?>
                        </a>
                        <a class="eb-pro-shop-list-view">
                            <?php echo $list_svg; ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php

            if ( $products->have_posts() ) {
                // get all the products that are in cart.
                $cart_products = WC()->cart->get_cart();
                $cart_product_ids = array();
                foreach ( $cart_products as $cart_product ) {
                    $cart_product_ids[] = $cart_product['product_id'];
                }
                ?>
                <div class="eb-pro-shop-body">
                    <ul class="eb-pro-shop-products-card-wrap products columns-4" <?php echo 'list' === $settings['default_layout'] ? 'style="display:none;"' : ''; ?>>
                        <?php
                        while ( $products->have_posts() ) {
                            $products->the_post();
                            global $product;

                            $courses = wooInt\get_wp_courses_from_product_id(get_the_ID());
                            ?>
                            <li <?php wc_product_class( 'eb-pro-shop-product-card', get_the_ID() ); ?>>
                                <div class="eb-pro-shop-product-thumbnail-wrap">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php
                                        if ( has_post_thumbnail() ) {
                                            the_post_thumbnail( 'shop_catalog' );
                                        } else {
                                            $default_img = EB_PRO_PLUGIN_URL . 'public/assets/images/course-bg.png';
                                            echo '<img src="' . $default_img . '" alt="Course Image" />';
                                        }
                                        ?>
                                    </a>

                                    <!-- Already Enrolled label -->
                                    <?php
                                    if( count( $courses ) == 1 ) {
                                        $user_id = get_current_user_id();
                                        $user_has_access = eb\edwiser_bridge_instance()->enrollment_manager()->user_has_course_access( $user_id, $courses[0] );
                                        if ( $user_has_access ) {
                                            echo '<span class="eb-pro-shop-product-enrolled-badge">' . __( 'Already enrolled', 'edwiser-bridge-pro' ) . '</span>';
                                        }
                                    } elseif( count( $courses ) > 1 ){
                                        $has_access = true;
                                        foreach ( $courses as $course ) {
                                            $user_id = get_current_user_id();
                                            $user_has_access = eb\edwiser_bridge_instance()->enrollment_manager()->user_has_course_access( $user_id, $course );
                                            if ( ! $user_has_access ) {
                                                $has_access = false;
                                                break;
                                            }
                                        }
                                        if ( $has_access ) {
                                            echo '<span class="eb-pro-shop-product-enrolled-badge">' . __( 'Already enrolled', 'edwiser-bridge-pro' ) . '</span>';
                                        }
                                    }
                                    ?>

                                    <!-- Sale label -->
                                    <?php
                                    if ( $product->is_on_sale() ) {
                                        echo '<span class="eb-pro-shop-product-sale-badge">' . esc_html__( 'Sale', 'edwiser-bridge-pro' ) . '</span>';
                                    }
                                    ?>
                                </div>
                                <div class="eb-pro-shop-product-content">
                                    <!-- Category ( show only first category) -->
                                    <?php
                                        $product_cats = wp_get_post_terms( get_the_ID(), 'product_cat' );
                                        // check if it uncatagorized if yes then do not show category
                                        if ( $product_cats && 'yes' === $settings['show_category'] && $product_cats[0]->name != 'Uncategorized' ) {
                                            $category_link = wc_get_page_permalink( 'shop' ) . '?category=' . $product_cats[0]->term_id;
                                            ?>
                                            <div class="eb-pro-product-category">
                                            <a href="<?php echo esc_url( $category_link ); ?>"><?php echo wp_trim_words( $product_cats[0]->name, 3, '...' ); ?></a>
                                            </div>
                                            <?php
                                        }
                                    ?>
                                    
                                    <h2 class="eb-pro-product-title woocommerce-loop-product__title"><a href="<?php the_permalink(); ?>"><?php echo wp_trim_words( get_the_title(), 10, '...' ); ?></a></h2>

                                    <?php if ( 'yes' === $settings['show_description'] ) : ?>
                                        <p class="eb-pro-product-desc" ><?php echo wp_trim_words( $product->get_short_description(), 10, '...' ); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="eb-pro-product-info">
                                        <!-- Rating -->
                                        <div class="product-rating">
                                            <?php
                                            if ( 'yes' === get_option( 'woocommerce_enable_review_rating' ) && 'yes' === $settings['show_rating'] && $product->get_rating_count() > 0 ) {
                                            ?>
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g id="Star">
                                                    <path id="Vector" d="M11.622 15C11.4397 15 11.2574 14.9561 11.0961 14.8611L8.07376 13.2019C8.02468 13.1727 7.96858 13.1727 7.91949 13.2019L4.89718 14.8611C4.34321 15.1681 3.656 14.9415 3.3685 14.3641C3.24929 14.1302 3.21422 13.8671 3.2563 13.6112L3.83131 10.1028C3.83832 10.0589 3.8243 10.0005 3.78222 9.95661L1.34193 7.47146C0.893146 7.01829 0.886133 6.27275 1.3209 5.80495C1.49621 5.61491 1.7206 5.49796 1.97304 5.46142L5.34597 4.94977C5.40207 4.94246 5.44414 4.90592 5.47219 4.85475L6.97984 1.6606C7.25332 1.07586 7.93352 0.834658 8.4945 1.11972C8.7189 1.23667 8.90122 1.42671 9.01342 1.6606L10.5211 4.85475C10.5421 4.90592 10.5912 4.94246 10.6473 4.94977L14.0272 5.46142C14.6443 5.55644 15.0791 6.1558 14.9879 6.79901C14.9529 7.05484 14.8336 7.29604 14.6583 7.47877L12.211 9.95661C12.169 9.99316 12.1549 10.0516 12.1619 10.1101L12.737 13.6186C12.8421 14.2618 12.4284 14.8757 11.8113 14.9854C11.7482 14.9927 11.6851 15 11.622 15Z" fill="#F98012"/>
                                                </g>
                                            </svg>
                                            <?php
                                            echo '<span class="rating">' . round( $product->get_average_rating(), 1 ) . ' (' . $product->get_rating_count() . ')</span>';
                                            ?>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                        <!-- Enrolled Students -->
                                        <?php if ( 'yes' === $settings['show_enrolled'] && count( $courses ) === 1 ) : ?>         
                                            <div class="enrolled-students">
                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g id="Frame" clip-path="url(#clip0_367_5700)"><g id="Group">
                                                        <path id="Vector" fill-rule="evenodd" clip-rule="evenodd" d="M4.15427 1.58363C3.21747 1.58363 2.46468 2.33488 2.46468 3.25737C2.46468 4.17986 3.22305 4.93111 4.15427 4.93111C5.09108 4.93111 5.84387 4.17986 5.84387 3.25737C5.84387 2.33488 5.0855 1.58363 4.15427 1.58363ZM1.53903 3.25737C1.53903 1.82668 2.71004 0.666656 4.15427 0.666656C5.59851 0.666656 6.76952 1.82668 6.76952 3.25737C6.76952 4.68806 5.59851 5.84808 4.15427 5.84808C2.71004 5.84808 1.53903 4.68806 1.53903 3.25737ZM2.75465 7.90298L2.64312 7.91955C1.65056 8.07422 0.920074 8.9249 0.920074 9.9192C0.920074 10.1954 1.14312 10.4164 1.42193 10.4164H6.87546C7.15427 10.4164 7.37732 10.1954 7.37732 9.9192C7.37732 8.9249 6.64684 8.07422 5.65427 7.91955L5.54275 7.90298C4.62268 7.75383 3.6803 7.75383 2.75465 7.90298ZM2.60967 6.99706C3.63011 6.83686 4.67286 6.83686 5.69331 6.99706L5.80483 7.01363C7.24349 7.24011 8.30297 8.47194 8.30297 9.9192C8.30297 10.6981 7.66729 11.3333 6.87546 11.3333H1.42751C0.635688 11.3333 0 10.6981 0 9.9192C0 8.47194 1.05948 7.24563 2.49814 7.01363L2.60967 6.99706Z" fill="#008B91"/>
                                                        <path id="Vector_2" fill-rule="evenodd" clip-rule="evenodd" d="M7.38287 1.12514C7.38287 0.871041 7.58919 0.666656 7.8457 0.666656C9.28994 0.666656 10.4609 1.82668 10.4609 3.25737C10.4609 4.68806 9.28994 5.84808 7.8457 5.84808C7.58919 5.84808 7.38287 5.6437 7.38287 5.3896C7.38287 5.1355 7.58919 4.93111 7.8457 4.93111C8.7825 4.93111 9.53529 4.17986 9.53529 3.25737C9.53529 2.33488 8.77693 1.58363 7.8457 1.58363C7.58919 1.58363 7.38287 1.37372 7.38287 1.12514ZM8.14681 7.45002C8.14681 7.19592 8.35313 6.99153 8.60964 6.99153H9.20629C9.30667 6.99153 9.40146 6.99706 9.50183 7.01363C10.9405 7.24011 12 8.47194 12 9.9192C12 10.6981 11.3643 11.3333 10.5725 11.3333H9.31224C9.05574 11.3333 8.84942 11.1289 8.84942 10.8748C8.84942 10.6207 9.05574 10.4164 9.31224 10.4164H10.5669C10.8457 10.4164 11.0687 10.1954 11.0687 9.9192C11.0687 8.9249 10.3383 8.07422 9.3457 7.91955C9.29551 7.91402 9.24533 7.9085 9.19514 7.9085H8.59849C8.35313 7.9085 8.14681 7.70412 8.14681 7.45002Z" fill="#008B91"/>
                                                    </g></g>
                                                    <defs><clipPath id="clip0_367_5700"><rect width="12" height="10.6667" fill="white" transform="translate(0 0.666656)"/></clipPath>
                                                    </defs>
                                                </svg>
                                                <?php
                                                // show enrolled students
                                                global $wpdb;
                                                $enrolled_students = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}moodle_enrollment WHERE course_id IN ( %s )", implode( ',', $courses ) ) );

                                                echo '<span>' . $enrolled_students . ' ' . __( 'Enrolled', 'edwiser-bridge-pro' ) . ' </span>';

                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                        if( $product->is_type( 'subscription' ) || $product->is_type( 'variable-subscription' ) ) {
                                            $style_warp = 'style="flex-wrap: wrap;"';
                                            $style_full_width = 'style="max-width: 100%;margin-bottom:8px !important;"';
                                        } else {
                                            $style_warp = '';
                                            $style_full_width = '';
                                        }
                                    ?>
                                    <div class="eb-pro-shop-product-actions" <?php echo $style_warp; ?>>
                                        <span class="price" <?php echo $style_full_width; ?>><?php echo $product->get_price_html(); ?></span>
                                        <div class="action-buttons">
                                            <div class="cart-button">
                                                <?php
                                                $buy_now_svg = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="Icons"><path id="Vector" d="M2.08334 3.54166C2.08334 3.3759 2.14919 3.21692 2.2664 3.09971C2.38361 2.9825 2.54258 2.91666 2.70834 2.91666H3.17334C3.96501 2.91666 4.44001 3.44916 4.71084 3.94416C4.89168 4.27416 5.02251 4.65666 5.12501 5.00332C5.15273 5.00113 5.18053 5.00002 5.20834 4.99999H15.6233C16.315 4.99999 16.815 5.66166 16.625 6.32749L15.1017 11.6683C14.9651 12.1474 14.6761 12.5689 14.2786 12.869C13.881 13.1691 13.3965 13.3315 12.8983 13.3317H7.94168C7.43959 13.3317 6.95139 13.1668 6.55211 12.8624C6.15284 12.558 5.86459 12.1308 5.73168 11.6467L5.09834 9.33666L4.04834 5.79666L4.04751 5.78999C3.91751 5.31749 3.79584 4.87499 3.61418 4.54499C3.44001 4.22416 3.30001 4.16666 3.17418 4.16666H2.70834C2.54258 4.16666 2.38361 4.10081 2.2664 3.9836C2.14919 3.86639 2.08334 3.70742 2.08334 3.54166ZM6.31084 9.03332L6.93668 11.3158C7.06168 11.7675 7.47251 12.0817 7.94168 12.0817H12.8983C13.1248 12.0817 13.345 12.0079 13.5258 11.8715C13.7065 11.7351 13.8379 11.5436 13.9 11.3258L15.3475 6.24999H5.48751L6.29918 8.98916L6.31084 9.03332ZM9.16668 15.8333C9.16668 16.2754 8.99108 16.6993 8.67852 17.0118C8.36596 17.3244 7.94204 17.5 7.50001 17.5C7.05798 17.5 6.63406 17.3244 6.3215 17.0118C6.00894 16.6993 5.83334 16.2754 5.83334 15.8333C5.83334 15.3913 6.00894 14.9674 6.3215 14.6548C6.63406 14.3423 7.05798 14.1667 7.50001 14.1667C7.94204 14.1667 8.36596 14.3423 8.67852 14.6548C8.99108 14.9674 9.16668 15.3913 9.16668 15.8333ZM7.91668 15.8333C7.91668 15.7228 7.87278 15.6168 7.79464 15.5387C7.7165 15.4606 7.61052 15.4167 7.50001 15.4167C7.3895 15.4167 7.28352 15.4606 7.20538 15.5387C7.12724 15.6168 7.08334 15.7228 7.08334 15.8333C7.08334 15.9438 7.12724 16.0498 7.20538 16.128C7.28352 16.2061 7.3895 16.25 7.50001 16.25C7.61052 16.25 7.7165 16.2061 7.79464 16.128C7.87278 16.0498 7.91668 15.9438 7.91668 15.8333ZM15 15.8333C15 16.2754 14.8244 16.6993 14.5119 17.0118C14.1993 17.3244 13.7754 17.5 13.3333 17.5C12.8913 17.5 12.4674 17.3244 12.1548 17.0118C11.8423 16.6993 11.6667 16.2754 11.6667 15.8333C11.6667 15.3913 11.8423 14.9674 12.1548 14.6548C12.4674 14.3423 12.8913 14.1667 13.3333 14.1667C13.7754 14.1667 14.1993 14.3423 14.5119 14.6548C14.8244 14.9674 15 15.3913 15 15.8333ZM13.75 15.8333C13.75 15.7228 13.7061 15.6168 13.628 15.5387C13.5498 15.4606 13.4439 15.4167 13.3333 15.4167C13.2228 15.4167 13.1169 15.4606 13.0387 15.5387C12.9606 15.6168 12.9167 15.7228 12.9167 15.8333C12.9167 15.9438 12.9606 16.0498 13.0387 16.128C13.1169 16.2061 13.2228 16.25 13.3333 16.25C13.4439 16.25 13.5498 16.2061 13.628 16.128C13.7061 16.0498 13.75 15.9438 13.75 15.8333Z" fill="#C7660E"/></g></svg>';
                                                $added_in_cart = '<svg class="added-in-cart" width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="Icons"><path id="Path (Stroke)" fill-rule="evenodd" clip-rule="evenodd" d="M3.54911 8.04633C3.83686 7.76231 4.30341 7.76231 4.59116 8.04633L6.66663 10.0775L11.4088 5.37966C11.6965 5.09564 12.1631 5.09564 12.4508 5.37966C12.7386 5.66368 12.7386 6.12416 12.4508 6.40818L7.18765 11.6203C6.8999 11.9043 6.43335 11.9043 6.1456 11.6203L3.54911 9.07485C3.26135 8.79083 3.26135 8.33035 3.54911 8.04633Z" fill="white"/></g></svg>';
                                                $in_cart = in_array( get_the_ID(), $cart_product_ids ) ? true : false;
                                                if ( $product->is_purchasable() && ! $product->is_type( 'variable' ) ) {
                                                    echo apply_filters( 'woocommerce_loop_add_to_cart_link', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        sprintf( '<a href="%s" data-quantity="1" class="%s">%s</a>',
                                                            $in_cart ? wc_get_cart_url() : esc_url( $product->add_to_cart_url() ),
                                                            'eb-pro-shop-add-to-cart-button',
                                                            $buy_now_svg
                                                        ),
                                                        $product );

                                                    if( $in_cart ) {
                                                        echo $added_in_cart;
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <?php
                                            if ( 'simple' === $product->get_type() ) {
                                                global $eb_pro_plugin_data;
                                                $public_class = new \app\wisdmlabs\edwiserBridgePro\pb\Bridge_Woocommerce_Public( $eb_pro_plugin_data['plugin_slug'], $eb_pro_plugin_data['plugin_version'] );
                                                $args = array( 'product' => $product, 'class' => 'eb-pro-shop-buy-now-button' );
                                                echo str_replace( 'button ', '', $public_class :: get_buy_now_button( $args ) );
                                            }
                                            ?>
                                            <?php if ( 'yes' === $settings['show_view_button'] ) :
                                                $button_text = __( 'View', 'edwiser-bridge-pro' );
                                                if( $product->is_type( 'variable' ) ) {
                                                    $button_text = __( 'Select options', 'edwiser-bridge-pro' );
                                                } elseif( $product->is_type( 'subscription' ) ) {
                                                    $button_text = __( 'Sign up now', 'edwiser-bridge-pro' );
                                                } elseif( $product->is_type( 'variable-subscription' ) ) {
                                                    $button_text = __( 'Select options', 'edwiser-bridge-pro' );
                                                } elseif( ! $product->is_purchasable() ) {
                                                    $button_text = __( 'Read more', 'edwiser-bridge-pro' );
                                                }
                                                ?>
                                                <a href="<?php the_permalink(); ?>" class="eb-pro-shop-buy-now-button"><?php echo esc_html( $button_text ); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <ul class="eb-pro-shop-products-list-wrap products list" <?php echo 'grid' === $settings['default_layout'] ? 'style="display:none !important;"' : ''; ?>>
                        <?php
                        while ( $products->have_posts() ) {
                            $products->the_post();
                            global $product;

                            $courses = wooInt\get_wp_courses_from_product_id(get_the_ID());
                            ?>
                            <li <?php wc_product_class( 'eb-pro-shop-product-list', get_the_ID() ); ?>>
                                <div class="eb-pro-shop-product-thumbnail-wrap">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php
                                        if ( has_post_thumbnail() ) {
                                            the_post_thumbnail( 'shop_catalog' );
                                        } else {
                                            $default_img = EB_PRO_PLUGIN_URL . 'public/assets/images/course-bg.png';
                                            echo '<img src="' . $default_img . '" alt="Course Image" />';
                                        }
                                        ?>
                                    </a>

                                    <!-- Already Enrolled label -->
                                    <?php
                                    if( count( $courses ) == 1 ) {
                                        $user_id = get_current_user_id();
                                        $user_has_access = eb\edwiser_bridge_instance()->enrollment_manager()->user_has_course_access( $user_id, $courses[0] );
                                        if ( $user_has_access ) {
                                            echo '<span class="eb-pro-shop-product-enrolled-badge">' . __( 'Already enrolled', 'edwiser-bridge-pro' ) . '</span>';
                                        }
                                    }
                                    ?>

                                    <!-- Sale label -->
                                    <?php
                                    if ( $product->is_on_sale() ) {
                                        echo '<span class="eb-pro-shop-product-sale-badge">' . esc_html__( 'Sale', 'edwiser-bridge-pro' ) . '</span>';
                                    }
                                    ?>
                                </div>
                                <div class="eb-pro-shop-product-content">
                                    <div class="eb-pro-shop-product-header">
                                        <!-- Category ( show only first category) -->
                                        <?php
                                            $product_cats = wp_get_post_terms( get_the_ID(), 'product_cat' );
                                            if ( $product_cats && 'yes' === $settings['show_category'] && $product_cats[0]->name != 'Uncategorized' ) {
                                                $category_link = wc_get_page_permalink( 'shop' ) . '?category=' . $product_cats[0]->term_id;
                                                ?>
                                                <div class="eb-pro-product-category">
                                                <a href="<?php echo esc_url( $category_link );; ?>"><?php echo $product_cats[0]->name; ?></a>
                                                </div>
                                                <?php
                                            }
                                        ?>
                                        <h2 class="eb-pro-product-title woocommerce-loop-product__title"><a href="<?php the_permalink(); ?>"><?php echo wp_trim_words( get_the_title(), 15, '...' ); ?></a></h2>
                                        <?php if ( 'yes' === $settings['show_description'] ) : ?>
                                            <p class="eb-pro-product-desc" ><?php echo wp_trim_words( $product->get_short_description(), 35, '...' ); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="eb-pro-shop-product-footer">
                                        <div class="eb-pro-product-info">
                                            <!-- Rating -->
                                            <div class="product-rating">
                                                <?php
                                                if ( 'yes' === get_option( 'woocommerce_enable_review_rating' ) && 'yes' === $settings['show_rating'] && $product->get_rating_count() > 0 ) {
                                                ?>
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g id="Star">
                                                        <path id="Vector" d="M11.622 15C11.4397 15 11.2574 14.9561 11.0961 14.8611L8.07376 13.2019C8.02468 13.1727 7.96858 13.1727 7.91949 13.2019L4.89718 14.8611C4.34321 15.1681 3.656 14.9415 3.3685 14.3641C3.24929 14.1302 3.21422 13.8671 3.2563 13.6112L3.83131 10.1028C3.83832 10.0589 3.8243 10.0005 3.78222 9.95661L1.34193 7.47146C0.893146 7.01829 0.886133 6.27275 1.3209 5.80495C1.49621 5.61491 1.7206 5.49796 1.97304 5.46142L5.34597 4.94977C5.40207 4.94246 5.44414 4.90592 5.47219 4.85475L6.97984 1.6606C7.25332 1.07586 7.93352 0.834658 8.4945 1.11972C8.7189 1.23667 8.90122 1.42671 9.01342 1.6606L10.5211 4.85475C10.5421 4.90592 10.5912 4.94246 10.6473 4.94977L14.0272 5.46142C14.6443 5.55644 15.0791 6.1558 14.9879 6.79901C14.9529 7.05484 14.8336 7.29604 14.6583 7.47877L12.211 9.95661C12.169 9.99316 12.1549 10.0516 12.1619 10.1101L12.737 13.6186C12.8421 14.2618 12.4284 14.8757 11.8113 14.9854C11.7482 14.9927 11.6851 15 11.622 15Z" fill="#F98012"/>
                                                    </g>
                                                </svg>
                                                <?php
                                                echo '<span class="rating">' . round( $product->get_average_rating(), 1 ) . ' (' . $product->get_rating_count() . ')</span>';
                                                ?>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                            <!-- Enrolled Students -->
                                            <?php if ( 'yes' === $settings['show_enrolled'] && count( $courses ) === 1 ) : ?>          
                                                <div class="enrolled-students">
                                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g id="Frame" clip-path="url(#clip0_367_5700)"><g id="Group">
                                                            <path id="Vector" fill-rule="evenodd" clip-rule="evenodd" d="M4.15427 1.58363C3.21747 1.58363 2.46468 2.33488 2.46468 3.25737C2.46468 4.17986 3.22305 4.93111 4.15427 4.93111C5.09108 4.93111 5.84387 4.17986 5.84387 3.25737C5.84387 2.33488 5.0855 1.58363 4.15427 1.58363ZM1.53903 3.25737C1.53903 1.82668 2.71004 0.666656 4.15427 0.666656C5.59851 0.666656 6.76952 1.82668 6.76952 3.25737C6.76952 4.68806 5.59851 5.84808 4.15427 5.84808C2.71004 5.84808 1.53903 4.68806 1.53903 3.25737ZM2.75465 7.90298L2.64312 7.91955C1.65056 8.07422 0.920074 8.9249 0.920074 9.9192C0.920074 10.1954 1.14312 10.4164 1.42193 10.4164H6.87546C7.15427 10.4164 7.37732 10.1954 7.37732 9.9192C7.37732 8.9249 6.64684 8.07422 5.65427 7.91955L5.54275 7.90298C4.62268 7.75383 3.6803 7.75383 2.75465 7.90298ZM2.60967 6.99706C3.63011 6.83686 4.67286 6.83686 5.69331 6.99706L5.80483 7.01363C7.24349 7.24011 8.30297 8.47194 8.30297 9.9192C8.30297 10.6981 7.66729 11.3333 6.87546 11.3333H1.42751C0.635688 11.3333 0 10.6981 0 9.9192C0 8.47194 1.05948 7.24563 2.49814 7.01363L2.60967 6.99706Z" fill="#008B91"/>
                                                            <path id="Vector_2" fill-rule="evenodd" clip-rule="evenodd" d="M7.38287 1.12514C7.38287 0.871041 7.58919 0.666656 7.8457 0.666656C9.28994 0.666656 10.4609 1.82668 10.4609 3.25737C10.4609 4.68806 9.28994 5.84808 7.8457 5.84808C7.58919 5.84808 7.38287 5.6437 7.38287 5.3896C7.38287 5.1355 7.58919 4.93111 7.8457 4.93111C8.7825 4.93111 9.53529 4.17986 9.53529 3.25737C9.53529 2.33488 8.77693 1.58363 7.8457 1.58363C7.58919 1.58363 7.38287 1.37372 7.38287 1.12514ZM8.14681 7.45002C8.14681 7.19592 8.35313 6.99153 8.60964 6.99153H9.20629C9.30667 6.99153 9.40146 6.99706 9.50183 7.01363C10.9405 7.24011 12 8.47194 12 9.9192C12 10.6981 11.3643 11.3333 10.5725 11.3333H9.31224C9.05574 11.3333 8.84942 11.1289 8.84942 10.8748C8.84942 10.6207 9.05574 10.4164 9.31224 10.4164H10.5669C10.8457 10.4164 11.0687 10.1954 11.0687 9.9192C11.0687 8.9249 10.3383 8.07422 9.3457 7.91955C9.29551 7.91402 9.24533 7.9085 9.19514 7.9085H8.59849C8.35313 7.9085 8.14681 7.70412 8.14681 7.45002Z" fill="#008B91"/>
                                                        </g></g>
                                                        <defs><clipPath id="clip0_367_5700"><rect width="12" height="10.6667" fill="white" transform="translate(0 0.666656)"/></clipPath>
                                                        </defs>
                                                    </svg>
                                                    <?php
                                                    // show enrolled students
                                                    global $wpdb;
                                                    $enrolled_students = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}moodle_enrollment WHERE course_id IN ( %s )", implode( ',', $courses ) ) );

                                                    echo '<span>' . $enrolled_students . ' ' . __( 'Enrolled', 'edwiser-bridge-pro' ) . ' </span>';

                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="eb-pro-shop-product-actions">
                                            <span class="price"><?php echo $product->get_price_html(); ?></span>
                                            <div class="action-buttons">
                                                <div class="cart-button">
                                                    <?php
                                                    $buy_now_svg = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="Icons"><path id="Vector" d="M2.08334 3.54166C2.08334 3.3759 2.14919 3.21692 2.2664 3.09971C2.38361 2.9825 2.54258 2.91666 2.70834 2.91666H3.17334C3.96501 2.91666 4.44001 3.44916 4.71084 3.94416C4.89168 4.27416 5.02251 4.65666 5.12501 5.00332C5.15273 5.00113 5.18053 5.00002 5.20834 4.99999H15.6233C16.315 4.99999 16.815 5.66166 16.625 6.32749L15.1017 11.6683C14.9651 12.1474 14.6761 12.5689 14.2786 12.869C13.881 13.1691 13.3965 13.3315 12.8983 13.3317H7.94168C7.43959 13.3317 6.95139 13.1668 6.55211 12.8624C6.15284 12.558 5.86459 12.1308 5.73168 11.6467L5.09834 9.33666L4.04834 5.79666L4.04751 5.78999C3.91751 5.31749 3.79584 4.87499 3.61418 4.54499C3.44001 4.22416 3.30001 4.16666 3.17418 4.16666H2.70834C2.54258 4.16666 2.38361 4.10081 2.2664 3.9836C2.14919 3.86639 2.08334 3.70742 2.08334 3.54166ZM6.31084 9.03332L6.93668 11.3158C7.06168 11.7675 7.47251 12.0817 7.94168 12.0817H12.8983C13.1248 12.0817 13.345 12.0079 13.5258 11.8715C13.7065 11.7351 13.8379 11.5436 13.9 11.3258L15.3475 6.24999H5.48751L6.29918 8.98916L6.31084 9.03332ZM9.16668 15.8333C9.16668 16.2754 8.99108 16.6993 8.67852 17.0118C8.36596 17.3244 7.94204 17.5 7.50001 17.5C7.05798 17.5 6.63406 17.3244 6.3215 17.0118C6.00894 16.6993 5.83334 16.2754 5.83334 15.8333C5.83334 15.3913 6.00894 14.9674 6.3215 14.6548C6.63406 14.3423 7.05798 14.1667 7.50001 14.1667C7.94204 14.1667 8.36596 14.3423 8.67852 14.6548C8.99108 14.9674 9.16668 15.3913 9.16668 15.8333ZM7.91668 15.8333C7.91668 15.7228 7.87278 15.6168 7.79464 15.5387C7.7165 15.4606 7.61052 15.4167 7.50001 15.4167C7.3895 15.4167 7.28352 15.4606 7.20538 15.5387C7.12724 15.6168 7.08334 15.7228 7.08334 15.8333C7.08334 15.9438 7.12724 16.0498 7.20538 16.128C7.28352 16.2061 7.3895 16.25 7.50001 16.25C7.61052 16.25 7.7165 16.2061 7.79464 16.128C7.87278 16.0498 7.91668 15.9438 7.91668 15.8333ZM15 15.8333C15 16.2754 14.8244 16.6993 14.5119 17.0118C14.1993 17.3244 13.7754 17.5 13.3333 17.5C12.8913 17.5 12.4674 17.3244 12.1548 17.0118C11.8423 16.6993 11.6667 16.2754 11.6667 15.8333C11.6667 15.3913 11.8423 14.9674 12.1548 14.6548C12.4674 14.3423 12.8913 14.1667 13.3333 14.1667C13.7754 14.1667 14.1993 14.3423 14.5119 14.6548C14.8244 14.9674 15 15.3913 15 15.8333ZM13.75 15.8333C13.75 15.7228 13.7061 15.6168 13.628 15.5387C13.5498 15.4606 13.4439 15.4167 13.3333 15.4167C13.2228 15.4167 13.1169 15.4606 13.0387 15.5387C12.9606 15.6168 12.9167 15.7228 12.9167 15.8333C12.9167 15.9438 12.9606 16.0498 13.0387 16.128C13.1169 16.2061 13.2228 16.25 13.3333 16.25C13.4439 16.25 13.5498 16.2061 13.628 16.128C13.7061 16.0498 13.75 15.9438 13.75 15.8333Z" fill="#C7660E"/></g></svg>';
                                                    $added_in_cart = '<svg class="added-in-cart" width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="Icons"><path id="Path (Stroke)" fill-rule="evenodd" clip-rule="evenodd" d="M3.54911 8.04633C3.83686 7.76231 4.30341 7.76231 4.59116 8.04633L6.66663 10.0775L11.4088 5.37966C11.6965 5.09564 12.1631 5.09564 12.4508 5.37966C12.7386 5.66368 12.7386 6.12416 12.4508 6.40818L7.18765 11.6203C6.8999 11.9043 6.43335 11.9043 6.1456 11.6203L3.54911 9.07485C3.26135 8.79083 3.26135 8.33035 3.54911 8.04633Z" fill="white"/></g></svg>';
                                                    $in_cart = in_array( get_the_ID(), $cart_product_ids ) ? true : false;
                                                    if ( $product->is_purchasable() && ! $product->is_type( 'variable' ) ) {
                                                        echo apply_filters( 'woocommerce_loop_add_to_cart_link', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            sprintf( '<a href="%s" data-quantity="1" class="%s">%s</a>',
                                                                $in_cart ? wc_get_cart_url() : esc_url( $product->add_to_cart_url() ),
                                                                'eb-pro-shop-add-to-cart-button',
                                                                $buy_now_svg
                                                            ),
                                                            $product );

                                                        if( $in_cart ) {
                                                            echo $added_in_cart;
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <?php
                                                if ( 'simple' === $product->get_type() ) {
                                                    global $eb_pro_plugin_data;
                                                    $public_class = new \app\wisdmlabs\edwiserBridgePro\pb\Bridge_Woocommerce_Public( $eb_pro_plugin_data['plugin_slug'], $eb_pro_plugin_data['plugin_version'] );
                                                    $args = array( 'product' => $product, 'class' => 'eb-pro-shop-buy-now-button' );
                                                    echo str_replace( 'button ', '', $public_class :: get_buy_now_button( $args ) );
                                                }
                                                ?>
                                                <?php if ( 'yes' === $settings['show_view_button'] ) : 
                                                    $button_text = __( 'View', 'edwiser-bridge-pro' );
                                                    if( $product->is_type( 'variable' ) ) {
                                                        $button_text = __( 'Select options', 'edwiser-bridge-pro' );
                                                    } elseif( $product->is_type( 'subscription' ) ) {
                                                        $button_text = __( 'Sign up now', 'edwiser-bridge-pro' );
                                                    } elseif( $product->is_type( 'variable-subscription' ) ) {
                                                        $button_text = __( 'Select options', 'edwiser-bridge-pro' );
                                                    } elseif( ! $product->is_purchasable() ) {
                                                        $button_text = __( 'Read more', 'edwiser-bridge-pro' );
                                                    }
                                                    ?>
                                                    <a href="<?php the_permalink(); ?>" class="eb-pro-shop-buy-now-button"><?php echo esc_html( $button_text ); ?></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>

                    <!-- pagination -->
                    <div class="eb-pro-shop-pagination">
                        <?php
                        echo paginate_links( [
                            'total'   => $products->max_num_pages,
                            'current' => max( 1, get_query_var( 'paged' ) ),
                            'prev_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.6805 5.32372C16.1065 5.75536 16.1065 6.45517 15.6805 6.8868L10.6337 12L15.6805 17.1132C16.1065 17.5448 16.1065 18.2446 15.6805 18.6763C15.2545 19.1079 14.5637 19.1079 14.1377 18.6763L8.31952 12.7815C7.89349 12.3499 7.89349 11.6501 8.31952 11.2185L14.1377 5.32372C14.5637 4.89209 15.2545 4.89209 15.6805 5.32372Z" fill="#273E3F"/></svg>',
                            'next_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.31952 18.6763C7.89349 18.2446 7.89349 17.5448 8.31952 17.1132L13.3663 12L8.31952 6.8868C7.89349 6.45517 7.89349 5.75536 8.31952 5.32372C8.74555 4.89209 9.43627 4.89209 9.8623 5.32372L15.6805 11.2185C16.1065 11.6501 16.1065 12.3499 15.6805 12.7815L9.8623 18.6763C9.43627 19.1079 8.74555 19.1079 8.31952 18.6763Z" fill="#273E3F"/></svg>',
                        ] );
                        ?>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php

        wp_reset_postdata();

        wp_reset_query();
    }
}

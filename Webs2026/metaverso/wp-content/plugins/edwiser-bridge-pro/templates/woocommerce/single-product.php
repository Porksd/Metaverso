<?php

/**
 * The Template for displaying all single products
 *
 * @package Edwiser Bridge Pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('shop'); ?>

<?php
/**
 * eb_pro_single_product_before_content hook.
 */
do_action('eb_pro_single_product_before_content');

$page_id = get_option('eb_woo_gutenberg_pages', array())['eb_pro_single_product_page_id'];
// $product_id = get_option('eb_woo_gutenberg_pages', array())['eb_pro_single_product_page_product_id'];
$page = get_post($page_id);
if ($page && !is_wp_error($page)) {
    // $content = str_replace($product_id, get_the_ID(), $page->post_content);
    echo apply_filters('the_content', $page->post_content);
} else {
    echo do_blocks(
        '<!-- wp:edwiser-bridge-pro/single-product -->
<div class="wp-block-edwiser-bridge-pro-single-product"><div id="eb-product-desc" data-show-category="true" data-show-ratings="true" data-show-created="true" data-show-course-access="true" data-show-enrolled="true" data-show-associated-courses="true" data-show-related-courses="true"></div></div>
<!-- /wp:edwiser-bridge-pro/single-product -->'
    );
}

/**
 * eb_pro_single_product_after_content hook.
 */
do_action('eb_pro_single_product_after_content');
?>

<?php
get_footer('shop');

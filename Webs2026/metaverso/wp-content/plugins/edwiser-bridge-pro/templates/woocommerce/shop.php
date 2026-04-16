<?php
/**
 * The template for displaying shop page
 *
 * @package Edwiser Bridge Pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
get_header();
/**
 * Hook: eb_pro_shop_before_content
 *
 * @hooked Eb_Pro_Woo_Int::shop_wrapper_start - 10
 */
do_action('eb_pro_shop_before_content');

$custom_shop_page_id =  get_option('eb_woo_gutenberg_pages', array())['eb_pro_shop_page_id'];
$content_page = get_post( $custom_shop_page_id );
if ($content_page && !is_wp_error($content_page)) {
    // Apply the_content filter to process shortcodes and blocks
    $content = apply_filters('the_content', $content_page->post_content);
    echo $content;
} else {
    // Fallback content if page not found
    echo '<!-- wp:paragraph --><p>Content page not found.</p><!-- /wp:paragraph -->';
}

/**
 * Hook: eb_pro_shop_after_content
 *
 * @hooked Eb_Pro_Woo_Int::shop_wrapper_end - 10
 */
do_action('eb_pro_shop_after_content');

get_footer(); 

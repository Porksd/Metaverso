<?php
/**
 * Custom Cart Template
 * Overrides the default WooCommerce cart template
 *
 * @package    Edwiser Bridge Pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the content from the custom cart page
$page_id = get_option('eb_woo_gutenberg_pages', array())['eb_pro_cart_page_id'];
$page = get_post($page_id);

if ($page && !is_wp_error($page)) {
    // Apply filters to content
    $content = apply_filters('the_content', $page->post_content);
    
    // Output the content
    echo '<div class="custom-cart-content">';
    echo $content;
    echo '</div>';
} 

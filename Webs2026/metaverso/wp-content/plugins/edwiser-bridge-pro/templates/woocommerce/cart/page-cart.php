<?php
/**
 * Custom Cart Page Template
 * Overrides the default WooCommerce cart page template
 *
 * @package    Edwiser Bridge Pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('shop'); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php
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
            
            // No WooCommerce actions here - rely on Gutenberg block only
        } else {
            // Fallback to default WooCommerce cart if custom page not found
            wc_get_template('cart/cart.php');
        }
        ?>
    </main>
</div>

<?php
get_footer('shop'); 

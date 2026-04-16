<?php

class EdwiserBridgePro_Blocks
{
    public function __construct()
    {
        add_action('init', array($this, 'eb_register_blocks'));
        add_action('wp_enqueue_scripts', array($this, 'eb_set_script_translations'));
        add_filter('block_categories_all', array($this, 'eb_register_edwiser_category'));
        add_action('wp_enqueue_scripts', array($this, 'eb_woo_storeapi_nonce'));
        add_action('wp_enqueue_scripts', array($this, 'eb_localize_user_data'));
        add_action('wp_enqueue_scripts', array($this, 'eb_localize_eb_settings'));
        add_action('enqueue_block_editor_assets', array($this, 'eb_localize_eb_settings'));
        add_action('wp_after_insert_post', array($this, 'handle_block_setting_change'), 10, 3);
    }

    public function eb_register_blocks()
    {
        load_plugin_textdomain('edwiser-bridge-pro', false, dirname(plugin_basename(__DIR__)) . '/languages');

        wp_register_script(
            'eb-shop-script',
            plugins_url('/blocks/build/shop/index.js', __DIR__),
            array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'),
            filemtime(plugin_dir_path(__DIR__) . 'blocks/build/shop/index.js')
        );
        wp_register_script(
            'eb-cart-script',
            plugins_url('/blocks/build/cart/index.js', __DIR__),
            array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'),
            filemtime(plugin_dir_path(__DIR__) . 'blocks/build/cart/index.js')
        );
        wp_register_script(
            'eb-single-product-script',
            plugins_url('/blocks/build/single-product/index.js', __DIR__),
            array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'),
            filemtime(plugin_dir_path(__DIR__) . 'blocks/build/single-product/index.js')
        );
        wp_register_script(
            'eb-thank-you-script',
            plugins_url('/blocks/build/thank-you/index.js', __DIR__),
            array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'),
            filemtime(plugin_dir_path(__DIR__) . 'blocks/build/thank-you/index.js')
        );
        wp_register_script(
            'eb-legacy-checkout-script',
            plugins_url('/blocks/build/legacy-checkout/index.js', __DIR__),
            array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components'),
            filemtime(plugin_dir_path(__DIR__) . 'blocks/build/legacy-checkout/index.js')
        );

        register_block_type(__DIR__  . '/../blocks/build/shop');
        register_block_type(__DIR__  . '/../blocks/build/cart');
        register_block_type(__DIR__  . '/../blocks/build/single-product');
        register_block_type(__DIR__  . '/../blocks/build/thank-you');
        register_block_type(__DIR__  . '/../blocks/build/legacy-checkout');
        register_block_type(__DIR__  . '/../blocks/build/group-management');

        register_post_meta('page', 'productId', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            }
        ));
    }

    public function eb_set_script_translations()
    {
        wp_set_script_translations('eb-shop-script', 'edwiser-bridge-pro', plugin_dir_path(__FILE__) . 'languages/');
        wp_set_script_translations('eb-cart-script', 'edwiser-bridge-pro', plugin_dir_path(__FILE__) . 'languages/');
        wp_set_script_translations('eb-single-product-script', 'edwiser-bridge-pro', plugin_dir_path(__FILE__) . 'languages/');
        wp_set_script_translations('eb-thank-you-script', 'edwiser-bridge-pro', plugin_dir_path(__FILE__) . 'languages/');
        wp_set_script_translations('eb-legacy-checkout-script', 'edwiser-bridge-pro', plugin_dir_path(__FILE__) . 'languages/');
    }

    public function eb_woo_storeapi_nonce()
    {
        wp_register_script('eb_woo_storeapi_nonce', '', [], '', true);

        wp_enqueue_script('eb_woo_storeapi_nonce');

        $nonce = wp_create_nonce('wc_store_api');

        wp_localize_script(
            'eb_woo_storeapi_nonce',
            'ebStoreApiNonce',
            array(
                'nonce' => $nonce,
            )
        );
    }

    public function eb_register_edwiser_category($categories)
    {

        $categories[] = array(
            'slug'  => 'edwiser',
            'title' => 'Edwiser'
        );

        return $categories;
    }

    public function eb_localize_user_data()
    {
        wp_register_script('eb_localize_user_data', '', [], '', true);

        wp_enqueue_script('eb_localize_user_data');

        $current_user = wp_get_current_user();

        if ($current_user->ID !== 0) {
            wp_localize_script('eb_localize_user_data', 'ebCurrentUser', array(
                'ID' => $current_user->ID,
                'display_name' => $current_user->display_name,
                'user_email' => $current_user->user_email
            ));
        }
    }

    public function eb_localize_eb_settings()
    {
        wp_register_script('eb_settings', '', [], '', true);
        wp_enqueue_script('eb_settings');

        wp_localize_script(
            'eb_settings',
            'ebSettings',
            array(
                'adminUrl' => admin_url(),
                'siteUrl' => site_url()
            )
        );
    }

    public function handle_block_setting_change($post_id, $post, $update)
    {
        // Only run on post updates, not new posts
        if (!$update) return;

        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Only handle specific post types if needed
        if (!in_array($post->post_type, ['page'])) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Get the current setting value
        $current_value = get_post_meta($post_id, 'productId', true);

        // Get the previous value (stored in a separate meta field)
        $previous_value = get_post_meta($post_id, 'productIdold', true);

        // Check if the value has changed
        if ($current_value !== $previous_value) {
            $product_id = $current_value;
            $gutenberg_pages = get_option('eb_woo_gutenberg_pages', array());
            $gutenberg_pages['eb_pro_single_product_page_product_id'] = (int) $product_id;
            update_option('eb_woo_gutenberg_pages', $gutenberg_pages);
            // Update the previous value for next comparison
            update_post_meta($post_id, 'productIdold', $current_value);
        }
    }
}

new EdwiserBridgePro_Blocks();

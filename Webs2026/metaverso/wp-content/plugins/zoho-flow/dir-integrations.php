<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

$zoho_flow_dir_integrations_config = array (
    array (
        'name' => esc_html__('WooCommerce'),
        'id' => 'woocommerce',
        'gallery_app_link' => 'woocommerce',
        'description' => esc_html__('Use WooCommerce to create and manage an e-commerce platform on your WordPress site. By integrating WooCommerce with your favourite applications, you will be able to create and share invoices automatically when someone makes a purchase on your e-commerse site.', 'zoho-flow'),
        'icon_file' => 'woocommerce.png',
        'class_test' => 'WooCommerce',
    ),
    array (
        'name' => esc_html__('MemberPress'),
        'id' => 'memberpress',
        'gallery_app_link' => 'memberpress',
        'description' => esc_html__('Use MemberPress to manage and track membership subscriptions and sell digital download products on your WordPress site. Integrate MemberPress with other applications to share content with your customers based on their membership level.', 'zoho-flow'),
        'icon_file' => 'memberpress.png',
        'class_test' => 'MeprCtrlFactory',
    ),
    array (
        'name' => esc_html__('SureCart'),
        'id' => 'surecart',
        'gallery_app_link' => 'surecart',
        'description' => esc_html__('Use SureCart to set up and manage an online store on your WordPress site. By integrating SureCart with your favourite applications, you will be able to make changes to your inventory when a sale is completed on your e-commerce site.', 'zoho-flow'),
        'icon_file' => 'surecart.png',
        'class_test' => 'SureCart',
    ),
    array (
        'name' => esc_html__('Bit Form'),
        'id' => 'bit-form',
        'gallery_app_link' => 'bit-form',
        'description' => esc_html__('Use Bit Form to build different types of contact forms on your WordPress site. By integrating Bit Form with other applications, you\'ll be able to automate workflows based on your form entries.', 'zoho-flow'),
        'icon_file' => 'bit-form.png',
        'class_test' => 'BitCode\BitForm\API\BitForm_Public\BitForm_Public',
    ),
    array (
        'name' => esc_html__('Groundhogg'),
        'id' => 'groundhogg',
        'gallery_app_link' => 'groundhogg',
        'description' => esc_html__('Use Groundhogg to create marketing funnels, manage email campaigns, send newsletters, and more on your WordPress site. By integrating Groundhogg with other applications, you can create new contacts automatically whenever a new lead signs up in your website.', 'zoho-flow'),
        'icon_file' => 'groundhogg.png',
        'class_test' => 'Groundhogg\Admin\Welcome\Welcome_Page',
    ),
    array (
        'name' => esc_html__('Beamer'),
        'id' => 'beamer',
        'gallery_app_link' => 'beamer',
        'description' => esc_html__('Use Beamer to announce news, latest features, and other updates on your WordPress site. By integrating Beamer with your favourite applications, you can get instant notifications whenever a new announcement is made.', 'zoho-flow'),
        'icon_file' => 'beamer.png',
        'class_test' => 'BeamerSettings',
    )
);

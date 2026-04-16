<?php
/**
 * Woo Int Public Module
 * This class is responsible for SSO module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for SSO module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\pb;

use \app\wisdmlabs\edwiserBridge\Eb_Template_Loader;
use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

/**
 * Class Bridge Woocommerce Shortcode Single Cart Checkout
 */
class Bridge_Woocommerce_Shortcode_Single_Cart_Checkout {
	/**
	 * Get the shortcode content.
	 *
	 * @since  1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function get( $atts ) {
		return Bridge_Woocommerce_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @since  1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function output( $atts ) {
		// Currently no arguments.
		extract( shortcode_atts( array(), $atts ) ); // @codingStandardsIgnoreLine

		require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

		$ed_bdg = new EdwiserBridge();

		require_once WP_PLUGIN_DIR . '/edwiser-bridge/public/class-eb-template-loader.php';
		$tpl_loader = new Eb_Template_Loader( $ed_bdg->getPluginName(), $ed_bdg->getVersion() );

		$tpl_loader->wpGetTemplate(
			'single-cart-checkout.php',
			array(),
			'',
			EB_PRO_PLUGIN_PATH . 'public/templates/'
		);
	}
}


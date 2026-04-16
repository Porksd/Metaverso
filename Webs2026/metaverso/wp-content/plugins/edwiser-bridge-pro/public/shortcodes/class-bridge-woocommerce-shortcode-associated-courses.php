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
 * Slass bridge woo associated courses.
 */
class Bridge_Woocommerce_Shortcode_Associated_Courses {
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
		// Including required files.
		include_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-bridge-woo-functions.php';

		require_once WP_PLUGIN_DIR . '/edwiser-bridge/public/class-eb-template-loader.php';

		extract( shortcode_atts( array( 'product_id' => '' ), $atts ) ); // @codingStandardsIgnoreLine
		$edwiser_bridge = new EdwiserBridge();

		$plugin_tpl_loader = new Eb_Template_Loader( $edwiser_bridge->getPluginName(), $edwiser_bridge->getVersion() );

		if ( empty( $product_id ) ) {
			$product_id = '';
		}
		$plugin_tpl_loader->wpGetTemplate(
			'associated-courses-product-page.php',
			array(
				'product_id' => $product_id,
			),
			'',
			EB_PRO_PLUGIN_PATH . 'public/templates/'
		);
	}
}


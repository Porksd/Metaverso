<?php
/**
 * Woo Integration Module
 * This class is responsible for Woo Integration module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for Woo Integration module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\wooInt;

use \app\wisdmlabs\edwiserBridge\EdwiserBridge;
use app\wisdmlabs\edwiserBridgePro\includes as includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Woo Integration AJAX class
 */
class Bridge_Woocommerce_Ajax {

	/**
	 * EdwiserBridge object
	 *
	 * @var object
	 */
	private $edwiser_bridge;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_handle_product_synchronization', array( $this, 'handle_product_synchronization_callback' ) );
		add_action( 'wp_ajax_handle_product_synchronization', array( $this, 'handle_product_synchronization_callback' ) );

		require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

		$this->edwiser_bridge = new EdwiserBridge();
	}

	/**
	 * This is Product synchronization AJAX callback
	 * of the plugin.
	 *
	 * @since    1.0.2
	 * @access   public
	 */
	public function handle_product_synchronization_callback() {
		$this->edwiser_bridge->logger()->add( 'product', 'Initiating Product sync process....' ); // Add product updated log.

		if ( ! isset( $_POST['_wpnonce_field'] ) ) {
			die( 'Busted!' );
		}

		$nonce = isset( $_POST['_wpnonce_field'] ) ? esc_attr( $_POST['_wpnonce_field'] ) : ''; // @codingStandardsIgnoreLine

		// verifying generated nonce we created earlier.
		if ( ! wp_verify_nonce( $nonce, 'check_product_sync_action' ) ) {
			die( 'Busted !' );
		}

		$sync_options = isset( $_POST['sync_options'] ) ? esc_attr( $_POST['sync_options'] ) : ''; // @codingStandardsIgnoreLine
		// get sync options.
		$sync_options      = json_decode( str_replace( '\\', '', html_entity_decode( $sync_options ) ), 1); // @codingStandardsIgnoreLine
		$course_woo_plugin = new Bridge_Woocommerce_Course( includes\edwiser_bridge_pro()->get_plugin_name(), includes\edwiser_bridge_pro()->get_version() );
		$response          = $course_woo_plugin->bridge_woo_product_sync_handler( $sync_options );

		echo json_encode( $response ); // @codingStandardsIgnoreLine

		die();
	}
}
new Bridge_Woocommerce_Ajax();

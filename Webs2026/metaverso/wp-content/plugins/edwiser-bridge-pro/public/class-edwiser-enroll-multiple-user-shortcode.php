<?php
/**
 * The provides enroll student page shortcode functionality.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\pb;

use app\wisdmlabs\edwiserBridgePro\includes as includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Edwiser_Enroll_Multiple_User_Shortcode' ) ) {

	/**
	 * Class provides the shortcode for the enroll students page.
	 */
	class Edwiser_Enroll_Multiple_User_Shortcode {
		/**
		 * Init shortcodes.
		 */
		public static function init() {

			$shortcodes = array(
				'bridge_woo_enroll_users' => __CLASS__ . '::enroll_users',
			);

			foreach ( $shortcodes as $shortcode => $function ) {
				add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
			}
		}

		/**
		 * Shortcode Wrapper.
		 *
		 * @param mixed $function name of the function.
		 * @param array $atts attributes array.
		 * @param array $wrapper wrapeer structure array.
		 */
		public static function shortcode_wrapper(
			$function,
			$atts = array(),
			$wrapper = array(
				'class'  => '',
				'before' => null,
				'after'  => null,
			)
		) {
			ob_start();

			$before = empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
			$after  = empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

			echo wp_kses( $before, includes\bulkPurchase\eb_bp_get_allowed_html_tags() );
			call_user_func( $function, $atts );
			echo wp_kses( $after, includes\bulkPurchase\eb_bp_get_allowed_html_tags() );

			return ob_get_clean();
		}

		/**
		 * Enroll user shortcode.
		 *
		 * @since  1.0.0
		 *
		 * @param array $atts Shortcode attributes array.
		 *
		 * @return string
		 */
		public static function enroll_users( $atts ) {
			wp_enqueue_script( 'eb-pro-bulk-purchase-enroll-students' );
			include_once EB_PRO_PLUGIN_PATH . 'public/shortcodes/class-eb-shortcode-enroll-users.php';

			return self::shortcode_wrapper(
				array(
					'app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Shortcode_Enroll_Users',
					'output',
				),
				$atts
			);
		}
	}
}

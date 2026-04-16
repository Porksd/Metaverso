<?php
/**
 * The file that defines the enroll user shortcode.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

use app\wisdmlabs\edwiserBridge as edwiserBridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Eb_Shortcode_Enroll_Users' ) ) {
	/**
	 * Class defines the shortcode for user enrollment page.
	 */
	class Eb_Shortcode_Enroll_Users {

		/**
		 * Get the shortcode content.
		 *
		 * @param array $atts shortcode attributes.
		 *
		 * @return string
		 */
		public static function get( $atts ) {
			return \app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Edwiser_Enroll_Multiple_User_Shortcode::shortcode_wrapper(
				array( __CLASS__, 'output' ),
				$atts
			);
		}

		/**
		 * Output the shortcode.
		 *
		 * @param array $atts shortcode attributes.
		 */
		public static function output( $atts ) {
			extract( // @codingStandardsIgnoreLine
				shortcode_atts(
					array(
						'user_id' => '',
					),
					$atts
				)
			);

			$plu_template_loader = new edwiserBridge\Eb_Template_Loader(
				edwiserBridge\edwiser_bridge_instance()->get_plugin_name(),
				edwiserBridge\edwiser_bridge_instance()->get_version()
			);
			$user_ID             = get_current_user_id();
			$plu_template_loader->wpGetTemplate(
				'enroll-users-page.php',
				array(
					'user_id' => $user_ID,
				),
				'',
				EB_PRO_PLUGIN_PATH . 'public/templates/'
			);
		}
	}
}

<?php
/**
 * Edwiser Bridge Pro
 *
 * @link       https://edwiser.org
 *
 * @package    Edwiser Bridge Pro
 * @subpackage Edwiser Bridge Pro/admin
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

use app\wisdmlabs\edwiserBridgePro\includes as includes;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Eb_Pro_Elementor_Template_Settings' ) ) :

	/**
	 * Eb_Settings_Licensing.
	 */
	class Eb_Pro_Elementor_Template_Settings extends \app\wisdmlabs\edwiserBridge\EB_Settings_Page {

		/**
		 * Addon licensing.
		 *
		 * @var text $addon_licensing addon licensing
		 */
		public $addon_licensing;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->addon_licensing = array( 'test' );
			$this->_id             = 'elementor_template_settings';
			$this->label           = __( 'Templates', 'edwiser-bridge-pro' );

			add_filter( 'eb_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'eb_settings_' . $this->_id, array( $this, 'output' ) );
		}

		/**
		 * Output the settings.
		 *
		 * @since  1.0.0
		 */
		public function output() {
			// Hide the save button.
			$GLOBALS['hide_save_button'] = true;
			$plugin_path                 = plugin_dir_path( __DIR__ );

            $this->handle_template_actions();
			require_once $plugin_path . 'partials/html-elementor-templates.php';
		}

		/**
		 * Get settings array.
		 *
		 * @since  1.0.0
		 * @param text $current_section current section.
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			$settings = apply_filters(
				'eb_licensing',
				array(
					array(
						'title' => __( 'Templates', 'edwiser-bridge-pro' ),
						'type'  => 'title',
						'id'    => 'elementor_template_settings',
					),
					array(
						'type' => 'sectionend',
						'id'   => 'elementor_template_settings',
					),
				)
			);

			return apply_filters( 'eb_get_settings_' . $this->_id, $settings, $current_section );
		}

        public function handle_template_actions() {
            if ( isset( $_GET['action'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'eb_pro_elementor_template' ) ) {
                $template = sanitize_text_field( wp_unslash( $_GET['template'] ) );
                require_once EB_PRO_PLUGIN_PATH . 'includes/class-eb-pro-activator.php';

                if ( 'product_archive' === $template ) {
                    $activator = new includes\Eb_Pro_Activator();
                    $post_id   = $activator::create_elementor_shop_page_template();
					wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=elementor' ) );
                } elseif ( 'product_single' === $template ) {
                    $activator = new includes\Eb_Pro_Activator();
                    $post_id   =$activator::create_elementor_product_page_template();
					wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=elementor' ) );
                }

            }
        }
	}

endif;

return new Eb_Pro_Elementor_Template_Settings();

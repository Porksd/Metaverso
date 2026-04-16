<?php
/**
 * Woo Int Module
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

use app\wisdmlabs\edwiserBridgePro\includes as includes;

/*
 * EDW General Settings
 *
 * @link       https://edwiser.org
 * @since      1.0.0
 *
 * @package    Edwiser Bridge
 * @subpackage Edwiser Bridge/admin
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Bridge_Woocommerce_Settings' ) ) :

	/**
	 * Bridge_Woocommerce_Settings.
	 */
	class Bridge_Woocommerce_Settings extends \app\wisdmlabs\edwiserBridge\EB_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->_id   = 'woo_int_settings';
			$this->label = __( 'Woo Integration', 'edwiser-bridge-pro' );

			add_filter( 'eb_settings_tabs_array', array( $this, 'addSettingsPage' ), 20 );
			add_action( 'eb_settings_' . $this->_id, array( $this, 'output' ) );
			add_action( 'eb_settings_save_' . $this->_id, array( $this, 'save' ) );
		}

		/**
		 * Get settings array.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function getSettings() {
			$settings = apply_filters(
				'wooint_settings_fields',
				array(
					array(
						'title' => __( 'WooCommerce Integration Options', 'edwiser-bridge-pro' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wooint_options',
					),
					// Adding Enable Redirection Option On Checkout Page.
					array(
						'title'    => __( 'Enable Redirection', 'edwiser-bridge-pro' ),
						'desc'     => __( 'This enables user to redirect to <strong>My Courses</strong> page after order completion.', 'edwiser-bridge-pro' ),
						'id'       => 'wi_enable_redirect',
						'default'  => 'yes',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Associated Courses Section', 'edwiser-bridge-pro' ),
						'desc'     => __( 'This shows the associated courses section on the single product page.', 'edwiser-bridge-pro' ),
						'id'       => 'wi_enable_asso_courses',
						'default'  => 'yes',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Control Moodle User Creation', 'edwiser-bridge-pro' ),
						'desc'     => __( 'If Enabled: When a user purchases or subscribes to a product directly associated to a Moodle course, only their details will be synchronised with the Moodle site. If Disabled: All users purchasing any products will be synced to the Moodle site.', 'edwiser-bridge-pro' ),
						'id'       => 'wi_enable_moodle_user_creation',
						'default'  => 'yes',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'One Click Checkout', 'edwiser-bridge-pro' ),
						'desc'     => __( 'This enables <strong>Buy Now</strong> button for simple products. Using this, users will be directly redirected to <strong>Single Cart Checkout</strong> page and the product will be added to their cart.', 'edwiser-bridge-pro' ),
						'id'       => 'wi_enable_buynow',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'   => __( 'Buy Now Button Text', 'edwiser-bridge-pro' ),
						'desc'    => '<br />' . __( 'This text will be shown on <strong>Buy Now</strong> button. Default will be <strong>Buy Now</strong>.', 'edwiser-bridge-pro' ),
						'id'      => 'wi_buy_now_text',
						'default' => __( 'Buy Now', 'edwiser-bridge-pro' ),
						'type'    => 'text',
						'css'     => 'min-width:300px;',
					),
					array(
						'title'   => __( 'Single Cart Checkout Page', 'edwiser-bridge-pro' ),
						'desc'    => '<br/>' . __( 'Add shortcode <code>[bridge_woo_single_cart_checkout]</code> in the selected page.', 'edwiser-bridge-pro' ),
						'id'      => 'wi_scc_page_id',
						'type'    => 'single_select_page',
						'default' => '',
						'css'     => 'min-width:300px;',
						'args'    => array(
							'show_option_none'  => __( '- Select a page -', 'edwiser-bridge-pro' ),
							'option_none_value' => '',
						),
					),
					array(
						'type' => 'sectionend',
						'id'   => 'wooint_options',
					),
					array(
						'title' => __( 'WooCommerce My Account Page Settings', 'edwiser-bridge-pro' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wooint_myaccount_page_settings',
					),
					array(
						'title'    => __( 'Enable Account Creation', 'edwiser-bridge-pro' ),
						'desc'     => __( 'This enables <strong>User creation from woocommerce my account page.</strong>', 'edwiser-bridge-pro' ),
						'id'       => 'wi_enable_my_account_user_creation',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Disable Account Creation if payment failed', 'edwiser-bridge-pro' ),
						'desc'     => __( 'This disables <strong>User creation from woocommerce checkout page.</strong> if payment is failed during order process. This also affects:

							<p>⚠️ User passwords will not synchronise from WordPress to Moodle, leading to a different Moodle password at enrolment, but will synchronise if changed later.</p>

							<p>⚠️ Custom fields added to the WooCommerce checkout will not synchronise if the payment fails, but will synchronise after successful enrolment.</p>

							<p>⚠️ This functionality is unavailable for WooCommerce Subscription users; enabling it will affect user enrolments.</p>
							', 'edwiser-bridge-pro' ),
						'id'       => 'wi_disable_checkout_user_creation',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Update My Account Fields On Moodle', 'edwiser-bridge-pro' ),
						'desc'     => __( 'This enables updating fields on the Moodle from woocommerce My-account page.', 'edwiser-bridge-pro' ),
						'id'       => 'wi_enable_my_account_field_update',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Show My Courses Page On My Account Page', 'edwiser-bridge-pro' ),
						'desc'     => __( 'This will show My Courses tab in the My-account page. <b>Note : </b> Please update permalinks once again after enabling this setting.', 'edwiser-bridge-pro' ),
						'id'       => 'wi_show_my_courses_on_my_account',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Enable Purchase Product For Someone Else', 'edwiser-bridge-pro' ),
						'desc'     => __( 'This enables purchase course for someone else on checkout page. User will be able to enroll other users.', 'edwiser-bridge-pro' ),
						'id'       => 'wi_enable_purchase_for_someone_else',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'wooint_myaccount_page_settings',
					),

				)
			);
			$is_subscription_active = $this->checkWoocommerceSubscriptionIsActive();
			if ( $is_subscription_active ) {
				$settings[] = array(
					'title' => __( 'WooCommerce Subscriptions Settings', 'edwiser-bridge-pro' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'wooint_subscriptions',
				);

				$settings[] = array(
					'title'   => __( 'On Subscription Expiration', 'edwiser-bridge-pro' ),
					'desc'    => '<br/>' . __( 'Select an action to perform on expiration of Product Subscription.', 'edwiser-bridge-pro' ),
					'id'      => 'wi_on_subscription_expiration',
					'type'    => 'select',
					'default' => '',
					'css'     => 'min-width:300px;',
					'options' => array(
						'suspend'    => 'Suspend',
						'unenroll'   => 'Unenroll',
						'do-nothing' => 'Do Nothing',
					),
					'args'    => array(
						'show_option_none'  => __( '- Select a page -', 'edwiser-bridge-pro' ),
						'option_none_value' => '',
					),
				);
				$settings[] = array(
					'type' => 'sectionend',
					'id'   => 'wooint_subscriptions',
				);
			}

			$is_membership_active = includes\wooInt\check_woocommerce_membership_is_active();
			if ( $is_membership_active ) {
				$settings[] = array(
					'title' => __( 'WooCommerce Membership Settings', 'edwiser-bridge-pro' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'wooint_membership',
				);

				$settings[] = array(
					'title'   => __( 'On Membership Expiration', 'edwiser-bridge-pro' ),
					'desc'    => '<br/>' . __( 'Select an action to perform on Membership expiration.', 'edwiser-bridge-pro' ),
					'id'      => 'wi_on_membership_expired',
					'type'    => 'select',
					'default' => '',
					'css'     => 'min-width:300px;',
					'options' => array(
						'suspend'    => 'Suspend',
						'unenroll'   => 'Unenroll',
						'do-nothing' => 'Do Nothing',
					),
					'args'    => array(
						'show_option_none'  => __( '- Select a page -', 'edwiser-bridge-pro' ),
						'option_none_value' => '',
					),
				);
				$settings[] = array(
					'title'   => __( 'On Membership Cancellation', 'edwiser-bridge-pro' ),
					'desc'    => '<br/>' . __( 'Select an action to perform on Membership Cancellation.', 'edwiser-bridge-pro' ),
					'id'      => 'wi_on_membership_cancelled',
					'type'    => 'select',
					'default' => '',
					'css'     => 'min-width:300px;',
					'options' => array(
						'suspend'    => 'Suspend',
						'unenroll'   => 'Unenroll',
						'do-nothing' => 'Do Nothing',
					),
					'args'    => array(
						'show_option_none'  => __( '- Select a page -', 'edwiser-bridge-pro' ),
						'option_none_value' => '',
					),
				);
				$settings[] = array(
					'type' => 'sectionend',
					'id'   => 'wooint_membership',
				);
			}

			return apply_filters( 'eb_get_settings_' . $this->_id, $settings );
		}



		/**
		 * Functionality to check if the subscription is activated.
		 *
		 * @return bool true if activated else false.
		 */
		public function checkWoocommerceSubscriptionIsActive() {
			$array_of_activated_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );

			if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $array_of_activated_plugins, true ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Save settings.
		 *
		 * @since  1.0.0
		 */
		public function save() {
			$settings = $this->getSettings();

			\app\wisdmlabs\edwiserBridge\Eb_Admin_Settings::saveFields( $settings );
		}
	}

endif;

return new Bridge_Woocommerce_Settings();

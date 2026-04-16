<?php
/**
 * Add new menu item to the Edwiser Bridge menu.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

use app\wisdmlabs\edwiserBridgePro\includes as includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Eb_Pro_Menu {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Instance of the class.
	 *
	 * @since 3.0.0
	 * @access   protected
	 * @var Eb_Pro_Menu The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Main CustomFields Instance
	 *
	 * Ensures only one instance of Edwiser_Bridge_Pro is loaded or can be loaded.
	 *
	 * @since 3.0.0
	 * @static
	 * @return Eb_Pro_Menu - Main instance
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $eb_pro_plugin_data;
		$this->plugin_name = $eb_pro_plugin_data['plugin_slug'];
		$this->version     = $eb_pro_plugin_data['plugin_version'];
	}

	/**
	 * Add group managment submenu item in Edwiser Bridge menu.
	 *
	 * @since 1.0.0
	 */
	public function eb_bp_menu() {
		add_submenu_page(
			'edit.php?post_type=eb_course',
			__( 'Manage Groups', 'edwiser-bridge-pro' ),
			__( 'Manage Groups', 'edwiser-bridge-pro' ),
			'manage_options',
			'eb-manage-groups',
			array( $this, 'manage_group_page' )
		);
	}

	/**
	 * Add settings submenu item in Edwiser Bridge menu.
	 *
	 * @since 1.0.0
	 */
	public function custom_field_menu() {
		add_submenu_page(
			'edit.php?post_type=eb_course',
			__( 'Custom User Fields', 'edwiser-bridge-pro' ),
			__( 'Custom User Fields', 'edwiser-bridge-pro' ),
			'manage_options',
			'eb-custom-fields',
			array( $this, 'custom_fields_page' )
		);
	}

	/**
	 * Initialize the settings page.
	 *
	 * @since 1.0.0
	 */
	public function manage_group_page() {

		// check if nonce eb-bp-edit-group is set.

		if ( isset( $_REQUEST['eb-bp-edit-group'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['eb-bp-edit-group'] ) ), 'eb-bp-edit-group' ) && isset( $_GET['mdl_cohort_id'] ) && is_numeric( $_GET['mdl_cohort_id'] ) ) {
			$edit_group = new includes\bulkPurchase\Eb_Bp_Edit_Group( wp_unslash( $_GET['mdl_cohort_id'] ) ); // @codingStandardsIgnoreLine
			$edit_group->output();
		} elseif ( isset( $_REQUEST['eb-bp-add-group'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['eb-bp-add-group'] ) ), 'eb-bp-add-group' ) ) {
			$add_group = new includes\bulkPurchase\Eb_Bp_Add_Group();
			$add_group->output();
		} else {
			global $ebbp_plugin_data;
			$manage_groups = new includes\bulkPurchase\Eb_Bp_Manage_Groups( $this->plugin_name, $this->version );
			$manage_groups->output();
		}
	}

	/**
	 * Initialize the settings page.
	 *
	 * @since 1.0.0
	 */
	public function custom_fields_page() {

		$custom_field_handler = new includes\customFields\Edwiser_Custom_Field_Handler();
		$custom_field_handler->eb_output_custom_fields();
	}
}

<?php
/**
 * Selective Sync Module
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Selective_Synch_Settings' ) ) :

	/**
	 * Bridge_Woocommerce_Settings.
	 */
	class Selective_Synch_Settings extends \app\wisdmlabs\edwiserBridge\EB_Settings_Page {
		/**
		 * Variable used to get the users settings object.
		 *
		 * @var array
		 */
		private $users_settings = null;

		/**
		 * Variable used to get the courses settings object.
		 *
		 * @var array
		 */
		private $courses_settings = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->_id   = 'selective_synch_settings';
			$this->label = __( 'Selective Sync', 'edwiser-bridge-pro' );

			$users_object  = new Selective_Synch_Users_Settings();
			$course_object = new Selective_Synch_Courses_Settings();

			// set user and course settinsg objects.
			$this->set_users_settings( $users_object );
			$this->set_courses_settings( $course_object );

			add_filter( 'eb_settings_tabs_array', array( $this, 'addSettingsPage' ), 20 );
			add_action( 'eb_settings_' . $this->_id, array( $this, 'output' ) );

			// Commented save function as we are not using it anymore.
			// add_action('eb_settings_save_'.$this->_id, array($this, 'save'));.
			add_action( 'eb_sections_' . $this->_id, array( $this, 'outputSections' ) );

			/**
			 * The table added in the end of the users setting is added by this hook.
			 * Wp-list-table handling Hook .
			 */
			add_action( 'eb_admin_field_selective_synch_list_table', array( $users_object, 'get_users_table' ) );
		}


		/**
		 * Setter for the users setting.
		 *
		 * @param object $user_settings_object object of the users settings.
		 * @since 1.2.0
		 */
		public function set_users_settings( $user_settings_object ) {
			$this->users_settings = $user_settings_object;
		}

		/**
		 * Setter for the course settings.
		 *
		 * @param object $courses_settings_object object of the courses settings.
		 * @since 1.2.0
		 */
		public function set_courses_settings( $courses_settings_object ) {
			$this->courses_settings = $courses_settings_object;
		}

		/**
		 * Function used to show 2 tabs on the selective synchronization page.
		 *
		 * @since 1.2.0
		 * @return array of the sections.
		 */
		public function getSections() {
			$sections = array(
				''      => __( 'Course', 'edwiser-bridge-pro' ),
				'users' => __( 'Users', 'edwiser-bridge-pro' ),
			);
			return apply_filters( 'eb_get_sections_' . $this->_id, $sections );
		}

		/**
		 * Print settings array.
		 *
		 * @since  1.2.0
		 */
		public function output() {
			global $current_section;
			$GLOBALS['hide_save_button'] = true;
			$settings                    = $this->get_settings( $current_section );
			\app\wisdmlabs\edwiserBridge\Eb_Admin_Settings::outputFields( $settings );
		}


		/**
		 * Get settings array.
		 *
		 * @param string $current_section current section.
		 * @since  1.2.0
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			/**
			 * Enqueue settings paige js only the setting page.
			 */
			$settings = array();

			$nonce = wp_create_nonce( 'check_select_sync_action' );

			$category_list = array();
			$settings      = array();

			// check which section is selected and show the settings accordingly.
			if ( 'users' === $current_section ) {
				$settings = $this->users_settings->get_settings();
			} else {
				$course_data   = $this->courses_settings->get_settings();
				$settings      = $course_data['settings'];
				$category_list = $course_data['category_list'];
			}

			$array_data = array(
				'admin_ajax_path'        => admin_url( 'admin-ajax.php' ),
				'nonce'                  => $nonce,
				'category_list'          => $category_list,
				'chk_error'              => __( 'Select atleast one course to Synchronize.', 'edwiser-bridge-pro' ),
				'select_success'         => __( 'Courses synchronized successfully.', 'edwiser-bridge-pro' ),
				'connect_error'          => __( 'There is a problem while connecting to moodle server.', 'edwiser-bridge-pro' ),
				'ajax_error'             => __( 'Unable to proceed request.', 'edwiser-bridge-pro' ),
				'user_migration_success' => __( 'Users creation and linking commpleted successfully.', 'edwiser-bridge-pro' ),
				'all_user_synch_warning' => __( 'It will take some time please be patient and do not refresh or change the page.', 'edwiser-bridge-pro' ),
			);

			wp_enqueue_script( 'select-admin-js' );
			wp_localize_script( 'select-admin-js', 'admin_js_select_data', $array_data );

			// Enqueuing scripts for datatables.
			wp_enqueue_script( 'eb-ss-button-datatable-js' );
			wp_enqueue_script( 'eb-ss-buttons-html5-datatable-js' );
			wp_enqueue_script( 'eb-ss-button-print-datatable-js' );

			return apply_filters( 'eb_get_settings_' . $this->_id, $settings );
		}
	}

endif;

return new Selective_Synch_Settings();

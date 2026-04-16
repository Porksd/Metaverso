<?php
/**
 * Core plugin class.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes;

use app\wisdmlabs\edwiserBridgePro\admin as admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro class
 */
class Edwiser_Bridge_Pro {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      Eb_Pro_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

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
	 * Modules data array.
	 * This array contains all the modules data.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      array    $modules_data    The array containing all the modules data.
	 */
	protected $modules_data;

	/**
	 * Instance of the class.
	 *
	 * @since 3.0.0
	 * @access   protected
	 * @var Edwiser_Bridge_Pro The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Main CustomFields Instance
	 *
	 * Ensures only one instance of Edwiser_Bridge_Pro is loaded or can be loaded.
	 *
	 * @since 3.0.0
	 * @static
	 * @return Edwiser_Bridge_Pro - Main instance
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

		if ( 'available' !== $this->check_plugin_licensing() ) {
			return;
		}

		$this->modules_data = get_option( 'eb_pro_modules_data' );

		$this->define_constants();
		$this->load_dependencies();
		$this->load_modules();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Define the constants for the plugin.
	 *
	 * @since    3.0.0
	 */
	private function define_constants() {
		// Plugin version.
		if ( ! defined( 'EB_PRO_PLUGIN_VERSION' ) ) {
			define( 'EB_PRO_PLUGIN_VERSION', $this->version );
		}

		// Plugin Folder URL.
		if ( ! defined( 'EB_PRO_PLUGIN_URL' ) ) {
			define( 'EB_PRO_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
		}

		// Plugin Folder Path.
		if ( ! defined( 'EB_PRO_PLUGIN_PATH' ) ) {
			define( 'EB_PRO_PLUGIN_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Files are included on the basis of which modules are enabled.
	 * Create an instance of the loader which will be used to register the hooks.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		if ( 'available' !== $this->check_plugin_licensing() ) {
			return;
		}

		// If user is not admin then load frontend dependencies.
		if ( ! is_admin() ) {
			$this->load_frontend_dependencies();
		}

		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once EB_PRO_PLUGIN_PATH . 'includes/class-eb-pro-loader.php';

		$this->loader = new Eb_Pro_Loader();

		/**
		 * The class responsible for handling admin side functionality.
		 */
		require_once EB_PRO_PLUGIN_PATH . 'admin/class-edwiser-bridge-pro-admin.php';

		/**
		 * The class responsible for adding and handling menu items.
		 */
		require_once EB_PRO_PLUGIN_PATH . 'admin/class-eb-pro-menu.php';

		/**
		 * Selective sync module.
		 */
		if ( isset( $this->modules_data['selective_sync'] ) && 'active' === $this->modules_data['selective_sync'] ) {
			require_once EB_PRO_PLUGIN_PATH . 'includes/selective-sync/class-eb-pro-selective-sync.php';
		}

		/**
		 * SSO module.
		 */
		if ( isset( $this->modules_data['sso'] ) && 'active' === $this->modules_data['sso'] ) {
			require_once EB_PRO_PLUGIN_PATH . 'includes/sso/class-eb-pro-sso.php';
		}

		/**
		 * WooCommerce integration module.
		 */
		if ( isset( $this->modules_data['woo_integration'] ) && 'active' === $this->modules_data['woo_integration'] ) {
			require_once EB_PRO_PLUGIN_PATH . 'includes/woo-int/class-eb-pro-woo-int.php';
		}

		/**
		 * Bulk purchase module.
		 */
		if ( isset( $this->modules_data['bulk_purchase'] ) && 'active' === $this->modules_data['bulk_purchase'] ) {
			require_once EB_PRO_PLUGIN_PATH . 'includes/bulk-purchase/class-eb-pro-bulk-purchase.php';
		}

		/**
		 * Custom fields module.
		 */
		if ( isset( $this->modules_data['custom_fields'] ) && 'active' === $this->modules_data['custom_fields'] ) {
			require_once EB_PRO_PLUGIN_PATH . 'includes/custom-fields/class-eb-pro-custom-fields.php';
		}
	}

	/**
	 * Load all active modules.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_modules() {

		if ( 'available' !== $this->check_plugin_licensing() ) {
			return;
		}

		/**
		 * Selective sync module.
		 */
		if ( isset( $this->modules_data['selective_sync'] ) && 'active' === $this->modules_data['selective_sync'] ) {
			$selective_sync = new selectiveSync\Eb_Pro_Selective_Sync( $this->loader );
		}

		/**
		 * SSO module.
		 */
		if ( isset( $this->modules_data['sso'] ) && 'active' === $this->modules_data['sso'] ) {
			$sso = new sso\Eb_Pro_Sso( $this->loader );
		}

		/**
		 * WooCommerce integration module.
		 */
		if ( isset( $this->modules_data['woo_integration'] ) && 'active' === $this->modules_data['woo_integration'] ) {
			$woo_int = new wooInt\Eb_Pro_Woo_Int( $this->loader );
		}

		/**
		 * Bulk purchase module.
		 */
		if ( isset( $this->modules_data['bulk_purchase'] ) && 'active' === $this->modules_data['bulk_purchase'] ) {
			$bulk_purchase = new bulkPurchase\Eb_Pro_Bulk_Purchase( $this->loader );
		}

		/**
		 * Custom fields module.
		 */
		if ( isset( $this->modules_data['custom_fields'] ) && 'active' === $this->modules_data['custom_fields'] ) {
			$custom_fields = new customFields\Eb_Pro_Custom_Fields( $this->loader );
		}
	}

	/**
	 * Load the required frontend dependencies for this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_frontend_dependencies() {

	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		if ( 'available' !== $this->check_plugin_licensing() ) {
			return;
		}

		$plugin_admin = new admin\Edwiser_Bridge_Pro_Admin();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 15 );

		$this->loader->add_filter( 'eb_get_settings_pages', $plugin_admin, 'add_templates_tab', 10, 1 );

		// Add admin notice for Typeform survey
		add_action('admin_notices', array($this, 'show_typeform_survey_notice'));
		add_action('wp_ajax_eb_pro_dismiss_typeform_notice', array($this, 'dismiss_typeform_notice'));
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    3.0.0
	 */
	public function run() {
		if ( 'available' === $this->check_plugin_licensing() ) {
			$this->loader->run();
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     3.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     3.0.0
	 * @return    Eb_Pro_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     3.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get plugin license data.
	 *
	 * @since     3.0.0
	 * @return    array    The license data of the plugin.
	 */
	public function check_plugin_licensing() {
		global $eb_pro_plugin_data;

		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eb-pro-get-plugin-data.php';
		$license_data = Eb_Pro_Get_Plugin_Data::get_data_from_db( $eb_pro_plugin_data );

		return $license_data;
	}

	/**
	 * Show Typeform survey notice after 24 hours of update
	 */
	public function show_typeform_survey_notice() {
		// Check if notice has been dismissed
		if (get_option('eb_pro_typeform_notice_dismissed')) {
			return;
		}

		// Get the last update time
		$last_update_time = get_option('edwiser_bridge_pro_last_update_time');
		if (!$last_update_time) {
			$last_update_time = time();
			update_option('edwiser_bridge_pro_last_update_time', $last_update_time);
			return;
		}

		// Check if 24 hours have passed
		if (time() - $last_update_time < 86400) { // 86400 seconds = 24 hours
			return;
		}

		// Show the notice
		?>
		<div class="notice notice-info is-dismissible eb-pro-typeform-notice">
			<div class="eb-pro-typeform-container">
				<div data-tf-live="01JRG72ZZ9S2CN0FQYB0F60BEY"></div>
				<script src="//embed.typeform.com/next/embed.js"></script>
			</div>
		</div>
		<!-- <style>
			.eb-pro-typeform-container {
				margin: 20px 0;
			}
			.eb-pro-typeform-notice {
				padding: 15px;
			}
		</style> -->
		<script>
		jQuery(document).ready(function($) {
			$('.eb-pro-typeform-notice').on('click', '.notice-dismiss', function() {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'eb_pro_dismiss_typeform_notice',
						nonce: '<?php echo wp_create_nonce('eb_pro_dismiss_typeform_notice'); ?>'
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Handle the dismissal of the Typeform notice
	 */
	public function dismiss_typeform_notice() {
		check_ajax_referer('eb_pro_dismiss_typeform_notice', 'nonce');
		update_option('eb_pro_typeform_notice_dismissed', true);
		wp_die();
	}
}

/**
 * Return the main instance of Eb_Pro to prevent the need to use globals.
 */
function edwiser_bridge_pro() {
	return Edwiser_Bridge_Pro::instance();
}

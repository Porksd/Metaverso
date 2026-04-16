<?php

/**
 * The plugin bootstrap file
 *
 * @link    https://edwiser.org
 * @since   1.0.0
 * @package Edwiser Bridge Pro
 *
 * @WordPress-plugin
 * Plugin Name:       Edwiser Bridge Pro
 * Plugin URI:        https://edwiser.org/bridge/
 * Description:       An enhanced e-commerce solution that extends Edwiser Bridge’s functionality, offering features like WooCommerce Integration, Single Sign On, Selective Synchronisation, and Bulk Purchase.
 * Version:           4.2.1
 * Author:            WisdmLabs
 * Author URI:        https://edwiser.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       edwiser-bridge-pro
 * Domain Path:       /languages
 */

namespace app\wisdmlabs\edwiserBridgePro;

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Global variables for the plugin for licensing.
 *
 * @var array
 */
global $eb_pro_plugin_data;
$eb_pro_plugin_data = array(
	'plugin_short_name' => 'Edwiser Bridge Pro - WordPress',
	'plugin_slug'       => 'edwiser_bridge_pro',
	'plugin_version'    => '4.2.1',
	'plugin_name'       => 'Edwiser Bridge Pro - WordPress',
	'store_url'         => 'https://edwiser.org/check-update',
	'author_name'       => 'WisdmLabs',
);

/**
 * Plugin activation function.
 *
 * @since 3.0.0
 */
function activate_edwiser_bridge_pro()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-eb-pro-activator.php';
	includes\Eb_Pro_Activator::activate();
}

register_activation_hook(__FILE__, '\app\wisdmlabs\edwiserBridgePro\activate_edwiser_bridge_pro');

/**
 * Plugin deactivation function.
 *
 * @since 3.0.0
 */
function deactivate_edwiser_bridge_pro()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-eb-pro-deactivator.php';
	includes\Eb_Pro_Deactivator::deactivate();
}

register_deactivation_hook(__FILE__, '\app\wisdmlabs\edwiserBridgePro\deactivate_edwiser_bridge_pro');

/**
 * Old plugin activation notice.
 *
 * @since 3.0.0
 */
function eb_pro_old_plugin_activation_notice()
{
	$eb_plugin_data = \get_plugin_data(WP_PLUGIN_DIR . '/edwiser-bridge/edwiser-bridge.php');
?>
	<div class="eb-admin-pro-popup">
		<div class='eb-admin-pro-popup-content'>
			<svg class="eb-admin-pro-popup-img" width="87" height="60" viewBox="0 0 87 60" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g clip-path="url(#clip0_1912_8988)">
					<path d="M4.78405 58.7893C2.79583 58.7893 1.19629 57.1701 1.19629 55.1575C1.19629 54.5219 1.36073 53.8864 1.67466 53.3416L30.3767 3.02631C31.3633 1.28608 33.5609 0.695919 35.28 1.69466C35.8331 2.01244 36.2816 2.46641 36.5955 3.02631L65.2826 53.3265C66.2692 55.0667 65.6862 57.2911 63.9671 58.2899C63.414 58.6077 62.8011 58.7741 62.1732 58.7741L4.78405 58.7893Z" fill="#FFD21E" />
					<path d="M33.4859 2.42112C34.338 2.40599 35.1303 2.87509 35.5638 3.63171L64.2509 53.947C64.9087 55.1122 64.52 56.5951 63.3689 57.261C63.0102 57.4728 62.5916 57.5787 62.173 57.5787H4.78386C3.46835 57.5787 2.39202 56.4892 2.39202 55.1576C2.39202 54.7339 2.49667 54.3101 2.70595 53.947L31.408 3.63171C31.8266 2.87509 32.6189 2.40599 33.4859 2.42112ZM33.4859 -6.37957e-05C31.7668 -0.0151962 30.1822 0.923013 29.3451 2.42112L0.642991 52.7364C-0.67252 55.0516 0.104827 58.0176 2.39202 59.3492C3.12452 59.7729 3.94672 59.9999 4.78386 59.9999H62.173C64.819 59.9999 66.9567 57.836 66.9567 55.1576C66.9567 54.3101 66.7325 53.4779 66.3139 52.7364L37.6268 2.42112C36.7747 0.923013 35.1901 -0.0151962 33.4859 -6.37957e-05Z" fill="#373737" />
					<path d="M33.4861 50.5724C34.807 50.5724 35.8779 49.4883 35.8779 48.1512C35.8779 46.814 34.807 45.73 33.4861 45.73C32.1651 45.73 31.0942 46.814 31.0942 48.1512C31.0942 49.4883 32.1651 50.5724 33.4861 50.5724Z" fill="#373737" />
					<path d="M33.4861 16.6758C34.8016 16.6758 35.8779 17.7653 35.8779 19.097V38.4664C35.8779 39.7981 34.8016 40.8876 33.4861 40.8876C32.1706 40.8876 31.0942 39.7981 31.0942 38.4664V19.097C31.0942 17.7502 32.1556 16.6758 33.4861 16.6758Z" fill="#373737" />
				</g>
				<defs>
					<clipPath id="clip0_1912_8988">
						<rect width="66.9565" height="60" fill="white" />
					</clipPath>
				</defs>
			</svg>

			<p class="eb-admin-pro-popup-title"><?php echo esc_html__('This Edwiser Bridge Pro Add-on plugin', 'edwiser-bridge-pro') . '<strong> ' . esc_html__('is not compatible', 'edwiser-bridge-pro') . '</strong> ' . esc_html__('with active Edwiser Bridge', 'edwiser-bridge-pro') . '<strong> ' . esc_html__('version', 'edwiser-bridge-pro') . ' ' . esc_html($eb_plugin_data['Version']) . ' </strong>'; ?></p>
			<p class="eb-admin-pro-popup-text"><?php echo esc_html__('Starting from Edwiser Bridge version 3.0.0 you no longer need to activate each add-on separately.', 'edwiser-bridge-pro'); ?></p>
			<p class="eb-admin-pro-popup-text"><?php echo esc_html__('All the Edwiser Bridge Pro Add-ons plugin have been combined into a single plugin Edwiser Bridge Pro version 3.0.0. You can enable/disable Edwiser Bridge Pro features from', 'edwiser-bridge-pro') . '<a href="' . esc_url(admin_url('admin.php?page=eb-settings&tab=pro_features')) . '">' . esc_html__(' here.', 'edwiser-bridge-pro') . '</a>'; ?></p>
			<div class="eb-admin-pro-popup-dismiss">
				<span class="dashicons dashicons-no-alt eb_admin_pro_popup_hide"></span>
			</div>
		</div>
	</div>
<?php
}

/**
 * Dependency check function.
 *
 * @since 3.0.0
 */
function eb_pro_dependency_check()
{

	$eb_old = false;
	if (! is_plugin_active('edwiser-bridge/edwiser-bridge.php')) {
		deactivate_plugins(plugin_basename(__FILE__));
		unset($_GET['activate']); // @codingStandardsIgnoreLine
		add_action(
			'admin_notices',
			function () {
				echo '<div class="error"><p>' . esc_html__('Edwiser Bridge Pro requires Edwiser Bridge plugin to be installed and activated.', 'edwiser-bridge-pro') . '</p></div>';
			}
		);
		$eb_old = true;
	} else {
		$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/edwiser-bridge/edwiser-bridge.php');
		if (version_compare($plugin_data['Version'], '3.0.0', '<')) {
			deactivate_plugins(plugin_basename(__FILE__));
			unset($_GET['activate']); // @codingStandardsIgnoreLine
			add_action(
				'admin_notices',
				function () {
					echo '<div class="error"><p>' . esc_html__('Edwiser Bridge Pro requires Edwiser Bridge plugin version 3.0.0 or higher.', 'edwiser-bridge-pro') . '</p></div>';
				}
			);
			$eb_old = true;
		}
	}

	if (! is_plugin_active('woocommerce/woocommerce.php')) {
		$modules_data = get_option('eb_pro_modules_data');

		if (isset($modules_data['woo_integration']) && 'active' === $modules_data['woo_integration']) {
			$modules_data['woo_integration'] = 'deactive';
			add_action(
				'admin_notices',
				function () {
					echo '<div class="error"><p>' . esc_html__('Edwiser Bridge Pro feature Woocommerce Integration is disabled', 'edwiser-bridge-pro') . '</p></div>';
				}
			);
		}

		if (isset($modules_data['bulk_purchase']) && 'active' === $modules_data['bulk_purchase']) {
			$modules_data['bulk_purchase'] = 'deactive';
			add_action(
				'admin_notices',
				function () {
					echo '<div class="error"><p>' . esc_html__('Edwiser Bridge Pro feature Bulk Purchase is disabled', 'edwiser-bridge-pro') . '</p></div>';
				}
			);
		}
		update_option('eb_pro_modules_data', $modules_data);
	}

	// older plugin activation check.
	if (! $eb_old) {
		$extensions = array(
			'woocommerce-integration/bridge-woocommerce.php',
			'selective-synchronization/selective-synchronization.php',
			'edwiser-bridge-sso/sso.php',
			'edwiser-multiple-users-course-purchase/edwiser-multiple-users-course-purchase.php',
			'edwiser-custom-fields/edwiser-custom-fields.php',
		);
		foreach ($extensions as $extension) {
			if (is_plugin_active($extension)) {
				deactivate_plugins($extension);
				add_action('admin_notices', '\app\wisdmlabs\edwiserBridgePro\eb_pro_old_plugin_activation_notice');
			}
		}
	}
}

add_action('admin_init', '\app\wisdmlabs\edwiserBridgePro\eb_pro_dependency_check');

/**
 * Check plugin update
 *
 * @since 3.0.0
 */
if (! class_exists('Eb_Pro_Plugin_Updater')) {
	require_once plugin_dir_path(__FILE__) . 'includes/class-eb-pro-plugin-updater.php';
}

/**
 * run install function on plugin updated
 */
function eb_pro_plugin_updated()
{
	global $eb_pro_plugin_data;
	$plugin_version = get_option('edwiser_bridge_pro_version');
	if ($plugin_version !== $eb_pro_plugin_data['plugin_version']) {
		require_once plugin_dir_path(__FILE__) . 'includes/class-eb-pro-activator.php';
		includes\Eb_Pro_Activator::update();
		update_option('edwiser_bridge_pro_version', $eb_pro_plugin_data['plugin_version']);

		if (version_compare($plugin_version, '4.2.0', '<')) {
			update_option('eb_pro_show_enroll_students_update_modal', true);
		}
	}
}

add_action('plugins_loaded', '\app\wisdmlabs\edwiserBridgePro\eb_pro_plugin_updated');


/**
 * Initiate plugin updater class.
 * This class checks for plugin updates.
 */
new includes\Eb_Pro_Plugin_Updater(
	$eb_pro_plugin_data['store_url'],
	__FILE__,
	array(
		'version'   => $eb_pro_plugin_data['plugin_version'],
		'license'   => trim(get_option('edd_edwiser_bridge_pro_license_key')),
		'item_name' => $eb_pro_plugin_data['plugin_name'],
		'author'    => $eb_pro_plugin_data['author_name'],
	)
);

/**
 * load text domain
 */
function eb_pro_load_textdomain()
{
	load_plugin_textdomain('edwiser-bridge-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('init', '\app\wisdmlabs\edwiserBridgePro\eb_pro_load_textdomain');
/**
 * Plugin core class.
 *
 * @since 3.0.0
 */
require plugin_dir_path(__FILE__) . 'includes/class-edwiser-bridge-pro.php';

/**
 * Begins execution of the plugin.
 *
 * @since 3.0.0
 */
function run_edwiser_bridge_pro()
{
	$plugin = new includes\Edwiser_Bridge_Pro();
	$plugin->run();
}

add_action('init', function () {
	// Check if we need to include the plugin.php file
	if (!function_exists('get_plugin_data')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_data = \get_plugin_data(WP_PLUGIN_DIR . '/edwiser-bridge/edwiser-bridge.php');

	if (version_compare($plugin_data['Version'], '3.0.0', '<')) {
		add_action('admin_init', function () {
			deactivate_plugins(plugin_basename(__FILE__));
			if (isset($_GET['activate'])) {
				unset($_GET['activate']);
			}
		});
	} else {
		// Only run the Pro version if main plugin meets requirements
		run_edwiser_bridge_pro();
	}
}, 1);

/**
 * Woocommerce HPOS compatibility
 */
add_action('before_woocommerce_init', function () {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});

/**
 * Register gutenberg blocks.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-eb-pro-blocks.php';

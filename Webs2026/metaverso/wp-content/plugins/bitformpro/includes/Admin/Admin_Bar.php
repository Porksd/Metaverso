<?php

namespace BitCode\BitFormPro\Admin;

use BitCode\BitFormPro\Core\Update\API;
use BitCode\BitFormPro\Core\Update\Updater;

/**
 * The admin menu and page handler class
 */

class Admin_Bar
{
    public function register()
    {
        add_action('admin_menu', [$this, 'AdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'AdminAssets']);
        add_filter('bitforms_localized_script', [$this, 'filterAdminScriptVar'], 10, 1);
    }

    /**
     * Register the admin menu
     *
     * @return void
     */
    public function AdminMenu()
    {
        $capability = apply_filters('bitforms_form_access_capability', 'manage_options');
        if (current_user_can($capability)) {
        }
    }

    /**
     * Filter variables for bitform admin script
     *
     * @param array $previousValue Current values
     *
     * @return $previousValue Filtered Values
     */
    public function filterAdminScriptVar(array $previousValue)
    {
        $integrateData = get_option('bitformpro_integrate_key_data');
        if (isset($previousValue['isPro']) && !empty($integrateData) && is_array($integrateData) && $integrateData['status'] === 'success') {
            $previousValue['isPro'] = true;
            $previousValue['proInfo'] = [
                'installedVersion' => BITFORMPRO_VERSION,
                'latestVersion' => is_wp_error(API::getUpdatedInfo()) ? '' : API::getUpdatedInfo()->version,
            ];
        }
        return $previousValue;
    }

    /**
     * Load the asset libraries
     *
     * @return void
     */
    public function AdminAssets($current_screen)
    {
        if (!strpos($current_screen, 'bitform')) {
            return;
        }

        if (!defined('BITAPPS_DEV') || (defined('BITAPPS_DEV') && !BITAPPS_DEV)) {
            wp_dequeue_script('index-BITFORM-MODULE');
            wp_dequeue_style('bf-css');

            $build_hash = file_get_contents(BITFORMS_PLUGIN_DIR_PATH . '/build-hash.txt');
            wp_enqueue_script('index-BITFORM-MODULE', BITFORMS_ASSET_URI . "/main-{$build_hash}.js", [], null);
            wp_enqueue_style('bf-css', BITFORMS_ASSET_URI . "/main-{$build_hash}.css");
        }
    }

    /**
     * Bitforms  apps-root id provider
     * @return void
     */
    public function RootPage()
    {
        require_once BITFORMPRO_PLUGIN_DIR_PATH . '/views/view-root.php';
    }
}
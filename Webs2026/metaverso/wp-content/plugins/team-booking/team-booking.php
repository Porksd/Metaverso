<?php
/*
Plugin Name:        TheBooking
Description:        A booking plugin for WordPress.
Version:            3.0.14
Requires PHP:       7.3
Requires at least:  5.3
License:            GPL v2 or later
Author:             VonStroheim
Author URI:         https://stroheimdesign.com
Text Domain:        team-booking
Domain Path:        /languages
*/

class theBooking
{

    /**
     * Class constructor
     */
    public function __construct()
    {
        require('plugin_options.php');
    }

    /**
     * Initialize hooks.
     */
    public function init(): void
    {
        /**
         * Load modules
         */
        \VSHM\Tools::subscribe_classes_in_dir(
            __DIR__ . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR,
            '\\VSHM\\Modules\\'
        );
        \VSHM\Update::maybe_update();
    }
}

/**
 * @NEXT drop this when WP 6.2 becomes the minimum WP version
 */
if (!defined('REQUESTS_SILENCE_PSR0_DEPRECATIONS')) {
    define('REQUESTS_SILENCE_PSR0_DEPRECATIONS', TRUE);
}

// Set the right include path (temporary)...
$prev_include_path = set_include_path(__DIR__);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/Autoloader.php';

\VSHM\Autoloader::VSHM(__DIR__);
\VSHM\Autoloader::run();

register_activation_hook(__FILE__, [\VSHM\Install::class, 'plugin_install']);
register_deactivation_hook(__FILE__, [\VSHM\Install::class, 'plugin_deactivate']);

/**
 * Load Settings Classes
 */
\VSHM\Tools::subscribe_classes_in_dir_recursive(
    __DIR__ . DIRECTORY_SEPARATOR . 'Settings',
    '\\VSHM\\Settings\\',
    'subscribe',
    [
        'SettingBase.php',
        'CustomerSettingBase.php',
        'LocationSettingBase.php',
        'PromotionSettingBase.php',
        'ReservationSettingBase.php',
        'PropertyBase.php',
        'SettingCustomizer.php',
        'ServiceSettingBase.php',
        'ServicePersonalSettingBase.php',
        'ProviderSettingBase.php',
    ]
);

/**
 * Load Data Providers
 */
\VSHM\Tools::subscribe_classes_in_dir(
    __DIR__ . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR,
    '\\VSHM\\Providers\\',
    'subscribe',
    [
        'Provider.php',
        'ProviderBase.php'
    ]
);

/**
 * Load Payment Gateways Classes
 */
\VSHM\Tools::subscribe_classes_in_dir(
    __DIR__ . DIRECTORY_SEPARATOR . 'Plugin' . DIRECTORY_SEPARATOR . 'PaymentGateways' . DIRECTORY_SEPARATOR,
    '\\VSHM\\Plugin\\PaymentGateways\\',
    'subscribe',
    [
        'PaymentGateway.php'
    ]
);

function vshm(): \VSHM\VSHM
{
    $plugin_data = get_file_data(__FILE__, [
        'Version'    => 'Version',
        'TextDomain' => 'Text Domain',
        'Name'       => 'Plugin Name',
    ], 'plugin');

    return \VSHM\VSHM::instance('tbkBackend', [
        'PLUGIN_FILE'    => __FILE__,
        'PLUGIN_DIR'     => __DIR__,
        'PLUGIN_VERSION' => $plugin_data['Version'],
        'PLUGIN_PATH'    => plugin_dir_path(__FILE__),
        'PLUGIN_URL'     => plugin_dir_url(__FILE__),
        'PLUGIN_SLUG'    => $plugin_data['TextDomain'],
        'PLUGIN_NAME'    => $plugin_data['Name'],
    ]);
}

/**
 * Hooking the main instance call only when
 * the plugins are loaded to let other plugins
 * to use TheBooking actions/filters in time.
 */
add_action('plugins_loaded', function () {
    try {
        $plugin = new theBooking();
        $plugin->init();
    } catch (\Exception $e) {
        add_action('admin_notices', static function () use ($e) {
            $error = $e->getMessage();
            echo "<div class='notice notice-error'><p><strong>TheBooking error: </strong> $error - The plugin is not properly loaded.</p></div>";
        });
    }

});

// ...restore previous include_path
set_include_path($prev_include_path);
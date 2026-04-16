<?php
if (!defined('ABSPATH')) {
    exit;
}
$scheme = parse_url(home_url())['scheme'];
define('BITFORMPRO_PLUGIN_BASENAME', plugin_basename(BITFORMPRO_PLUGIN_MAIN_FILE));
define('BITFORMPRO_PLUGIN_DIR_PATH', plugin_dir_path(BITFORMPRO_PLUGIN_MAIN_FILE));
define('BITFORMPRO_ROOT_URI', set_url_scheme(plugins_url('', BITFORMPRO_PLUGIN_MAIN_FILE), $scheme));
define('BITFORMPRO_ASSET_URI', BITFORMPRO_ROOT_URI . '/assets');
// Autoload vendor files.
if (file_exists(BITFORMPRO_PLUGIN_DIR_PATH . 'vendor/autoload.php')) {
    require_once BITFORMPRO_PLUGIN_DIR_PATH . 'vendor/autoload.php';
}

// Initialize the plugin.
BitCode\BitFormPro\Plugin::load(BITFORMPRO_PLUGIN_MAIN_FILE);

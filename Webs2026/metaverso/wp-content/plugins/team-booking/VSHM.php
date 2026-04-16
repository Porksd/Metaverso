<?php

namespace VSHM;

use VSHM\Plugin\BusMiddleware;

defined('ABSPATH') || exit;

/**
 * Class VSHM
 */
final class VSHM extends Single
{
    /**
     * Dispatches commands
     *
     * @var \VSHF\Bus\Bus
     */
    public $bus;

    /**
     * Settings
     *
     * @var \VSHF\Config\Config
     */
    public $settings;

    /**
     * Plugin-specific variables
     *
     * @var []
     */
    public $plugin = [];

    /**
     * Returns the WP option name of the settings array
     *
     * @return string
     */
    public function get_settings_name(): string
    {
        return $this->plugin['SLUG'] . '-options';
    }

    /**
     * @param string $handle
     */
    public function add_frontend_env_vars(string $handle): void
    {
        wp_add_inline_script($handle, 'const VSHM_REST_ROUTE_ROOT="' . REST_Controller::get_root_rest_url() . '"', 'before');
        wp_add_inline_script($handle, 'const VSHM_PLUGIN_VARS=' . json_encode($this->plugin), 'before');
        wp_add_inline_script($handle, 'const VSHM_CURRENT_USER=' . get_current_user_id(), 'before');
        wp_add_inline_script($handle, 'const VSHM_CURRENT_USER_EMAIL=' . json_encode(wp_get_current_user()->user_email), 'before');
        wp_add_inline_script($handle, 'const VSHM_FRONTEND_SERVICES=' . json_encode(apply_filters('vshm_frontend_services', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_FRONTEND_PROVIDERS=' . json_encode(apply_filters('vshm_frontend_providers', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_SETTINGS=' . json_encode(apply_filters('vshm_frontend_settings', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_NONCE_ENDPOINT="' . admin_url('admin-ajax.php?action=rest-nonce') . '"', 'before');
        wp_add_inline_script($handle, 'const VSHM_I18N=' . json_encode([
                'weekDaysLabels'      => Tools::i18n_weekdays_labels(),
                'shortWeekDaysLabels' => Tools::i18n_weekdays_labels('D'),
            ]), 'before');
        do_action('tbk_frontend_env_vars', $handle);
    }

    /**
     * @param string $handle
     */
    public function add_backend_env_vars(string $handle): void
    {
        wp_add_inline_script(
            $handle,
            'const VSHM_BACKEND_SETTINGS=' . json_encode(apply_filters('vshm_backend_settings', $this->settings->getAllByContext())),
            'before'
        );
        wp_add_inline_script($handle, 'const VSHM_BACKEND_MENU_ITEMS=' . json_encode(apply_filters('vshm_backend_menu_items', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_BACKEND_SERVICES=' . json_encode(apply_filters('vshm_backend_services', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_BACKEND_PROVIDERS=' . json_encode(apply_filters('vshm_backend_providers', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_BACKEND_CUSTOMERS=' . json_encode(apply_filters('vshm_backend_customers', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_BACKEND_PROMOTIONS=' . json_encode(apply_filters('vshm_backend_promotions', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_MIGRATION_FROM_V2=' . json_encode(Update::is_awaiting_migration_from_v2()), 'before');
        wp_add_inline_script($handle, 'const VSHM_BACKEND_WP_USERS=' . json_encode(apply_filters('vshm_backend_wp_users',
                array_map(
                    static function ($user) {
                        $user->avatar = get_avatar_url($user->ID);
                        $user->ID     = (int)$user->ID;
                        $user->can    = Functions::user_can_admin($user->ID) ? 'admin' : (Functions::user_is_provider($user->ID) ? 'provide' : NULL);

                        return $user;
                    },
                    get_users([
                        'fields' => [
                            'ID', 'display_name', 'user_email', 'user_login'
                        ]
                    ])
                )
            )), 'before');
        wp_add_inline_script($handle, 'const VSHM_REST_ROUTE_ROOT="' . REST_Controller::get_root_rest_url() . '"', 'before');
        wp_add_inline_script($handle, 'const VSHM_NONCE_ENDPOINT="' . admin_url('admin-ajax.php?action=rest-nonce') . '"', 'before');
        wp_add_inline_script($handle, 'const VSHM_PLUGIN_VARS=' . json_encode($this->plugin), 'before');
        wp_add_inline_script($handle, 'const VSHM_CURRENT_USER=' . get_current_user_id(), 'before');
        wp_add_inline_script($handle, 'const VSHM_CURRENT_USER_PREFS=' . json_encode(apply_filters('vshm_backend_user_prefs', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_I18N=' . json_encode([
                'weekDaysLabels'      => Tools::i18n_weekdays_labels(),
                'shortWeekDaysLabels' => Tools::i18n_weekdays_labels('D'),
            ]), 'before');

        do_action('tbk_add_backend_env_vars', $handle);
    }

    /**
     * Registers WP REST API routes.
     */
    public function register_routes(): void
    {
        /**
         * Built-in routes
         */
        Routes\SaveSettingsRoute::register();

        /**
         * Hook for custom routes
         */
        do_action('vshm_registering_routes', $this->plugin['SLUG']);
    }

    /**
     * @param array $config
     */
    protected function __construct(array $config)
    {
        $this->plugin = [
            'FILE'    => $config['PLUGIN_FILE'],
            'DIR'     => $config['PLUGIN_DIR'],
            'VERSION' => $config['PLUGIN_VERSION'],
            'PATH'    => $config['PLUGIN_PATH'],
            'URL'     => $config['PLUGIN_URL'],
            'SLUG'    => $config['PLUGIN_SLUG'],
            'NAME'    => $config['PLUGIN_NAME'],
            'LANG'    => str_replace('_', '-', get_locale())
        ];

        $this->bus = new \VSHF\Bus\Bus();
        $this->bus->addMiddleware(BusMiddleware::class, 99);

        $retrieved = get_option($this->get_settings_name(), []);

        $styleContext = $retrieved['style'] ?? [];
        unset($retrieved['style']);

        $this->settings = new \VSHF\Config\Config($retrieved);
        $this->settings->hydrate($styleContext, 'style');
    }
}
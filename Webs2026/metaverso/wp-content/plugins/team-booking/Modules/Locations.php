<?php

namespace VSHM\Modules;

use VSHM\Bus\CreateLocation;
use VSHM\Bus\DeleteLocation;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Settings\Location\Address;
use VSHM\Settings\Location\LocationSettingBase;
use VSHM\Settings\Location\Name;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Locations
 *
 * @author VonStroheim
 */
class Locations
{
    /**
     * @var string
     */
    public static $route_path = '/locations/';

    public static function bootstrap(): void
    {
        if (\VSHM\Functions::current_user_can_admin()) {
            add_filter('vshm_backend_menu_items', [self::class, 'add_main_menu_item']);
        }
        add_action('tbk_add_backend_env_vars', [self::class, 'add_env_vars']);
        add_filter('vshm_backend_locations', [self::class, 'add_backend_locations']);

        REST_Controller::register_routes([
            self::$route_path . 'remove/'       => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'removeLocation'],
                'args'                => [
                    'data' => [
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$route_path . 'add/'          => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'addLocation'],
                'args'                => [
                    'data' => [
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$route_path . 'remove/multi/' => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'removeLocations'],
                'args'                => [
                    'data' => [
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$route_path . 'save/'         => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'saveLocation'],
                'args'                => [
                    'data' => [
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
        ]);
    }

    public static function saveLocation(\WP_REST_Request $request): \WP_REST_Response
    {
        $settings = $request->get_param('data');
        if (is_array($settings)) {
            foreach ($settings as $setting) {
                try {
                    vshm()->settings->saveProperty(
                        $setting['key'],
                        $setting['value'],
                        LocationSettingBase::CONTEXT,
                        $setting['location_id']
                    );
                } catch (\UnexpectedValueException $e) {
                    // setting key not found, or invalid value
                }
            }
        }

        return REST_Controller::get_ok_response('location_save', [
            'locations' => apply_filters('vshm_backend_locations', []),
            'message'   => __('Settings have been saved!', 'team-booking')
        ]);
    }

    public static function addLocation(\WP_REST_Request $request): \WP_REST_Response
    {
        $item = $request->get_param('data');

        // TODO: validation

        $locationId = apply_filters('tbk_location_token_gen', Tools::generate_token('alnum', 32, 'l_'));
        $location   = new CreateLocation($item['name'], $locationId, $item['address'], 1, NULL, NULL, 0);
        vshm()->bus->dispatch($location);

        return REST_Controller::get_ok_response('location_add', [
            'locations' => apply_filters('vshm_backend_locations', []),
            'message'   => __('Location added!', 'team-booking')
        ]);
    }

    public static function removeLocation(\WP_REST_Request $request): \WP_REST_Response
    {
        $item = $request->get_param('data');

        vshm()->bus->dispatch(new DeleteLocation($item['id']));

        // TODO: validation

        return REST_Controller::get_ok_response('location_remove', [
            'locations' => apply_filters('vshm_backend_locations', []),
            'message'   => __('Location removed!', 'team-booking')
        ]);
    }

    public static function removeLocations(\WP_REST_Request $request): \WP_REST_Response
    {
        $item = $request->get_param('data');

        if (isset($item['ids']) && is_array($item['ids'])) {
            foreach ($item['ids'] as $id) {
                vshm()->bus->dispatch(new DeleteLocation($id));
            }
        }

        return REST_Controller::get_ok_response('locations_remove', [
            'locations' => apply_filters('vshm_backend_locations', []),
            'message'   => __('Locations removed!', 'team-booking')
        ]);
    }

    public static function add_backend_locations($locations): array
    {
        $retrievedLocations = \VSHM\Providers\Locations::provide();

        return $locations + $retrievedLocations;
    }

    public static function add_env_vars($handle): void
    {
        wp_add_inline_script($handle, 'const VSHM_BACKEND_LOCATIONS=' . json_encode(apply_filters('vshm_backend_locations', [])), 'before');
    }

    public static function add_main_menu_item($structure)
    {
        $page           = \VSHM\UI\Admin\Page::full_width();
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $table = \VSHM\UI\Admin\Plugin\DataTableLocations::get('');
        $table->setEndpoint(static::$route_path);
        $table->addColumn(__('Name', 'team-booking'), 'name', 'name');
        $table->addColumn(__('Address', 'team-booking'), 'address', 'address');
        $table->addColumn(__('Actions', 'team-booking'), 'actions');

        $settingsItems = [
            [
                'id'    => 'general',
                'label' => __('General', 'team-booking'),
                'items' => [
                    Name::getBackendElement()->get_structure(),
                    Address::getBackendElement()->get_structure()
                ]
            ],
        ];

        $table->addSettingItems($settingsItems);

        $settings_panel->addItem($table);

        $page->setContent($settings_panel);

        $item        = \VSHM\UI\Admin\MenuItem::tab(__('Locations', 'team-booking'), $page, \VSHM\UI\Admin\Icons::LOCATION, 'locations');
        $structure[] = $item->get_structure();

        return $structure;
    }
}
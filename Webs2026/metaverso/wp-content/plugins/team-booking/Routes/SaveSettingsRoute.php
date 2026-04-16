<?php

namespace VSHM\Routes;

use VSHM\Bus\SaveSettings;
use VSHM\Bus\SaveUserPrefs;
use VSHM\DB;
use VSHM\Functions;
use VSHM\Modules\EventLogger;
use VSHM\Providers\ApiTokens;
use VSHM\Providers\Customers;
use VSHM\Providers\Files;
use VSHM\Providers\FormEntries;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;
use VSHM\Providers\Locations;
use VSHM\Providers\Promotions;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Update;

defined('ABSPATH') || exit;

/**
 * Class SaveSettingsRoute
 *
 * @package VSHM\Routes
 */
final class SaveSettingsRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/';

    public static function getSaveSettingsPath(): string
    {
        return self::$path . 'save/settings/';
    }

    public static function register(): void
    {
        REST_Controller::register_routes([
            self::$path . 'save/settings/'             => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    $command = new SaveSettings($request->get_param('settings'));
                    vshm()->bus->dispatch($command);

                    return REST_Controller::get_ok_response(
                        self::getSaveSettingsPath(),
                        ['message' => __('Settings have been saved!', 'team-booking')]
                    );
                },
                'args'                => [
                    'settings' => [
                        'required' => TRUE
                    ]
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$path . 'reset/defaults/'            => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    $services  = Services::provide();
                    $providers = ServiceProviders::provide();
                    $to_insert = [];
                    foreach ($services as $service) {
                        if ($service->class === 'unscheduled') {
                            continue;
                        }
                        $defaults = apply_filters('vshm_default_service_personal_settings', [], vshm()->plugin['SLUG'], $service);
                        foreach ($providers as $provider) {
                            foreach ($defaults as $key => $default) {
                                $to_insert[] = [
                                    'service_id'  => $service->id,
                                    'provider_id' => $provider['id'],
                                    'key'         => $key,
                                    'value'       => $default,
                                ];
                            }
                        }
                    }
                    ServiceProviderCustomData::storeMany($to_insert);

                    return REST_Controller::get_ok_response(
                        self::$path . 'reset/defaults/',
                        ['message' => __('Defaults restored!', 'team-booking')]
                    );
                },
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$path . 'save/userprefs/'            => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    $prefs = $request->get_param('prefs');

                    foreach ($prefs as $id => $value) {
                        vshm()->bus->dispatch(new SaveUserPrefs($id, $value));
                    }

                    return REST_Controller::get_ok_response(self::$path . 'save/userprefs/');
                },
                'args'                => [
                    'prefs' => [
                        'required' => TRUE
                    ]
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider(TRUE);
                }
            ],
            self::$path . 'import/settings/'           => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    $settings = $request->get_param('settings');
                    $version  = $request->get_param('version');

                    $command = new SaveSettings(apply_filters('vshm_import_settings', [], $settings, $version));
                    vshm()->bus->dispatch($command);

                    return REST_Controller::get_ok_response(
                        self::$path . 'import/settings/',
                        ['message' => __('Settings imported, reloading page.', 'team-booking')]
                    );
                },
                'args'                => [
                    'settings' => [
                        'required' => TRUE
                    ],
                    'version'  => [
                        'required' => TRUE
                    ]
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$path . 'export/settings/'           => [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    return REST_Controller::get_ok_response(self::$path . 'export/settings/', [
                        'settings' => apply_filters('vshm_export_settings', []),
                        'version'  => vshm()->plugin['VERSION']
                    ]);
                },
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$path . 'migrate/remove/v2data/'     => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    Update::remove_v2_data();

                    return REST_Controller::get_ok_response(
                        self::$path . 'migrate/remove/v2data/',
                        ['message' => __('Data removed!', 'team-booking')]
                    );
                },
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$path . 'migrate/set/migratedfrom2/' => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    Update::set_migrated_from_v2(TRUE);

                    return REST_Controller::get_ok_response(
                        self::$path . 'migrate/set/migratedfrom2/'
                    );
                },
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$path . 'migrate/fromv2/'            => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    DB::drop_table(Services::TABLE_NAME);
                    DB::drop_table(ServicesData::TABLE_NAME);
                    DB::drop_table(Reservations::TABLE_NAME);
                    DB::drop_table(ReservationsData::TABLE_NAME);
                    DB::drop_table(Promotions::TABLE_NAME);
                    DB::drop_table(Customers::TABLE_NAME);
                    DB::drop_table(ServiceProviderCustomData::TABLE_NAME);
                    DB::drop_table(Locations::TABLE_NAME);
                    DB::drop_table(Forms::TABLE_NAME);
                    DB::drop_table(Files::TABLE_NAME);
                    DB::drop_table(FormFields::TABLE_NAME);
                    DB::drop_table(FormEntries::TABLE_NAME);
                    DB::drop_table(ApiTokens::TABLE_NAME);
                    DB::drop_table(EventLogger::TABLE_NAME);

                    Services::maybe_create_table();
                    ServicesData::maybe_create_table();
                    Reservations::maybe_create_table();
                    ReservationsData::maybe_create_table();
                    Promotions::maybe_create_table();
                    Customers::maybe_create_table();
                    ServiceProviderCustomData::maybe_create_table();
                    Locations::maybe_create_table();
                    Forms::maybe_create_table();
                    Files::maybe_create_table();
                    FormFields::maybe_create_table();
                    FormEntries::maybe_create_table();
                    ApiTokens::maybe_create_table();
                    EventLogger::maybe_create_table();

                    Update::to_3();

                    return REST_Controller::get_ok_response(
                        self::$path . 'migrate/fromv2/',
                        ['message' => __('Data migrated!', 'team-booking')]
                    );
                },
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ]
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
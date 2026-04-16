<?php

namespace VSHM\Routes\Services;

use VSHM\Bus\UpdateOrCreateServiceProperty;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class DataSave
 *
 * @package VSHM\Routes
 */
class DataSave implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'data/save/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $settings = $request->get_param('data');
                if (is_array($settings)) {
                    foreach ($settings as $setting) {
                        vshm()->bus->dispatch(new UpdateOrCreateServiceProperty($setting['service_id'], $setting['key'], $setting['value']));
                    }
                }

                return REST_Controller::get_ok_response(
                    self::getPath(),
                    ['message' => __('Settings have been saved!', 'team-booking')]
                );

            },
            'args'                => [
                'data' => [
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
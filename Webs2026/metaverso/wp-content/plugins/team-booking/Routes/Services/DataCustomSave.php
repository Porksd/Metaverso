<?php

namespace VSHM\Routes\Services;

use VSHM\Bus\UpdateOrCreateServicePersonalProperty;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class DataCustomSave
 *
 * @package VSHM\Routes
 */
class DataCustomSave implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'data/custom/save/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $settings = $request->get_param('data');
                if (is_array($settings)) {
                    $providerId = get_current_user_id();
                    if (!$providerId) {
                        return REST_Controller::get_error_response(self::getPath(), ['message' => __('Provider ID not found.', 'team-booking')], 404);
                    }
                    foreach ($settings as $setting) {
                        vshm()->bus->dispatch(new UpdateOrCreateServicePersonalProperty($setting['service_id'], $providerId, $setting['key'], $setting['value']));
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
                return Functions::current_user_is_provider(TRUE);
            }
        ];
    }
}
<?php

namespace VSHM\Routes\Services;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class DataGet
 *
 * @package VSHM\Routes
 */
class DataGet implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'data/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $service_id = $request->get_param('service_id');

                $data = Functions::organize_service_data(\VSHM\Providers\ServicesData::provideBy(['service_id' => $service_id]));

                $return = [];

                foreach ($data[ $service_id ] as $key => $value) {
                    $return[] = [
                        'service_id' => $service_id,
                        'value'      => $value,
                        'key'        => $key
                    ];
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'data' => $return
                ]);
            },
            'args'                => [
                'service_id' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_is_provider();
            }
        ];
    }
}
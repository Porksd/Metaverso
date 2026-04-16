<?php

namespace VSHM\Routes\API\Services;

use VSHM\Bus\UseApiToken;
use VSHM\DB;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Get
 *
 * @package VSHM\Routes\API\Services
 */
class Get implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'services/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $services = Services::provide();

                if (filter_var($request->get_param('full'), FILTER_VALIDATE_BOOLEAN)) {
                    foreach ($services as $key => $service) {
                        $serviceData = ServicesData::provideBy(['service_id' => $service->id]);
                        foreach ($serviceData as $serviceDatum) {
                            $service->data[ $serviceDatum['key'] ] = $serviceDatum['value'];
                        }
                        $services[ $key ] = (array)$service;
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), ['response' => $services]);
            },
            'args'                => [
                'full' => [
                    'type' => 'boolean'
                ]
            ],
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_read_request($request);
            }
        ];
    }
}
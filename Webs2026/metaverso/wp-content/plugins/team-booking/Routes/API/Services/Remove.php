<?php

namespace VSHM\Routes\API\Services;

use VSHM\Bus\DeleteService;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\Reservations;
use VSHM\Providers\Services;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Remove
 *
 * @package VSHM\Routes\API\Services
 */
class Remove implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'services/remove/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $service = Services::provideBy(['id' => $request->get_param('id')], TRUE);

                if (!$service) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service not found.'], 404);
                }

                $reservations = Reservations::provideBy(['serviceId' => $service->id]);
                if (!empty($reservations)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => "Service can't be removed. There are reservations for this service."], 403);
                }

                vshm()->bus->dispatch(new DeleteService($service->id), vshm()->bus::AGENT_APP, $token['token']);

                return REST_Controller::get_ok_response(self::getPath());
            },
            'args'                => [
                'id' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ],
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
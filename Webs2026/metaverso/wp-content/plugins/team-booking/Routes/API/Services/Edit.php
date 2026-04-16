<?php

namespace VSHM\Routes\API\Services;

use VSHM\Bus\UpdateOrCreateServiceProperty;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\Services;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Edit
 *
 * @package VSHM\Routes\API\Services
 */
class Edit implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'services/edit/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $record = json_decode($request->get_body(), TRUE);

                if (!is_array($record)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Body is not a valid JSON']);
                }

                if (!isset($record['id'])) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'No service ID found in body data']);
                }

                $service = Services::provideBy(['id' => $record['id']], TRUE);

                if (!$service) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service not found.'], 404);
                }

                foreach ($record as $key => $property) {
                    if ($key === 'id') {
                        continue;
                    }
                    if ($key === 'data' && is_array($property)) {
                        foreach ($property as $propKey => $propValue) {
                            vshm()->bus->dispatch(new UpdateOrCreateServiceProperty($service->id, (string)$propKey, $propValue), vshm()->bus::AGENT_APP, $token['token']);
                        }
                    } else {
                        vshm()->bus->dispatch(new UpdateOrCreateServiceProperty($service->id, (string)$key, $property), vshm()->bus::AGENT_APP, $token['token']);
                    }
                }


                return REST_Controller::get_ok_response(self::getPath());
            },
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
<?php

namespace VSHM\Routes\API\Providers;

use VSHM\Bus\UpdateProviderProperty;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\ServiceProviders;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Provider\AllowedServices;
use VSHM\Settings\Provider\RestrictServices;

defined('ABSPATH') || exit;

/**
 * Class Edit
 *
 * @package VSHM\Routes\API\Providers
 */
class Edit implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'providers/edit/';
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
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Data is incomplete']);
                }

                $provider = ServiceProviders::provideBy(['id' => $record['id']], TRUE);

                if (!$provider) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service provider not found'], 404);
                }

                foreach ($record as $key => $property) {
                    if ($key === 'id' || !in_array($key, [AllowedServices::ID, RestrictServices::ID], TRUE)) {
                        continue;
                    }
                    vshm()->bus->dispatch(new UpdateProviderProperty($provider['id'], (string)$key, $property), vshm()->bus::AGENT_APP, $token['token']);
                }

                return REST_Controller::get_ok_response(self::getPath());
            },
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
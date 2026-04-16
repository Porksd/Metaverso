<?php

namespace VSHM\Routes\API\Providers;

use VSHM\Bus\UpdateOrCreateServicePersonalProperty;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Provider\AllowedServices;
use VSHM\Settings\Provider\RestrictServices;

defined('ABSPATH') || exit;

/**
 * Class EditServiceSettings
 *
 * @package VSHM\Routes\API\Providers
 */
class EditServiceSettings implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'providers/edit/service/';
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

                $provider = ServiceProviders::provideBy(['id' => $request->get_param('providerId')], TRUE);
                if (!$provider) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service provider not found'], 404);
                }
                $service = Services::provideBy(['id' => $request->get_param('serviceId')], TRUE);
                if (!$service) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service not found'], 404);
                }

                foreach ($record as $key => $property) {
                    if ($key === 'id' || !in_array($key, [AllowedServices::ID, RestrictServices::ID], TRUE)) {
                        continue;
                    }
                    vshm()->bus->dispatch(new UpdateOrCreateServicePersonalProperty(
                        $service->id,
                        $provider['id'],
                        $key,
                        $property
                    ), vshm()->bus::AGENT_APP, $token['token']);
                }

                return REST_Controller::get_ok_response(self::getPath());
            },
            'args'                => [
                'serviceId'  => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'providerId' => [
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
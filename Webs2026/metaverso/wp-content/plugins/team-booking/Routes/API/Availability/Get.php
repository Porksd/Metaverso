<?php

namespace VSHM\Routes\API\Availability;

use VSHM\Bus\UseApiToken;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Get
 *
 * @package VSHM\Routes\API\Availability
 */
class Get implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'availability/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $reservations = apply_filters(
                    'tbk_reservations_to_be_computed_by_slots',
                    \VSHM\Providers\Reservations::provideBetween(
                        (int)$request->get_param('min_timestamp'),
                        (int)$request->get_param('max_timestamp'),
                        [
                            'status' => [
                                'operator' => '!=',
                                'value'    => 'cancelled'
                            ]
                        ]
                    ),
                    $request
                );

                #add_filter('tbk_availability_request_is_frontend', '__return_true');

                $services  = $request->get_param('services') ? explode(',', (string)$request->get_param('services')) : NULL;
                $providers = $request->get_param('providers') ? explode(',', (string)$request->get_param('providers')) : NULL;

                $slots = apply_filters(
                    'tbk_availability',
                    [],
                    (int)$request->get_param('min_timestamp'),
                    (int)$request->get_param('max_timestamp'),
                    $reservations,
                    $services,
                    $providers
                );

                return REST_Controller::get_ok_response(self::getPath(), ['response' => $slots]);
            },
            'args'                => [
                'min_timestamp' => [
                    'type'     => 'int',
                    'required' => TRUE
                ],
                'max_timestamp' => [
                    'type'     => 'int',
                    'required' => TRUE
                ],
                'services'      => [
                    'type' => 'string'
                ],
                'providers'     => [
                    'type' => 'string'
                ]
            ],
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_read_request($request);
            }
        ];
    }
}
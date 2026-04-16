<?php

namespace VSHM\Routes\API\Reservations;

use VSHM\Bus\UseApiToken;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Get
 *
 * @package VSHM\Routes\API\Reservations
 */
class Get implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'reservations/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $services  = $request->get_param('services') ? explode(',', (string)$request->get_param('services')) : NULL;
                $providers = $request->get_param('providers') ? explode(',', (string)$request->get_param('providers')) : NULL;
                $customers = $request->get_param('customers') ? explode(',', (string)$request->get_param('customers')) : NULL;

                $conditions = [];
                if (NULL !== $services) {
                    $conditions['serviceId'] = [
                        'operator' => 'IN',
                        'value'    => $services
                    ];
                }
                if (NULL !== $providers) {
                    $conditions['provider_id'] = [
                        'operator' => 'IN',
                        'value'    => $providers
                    ];
                }
                if (NULL !== $customers) {
                    $conditions['customerId'] = [
                        'operator' => 'IN',
                        'value'    => $customers
                    ];
                }
                if ($request->get_param('status')) {
                    $conditions['status'] = (string)$request->get_param('status');
                }
                if (!filter_var($request->get_param('past'), FILTER_VALIDATE_BOOLEAN)) {
                    $conditions['start'] = [
                        'operator' => '>=',
                        'value'    => time()
                    ];
                }

                if (empty($conditions)) {
                    $reservations = Reservations::provide();
                } else {
                    $reservations = Reservations::provideBy($conditions);
                }

                $parsed = [];

                foreach ($reservations as $key => $reservation) {
                    if (apply_filters('tbk_is_reservation_expired', FALSE, $reservation)) {
                        continue;
                    }

                    $parsed[ $key ] = (array)$reservation;

                    if (filter_var($request->get_param('full'), FILTER_VALIDATE_BOOLEAN)) {

                        $data = ReservationsData::provideBy(['reservation_id' => $reservation->id]);

                        foreach ($data as $datum) {
                            $parsed[ $key ][ $datum['key'] ] = $datum['value'];
                        }
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), ['response' => array_values($parsed)]);
            },
            'args'                => [
                'full'      => [
                    'type' => 'bool'
                ],
                'past'      => [
                    'type' => 'bool'
                ],
                'customers' => [
                    'type' => 'string'
                ],
                'services'  => [
                    'type' => 'string'
                ],
                'providers' => [
                    'type' => 'string'
                ],
                'status'    => [
                    'type' => 'string'
                ],
            ],
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_read_request($request);
            }
        ];
    }
}
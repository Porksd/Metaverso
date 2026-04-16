<?php

namespace VSHM\Routes\Reservations;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Get
 *
 * @package VSHM\Routes\Reservations
 */
class Get implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $pastAsWell = filter_var($request->get_param('past'), FILTER_VALIDATE_BOOLEAN);

                if (Functions::current_user_can_admin()) {
                    if (is_array($request->get_param('ids'))) {
                        $reservations = \VSHM\Providers\Reservations::provideByWithData([
                            'id' => [
                                'operator' => 'IN',
                                'value'    => $request->get_param('ids')
                            ]
                        ]);
                    } else {
                        $reservations = $pastAsWell
                            ? \VSHM\Providers\Reservations::provideByWithData()
                            : \VSHM\Providers\Reservations::provideByWithData([], FALSE, time(), NULL, TRUE);
                    }
                } else {
                    if (is_array($request->get_param('ids'))) {
                        $reservations = \VSHM\Providers\Reservations::provideByWithData([
                            'id'          => [
                                'operator' => 'IN',
                                'value'    => $request->get_param('ids')
                            ],
                            'provider_id' => get_current_user_id()
                        ]);
                    } else {
                        $reservations = $pastAsWell
                            ? \VSHM\Providers\Reservations::provideByWithData(['provider_id' => get_current_user_id()])
                            : \VSHM\Providers\Reservations::provideByWithData(
                                [
                                    'provider_id' => get_current_user_id()
                                ], FALSE, time(), NULL, TRUE);
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservations' => ReservationsRoute::prepare_for_frontend($reservations)
                ]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_is_provider();
            }
        ];
    }
}
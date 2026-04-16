<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\DeleteReservation;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class RemoveOrder
 *
 * @package VSHM\Routes\Reservations
 */
class RemoveOrder implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'remove/order/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => function (\WP_REST_Request $request) {

                $reservationId = $request->get_param('id');
                $hash          = $request->get_param('hash');

                $reservation = Reservations::provideBy(['id' => $reservationId], TRUE);
                $customer    = Customers::provideBy(['id' => $reservation->customerId], TRUE);


                if ($reservation && $hash === md5($reservationId . $customer['access_token'])) {
                    vshm()->bus->dispatch(new DeleteReservation($reservationId), vshm()->bus::AGENT_SYSTEM);
                }

                return REST_Controller::get_ok_response(self::getPath());
            },
            'args'     => [
                'id'   => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'hash' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ]
        ];
    }
}
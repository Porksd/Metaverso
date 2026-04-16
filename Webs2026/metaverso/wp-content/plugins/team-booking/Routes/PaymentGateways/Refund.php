<?php

namespace VSHM\Routes\PaymentGateways;

use VSHM\Bus\RefundReservation;
use VSHM\REST_Controller;
use VSHM\Routes\PaymentGatewaysRoute;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Refund
 *
 * @package VSHM\Routes
 */
class Refund implements SingleRoute
{
    public static function getPath(): string
    {
        return PaymentGatewaysRoute::getPath() . 'refund/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => function (\WP_REST_Request $request) {

                $gatewayId     = $request->get_param('id');
                $reservationId = $request->get_param('reservationId');

                vshm()->bus->dispatch(new RefundReservation($reservationId, $gatewayId));

                return REST_Controller::get_ok_response(self::getPath(),
                    [
                        'reservations' => ReservationsRoute::prepare_for_frontend(),
                        'message'      => __('Refund issued.', 'team-booking')
                    ]);
            },
            'args'     => [
                'id'            => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'reservationId' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ]
        ];
    }
}
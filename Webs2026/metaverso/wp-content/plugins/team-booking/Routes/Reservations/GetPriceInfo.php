<?php

namespace VSHM\Routes\Reservations;

use VSHM\Functions;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\PaymentPendingTime;

defined('ABSPATH') || exit;

/**
 * Class GetPriceInfo
 */
class GetPriceInfo implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'get/price/info/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => function (\WP_REST_Request $request) {

                $reservation = Reservations::provideBy(['id' => $request->get_param('id')], TRUE);
                if (!$reservation) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Reservation not found.', 'team-booking')], 404);
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'finalPrice' => Functions::reservation_get_final_price($request->get_param('id'))->inclusive()->getAmount()->toFloat(),
                    'expiresIn'  => vshm()->settings->get(PaymentPendingTime::ID) - (time() - $reservation->created),
                ]);
            },
            'args'     => [
                'id' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ]
        ];
    }
}
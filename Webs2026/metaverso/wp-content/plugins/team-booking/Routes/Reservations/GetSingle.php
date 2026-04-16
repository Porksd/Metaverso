<?php

namespace VSHM\Routes\Reservations;

use VSHM\Functions;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class GetSingle
 *
 * @package VSHM\Routes\Reservations
 */
class GetSingle implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'get/single/(?P<id>[\w]+)/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => function (\WP_REST_Request $request) {

                $reservationId = $request->get_url_params()['id'];
                $hash          = $request->get_param('hash');

                $reservation = Reservations::provideBy(['id' => $reservationId], TRUE);

                if (!$reservation) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Reservation not found.', 'team-booking')], 404);
                }

                $customer = Customers::provideBy(['id' => $reservation->customerId], TRUE);

                if (!$customer) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Customer not found', 'team-booking')], 404);
                }

                $realHash = md5($reservationId . $customer['access_token']);

                if ($realHash !== $hash) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Not authorized', 'team-booking')], 403);
                }

                /**
                 * Filtering out expired reservations
                 */
                if (apply_filters('tbk_is_reservation_expired', FALSE, $reservation)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Reservation expired', 'team-booking')], 400);
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservations' => ReservationsRoute::prepare_for_frontend([
                        $reservation
                    ])
                ]);
            }
        ];
    }
}
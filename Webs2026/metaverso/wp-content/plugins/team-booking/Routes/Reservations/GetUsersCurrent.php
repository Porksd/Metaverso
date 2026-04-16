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
 * Class GetUsersCurrent
 *
 * @package VSHM\Routes\Reservations
 */
class GetUsersCurrent implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'get/users/current/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $customer = Customers::provideBy(['wp_user' => get_current_user_id()], TRUE);

                if (!$customer) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('The current WordPress User is not linked to any customer.', 'team-booking')], 404);
                }

                $reservations = Reservations::provideBetween(
                    time(),
                    NULL,
                    ['customer_id' => $customer['id']]);

                /**
                 * Filtering out expired reservations
                 */
                foreach ($reservations as $key => $reservation) {
                    if (apply_filters('tbk_is_reservation_expired', FALSE, $reservation)) {
                        unset($reservations[ $key ]);
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), ['reservations' => ReservationsRoute::prepare_for_frontend(array_values($reservations))]);
            },
            'permission_callback' => static function () {
                return is_user_logged_in();
            }
        ];
    }
}
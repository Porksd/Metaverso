<?php

namespace VSHM\Routes\Customers;

use VSHM\Bus\DeleteCustomer;
use VSHM\Functions;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\CustomersRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Remove
 *
 * @package VSHM\Routes
 */
class Remove implements SingleRoute
{
    public static function getPath(): string
    {
        return CustomersRoute::getPath() . 'remove/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $customer = $request->get_param('data');

                $customerReservations = Reservations::provideBy(['customerId' => $customer['id']]);

                if (!empty($customerReservations)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('There are reservations for this customer.', 'team-booking')]);
                }

                vshm()->bus->dispatch(new DeleteCustomer($customer['id']));

                return REST_Controller::get_ok_response(self::getPath(), [
                    'data'    => \VSHM\Providers\Customers::provide(),
                    'message' => __('Customer removed', 'team-booking')
                ]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
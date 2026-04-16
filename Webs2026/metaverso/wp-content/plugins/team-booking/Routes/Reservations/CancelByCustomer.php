<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\CancelReservation;
use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Functions;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Reservation\CancellationOrDenyReason;
use VSHM\Settings\Service\AllowCancellation;

defined('ABSPATH') || exit;

/**
 * Class CancelByCustomer
 *
 * @package VSHM\Routes\Reservations
 */
class CancelByCustomer implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'cancel/by/customer/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => function (\WP_REST_Request $request) {

                $wpId        = get_current_user_id();
                $reservation = Reservations::provideBy(['id' => $request->get_param('id')], TRUE);

                if (!$reservation) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Reservation not found'], 404);
                }

                if ($reservation->status !== 'confirmed') {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Already cancelled'], 403);
                }

                $serviceAllowsCancel = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => AllowCancellation::ID], TRUE);

                if (!$serviceAllowsCancel) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Not allowed'], 403);
                }

                if ($wpId) {

                    if (!Functions::current_user_is_provider()) {
                        $customer = Customers::provideBy(['wp_user' => $wpId], TRUE);
                        if (!$customer) {
                            return REST_Controller::get_error_response(self::getPath(), ['message' => 'The current user is not a customer'], 404);
                        }

                        if ($customer['id'] !== $reservation->customerId) {
                            return REST_Controller::get_error_response(self::getPath(), ['message' => 'Reservation not made by current user'], 404);
                        }
                    }

                    // TODO: limit providers to cancel only reservations that they can manage?

                } else {

                    $customer = Customers::provideBy(['id' => $reservation->customerId], TRUE);

                    if (!($customer && $request->get_param('hash') === md5($reservation->id . $customer['access_token']))) {

                        return REST_Controller::get_error_response(self::getPath(), ['message' => 'Customer not found or not the same'], 404);
                    }
                }

                if ($request->get_param('reason')) {
                    vshm()->bus->dispatch(new UpdateOrCreateReservationProperty(
                        $reservation->id,
                        CancellationOrDenyReason::ID,
                        $request->get_param('reason')
                    ), vshm()->bus::AGENT_USER, $wpId || $customer['id']);
                }

                vshm()->bus->dispatch(new CancelReservation($reservation->id), vshm()->bus::AGENT_USER, $wpId || $customer['id']);

                return REST_Controller::get_ok_response(self::getPath());
            },
            'args'     => [
                'id'     => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'hash'   => [
                    'type' => [
                        'string',
                        'null'
                    ]
                ],
                'reason' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ]
            ]
        ];
    }
}
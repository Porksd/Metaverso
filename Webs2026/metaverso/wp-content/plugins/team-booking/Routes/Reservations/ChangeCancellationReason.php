<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Functions;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Reservation\CancellationOrDenyReason;

defined('ABSPATH') || exit;

/**
 * Class ChangeCancellationReason
 *
 * @package VSHM\Routes\Reservations
 */
class ChangeCancellationReason implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'change/reason/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $reservation_id = $request->get_param('id');
                $reason         = $request->get_param('reason') ?? CancellationOrDenyReason::getDefault();

                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservation_id, CancellationOrDenyReason::ID, $reason), vshm()->bus::AGENT_USER, get_current_user_id());

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservation' => ReservationsRoute::prepare_for_frontend([Reservations::provideByWithData(['id' => $reservation_id], TRUE)])[0]
                ]);
            },
            'args'                => [
                'id'     => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'reason' => [
                    'type' => 'string'
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_is_provider(TRUE);
            }
        ];
    }
}
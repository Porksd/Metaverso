<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\ChangeReservationStatus;
use VSHM\Functions;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class ChangeStatus
 *
 * @package VSHM\Routes\Reservations
 */
class ChangeStatus implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'change/status/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $reservation_id = $request->get_param('id');
                $status         = $request->get_param('status');

                vshm()->bus->dispatch(new ChangeReservationStatus($reservation_id, $status), vshm()->bus::AGENT_USER, get_current_user_id());

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservation' => ReservationsRoute::prepare_for_frontend([Reservations::provideByWithData(['id' => $reservation_id], TRUE)])[0]
                ]);
            },
            'args'                => [
                'id'     => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'status' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
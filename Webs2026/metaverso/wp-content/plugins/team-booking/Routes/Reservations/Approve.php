<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\ApproveReservation;
use VSHM\Functions;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Approve
 *
 * @package VSHM\Routes\Reservations
 */
class Approve implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'approve/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $item = $request->get_param('data');

                if (!isset($item['id'])) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'No reservation ID in the request.']);
                }

                vshm()->bus->dispatch(new ApproveReservation($item['id']), vshm()->bus::AGENT_USER, get_current_user_id());

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservation' => ReservationsRoute::prepare_for_frontend([Reservations::provideByWithData(['id' => $item['id']], TRUE)])[0]
                ]);
            },
            'args'                => [
                'data' => [
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_is_provider(TRUE);
            }
        ];
    }
}
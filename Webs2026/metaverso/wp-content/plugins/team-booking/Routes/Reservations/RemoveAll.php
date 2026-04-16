<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\DeleteAllReservations;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class RemoveAll
 *
 * @package VSHM\Routes\Reservations
 */
class RemoveAll implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'remove/all/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $reset = $request->get_param('resetIds');

                vshm()->bus->dispatch(new DeleteAllReservations($reset), vshm()->bus::AGENT_USER, get_current_user_id());

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservations' => ReservationsRoute::prepare_for_frontend()
                ]);
            },
            'args'                => [
                'resetIds' => [
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
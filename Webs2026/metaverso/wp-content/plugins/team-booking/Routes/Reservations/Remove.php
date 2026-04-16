<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\DeleteReservation;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Remove
 *
 * @package VSHM\Routes\Reservations
 */
class Remove implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'remove/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $item = $request->get_param('data');

                if (isset($item['id'])) {
                    vshm()->bus->dispatch(new DeleteReservation($item['id']), vshm()->bus::AGENT_USER, get_current_user_id());
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservations' => ReservationsRoute::prepare_for_frontend()
                ]);
            },
            'args'                => [
                'data' => [
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
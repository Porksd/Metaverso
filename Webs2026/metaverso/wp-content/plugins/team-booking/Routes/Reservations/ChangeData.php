<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\ChangeReservationDate;
use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Functions;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class ChangeDate
 *
 * @package VSHM\Routes\Reservations
 */
class ChangeData implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'change/data/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $reservation_id = $request->get_param('id');
                $value          = $request->get_param('value');
                $key            = $request->get_param('key');

                // TODO: whitelist property keys?

                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservation_id, $key, $value), vshm()->bus::AGENT_USER, get_current_user_id());

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservation' => ReservationsRoute::prepare_for_frontend([Reservations::provideByWithData(['id' => $reservation_id], TRUE)])[0]
                ]);
            },
            'args'                => [
                'id'    => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'value' => [
                    'required' => TRUE
                ],
                'key'   => [
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
<?php

namespace VSHM\Routes\Reservations;

use VSHM\Functions;
use VSHM\Providers\FormEntries;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class DeleteFormEntry
 *
 * @package VSHM\Routes\Reservations
 */
class DeleteFormEntry implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'delete/form/entry/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $field_id       = $request->get_param('field_id');
                $reservation_id = $request->get_param('reservation_id');

                $reservation = Reservations::provideBy(['id' => $reservation_id], TRUE);
                if ($reservation) {
                    FormEntries::remove(['id' => $field_id]);
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservation' => ReservationsRoute::prepare_for_frontend([Reservations::provideByWithData(['id' => $reservation_id], TRUE)])[0],
                    'message'     => __('Entry has been deleted', 'team-booking')
                ]);
            },
            'args'                => [
                'field_id'       => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'reservation_id' => [
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
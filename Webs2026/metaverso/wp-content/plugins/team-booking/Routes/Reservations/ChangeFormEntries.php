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
 * Class ChangeFormEntries
 *
 * @package VSHM\Routes\Reservations
 */
class ChangeFormEntries implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'change/form/entries/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $fieldEntries   = $request->get_param('values');
                $reservation_id = $request->get_param('reservation_id');

                $reservation = Reservations::provideBy(['id' => $reservation_id], TRUE);
                if (is_array($fieldEntries) && $reservation) {

                    foreach ($fieldEntries as $id => $value) {
                        $existingField = FormEntries::provideBy(['reservationId' => $reservation_id, 'id' => $id], TRUE);
                        if ($existingField) {
                            FormEntries::update([
                                'reservationId' => $reservation_id,
                                'id'            => $id,
                                'data'          => $existingField['data'],
                                'value'         => $value
                            ]);
                        } else {
                            FormEntries::store([
                                'reservationId' => $reservation_id,
                                'id'            => $id,
                                'data'          => [
                                    'priceIncrement' => 0 // TODO
                                ],
                                'value'         => $value
                            ]);
                        }
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservation' => ReservationsRoute::prepare_for_frontend([Reservations::provideByWithData(['id' => $reservation_id], TRUE)])[0],
                    'message'     => __('Reservation data saved', 'team-booking')
                ]);
            },
            'args'                => [
                'values'         => [
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
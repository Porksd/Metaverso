<?php

namespace VSHM\Routes\Reservations;

use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Functions;
use VSHM\Providers\ReservationsData;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class DeleteFile
 *
 * @package VSHM\Routes\Reservations
 */
class DeleteFile implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'delete/file/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $fileHash       = $request->get_param('fileHash');
                $reservation_id = $request->get_param('reservation_id');

                $reservationFiles = ReservationsData::provideBy(['reservation_id' => $reservation_id, 'key' => 'files'], TRUE);
                if ($reservationFiles && is_array($reservationFiles)) {
                    foreach ($reservationFiles as $fieldId => $hash) {
                        if ($fileHash === $hash) {
                            unset($reservationFiles[ $fieldId ]);
                            break;
                        }
                    }
                    vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservation_id, 'files', $reservationFiles), vshm()->bus::AGENT_USER, get_current_user_id());
                }

                vshm()->bus->dispatch(new \VSHM\Bus\DeleteFile($fileHash));

                return REST_Controller::get_ok_response(self::getPath(), [
                    'reservations' => ReservationsRoute::prepare_for_frontend(),
                    'message'      => __('File has been deleted', 'team-booking')
                ]);
            },
            'args'                => [
                'fileHash'       => [
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
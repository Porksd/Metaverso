<?php

namespace VSHM\Modules\Gcal3Way\Routes;

use VSHM\Functions;
use VSHM\Modules\Gcal2Ways;
use VSHM\Modules\Gcal3Way\GoogleBusMiddleware;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\REST_Controller;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Reservation\GoogleCalendarEventId;

defined('ABSPATH') || exit;

/**
 * Class ManuallyCreateEvent
 *
 * @package VSHM\Modules\Gcal3Way\Routes
 */
class ManuallyCreateEvent implements SingleRoute
{
    public static function getPath(): string
    {
        return Gcal2Ways::$route_path . 'create/event/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => static function (\WP_REST_Request $request) {


                $reservation = Reservations::provideByWithData(['id' => $request->get_param('reservationId')], TRUE);

                $created = GoogleBusMiddleware::createEvent(
                    $reservation->id,
                    $reservation->providerId,
                    $reservation->serviceId,
                    $reservation->start,
                    $reservation->end,
                    $reservation->customerId,
                    $reservation->status,
                    TRUE
                );

                if (!$created) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __('Event not created. No destination calendar is found, or the reservation is not confirmed.', 'team-booking')
                    ]);
                }

                $eventId = ReservationsData::provideBy([
                    'reservation_id' => $reservation->id,
                    'key'            => GoogleCalendarEventId::ID
                ], TRUE, FALSE, FALSE);

                return REST_Controller::get_ok_response(self::getPath(), [
                    'message'                 => __('Event created.', 'team-booking'),
                    GoogleCalendarEventId::ID => $eventId
                ]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_is_provider(TRUE);
            },
            'args'                => [
                'reservationId' => [
                    'required' => TRUE,
                    'type'     => 'string'
                ]
            ]
        ];
    }
}
<?php

namespace VSHM\Routes\API\Reservations;

use VSHM\Bus\DeleteReservation;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Remove
 *
 * @package VSHM\Routes\API\Reservations
 */
class Remove implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'reservations/remove/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $reservation = Reservations::provideBy(['id' => $request->get_param('id')], TRUE);

                if (!$reservation) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Reservation not found.'], 404);
                }

                if ($reservation->status !== 'pending' && $reservation->end > time()) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Reservation cannot be removed.'], 304);
                }

                vshm()->bus->dispatch(new DeleteReservation($reservation->id), vshm()->bus::AGENT_APP, $token['token']);

                return REST_Controller::get_ok_response(self::getPath());
            },
            'args'                => [
                'id' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ],
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
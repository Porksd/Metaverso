<?php

namespace VSHM\Routes\API\Reservations;

use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Edit
 *
 * @package VSHM\Routes\API\Reservations
 */
class Edit implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'reservations/edit/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $record = json_decode($request->get_body(), TRUE);

                if (!is_array($record)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Body is not a valid JSON']);
                }

                if (!isset($record['id'])) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'No reservation ID found in body data']);
                }

                $reservation = Reservations::provideBy(['id' => $record['id']], TRUE);

                if (!$reservation) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Reservation not found.'], 404);
                }

                foreach ($record as $key => $property) {
                    if ($key === 'id') {
                        continue;
                    }
                    vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservation->id, (string)$key, $property), vshm()->bus::AGENT_APP, $token['token']);
                }


                return REST_Controller::get_ok_response(self::getPath());
            },
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
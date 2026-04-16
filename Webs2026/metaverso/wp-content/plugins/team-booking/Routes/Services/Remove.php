<?php

namespace VSHM\Routes\Services;

use VSHM\Bus\DeleteService;
use VSHM\Functions;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Remove
 *
 * @package VSHM\Routes
 */
class Remove implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'remove/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $serviceReservations = Reservations::provideBy(['serviceId' => $request->get_param('id')]);

                if (!empty($serviceReservations)) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __('There are reservations for this service.', 'team-booking')
                    ]);
                }

                vshm()->bus->dispatch(new DeleteService($request->get_param('id')));

                return REST_Controller::get_ok_response(self::getPath(), ['data' => ServicesRoute::prepare_for_backend()]);

            },
            'args'                => [
                'id' => [
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
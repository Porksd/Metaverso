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
 * Class RemoveMulti
 *
 * @package VSHM\Routes
 */
class RemoveMulti implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'remove/multi/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $ids = $request->get_param('ids');

                $limited = FALSE;

                if (is_array($ids)) {

                    $reservations = array_column(Reservations::provide(), 'id', 'serviceId');

                    foreach ($ids as $id) {
                        if (!isset($reservations[ $id ])) {
                            vshm()->bus->dispatch(new DeleteService($id));
                        } else {
                            $limited = TRUE;
                        }
                    }
                }

                $args = ['data' => ServicesRoute::prepare_for_backend()];

                if ($limited) {
                    $args['message'] = __('Some services were not removed because there were reservations in place for them.', 'team-booking');
                }

                return REST_Controller::get_ok_response(self::getPath(), $args);
            },
            'args'                => [
                'ids' => [
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
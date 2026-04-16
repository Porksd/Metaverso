<?php

namespace VSHM\Routes\Forms;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\FormsRoute;
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
        return FormsRoute::getPath() . 'remove/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $item = $request->get_param('data');

                \VSHM\Providers\Forms::remove($item);

                return REST_Controller::get_ok_response(self::getPath(),
                    [
                        'data'    => \VSHM\Providers\Forms::provide(),
                        'message' => __('Form item removed', 'team-booking')
                    ]);

            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
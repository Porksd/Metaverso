<?php

namespace VSHM\Routes\Forms;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\FormsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Add
 *
 * @package VSHM\Routes
 */
class Add implements SingleRoute
{
    public static function getPath(): string
    {
        return FormsRoute::getPath() . 'add/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $item = $request->get_param('data');

                // TODO: validation

                \VSHM\Providers\Forms::store([
                    'id'       => Tools::generate_token(),
                    'fields'   => $item['fields'],
                    'required' => $item['required'],
                    'active'   => $item['active']
                ]);

                return REST_Controller::get_ok_response(self::getPath(), [
                    'data'    => \VSHM\Providers\Forms::provide(),
                    'message' => __('Form item added', 'team-booking')
                ]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
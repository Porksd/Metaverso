<?php

namespace VSHM\Routes\Services;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Get
 *
 * @package VSHM\Routes
 */
class Get implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {
                return REST_Controller::get_ok_response(self::getPath(), ['data' => ServicesRoute::prepare_for_backend()]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_is_provider();
            }
        ];
    }
}
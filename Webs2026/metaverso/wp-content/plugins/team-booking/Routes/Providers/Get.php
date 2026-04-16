<?php

namespace VSHM\Routes\Providers;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ProvidersRoute;
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
        return ProvidersRoute::getPath() . 'get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {
                return REST_Controller::get_ok_response(self::getPath(), ['providers' => \VSHM\Providers\ServiceProviders::provide()]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin();
            }
        ];
    }
}
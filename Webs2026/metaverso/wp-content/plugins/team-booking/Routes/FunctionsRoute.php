<?php

namespace VSHM\Routes;

use VSHM\Functions;
use VSHM\REST_Controller;

defined('ABSPATH') || exit;

/**
 * Class FunctionsRoute
 *
 * @package VSHM\Routes
 */
final class FunctionsRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/functions/';

    public static function register(): void
    {
        REST_Controller::register_routes([
            self::$path . 'get/token/' => [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => function (\WP_REST_Request $request) {
                    return REST_Controller::get_ok_response(self::$path . 'get/token/', ['data' => \VSHM\Tools::generate_token()]);
                },
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin();
                }
            ]
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
<?php

namespace VSHM\Routes\API\Customers;

use VSHM\Bus\UseApiToken;
use VSHM\Providers\Customers;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Get
 *
 * @package VSHM\Routes\API\Customers
 */
class Get implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'customers/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $customers = Customers::provide();

                return REST_Controller::get_ok_response(self::getPath(), ['response' => $customers]);
            },
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_read_request($request);
            }
        ];
    }
}
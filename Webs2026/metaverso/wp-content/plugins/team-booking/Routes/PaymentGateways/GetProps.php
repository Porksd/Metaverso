<?php

namespace VSHM\Routes\PaymentGateways;

use VSHM\Bus\DeleteService;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\PaymentGatewaysRoute;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class GetProps
 *
 * @package VSHM\Routes
 */
class GetProps implements SingleRoute
{
    public static function getPath(): string
    {
        return PaymentGatewaysRoute::getPath() . 'frontend/get_props/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => function (\WP_REST_Request $request) {

                return REST_Controller::get_ok_response(self::getPath(), ['gateways' => apply_filters('tbk_payment_gateways_frontend_choice', [])]);
            },
        ];
    }
}
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
 * Class Get
 *
 * @package VSHM\Routes
 */
class Get implements SingleRoute
{
    public static function getPath(): string
    {
        return PaymentGatewaysRoute::getPath() . '(?P<id>[\w]+)/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $gatewayId = $request->get_param('id');

                return REST_Controller::get_ok_response(PaymentGatewaysRoute::getPath() . 'get/', ['settings' => apply_filters('tbk_payment_gateway_config', [], $gatewayId)]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin();
            }
        ];
    }
}
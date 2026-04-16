<?php

namespace VSHM\Routes\PaymentGateways;

use VSHM\REST_Controller;
use VSHM\Routes\PaymentGatewaysRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Redirect
 *
 * @package VSHM\Routes
 */
class Redirect implements SingleRoute
{
    public static function getPath(): string
    {
        return PaymentGatewaysRoute::getPath() . '(?P<id>[\w]+)/redirect/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => function (\WP_REST_Request $request) {
                do_action('vshm_payment_gateways_redirect_' . $request->get_url_params()['id'], $request);

                return REST_Controller::get_ok_response(PaymentGatewaysRoute::getPath() . 'redirect/');
            }
        ];
    }
}
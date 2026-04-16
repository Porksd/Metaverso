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
 * Class Listen
 *
 * @package VSHM\Routes
 */
class Listen implements SingleRoute
{
    public static function getPath(): string
    {
        return PaymentGatewaysRoute::getPath() . '(?P<id>[\w]+)/listen/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => function (\WP_REST_Request $request) {

                // use raw POST data
                $raw_post_data  = @file_get_contents('php://input');
                $raw_post_array = explode('&', $raw_post_data);
                $post_data      = [];
                foreach ($raw_post_array as $keyval) {
                    $keyval = explode('=', $keyval);
                    if (count($keyval) === 2) {
                        $post_data[ $keyval[0] ] = urldecode($keyval[1]);
                    }
                }
                do_action('vshm_payment_gateways_listen_' . $request->get_url_params()['id'], $post_data, $raw_post_data);

                return REST_Controller::get_ok_response(PaymentGatewaysRoute::getPath() . 'listen/');
            }
        ];
    }
}
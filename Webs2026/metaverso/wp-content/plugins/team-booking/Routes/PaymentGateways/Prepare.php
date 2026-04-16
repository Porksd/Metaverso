<?php

namespace VSHM\Routes\PaymentGateways;

use VSHM\REST_Controller;
use VSHM\Routes\PaymentGatewaysRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Prepare
 *
 * @package VSHM\Routes
 */
class Prepare implements SingleRoute
{
    public static function getPath(): string
    {
        return PaymentGatewaysRoute::getPath() . 'prepare/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => function (\WP_REST_Request $request) {

                $gatewayId     = $request->get_param('id');
                $reservationId = $request->get_param('reservationId');

                return REST_Controller::get_ok_response(self::getPath(), ['redirect' => apply_filters('tbk_payment_gateways_prepare_link' . $gatewayId, '', $reservationId)]);
            },
            'args'     => [
                'id'            => [
                    'type'     => 'string',
                    'required' => TRUE
                ],
                'reservationId' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ]
        ];
    }
}
<?php

namespace VSHM\Routes;

use VSHM\REST_Controller;

defined('ABSPATH') || exit;

/**
 * Class PaymentGatewaysRoute
 *
 * @package VSHM\Routes
 */
final class PaymentGatewaysRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/payment_gateways/';

    public static function register(): void
    {
        REST_Controller::register_routes([
            \VSHM\Routes\PaymentGateways\Prepare::getPath()  => \VSHM\Routes\PaymentGateways\Prepare::get(),
            \VSHM\Routes\PaymentGateways\Redirect::getPath() => \VSHM\Routes\PaymentGateways\Redirect::get(),
            \VSHM\Routes\PaymentGateways\Refund::getPath()   => \VSHM\Routes\PaymentGateways\Refund::get(),
            \VSHM\Routes\PaymentGateways\Listen::getPath()   => \VSHM\Routes\PaymentGateways\Listen::get(),
            \VSHM\Routes\PaymentGateways\Get::getPath()      => \VSHM\Routes\PaymentGateways\Get::get(),
            \VSHM\Routes\PaymentGateways\GetProps::getPath() => \VSHM\Routes\PaymentGateways\GetProps::get(),
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
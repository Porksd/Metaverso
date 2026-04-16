<?php

namespace VSHM\Routes;

use VSHM\REST_Controller;

defined('ABSPATH') || exit;

/**
 * Class CustomersRoute
 *
 * @package VSHM\Routes
 */
final class CustomersRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/customers/';

    public static function register()
    {
        REST_Controller::register_routes([
            \VSHM\Routes\Customers\Get::getPath()         => \VSHM\Routes\Customers\Get::get(),
            \VSHM\Routes\Customers\Add::getPath()         => \VSHM\Routes\Customers\Add::get(),
            \VSHM\Routes\Customers\Remove::getPath()      => \VSHM\Routes\Customers\Remove::get(),
            \VSHM\Routes\Customers\RemoveMulti::getPath() => \VSHM\Routes\Customers\RemoveMulti::get(),
            \VSHM\Routes\Customers\Save::getPath()        => \VSHM\Routes\Customers\Save::get(),
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
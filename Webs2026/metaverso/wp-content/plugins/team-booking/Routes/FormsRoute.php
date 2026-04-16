<?php

namespace VSHM\Routes;

use VSHM\REST_Controller;

defined('ABSPATH') || exit;

/**
 * Class FormsRoute
 *
 * @package VSHM\Routes
 */
final class FormsRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/forms/';

    public static function register()
    {
        REST_Controller::register_routes([
            \VSHM\Routes\Forms\Get::getPath()         => \VSHM\Routes\Forms\Get::get(),
            \VSHM\Routes\Forms\Remove::getPath()      => \VSHM\Routes\Forms\Remove::get(),
            \VSHM\Routes\Forms\Add::getPath()         => \VSHM\Routes\Forms\Add::get(),
            \VSHM\Routes\Forms\Save::getPath()        => \VSHM\Routes\Forms\Save::get(),
            \VSHM\Routes\Forms\GetFrontend::getPath() => \VSHM\Routes\Forms\GetFrontend::get(),
        ]);
    }

    public static function getPath()
    {
        return self::$path;
    }
}
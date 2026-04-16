<?php

namespace VSHM\Routes;

use VSHM\REST_Controller;

defined('ABSPATH') || exit;

/**
 * Class ProvidersRoute
 *
 * @package VSHM\Routes
 */
final class ProvidersRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/providers/';

    public static function register(): void
    {
        REST_Controller::register_routes([
            \VSHM\Routes\Providers\Get::getPath()    => \VSHM\Routes\Providers\Get::get(),
            \VSHM\Routes\Providers\Revoke::getPath() => \VSHM\Routes\Providers\Revoke::get(),
            \VSHM\Routes\Providers\Save::getPath()   => \VSHM\Routes\Providers\Save::get(),
        ]);
    }

    public static function prepare_for_frontend(): array
    {
        $providers = \VSHM\Providers\ServiceProviders::provide();

        /**
         * Those are properties useful to have straight away in the frontend instead of querying for data
         */

        foreach ($providers as $key => $provider) {
            $providers[ $key ] = [
                'avatar' => $provider['avatar'],
                'id'     => $provider['id'],
                'name'   => $provider['name'],
                'url'    => $provider['url']
            ];
        }

        return $providers;
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
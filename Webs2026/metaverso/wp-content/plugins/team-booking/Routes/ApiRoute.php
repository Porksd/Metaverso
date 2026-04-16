<?php

namespace VSHM\Routes;

use VSHM\Bus\UseApiToken;
use VSHM\Providers\ApiTokens;
use VSHM\REST_Controller;

defined('ABSPATH') || exit;

/**
 * Class ApiRoute
 *
 * @package VSHM\Routes
 */
final class ApiRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/API/';

    public static function register(): void
    {
        REST_Controller::register_routes([
            \VSHM\Routes\API\Services\Get::getPath()                  => \VSHM\Routes\API\Services\Get::get(),
            \VSHM\Routes\API\Services\Remove::getPath()               => \VSHM\Routes\API\Services\Remove::get(),
            \VSHM\Routes\API\Services\Edit::getPath()                 => \VSHM\Routes\API\Services\Edit::get(),
            \VSHM\Routes\API\Availability\Get::getPath()              => \VSHM\Routes\API\Availability\Get::get(),
            \VSHM\Routes\API\Customers\Get::getPath()                 => \VSHM\Routes\API\Customers\Get::get(),
            \VSHM\Routes\API\Customers\Remove::getPath()              => \VSHM\Routes\API\Customers\Remove::get(),
            \VSHM\Routes\API\Customers\Edit::getPath()                => \VSHM\Routes\API\Customers\Edit::get(),
            \VSHM\Routes\API\Customers\Add::getPath()                 => \VSHM\Routes\API\Customers\Add::get(),
            \VSHM\Routes\API\Reservations\Get::getPath()              => \VSHM\Routes\API\Reservations\Get::get(),
            \VSHM\Routes\API\Reservations\Edit::getPath()             => \VSHM\Routes\API\Reservations\Edit::get(),
            \VSHM\Routes\API\Reservations\Remove::getPath()           => \VSHM\Routes\API\Reservations\Remove::get(),
            \VSHM\Routes\API\Reservations\Add::getPath()              => \VSHM\Routes\API\Reservations\Add::get(),
            \VSHM\Routes\API\Providers\Get::getPath()                 => \VSHM\Routes\API\Providers\Get::get(),
            \VSHM\Routes\API\Providers\Edit::getPath()                => \VSHM\Routes\API\Providers\Edit::get(),
            \VSHM\Routes\API\Providers\EditServiceSettings::getPath() => \VSHM\Routes\API\Providers\EditServiceSettings::get(),
        ]);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return false|string
     */
    public static function getBearer(\WP_REST_Request $request)
    {
        $headers = $request->get_headers();

        if (!isset($headers['authorization'])) {
            return FALSE;
        }
        if (empty($headers['authorization'])) {
            return FALSE;
        }
        foreach ((array)$headers['authorization'] as $header) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                return $matches[1];
            }
        }

        return FALSE;
    }

    public static function getStoredToken(\WP_REST_Request $request)
    {
        $token = self::getBearer($request);
        if (!$token) {
            return NULL;
        }

        return ApiTokens::provideBy(['token' => $token], TRUE);
    }

    public static function validate_read_request(\WP_REST_Request $request): bool
    {
        if (!self::getStoredToken($request)) {
            return FALSE;
        }

        return TRUE;
    }

    public static function validate_write_request(\WP_REST_Request $request): bool
    {
        $storedToken = self::getStoredToken($request);
        if (!$storedToken) {
            return FALSE;
        }

        if (!$storedToken['readonly']) {
            return TRUE;
        }

        return FALSE;
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
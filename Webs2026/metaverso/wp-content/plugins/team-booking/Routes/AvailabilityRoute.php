<?php

namespace VSHM\Routes;

use VSHM\Functions;
use VSHM\REST_Controller;


defined('ABSPATH') || exit;

/**
 * Class AvailabilityRoute
 *
 * @package VSHM\Routes
 */
final class AvailabilityRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/availability/';

    public static function register(): void
    {
        REST_Controller::register_routes([
            self::$path . 'get/'          => [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    $reservations = apply_filters(
                        'tbk_reservations_to_be_computed_by_slots',
                        \VSHM\Providers\Reservations::provideBetween(
                            (int)$request->get_param('min_timestamp'),
                            (int)$request->get_param('max_timestamp'),
                            [
                                'status' => [
                                    'operator' => '!=',
                                    'value'    => 'cancelled'
                                ]
                            ]
                        ),
                        $request
                    );

                    $slots = apply_filters(
                        'tbk_availability',
                        [],
                        (int)$request->get_param('min_timestamp'),
                        (int)$request->get_param('max_timestamp'),
                        $reservations
                    );

                    return REST_Controller::get_ok_response(self::$path . 'get/', ['slots' => $slots]);

                },
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider();
                }
            ],
            self::$path . 'get/frontend/' => [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => function (\WP_REST_Request $request) {

                    $reservations = apply_filters(
                        'tbk_reservations_to_be_computed_by_slots',
                        \VSHM\Providers\Reservations::provideBetween(
                            (int)$request->get_param('min_timestamp'),
                            (int)$request->get_param('max_timestamp'),
                            [
                                'status' => [
                                    'operator' => '!=',
                                    'value'    => 'cancelled'
                                ]
                            ]
                        ),
                        $request
                    );

                    add_filter('tbk_availability_request_is_frontend', '__return_true');

                    $slots = apply_filters(
                        'tbk_availability',
                        [],
                        (int)$request->get_param('min_timestamp'),
                        (int)$request->get_param('max_timestamp'),
                        $reservations,
                        $request->get_param('services'),
                        $request->get_param('providers')
                    );

                    return REST_Controller::get_ok_response(self::$path . 'get/frontend/', [
                        'slots' => apply_filters('tbk_maybe_apply_promotions', $slots)
                    ]);
                }
            ],
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
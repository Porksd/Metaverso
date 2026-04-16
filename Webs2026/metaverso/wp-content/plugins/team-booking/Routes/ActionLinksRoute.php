<?php

namespace VSHM\Routes;

use VSHM\REST_Controller;
use VSHM\Settings\FrontendBookingManagerPage;

defined('ABSPATH') || exit;

/**
 * Class ActionLinksRoute
 */
final class ActionLinksRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/links/';

    public static function register(): void
    {
        REST_Controller::register_routes([
            self::$path . 'reservations/' => [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => function (\WP_REST_Request $request) {

                    $reservationId = $request->get_param('tbk-id');
                    $hash          = $request->get_param('tbk-hash');

                    $redirectTo = vshm()->settings->get(FrontendBookingManagerPage::ID);

                    if (!$redirectTo) {
                        error_log('No destination page selected.');

                        return REST_Controller::get_error_response(self::getPath() . 'reservations/', ['message' => 'No destination page is found. Please inform the Admin of the website.'], 404);
                    }

                    $link = get_page_link($redirectTo);

                    if (!$link) {
                        error_log('No destination page found.');

                        return REST_Controller::get_error_response(self::getPath() . 'reservations/', ['message' => 'No destination page is found. Please inform the Admin of the website.'], 404);
                    }

                    return new \WP_REST_Response(apply_filters('vshm_action_links_reservations_response',
                        NULL),
                        302,
                        [
                            'Location' => add_query_arg([
                                'tbk-hash' => $hash,
                                'tbk-view' => 'reservations',
                                'tbk-id'   => $reservationId
                            ], $link),
                        ]
                    );
                }
            ],
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
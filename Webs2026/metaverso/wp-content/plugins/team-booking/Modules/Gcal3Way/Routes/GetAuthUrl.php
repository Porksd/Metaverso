<?php

namespace VSHM\Modules\Gcal3Way\Routes;

use VSHM\Functions;
use VSHM\Modules\Gcal2Ways;
use VSHM\REST_Controller;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class GetAuthUrl
 *
 * @package VSHM\Modules\Gcal3Way\Routes
 */
class GetAuthUrl implements SingleRoute
{
    public static function getPath(): string
    {
        return Gcal2Ways::$route_path . 'get/authUrl/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => static function (\WP_REST_Request $request) {

                if (!Gcal2Ways::is_configured()) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __('Google Calendar API is not configured.', 'team-booking')
                    ]);
                }

                $client = Gcal2Ways::_client();
                $client->setPrompt('consent');
                $state = [
                    'nonce' => wp_create_nonce('tbk_google2ways_oauth'),
                    'user'  => get_current_user_id()
                ];
                $client->setState(urlencode(json_encode($state)));

                return REST_Controller::get_ok_response(self::getPath(), [
                    'url' => $client->createAuthUrl()
                ]);

            },
            'permission_callback' => static function () {
                return Functions::current_user_is_provider();
            }
        ];
    }
}
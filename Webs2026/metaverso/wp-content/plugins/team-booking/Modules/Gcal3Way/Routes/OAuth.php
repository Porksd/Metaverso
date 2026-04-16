<?php

namespace VSHM\Modules\Gcal3Way\Routes;

use VSHM\Bus\UpdateProviderProperty;
use VSHM\Modules\Gcal2Ways;
use VSHM\Providers\ServiceProviders;
use VSHM\REST_Controller;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Provider\GoogleAccount;
use VSHM\Settings\Provider\GoogleApiToken;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class OAuth
 *
 * @package VSHM\Modules\Gcal3Way\Routes
 */
class OAuth implements SingleRoute
{
    public static function getPath(): string
    {
        return Gcal2Ways::$route_path . 'oauth';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => static function (\WP_REST_Request $request) {

                $code  = $request->get_param('code');
                $state = json_decode(urldecode($request->get_param('state')), TRUE);

                if (!isset($state['nonce'], $state['user'])) {

                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => 'Invalid request, missing nonce and/or user ID. Please retry.'
                    ], 401);
                }

                add_filter('nonce_user_logged_out', static function ($uid, $action) use ($state) {
                    if ($action === 'tbk_google2ways_oauth') {
                        return $state['user'];
                    }

                    return $uid;
                }, 10, 2);

                if (!wp_verify_nonce($state['nonce'], 'tbk_google2ways_oauth')) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => 'Invalid nonce. Please retry.'
                    ], 401);
                }

                if ($code) {

                    $client = Gcal2Ways::_client();
                    $client->fetchAccessTokenWithAuthCode($code);

                    $provider = ServiceProviders::provideBy(['id' => $state['user']], TRUE);

                    if ($provider) {
                        if ($client->getAccessToken()) {
                            vshm()->bus->dispatch(new UpdateProviderProperty($state['user'], GoogleApiToken::ID, $client->getAccessToken()));
                        }
                        try {
                            $email = Gcal2Ways::getTokenEmailAccount($state['user']);
                            if ($email) {
                                vshm()->bus->dispatch(new UpdateProviderProperty($state['user'], GoogleAccount::ID, $email));
                            }
                        } catch (\Exception $e) {
                            // Silence is golden
                            Tools::log_dump($e->getMessage());

                            return REST_Controller::get_error_response(self::getPath(), [
                                'message' => $e->getMessage()
                            ], 401);
                        }
                    } else if ($state['user']) {
                        /**
                         * https://wordpress.stackexchange.com/questions/354939/get-users-wp-user-query-returns-empty-when-logged-out/354941
                         */
                        return REST_Controller::get_error_response(self::getPath(), [
                            'message' => 'The plugin is not able to save Google Access tokens for the current WordPress user. Do you have an user manager plugin in place? If yes, check if there are restrictions about users list accessibility, or try to de-activate that plugin temporarily.'
                        ], 403);
                    }
                } else {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => 'Invalid request, code is missing. Please retry.'
                    ], 401);
                }

                return rest_ensure_response(new \WP_REST_Response(
                    NULL,
                    302,
                    [
                        'Location' => add_query_arg('page', vshm()->plugin['SLUG'], admin_url('admin.php')) . '#availability~google'
                    ]
                ));

            },
            'args'     => [
                'code' => [
                    'required' => TRUE
                ],
            ]
        ];
    }
}
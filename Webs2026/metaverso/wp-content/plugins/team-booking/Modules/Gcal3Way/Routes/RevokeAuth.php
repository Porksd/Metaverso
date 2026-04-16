<?php

namespace VSHM\Modules\Gcal3Way\Routes;

use VSHM\Bus\UpdateProviderProperty;
use VSHM\Functions;
use VSHM\Modules\Gcal2Ways;
use VSHM\Providers\ServiceProviders;
use VSHM\REST_Controller;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Provider\GoogleAccount;
use VSHM\Settings\Provider\GoogleApiToken;
use VSHM\Settings\Provider\GoogleCalendars;

defined('ABSPATH') || exit;

/**
 * Class RevokeAuth
 *
 * @package VSHM\Modules\Gcal3Way\Routes
 */
class RevokeAuth implements SingleRoute
{
    public static function getPath(): string
    {
        return Gcal2Ways::$route_path . 'revoke/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => static function (\WP_REST_Request $request) {
                $provider = ServiceProviders::provideBy(['id' => get_current_user_id()], TRUE);
                $message  = NULL;
                if ($provider && isset($provider[ GoogleApiToken::ID ])) {
                    $client = Gcal2Ways::_client();
                    $client->setAccessToken($provider[ GoogleApiToken::ID ]);
                    if (!$client->revokeToken()) {
                        $message = __('Access was already revoked manually.', 'team-booking');
                    }

                    vshm()->bus->dispatch(new UpdateProviderProperty(get_current_user_id(), GoogleApiToken::ID, GoogleApiToken::default(get_current_user_id())));
                    vshm()->bus->dispatch(new UpdateProviderProperty(get_current_user_id(), GoogleCalendars::ID, GoogleCalendars::default(get_current_user_id())));
                    vshm()->bus->dispatch(new UpdateProviderProperty(get_current_user_id(), GoogleAccount::ID, GoogleAccount::default(get_current_user_id())));

                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'providers' => ServiceProviders::provide(),
                    'message'   => $message
                ]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_is_provider(TRUE);
            }
        ];
    }
}
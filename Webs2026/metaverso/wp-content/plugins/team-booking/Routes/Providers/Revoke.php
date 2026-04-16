<?php

namespace VSHM\Routes\Providers;

use VSHM\Bus\ProviderRevoke;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ProvidersRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Revoke
 *
 * @package VSHM\Routes\Providers
 */
class Revoke implements SingleRoute
{
    public static function getPath(): string
    {
        return ProvidersRoute::getPath() . 'revoke/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $providerId = $request->get_param('provider');

                vshm()->bus->dispatch(new ProviderRevoke($providerId));

                return REST_Controller::get_ok_response(self::getPath(), ['providers' => \VSHM\Providers\ServiceProviders::provide()]);

            },
            'args'                => [
                'provider' => [
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_is_provider(TRUE);
            }
        ];
    }
}
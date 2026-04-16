<?php

namespace VSHM\Routes\Providers;

use VSHM\Bus\UpdateProviderProperty;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ProvidersRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Save
 *
 * @package VSHM\Routes\Providers
 */
class Save implements SingleRoute
{
    public static function getPath(): string
    {
        return ProvidersRoute::getPath() . 'save/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $settings = $request->get_param('data');
                if (is_array($settings)) {
                    foreach ($settings as $setting) {
                        vshm()->bus->dispatch(new UpdateProviderProperty($setting['provider_id'], $setting['key'], $setting['value']));
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'providers' => \VSHM\Providers\ServiceProviders::provide(),
                    'message'   => __('Settings have been saved!', 'team-booking')
                ]);

            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
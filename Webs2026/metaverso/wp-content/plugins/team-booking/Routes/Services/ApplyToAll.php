<?php

namespace VSHM\Routes\Services;

use VSHM\Bus\UpdateOrCreateServicePersonalProperty;
use VSHM\Functions;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class ApplyToAll
 *
 * @package VSHM\Routes
 */
class ApplyToAll implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'apply/to/all/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $serviceId       = $request->get_param('service');
                $currentProvider = get_current_user_id();

                $masterData = ServiceProviderCustomData::provideBy(['service_id' => $serviceId, 'provider_id' => $currentProvider]);
                $providers  = ServiceProviders::provide();

                foreach ($providers as $provider) {
                    if ($provider['id'] === $currentProvider) {
                        continue;
                    }
                    foreach ($masterData as $masterDatum) {
                        vshm()->bus->dispatch(new UpdateOrCreateServicePersonalProperty($serviceId, $provider['id'], $masterDatum['key'], $masterDatum['value']));
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), ['data' => ServicesRoute::prepare_for_backend()]);

            },
            'args'                => [
                'service' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
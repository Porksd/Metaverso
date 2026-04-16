<?php

namespace VSHM\Routes\Services;

use VSHM\Functions;
use VSHM\Providers\Services;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class DataCustomGet
 *
 * @package VSHM\Routes
 */
class DataCustomGet implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'data/custom/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $service_id = $request->get_param('service_id');

                $data = \VSHM\Providers\ServiceProviderCustomData::provideBy(['service_id' => $service_id, 'provider_id' => get_current_user_id()]);

                $service = Services::provideBy(['id' => $service_id], TRUE);

                if (!$service) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __('Service not found.', 'team-booking')
                    ], 404);
                }

                $organized = Functions::organize_service_custom_data($data);

                return REST_Controller::get_ok_response(self::getPath(), [
                    'data'        => apply_filters('vshm_default_service_personal_settings',
                        $organized[ get_current_user_id() ][ $service_id ] ?? [],
                        vshm()->plugin['SLUG'],
                        $service
                    ),
                    'serviceData' => \VSHM\Providers\ServicesData::provideBy(['service_id' => $service_id]) // this is needed because of settings dependence logic
                ]);

            },
            'args'                => [
                'service_id' => [
                    'type'     => 'string',
                    'required' => FALSE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_is_provider();
            }
        ];
    }
}
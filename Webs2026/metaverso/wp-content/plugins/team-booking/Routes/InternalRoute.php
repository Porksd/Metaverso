<?php

namespace VSHM\Routes;

use VSHM\Bus\DeleteForm;
use VSHM\Bus\DeleteFormField;
use VSHM\Bus\DeleteServicePersonalProperties;
use VSHM\Bus\DeleteServiceProperties;
use VSHM\Functions;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Settings\Service\ReservationFormId;

defined('ABSPATH') || exit;

/**
 * Class InternalRoute
 *
 * @package VSHM\Routes
 */
final class InternalRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/internal/';

    public static function register(): void
    {
        REST_Controller::register_routes([
            self::$path => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    $directive = $request->get_param('operation');

                    if ($directive === 'cleanServicesData') {
                        $services            = array_column(Services::provide(), 'name', 'id');
                        $servicesData        = ServicesData::provide();
                        $servicesDataGrouped = [];
                        foreach ($servicesData as $servicesDatum) {
                            $servicesDataGrouped[ $servicesDatum['service_id'] ][] = $servicesDatum;
                        }
                        $servicesPersonalData                  = ServiceProviderCustomData::provide();
                        $servicesPersonalDataGrouped           = [];
                        $servicesPersonalDataGroupedByProvider = [];
                        foreach ($servicesPersonalData as $servicesPersonalDatum) {
                            $servicesPersonalDataGrouped[ $servicesPersonalDatum['service_id'] ][]            = $servicesPersonalDatum;
                            $servicesPersonalDataGroupedByProvider[ $servicesPersonalDatum['provider_id'] ][] = $servicesPersonalDatum;
                        }
                        $providers = array_column(ServiceProviders::provide(), 'name', 'id');

                        foreach ($servicesDataGrouped as $serviceId => $datum) {
                            if (!isset($services[ $serviceId ])) {
                                vshm()->bus->dispatch(new DeleteServiceProperties($serviceId));
                            }
                        }
                        foreach ($servicesPersonalDataGrouped as $serviceId => $datum) {
                            if (!isset($services[ $serviceId ])) {
                                vshm()->bus->dispatch(new DeleteServicePersonalProperties($serviceId));
                            }
                        }
                        foreach ($servicesPersonalDataGroupedByProvider as $providerId => $datum) {
                            if (!isset($providers[ $providerId ])) {
                                vshm()->bus->dispatch(new DeleteServicePersonalProperties(NULL, $providerId));
                            }
                        }

                        $formFieldsUsed = [];
                        $formsToCheck   = array_column(ServicesData::provideBy(['key' => ReservationFormId::ID]), 'value', 'service_id');
                        foreach (Forms::provide() as $form) {
                            if (!in_array($form['id'], $formsToCheck, TRUE)) {
                                vshm()->bus->dispatch(new DeleteForm($form['id']));
                            } else {
                                foreach ($form['fields'] as $field) {
                                    $formFieldsUsed[] = $field;
                                }
                            }
                        }

                        foreach (FormFields::provide() as $item) {
                            if (!in_array($item['id'], $formFieldsUsed, TRUE)) {
                                vshm()->bus->dispatch(new DeleteFormField($item['id']));
                            }
                        }
                    }

                    if ($directive === 'clickme') {
                    }

                    return new \WP_REST_Response(apply_filters('vshm_internal_get_response',
                        [
                            'status' => 'OK'
                        ]), 200);
                },
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ]
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
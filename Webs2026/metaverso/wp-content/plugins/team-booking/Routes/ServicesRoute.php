<?php

namespace VSHM\Routes;

use VSHM\Functions;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Settings\Location\Address;
use VSHM\Settings\Location\LocationSettingBase;
use VSHM\Settings\Service\Access;
use VSHM\Settings\Service\AllowCancellation;
use VSHM\Settings\Service\Approval;
use VSHM\Settings\Service\CancellationEmailToCustomer;
use VSHM\Settings\Service\CancellationReason;
use VSHM\Settings\Service\ConfirmationEmailToCustomer;
use VSHM\Settings\Service\Location;
use VSHM\Settings\Service\LocationAssigned;
use VSHM\Settings\Service\LocationVisibility;
use VSHM\Settings\Service\MaxUserReservations;
use VSHM\Settings\Service\PaymentRequirement;
use VSHM\Settings\Service\Picture;
use VSHM\Settings\Service\Price;
use VSHM\Settings\Service\ReminderEmailToCustomer;
use VSHM\Settings\Service\ReservationFormId;
use VSHM\Settings\Service\ShortDescription;
use VSHM\Settings\Service\ShowMap;
use VSHM\Settings\Service\ShowProvider;
use VSHM\Settings\Service\ShowProviderUrl;
use VSHM\Settings\Service\ShowTimes;
use VSHM\Settings\Service\TotalSlotTickets;
use VSHM\Settings\Service\TotalUserSlotTickets;

defined('ABSPATH') || exit;

/**
 * Class ServicesRoute
 *
 * @package VSHM\Routes
 */
final class ServicesRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/services/';

    public static function prepare_for_backend(): array
    {
        $services = \VSHM\Providers\Services::provide();

        /**
         * Those are properties useful to have straight away in the frontend instead of querying for data
         */

        $servicesData = \VSHM\Providers\ServicesData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    TotalSlotTickets::ID,
                    Price::ID,
                    LocationAssigned::ID,
                    Approval::ID,
                    PaymentRequirement::ID,
                    ReminderEmailToCustomer::ID_SEND,
                    ConfirmationEmailToCustomer::ID_SEND,
                    CancellationEmailToCustomer::ID_SEND,
                    ShortDescription::ID,
                ]
            ]
        ]);

        $servicesData = Functions::organize_service_data($servicesData);

        foreach ($services as $key => $service) {

            $serviceData = $servicesData[ $service->id ] ?? [];

            if (isset($serviceData[ TotalSlotTickets::ID ]) && $serviceData[ TotalSlotTickets::ID ] > 1) {
                $service->data[ TotalSlotTickets::ID ] = (int)$serviceData[ TotalSlotTickets::ID ];
            } else {
                $service->data[ TotalSlotTickets::ID ] = 1;
            }


            $service->data[ Price::ID ]              = $serviceData[ Price::ID ] ?? 0;
            $service->data[ PaymentRequirement::ID ] = $serviceData[ PaymentRequirement::ID ] ?? NULL;
            $service->data[ Approval::ID ]           = $serviceData[ Approval::ID ] ?? 'none';
            $service->data[ ShortDescription::ID ]   = $serviceData[ ShortDescription::ID ] ?? ShortDescription::getDefault();

            $service->data[ CancellationEmailToCustomer::ID_SEND ] = isset($serviceData[ CancellationEmailToCustomer::ID_SEND ])
                && $serviceData[ CancellationEmailToCustomer::ID_SEND ];
            $service->data[ ConfirmationEmailToCustomer::ID_SEND ] = isset($serviceData[ ConfirmationEmailToCustomer::ID_SEND ])
                && $serviceData[ ConfirmationEmailToCustomer::ID_SEND ];
            $service->data[ ReminderEmailToCustomer::ID_SEND ]     = isset($serviceData[ ReminderEmailToCustomer::ID_SEND ])
                && $serviceData[ ReminderEmailToCustomer::ID_SEND ];

            if (isset($serviceData[ LocationAssigned::ID ])) {
                $service->data[ LocationAssigned::ID ] = $serviceData[ LocationAssigned::ID ];
            }
        }

        return $services;
    }

    public static function prepare_for_frontend()
    {
        $services = \VSHM\Providers\Services::provideBy(['status' => 1]);

        /**
         * Those are properties useful to have straight away in the frontend instead of querying for data
         */

        $servicesData = \VSHM\Providers\ServicesData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    TotalSlotTickets::ID,
                    TotalUserSlotTickets::ID,
                    Price::ID,
                    Location::ID,
                    LocationVisibility::ID,
                    LocationAssigned::ID,
                    ShowMap::ID,
                    ReservationFormId::ID,
                    Access::ID,
                    Approval::ID,
                    AllowCancellation::ID,
                    CancellationReason::ID,
                    PaymentRequirement::ID,
                    MaxUserReservations::ID,
                    ShortDescription::ID,
                    ShowTimes::ID,
                    ShowProvider::ID,
                    ShowProviderUrl::ID,
                    Picture::ID,
                ]
            ]
        ]);
        $servicesData = Functions::organize_service_data($servicesData);

        $forms = array_column(\VSHM\Providers\Forms::provide(), NULL, 'id');

        foreach ($services as $key => $service) {

            $serviceData = $servicesData[ $service->id ] ?? [];

            $service->name        = apply_filters('tbk_filtered_service_name', $service->name ?? '', $service->id);
            $service->description = apply_filters('tbk_filtered_service_description', $service->description ?? '', $service->id);

            if (isset($serviceData[ TotalSlotTickets::ID ]) && $serviceData[ TotalSlotTickets::ID ] > 1) {
                $service->data[ TotalSlotTickets::ID ] = (int)$serviceData[ TotalSlotTickets::ID ];
            } else {
                $service->data[ TotalSlotTickets::ID ] = TotalSlotTickets::getDefault();
            }

            if ($service->class === 'unscheduled'
                && isset($serviceData[ MaxUserReservations::ID ], $serviceData[ Access::ID ])
                && $serviceData[ Access::ID ] === Access::LOGGED_ONLY
                && get_current_user_id()
            ) {
                $customer = Customers::provideBy(['wp_user' => get_current_user_id()], TRUE);
                if ($customer) {
                    $user_reservations = Reservations::provideBy([
                        'customerId' => $customer['id'],
                        'serviceId'  => $service->id,
                        'status'     => [
                            'operator' => 'IN',
                            'value'    => [
                                'pending',
                                'confirmed'
                            ]
                        ]
                    ]);
                    foreach ($user_reservations as $r_key => $user_reservation) {
                        if (apply_filters('tbk_is_reservation_expired', FALSE, $user_reservation)) {
                            unset($user_reservations[ $r_key ]);
                        }
                    }
                }

                $service->data['reservationsLeft'] = (int)$serviceData[ MaxUserReservations::ID ] - count($user_reservations ?? []);
            }

            if (isset($serviceData[ TotalUserSlotTickets::ID ]) && $serviceData[ TotalUserSlotTickets::ID ] > 0) {
                $service->data[ TotalUserSlotTickets::ID ] = (int)$serviceData[ TotalUserSlotTickets::ID ];
            } else {
                $service->data[ TotalUserSlotTickets::ID ] = $service->data[ TotalSlotTickets::ID ];
            }

            $service->data[ ShowTimes::ID ]          = $serviceData[ ShowTimes::ID ] ?? ShowTimes::getDefault();
            $service->data[ ShowProvider::ID ]       = $serviceData[ ShowProvider::ID ] ?? ShowProvider::getDefault();
            $service->data[ ShowProviderUrl::ID ]    = $serviceData[ ShowProviderUrl::ID ] ?? ShowProviderUrl::getDefault();
            $service->data[ Price::ID ]              = $serviceData[ Price::ID ] ?? Price::getDefault();
            $service->data[ PaymentRequirement::ID ] = $serviceData[ PaymentRequirement::ID ] ?? PaymentRequirement::getDefault();
            $service->data[ Access::ID ]             = $serviceData[ Access::ID ] ?? NULL;
            $service->data[ Picture::ID ]            = $serviceData[ Picture::ID ] ?? Picture::getDefault();
            $service->data[ Approval::ID ]           = isset($serviceData[ Approval::ID ]) && $serviceData[ Approval::ID ] !== Approval::NONE;

            if (isset($serviceData[ ShortDescription::ID ])) {
                $service->data[ ShortDescription::ID ] = apply_filters('tbk_filtered_service_short_description', $serviceData[ ShortDescription::ID ], $service->id);
            } else {
                $service->data[ ShortDescription::ID ] = ShortDescription::getDefault();
            }

            if (isset($serviceData[ ReservationFormId::ID ], $forms[ $serviceData[ ReservationFormId::ID ] ])) {
                $form              = $forms[ $serviceData[ ReservationFormId::ID ] ];
                $price_starts_from = FALSE;
                $fields            = \VSHM\Providers\FormFields::provideByMultiple('field_id', $form['fields']);
                foreach ($fields as $field) {
                    switch ($field['type']) {
                        case 'select':
                        case 'radio':
                            foreach ($field['data']['options'] as $option) {
                                if (isset($option['price_increment']) && $option['price_increment'] && (string)$option['price_increment'] !== '0') {
                                    $price_starts_from = TRUE;
                                    break 3;
                                }
                            }
                            break;
                        case 'checkbox':
                            if (isset($field['data']['price_increment']) && $field['data']['price_increment'] && (string)$field['data']['price_increment'] !== '0') {
                                $price_starts_from = TRUE;
                                break 2;
                            }
                            break;
                    }
                }
                $service->data['priceStarts'] = $price_starts_from;
            }

            if (isset($serviceData[ LocationVisibility::ID ], $serviceData[ Location::ID ], $serviceData[ LocationAssigned::ID ])
                && $serviceData[ LocationVisibility::ID ]
                && $serviceData[ Location::ID ] === Location::FIXED
            ) {
                try {
                    $locationAddress               = vshm()->settings->getProperty(
                        Address::ID,
                        LocationSettingBase::CONTEXT,
                        $serviceData[ LocationAssigned::ID ]
                    );
                    $service->data[ Location::ID ] = $locationAddress;
                } catch (\UnexpectedValueException $e) {
                    $service->data[ Location::ID ] = NULL;
                }

            } else {
                $service->data[ Location::ID ] = NULL;
            }

            if (isset($serviceData[ ShowMap::ID ])) {
                $service->data[ ShowMap::ID ] = (bool)$serviceData[ ShowMap::ID ];
            } else {
                $service->data[ ShowMap::ID ] = ShowMap::getDefault();
            }

            if (isset($serviceData[ AllowCancellation::ID ])) {
                $service->data[ AllowCancellation::ID ] = (bool)$serviceData[ AllowCancellation::ID ];
            } else {
                $service->data[ AllowCancellation::ID ] = AllowCancellation::getDefault();
            }

            if (isset($serviceData[ CancellationReason::ID ])) {
                $service->data[ CancellationReason::ID ] = (bool)$serviceData[ CancellationReason::ID ];
            } else {
                $service->data[ CancellationReason::ID ] = CancellationReason::getDefault();
            }
        }

        return apply_filters('tbk_maybe_apply_promotions_to_unscheduled_services', $services);
    }


    public static function register(): void
    {
        REST_Controller::register_routes([
            \VSHM\Routes\Services\Get::getPath()            => \VSHM\Routes\Services\Get::get(),
            \VSHM\Routes\Services\Remove::getPath()         => \VSHM\Routes\Services\Remove::get(),
            \VSHM\Routes\Services\RemoveMulti::getPath()    => \VSHM\Routes\Services\RemoveMulti::get(),
            \VSHM\Routes\Services\Add::getPath()            => \VSHM\Routes\Services\Add::get(),
            \VSHM\Routes\Services\CloneService::getPath()   => \VSHM\Routes\Services\CloneService::get(),
            \VSHM\Routes\Services\ApplyToAll::getPath()     => \VSHM\Routes\Services\ApplyToAll::get(),
            \VSHM\Routes\Services\DataGet::getPath()        => \VSHM\Routes\Services\DataGet::get(),
            \VSHM\Routes\Services\DataCustomGet::getPath()  => \VSHM\Routes\Services\DataCustomGet::get(),
            \VSHM\Routes\Services\DataSave::getPath()       => \VSHM\Routes\Services\DataSave::get(),
            \VSHM\Routes\Services\DataCustomSave::getPath() => \VSHM\Routes\Services\DataCustomSave::get(),
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
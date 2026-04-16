<?php

namespace VSHM\Modules;

use VSHF\Bus\CommandInterface;
use VSHM\Bus\ApproveReservation;
use VSHM\Bus\CancelReservation;
use VSHM\Bus\ConfirmReservation;
use VSHM\Bus\CreateReservation;
use VSHM\Bus\DenyReservation;
use VSHM\Bus\SendAdminBookingCancellationEmail;
use VSHM\Bus\SendAdminBookingNotificationEmail;
use VSHM\Bus\SendCustomerBookingCancellationEmail;
use VSHM\Bus\SendCustomerBookingConfirmationEmail;
use VSHM\Bus\SendCustomerBookingReminderEmail;
use VSHM\Bus\SendProviderBookingCancellationEmail;
use VSHM\Bus\SendProviderBookingConfirmationEmail;
use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Functions;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Providers\Customers;
use VSHM\Providers\FormEntries;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\ActionLinksRoute;
use VSHM\Settings\Location\Address;
use VSHM\Settings\Location\LocationSettingBase;
use VSHM\Settings\Location\Name;
use VSHM\Settings\Reservation\CancellationOrDenyReason;
use VSHM\Settings\Reservation\CustomerTimezone;
use VSHM\Settings\Reservation\Tickets;
use VSHM\Settings\Service\Approval;
use VSHM\Settings\Service\CancellationEmailToAdmin;
use VSHM\Settings\Service\CancellationEmailToCustomer;
use VSHM\Settings\Service\ConfirmationEmailToAdmin;
use VSHM\Settings\Service\ConfirmationEmailToCustomer;
use VSHM\Settings\Service\LocationAssigned;
use VSHM\Settings\Service\Personal_CancellationEmailToProvider;
use VSHM\Settings\Service\Personal_ConfirmationEmailToProvider;
use VSHM\Settings\Service\ReminderEmailToCustomer;
use VSHM\Settings\Service\ReservationFormId;
use VSHM\Settings\Service\ShortDescription;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Notifications
 *
 * @author VonStroheim
 */
class Notifications
{

    /**
     * @var string
     */
    public static $route_path = '/notifications/';

    public static function bootstrap(): void
    {
        add_filter('tbk_service_personal_settings_items', [self::class, 'service_personal_settings_items']);
        add_filter('tbk_service_notifications_items', [self::class, 'service_notifications_items']);
        add_filter('tbk_template_hooks_editor', [self::class, 'templateHooks'], 10, 3);
        add_action('vshm_dispatched_CreateReservation', [self::class, 'notificationSend'], 99);
        add_action('vshm_dispatched_ConfirmReservation', [self::class, 'notificationSend'], 99);
        add_action('vshm_dispatched_ApproveReservation', [self::class, 'notificationSend'], 99);
        add_action('vshm_dispatched_CancelReservation', [self::class, 'cancellationSend'], 99);
        add_action('vshm_dispatched_DenyReservation', [self::class, 'cancellationSend'], 99);

        if (Tools::is_request('cron')) {
            add_action('tbk_hourly_cron', [self::class, 'customerReminderEmail']);
        }

        REST_Controller::register_routes([
            self::$route_path . 'get/hooks/'                => [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => [self::class, 'getTemplateHooks'],
                'args'     => [
                    'notificationId' => [
                        'type'     => 'string',
                        'required' => TRUE
                    ],
                    'serviceId'      => [
                        'type'     => 'string',
                        'required' => TRUE
                    ],
                ]
            ],
            self::$route_path . 'get/templates/'            => [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => [self::class, 'getTemplates'],
                'args'     => [
                    'notificationId' => [
                        'type' => 'string'
                    ],
                    'serviceId'      => [
                        'type' => 'string'
                    ],
                ]
            ],
            self::$route_path . 'manual/send/confirmation/' => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => static function (\WP_REST_Request $request) {
                    vshm()->bus->dispatch(new SendCustomerBookingConfirmationEmail(
                        $request->get_param('reservationId')
                    ), vshm()->bus::AGENT_USER, get_current_user_id());

                    return new \WP_REST_Response(apply_filters('vshm_notifications_manual_send_confirmation_response',
                        [
                            'status' => 'OK'
                        ]), 200);
                },
                'args'                => [
                    'reservationId' => [
                        'type'     => 'string',
                        'required' => TRUE
                    ]
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider(TRUE);
                }
            ],
            self::$route_path . 'manual/send/cancellation/' => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => static function (\WP_REST_Request $request) {
                    vshm()->bus->dispatch(new SendCustomerBookingCancellationEmail(
                        $request->get_param('reservationId')
                    ), vshm()->bus::AGENT_USER, get_current_user_id());

                    return new \WP_REST_Response(apply_filters('vshm_notifications_manual_send_cancellation_response',
                        [
                            'status' => 'OK'
                        ]), 200);
                },
                'args'                => [
                    'reservationId' => [
                        'type'     => 'string',
                        'required' => TRUE
                    ]
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider(TRUE);
                }
            ],
            self::$route_path . 'manual/send/reminder/'     => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => static function (\WP_REST_Request $request) {
                    vshm()->bus->dispatch(new SendCustomerBookingReminderEmail(
                        $request->get_param('reservationId')
                    ), vshm()->bus::AGENT_USER, get_current_user_id());

                    return new \WP_REST_Response(apply_filters('vshm_notifications_manual_send_reminder_response',
                        [
                            'status' => 'OK'
                        ]), 200);
                },
                'args'                => [
                    'reservationId' => [
                        'type'     => 'string',
                        'required' => TRUE
                    ]
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider(TRUE);
                }
            ]
        ]);
    }

    /**
     * @param CommandInterface $command
     *
     * @return void
     */
    protected static function _maybe_send_email(CommandInterface $command): void
    {
        /**
         * @var $command SendCustomerBookingConfirmationEmail|SendCustomerBookingCancellationEmail|SendCustomerBookingReminderEmail|SendAdminBookingNotificationEmail|SendAdminBookingCancellationEmail|SendProviderBookingConfirmationEmail|SendProviderBookingCancellationEmail
         */
        $reservation = Reservations::provideBy(['id' => $command->getReservationId()], TRUE);

        if (!$reservation) {
            return;
        }

        switch (TRUE) {
            case $command instanceof SendCustomerBookingConfirmationEmail:
                $key = ConfirmationEmailToCustomer::ID_SEND;
                break;
            case $command instanceof SendCustomerBookingCancellationEmail:
                $key = CancellationEmailToCustomer::ID_SEND;
                break;
            case $command instanceof SendCustomerBookingReminderEmail:
                $key = ReminderEmailToCustomer::ID_SEND;
                break;
            case $command instanceof SendAdminBookingNotificationEmail:
                $key = ConfirmationEmailToAdmin::ID_SEND;
                break;
            case $command instanceof SendAdminBookingCancellationEmail:
                $key = CancellationEmailToAdmin::ID_SEND;
                break;
            case $command instanceof SendProviderBookingConfirmationEmail:
                $key = Personal_ConfirmationEmailToProvider::ID_SEND;
                break;
            case $command instanceof SendProviderBookingCancellationEmail:
                $key = Personal_CancellationEmailToProvider::ID_SEND;
                break;
            default:
                $key = FALSE;
                break;
        }

        if (!$key) {
            return;
        }

        switch (TRUE) {
            case $command instanceof SendProviderBookingConfirmationEmail:
            case $command instanceof SendProviderBookingCancellationEmail:
                $send = ServiceProviderCustomData::provideBy([
                    'service_id'  => $reservation->serviceId,
                    'provider_id' => $reservation->providerId,
                    'key'         => $key
                ], TRUE);
                break;
            default:
                $send = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => $key], TRUE);
                break;
        }

        if ($send) {
            vshm()->bus->dispatch($command);
            if ($command instanceof SendCustomerBookingReminderEmail) {
                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservation->id, 'reminderSent', 1));
            }
        }
    }

    /**
     * @return void
     */
    public static function customerReminderEmail(): void
    {
        $reservations = Reservations::provideBy([
            'data'   => [
                'reminderSent' => [
                    'operator_key'   => '=',
                    'operator_value' => '!=',
                    'value'          => 1
                ]
            ],
            'start'  => [
                'operator' => '>=',
                'value'    => time()
            ],
            'status' => 'confirmed'
        ]);
        foreach ($reservations as $reservation) {
            $days_before = (int)ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => ReminderEmailToCustomer::ID_DAYS_BEFORE], TRUE);
            if ($days_before && $reservation->start - time() <= ($days_before * DAY_IN_SECONDS)) {
                self::_maybe_send_email(new SendCustomerBookingReminderEmail($reservation->id));
            }
        }
    }

    /**
     * @param CancelReservation|DenyReservation $command
     *
     * @return void
     */
    public static function cancellationSend($command): void
    {
        if ($command instanceof CancelReservation) {
            self::_maybe_send_email(new SendCustomerBookingCancellationEmail($command->getId()));

            // TODO: send to admin only if customer has cancelled
            self::_maybe_send_email(new SendAdminBookingCancellationEmail($command->getId()));

            self::_maybe_send_email(new SendProviderBookingCancellationEmail($command->getId()));

        } else if ($command instanceof DenyReservation) {

            // TODO: create a deny email
            self::_maybe_send_email(new SendCustomerBookingCancellationEmail($command->getId()));
        }
    }

    /**
     * @param CreateReservation|ApproveReservation|ConfirmReservation $command
     *
     * @return void
     */
    public static function notificationSend($command): void
    {
        if ($command instanceof CreateReservation) {
            if ($command->getStatus() === 'confirmed') {
                self::_maybe_send_email(new SendCustomerBookingConfirmationEmail($command->getId()));
            }

            $approval = ServicesData::provideBy(['service_id' => $command->getServiceId(), 'key' => Approval::ID], TRUE);

            if (!($approval === Approval::NONE && $command->getStatus() === 'pending')) {

                // Send to admin if not pending payment
                self::_maybe_send_email(new SendAdminBookingNotificationEmail($command->getId()));

                if ($approval !== Approval::ADMIN || $command->getStatus() === 'confirmed') {
                    self::_maybe_send_email(new SendProviderBookingConfirmationEmail($command->getId()));
                }

            } else {
                // reservation is pending payment
            }

        } else if ($command instanceof ConfirmReservation) {
            // Only Administrators can confirm
            self::_maybe_send_email(new SendCustomerBookingConfirmationEmail($command->getId()));
            self::_maybe_send_email(new SendProviderBookingConfirmationEmail($command->getId()));
            self::_maybe_send_email(new SendAdminBookingNotificationEmail($command->getId()));

        } else if ($command instanceof ApproveReservation) {
            self::_maybe_send_email(new SendCustomerBookingConfirmationEmail($command->getId()));
            self::_maybe_send_email(new SendProviderBookingConfirmationEmail($command->getId()));

        }

    }

    public static function getTemplates(\WP_REST_Request $request): \WP_REST_Response
    {
        $type      = $request->get_param('notificationId');
        $serviceId = $request->get_param('serviceId');
        $templates = [];

        switch ($type) {
            case ConfirmationEmailToCustomer::ID_BODY:
                $templates = ServicesData::provideBy(['key' => ConfirmationEmailToCustomer::ID_BODY]);
                break;
            case CancellationEmailToCustomer::ID_BODY:
                $templates = ServicesData::provideBy(['key' => CancellationEmailToCustomer::ID_BODY]);
                break;
            case ReminderEmailToCustomer::ID_BODY:
                $templates = ServicesData::provideBy(['key' => ReminderEmailToCustomer::ID_BODY]);
                break;
            case ConfirmationEmailToAdmin::ID_BODY:
                $templates = ServicesData::provideBy(['key' => ConfirmationEmailToAdmin::ID_BODY]);
                break;
            case CancellationEmailToAdmin::ID_BODY:
                $templates = ServicesData::provideBy(['key' => CancellationEmailToAdmin::ID_BODY]);
                break;
            case Personal_ConfirmationEmailToProvider::ID_BODY:
                $templates = ServiceProviderCustomData::provideBy([
                    'key'         => Personal_ConfirmationEmailToProvider::ID_BODY,
                    'provider_id' => get_current_user_id()
                ]);
                break;
            case Personal_CancellationEmailToProvider::ID_BODY:
                $templates = ServiceProviderCustomData::provideBy([
                    'key'         => Personal_CancellationEmailToProvider::ID_BODY,
                    'provider_id' => get_current_user_id()
                ]);
                break;
        }

        $return = [];

        foreach ($templates as $template) {
            if ($serviceId === $template['service_id']) {
                continue;
            }
            $service  = Services::provideBy(['id' => $template['service_id']], TRUE);
            $return[] = [
                'title'       => sprintf(
                /* translators: %s: name of the service */
                    __('Duplicate from %s', 'team-booking'),
                    $service->name
                ),
                'description' => '',
                'content'     => $template['value'],
            ];
        }

        return REST_Controller::get_response('notifications_get_templates', $return);
    }

    public static function getTemplateHooks(\WP_REST_Request $request): \WP_REST_Response
    {
        $type      = $request->get_param('notificationId');
        $serviceId = $request->get_param('serviceId');

        return REST_Controller::get_ok_response('notifications_get_template_hooks', [
            'hooks' => apply_filters('tbk_template_hooks_editor', [], $type, $serviceId)
        ]);
    }

    public static function service_notifications_items($items)
    {
        $items[] = [
            'id'    => 'customer',
            'label' => __('To the customer', 'team-booking'),
            'items' => [
                \VSHM\Settings\Service\ConfirmationEmailToCustomer::getBackendElement()->get_structure(),
                \VSHM\Settings\Service\CancellationEmailToCustomer::getBackendElement()->get_structure(),
                \VSHM\Settings\Service\ReminderEmailToCustomer::getBackendElement()->get_structure(),
            ]
        ];

        $items[] = [
            'id'    => 'admin',
            'label' => __('To the admin', 'team-booking'),
            'items' => [
                \VSHM\Settings\Service\ConfirmationEmailToAdmin::getBackendElement()->get_structure(),
                \VSHM\Settings\Service\CancellationEmailToAdmin::getBackendElement()->get_structure(),
            ]
        ];

        return $items;
    }

    public static function service_personal_settings_items($items)
    {
        $items[] = [
            'id'    => 'notifications',
            'label' => __('Personal notifications', 'team-booking'),
            'items' => [
                \VSHM\Settings\Service\Personal_ConfirmationEmailToProvider::getBackendElement()->get_structure(),
                \VSHM\Settings\Service\Personal_CancellationEmailToProvider::getBackendElement()->get_structure(),
            ]
        ];

        return $items;
    }

    /**
     * @param string      $reservation_id
     * @param string|null $notificationType
     *
     * @return array
     */
    public static function prepare_placeholders(string $reservation_id, string $notificationType = NULL): array
    {
        $reservation = Reservations::provideBy(['id' => $reservation_id], TRUE);
        $customer    = Customers::provideBy(['id' => $reservation->customerId], TRUE);
        $service     = Services::provideBy(['id' => $reservation->serviceId], TRUE);
        $provider    = ServiceProviders::provideBy(['id' => $reservation->providerId], TRUE);

        $status_link = REST_Controller::get_root_rest_url() . ActionLinksRoute::getPath() . 'reservations/';

        // TODO
        #$activeFields = $service->getMeta('formFieldsActive') ?: [];


        $serviceData = Functions::organize_service_data(ServicesData::provideBy(['service_id' => $service->id]))[ $service->id ];

        /**
         * Cached data may be incomplete at this point, making sure to dump it
         */
        $reservationData = array_column(ReservationsData::provideBy(
            ['reservation_id' => $reservation_id], FALSE, FALSE, FALSE
        ), 'value', 'key');

        $customerTimezone = ReservationsData::provideBy(['reservation_id' => $reservation_id, 'key' => CustomerTimezone::ID], TRUE);

        if (!$customerTimezone) {
            $customerTimezone = wp_timezone_string();
        }

        try {
            $customerTimezone = new \DateTimeZone($customerTimezone);
        } catch (\Exception $e) {
            $customerTimezone = new \DateTimeZone('UTC');
        }

        $toCustomer = in_array($notificationType, [
            ReminderEmailToCustomer::ID,
            ConfirmationEmailToCustomer::ID,
            CancellationEmailToCustomer::ID
        ], TRUE);

        $start = DateTimeTbk::createFromFormatSilently(
            'U', $reservation->start, $toCustomer ? $customerTimezone : NULL
        );
        $end   = DateTimeTbk::createFromFormatSilently(
            'U', $reservation->end, $toCustomer ? $customerTimezone : NULL
        );

        $statusLink = add_query_arg([
            'tbk-hash' => md5($reservation_id . $customer['access_token']),
            'tbk-id'   => $reservation_id
        ], $status_link);

        $preparedValues = [
            'status_link'                     => $statusLink,
            'cancellation_link'               => $statusLink, // legacy
            'pay_link'                        => $statusLink, // legacy
            'ics_link'                        => $statusLink, // legacy
            'decline_link'                    => $statusLink, // legacy
            'approve_link'                    => $statusLink, // legacy
            'manage_link'                     => $statusLink, // legacy
            'customer::name'                  => $customer['name'],
            'customer::email'                 => $customer['email'],
            'customer::phone'                 => $customer['phone'],
            'service::name'                   => $service->name,
            'service_name'                    => $service->name, // legacy
            'service::description'            => $service->description,
            'service::shortDescription'       => $serviceData[ ShortDescription::ID ] ?? ShortDescription::getDefault(),
            'provider::email'                 => $provider['email'],
            'provider::name'                  => $provider['name'],
            'coworker_name'                   => $provider['name'], // legacy
            'provider_name'                   => $provider['name'], // legacy
            'provider::url'                   => $provider['url'],
            'coworker_url'                    => $provider['url'], // legacy
            'provider_url'                    => $provider['url'], // legacy
            'reservation::startTime'          => $start->localized_time(),
            'start_time'                      => $start->localized_time(), // legacy
            'reservation::startDate'          => $start->localized_date(),
            'start_date'                      => $start->localized_date(),// legacy
            'start_datetime'                  => $start->localized_date_time(), // legacy
            'reservation::endTime'            => $end->localized_time(),
            'end_time'                        => $end->localized_time(), // legacy
            'reservation::endDate'            => $end->localized_date(),
            'end_date'                        => $end->localized_date(), // legacy
            'end_datetime'                    => $end->localized_date_time(), // legacy
            'reservation::timezone'           => $customerTimezone->getName(),
            'timezone'                        => $customerTimezone->getName(),// legacy
            'reservation::duration'           => Tools::human_time_diff($reservation->start, $reservation->end),
            'reservation::price'              => Functions::reservation_get_final_price($reservation->id, TRUE),
            'total_price'                     => Functions::reservation_get_final_price($reservation->id, TRUE), // legacy
            'reservation::tickets'            => $reservationData[ Tickets::ID ] ?? Tickets::getDefault(),
            'tickets_quantity'                => $reservationData[ Tickets::ID ] ?? Tickets::getDefault(), // legacy
            'reservation::id'                 => $reservation->db_id,
            'reservation_id'                  => $reservation->db_id, // legacy,
            'reservation::uid'                => $reservation_id,
            'post_id'                         => '', // legacy,
            'post_title'                      => '', // legacy,
            'reason'                          => $reservationData[ CancellationOrDenyReason::ID ] ?? CancellationOrDenyReason::getDefault(),  // legacy,
            'cancellation_reason'             => $reservationData[ CancellationOrDenyReason::ID ] ?? CancellationOrDenyReason::getDefault(),  // legacy,
            'reservation::cancellationReason' => $reservationData[ CancellationOrDenyReason::ID ] ?? CancellationOrDenyReason::getDefault(),
        ];

        $locationId = FALSE;
        if (isset($reservationData[ \VSHM\Settings\Reservation\LocationOverride::ID ])) {
            $preparedValues['reservation::locationAddress'] = $reservationData[ \VSHM\Settings\Reservation\LocationOverride::ID ];
            $preparedValues['reservation::locationName']    = ''; // TODO: get the name if already existent
        } elseif (isset($reservationData[ \VSHM\Settings\Reservation\Location::ID ])) {
            $locationId = $reservationData[ \VSHM\Settings\Reservation\Location::ID ];
        } elseif (isset($serviceData[ LocationAssigned::ID ])) {
            $locationId = $serviceData[ LocationAssigned::ID ];
        }

        if ($locationId) {
            try {
                $locationName = vshm()->settings->getProperty(
                    Name::ID,
                    LocationSettingBase::CONTEXT,
                    $locationId
                );

                $locationAddress = vshm()->settings->getProperty(
                    Address::ID,
                    LocationSettingBase::CONTEXT,
                    $locationId
                );

                $preparedValues['reservation::locationAddress'] = $locationAddress;
                $preparedValues['reservation::locationName']    = $locationName;

            } catch (\UnexpectedValueException $e) {
                error_log(sprintf(
                    'While sending notification for reservation %s, location %s  was not found.',
                    $reservation_id,
                    $locationId
                ));
            }
        }

        if (isset($serviceData[ ReservationFormId::ID ])) {
            $form = Forms::provideBy(['id' => $serviceData[ ReservationFormId::ID ]], TRUE);
            if ($form) {
                $entries = array_column(FormEntries::provideBy(['reservationId' => $reservation_id]), NULL, 'id');
                foreach ($form['active'] as $fieldId) {
                    $field = FormFields::provideBy(['id' => $fieldId], TRUE);
                    if (!$field) {
                        continue;
                    }

                    if ($field['type'] === 'checkbox') {
                        $preparedValues[ $field['hook'] ] = filter_var($entries[ $fieldId ]['value'] ?? FALSE, FILTER_VALIDATE_BOOLEAN)
                            ? __('Selected', 'team-booking')
                            : __('Not selected', 'team-booking');
                    } elseif ($field['type'] === 'select' || $field['type'] === 'radio') {
                        if (!isset($entries[ $fieldId ])) {
                            $preparedValues[ $field['hook'] ] = __('Not selected', 'team-booking');
                        } else {
                            $preparedValues[ $field['hook'] ] = $field['data']['options'][ $entries[ $fieldId ]['value'] ]['value']
                                ?? __('Not selected', 'team-booking');
                        }

                    } else {
                        $preparedValues[ $field['hook'] ] = $entries[ $fieldId ]['value'] ?? '';
                    }
                }
            }
        }

        return apply_filters('tbk_notification_templates', $preparedValues, $reservation_id, $notificationType);
    }

    /**
     * Find and replace hooks in a string.
     *
     * Hooks must be in the form: [hook] or [hook]SOME TEXT[/hook]
     *
     * @param mixed $string    String with hooks
     * @param array $variables Hooks values
     *
     * @return string String with hooks replaced by values
     */
    public static function find_and_replace_hooks($string, array $variables): string
    {
        // Lowercase conversion
        $convertedVariables = [];
        foreach ($variables as $array_key => $array_value) {
            $convertedVariables[ strtolower($array_key) ] = $array_value;
        }

        // Enclosure hooks (WordPress 4.4.0+ only)
        $pattern = get_shortcode_regex(apply_filters('tbk_email_link_hooks', ['cancellation_link', 'decline_link', 'approve_link', 'pay_link', 'ics_link', 'status_link']));
        $string  = preg_replace_callback("/$pattern/s", static function ($matches) use ($convertedVariables) {
            if (isset($convertedVariables[ strtolower(trim($matches[2], '[]')) ])) {
                $link = $convertedVariables[ strtolower(trim($matches[2], '[]')) ];
                unset($convertedVariables[ strtolower(trim($matches[2], '[]')) ]);

                return '<a href="' . esc_url($link) . '">' . $matches[5] . '</a>';
            }

            return $matches[0];
        }, $string);

        // Single hooks
        $regex  = "/(\[.*?\])/";
        $return = preg_replace_callback($regex, static function ($matches) use ($convertedVariables) {


            $hook = strtolower(trim($matches[1], '[]'));

            $filteredValue = apply_filters('tbk_email_hook_replace', $convertedVariables[ $hook ] ?? $matches[1], $hook, $convertedVariables);

            if (empty($filteredValue) && ($convertedVariables[ $hook ] ?? FALSE)) {
                // This is meant to wipe dynamic hooks when data is not present
                $filteredValue = '';
            }

            return $filteredValue;
        }, $string);

        return $return;
    }

    public static function templateHooks($hooks, $notificationType, $serviceId)
    {

        $service = Services::provideBy(['id' => $serviceId], TRUE);

        $hooks[] = [
            'value'        => 'customer::name',
            'label'        => __('Name', 'team-booking'),
            'context'      => 'customer',
            'contextLabel' => __('Customer', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'customer::email',
            'label'        => __('Email', 'team-booking'),
            'context'      => 'customer',
            'contextLabel' => __('Customer', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'customer::phone',
            'label'        => __('Phone', 'team-booking'),
            'context'      => 'customer',
            'contextLabel' => __('Customer', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'service::name',
            'label'        => __('Name', 'team-booking'),
            'context'      => 'service',
            'contextLabel' => __('Service', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'service::description',
            'label'        => __('Description', 'team-booking'),
            'context'      => 'service',
            'contextLabel' => __('Service', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'service::shortDescription',
            'label'        => __('Short description', 'team-booking'),
            'context'      => 'service',
            'contextLabel' => __('Service', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::startTime',
            'label'        => __('Start time', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::startDate',
            'label'        => __('Start date', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::endTime',
            'label'        => __('End time', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::endDate',
            'label'        => __('End date', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::timezone',
            'label'        => __('Timezone', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::locationName',
            'label'        => __('Location (name)', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::locationAddress',
            'label'        => __('Location (address)', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::duration',
            'label'        => __('Duration', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::price',
            'label'        => __('Price', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::tickets',
            'label'        => __('Tickets', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::cancellationReason',
            'label'        => __('Cancellation reason', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'reservation::id',
            'label'        => __('Id', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'provider::name',
            'label'        => __('Name', 'team-booking'),
            'context'      => 'provider',
            'contextLabel' => __('Provider', 'team-booking')
        ];
        $hooks[] = [
            'value'        => 'provider::email',
            'label'        => __('Email', 'team-booking'),
            'context'      => 'provider',
            'contextLabel' => __('Provider', 'team-booking')
        ];

        // @NEXT
        if ($notificationType === 'reschedule') {
            $hooks[] = [
                'value'        => 'reservation::startTime::old',
                'label'        => __('Start time (old)', 'team-booking'),
                'context'      => 'reservation',
                'contextLabel' => __('Reservation', 'team-booking')
            ];
            $hooks[] = [
                'value'        => 'reservation::startDate::old',
                'label'        => __('Start date (old)', 'team-booking'),
                'context'      => 'reservation',
                'contextLabel' => __('Reservation', 'team-booking')
            ];
            $hooks[] = [
                'value'        => 'reservation::endTime::old',
                'label'        => __('End time (old)', 'team-booking'),
                'context'      => 'reservation',
                'contextLabel' => __('Reservation', 'team-booking')
            ];
            $hooks[] = [
                'value'        => 'reservation::endDate::old',
                'label'        => __('End date (old)', 'team-booking'),
                'context'      => 'reservation',
                'contextLabel' => __('Reservation', 'team-booking')
            ];
            $hooks[] = [
                'value'        => 'reservation::duration::old',
                'label'        => __('Duration (old)', 'team-booking'),
                'context'      => 'reservation',
                'contextLabel' => __('Reservation', 'team-booking')
            ];
        }

        $formId = ServicesData::provideBy(['service_id' => $serviceId, 'key' => ReservationFormId::ID], TRUE);
        if ($formId) {
            $form = Forms::provideBy(['id' => $formId], TRUE);
            if ($form) {
                foreach ($form['active'] as $fieldId) {
                    $field = FormFields::provideBy(['id' => $fieldId], TRUE);
                    if ($field) {
                        $hooks[] = [
                            'value'        => $field['hook'],
                            'label'        => $field['label'],
                            'context'      => 'form',
                            'contextLabel' => __('Form', 'team-booking')
                        ];
                    }
                }
            }
        }

        return $hooks;
    }

}
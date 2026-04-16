<?php

namespace VSHM;

use VSHM\Bus\CleanExpiredReservations;
use VSHM\Bus\CleanFiles;
use VSHM\Bus\CleanLogs;
use VSHM\Bus\ConfirmReservation;
use VSHM\Bus\CreateForm;
use VSHM\Bus\CreateFormField;
use VSHM\Bus\CreateService;
use VSHM\Bus\DeleteForm;
use VSHM\Bus\DeleteFormField;
use VSHM\Bus\DeleteReservation;
use VSHM\Bus\DeleteService;
use VSHM\Bus\DeleteServicePersonalProperties;
use VSHM\Bus\DeleteServiceProperties;
use VSHM\Bus\RegisterPayment;
use VSHM\Bus\UpdateOrCreateServicePersonalProperty;
use VSHM\Bus\UpdateOrCreateServiceProperty;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Providers\Customers;
use VSHM\Providers\FormEntries;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;
use VSHM\Providers\Objects\Reservation;
use VSHM\Providers\Objects\Service;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\Settings\AllowedAdminWpRoles;
use VSHM\Settings\AllowedServiceProviderWpRoles;
use VSHM\Settings\CurrencyCode;
use VSHM\Settings\CurrencyFormat;
use VSHM\Settings\PaymentPendingTime;
use VSHM\Settings\PrepopulateBookingForm;
use VSHM\Settings\PriceDecimals;
use VSHM\Settings\PriceFormat;
use VSHM\Settings\Promotion\CouponMode;
use VSHM\Settings\Promotion\DiscountType;
use VSHM\Settings\Promotion\MaximumUses;
use VSHM\Settings\Promotion\PromotionType;
use VSHM\Settings\Reservation\Discount;
use VSHM\Settings\Reservation\Tickets;
use VSHM\Settings\Service\Approval;
use VSHM\Settings\Service\PaymentRequirement;
use VSHM\Settings\Service\ReservationFormId;
use Whitecube\Price\Modifier;
use Whitecube\Price\Price;

defined('ABSPATH') || exit;

/**
 * Class Functions
 */
class Functions
{

    public static function register(): void
    {

        if (Tools::is_request('cron')) {
            add_action('tbk_daily_cron', [self::class, 'daily_jobs']);
            add_action('tbk_hourly_cron', [self::class, 'hourly_jobs']);
            add_action('tbk_weekly_cron', [self::class, 'weekly_jobs']);
        }

        add_action('vshm_dispatched_CreateService', [self::class, 'post_create_service']);
        add_action('vshm_dispatched_DeleteService', [self::class, 'post_delete_service']);
        add_action('vshm_dispatching_DeleteForm', [self::class, 'before_delete_form']);
        add_action('vshm_dispatched_DeleteFormField', [self::class, 'after_delete_form_field']);
        add_action('vshm_dispatched_RegisterPayment', [self::class, 'after_payment_received']);
        add_filter('tbk_determine_customer_name', [self::class, 'determine_customer_name'], 10, 2);
        add_filter('tbk_determine_customer_phone', [self::class, 'determine_customer_phone'], 10, 2);
        add_filter('tbk_determine_slot_id', [self::class, 'determine_slot_id'], 10, 6);
        add_filter('tbk_reservations_to_be_computed_by_slots', [self::class, 'computed_reservations'], 10, 2);
        add_filter('tbk_is_reservation_expired', [self::class, '_is_reservation_expired'], 10, 3);
        add_filter('tbk_maybe_apply_promotions', [self::class, 'maybe_apply_promotions']);
        add_filter('tbk_maybe_apply_promotions_to_unscheduled_services', [self::class, 'maybe_add_promotions_to_frontend_unscheduled_service']);
        add_filter('tbk_is_promotion_applicable', [self::class, 'is_promotion_applicable'], 10, 5);
        add_filter('tbk_is_coupon_valid', [self::class, 'is_coupon_valid'], 10, 3);

        if (!Tools::is_request('cron')) {
            add_filter('wp_privacy_personal_data_exporters', [self::class, 'personal_data_exporter_register']);
            add_filter('wp_privacy_personal_data_erasers', [self::class, 'personal_data_eraser_register']);
            add_filter('tbk_populating_form', [self::class, 'populating_form']);
            add_filter('tbk_populating_form_frontend', [self::class, 'populating_form_frontend']);
            add_filter('vshm_backend_user_prefs', [self::class, 'get_user_prefs']);
            add_filter('plugin_row_meta', [self::class, 'plugin_row_meta'], 10, 4);
        }
    }

    public static function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status)
    {
        if (strpos($plugin_file, 'team-booking.php') !== FALSE) {
            foreach ($plugin_meta as $key => $item) {
                if (strpos($item, 'plugin-information') !== FALSE) {
                    unset($plugin_meta[ $key ]);
                }
            }
        }

        return $plugin_meta;
    }

    /**
     * Checks whether the coupon belongs to the promotion.
     *
     * It doesn't validate applicability, see @is_promotion_applicable for that.
     *
     * @param bool   $valid
     * @param array  $promotion
     * @param string $coupon
     *
     * @return bool
     */
    public static function is_coupon_valid(bool $valid, array $promotion, string $coupon): bool
    {
        if ($promotion[ PromotionType::ID ] !== PromotionType::COUPON) {
            return $valid;
        }

        $now = time();

        if ($promotion['promotionPeriod_start'] > $now
            || $promotion['promotionPeriod_end'] < $now) {
            return FALSE;
        }

        if ($promotion['data'][ CouponMode::ID ] === CouponMode::LIST && isset($promotion['data']['coupons'])
            && is_array($promotion['data']['coupons'])
            && in_array(Tools::mb_strtolower($coupon), array_map('\VSHM\Tools::mb_strtolower', $promotion['data']['coupons']), TRUE)) {

            /**
             * Only reservations with discounts records
             */
            $discountedReservations = ReservationsData::provideByWith(['key' => Discount::ID]);
            foreach ($discountedReservations as $discountedReservation) {
                foreach ($discountedReservation->data[ Discount::ID ] as $reservationDiscount) {
                    if ($reservationDiscount['id'] !== $promotion['id'] || !isset($reservationDiscount['coupon'])) {
                        continue;
                    }
                    if ($discountedReservation->status === 'confirmed'
                        || (
                            $discountedReservation->status === 'pending'
                            && !apply_filters('tbk_is_reservation_expired', FALSE, $discountedReservation)
                        )) {
                        // Used already
                        return FALSE;
                    }
                }
            }

            return TRUE;
        }

        if ($promotion['data'][ CouponMode::ID ] === CouponMode::FIXED && Tools::mb_strtolower($coupon) === Tools::mb_strtolower($promotion['promotionName'])) {

            if (isset($promotion['data'][ MaximumUses::ID ]) && $promotion['data'][ MaximumUses::ID ]) {
                $counts = 0;

                /**
                 * Only reservations with discounts records
                 */
                $discountedReservations = ReservationsData::provideByWith(['key' => Discount::ID]);

                foreach ($discountedReservations as $discountedReservation) {
                    foreach ($discountedReservation->data[ Discount::ID ] as $reservationDiscount) {
                        if ($reservationDiscount['id'] !== $promotion['id']) {
                            continue;
                        }
                        if ($discountedReservation->status === 'confirmed'
                            || (
                                $discountedReservation->status === 'pending'
                                && !apply_filters('tbk_is_reservation_expired', FALSE, $discountedReservation)
                            )) {
                            // Used
                            $counts++;
                        }
                    }
                }

                if ($counts > (int)$promotion['data'][ MaximumUses::ID ]) {
                    return FALSE;
                }
            }

            return TRUE;
        }

        return $valid;
    }

    /**
     * @param bool        $applicable
     * @param             $promotion
     * @param int         $start
     * @param int         $end
     * @param string      $serviceId
     * @param string|null $providerId // TODO: future cases
     *
     * @return bool
     */
    public static function is_promotion_applicable(bool $applicable, $promotion, $start, $end, string $serviceId, string $providerId = NULL): bool
    {
        $now = time();
        if ((int)$promotion['promotionPeriod_start'] <= $now
            && (int)$promotion['promotionPeriod_end'] >= $now
            && (
                !$promotion['data'][ \VSHM\Settings\Promotion\TimeslotMaxEndActive::ID ]
                || (int)$promotion['data'][ \VSHM\Settings\Promotion\TimeslotMaxEnd::ID ] >= (int)$end
            )
            && (
                !$promotion['data'][ \VSHM\Settings\Promotion\TimeslotMinStartActive::ID ]
                || (int)$promotion['data'][ \VSHM\Settings\Promotion\TimeslotMinStart::ID ] <= (int)$start
            )
            && in_array($serviceId, $promotion['data'][ \VSHM\Settings\Promotion\PromotionServices::ID ], TRUE)
        ) {
            return TRUE;
        }

        return $applicable;
    }

    /**
     * @param array $slots
     *
     * @return array
     */
    public static function maybe_apply_promotions(array $slots): array
    {
        $promotions = \VSHM\Providers\Promotions::provideBy(['status' => 1, 'promotionType' => 'campaign']);
        foreach ($slots as $key => $slot) {
            // TODO: PRICE??
            foreach ($promotions as $promotion) {

                if (apply_filters('tbk_is_promotion_applicable', FALSE, $promotion, (int)$slot['start'], (int)$slot['end'], $slot['serviceId'])) {
                    $slots[ $key ]['promotions'][] = [
                        'name'  => $promotion[ \VSHM\Settings\Promotion\Name::ID ],
                        'type'  => $promotion[ \VSHM\Settings\Promotion\DiscountType::ID ],
                        'value' => $promotion[ \VSHM\Settings\Promotion\Value::ID ],
                    ];
                }
            }

        }

        return $slots;
    }

    /**
     * @param Service[] $services
     *
     * @return array
     */
    public static function maybe_add_promotions_to_frontend_unscheduled_service(array $services): array
    {
        $promotions = \VSHM\Providers\Promotions::provideBy(['status' => 1, 'promotionType' => 'campaign']);
        foreach ($promotions as $promotion) {

            foreach ($services as $key => $service) {
                if ($service->class === 'unscheduled' && apply_filters('tbk_is_promotion_applicable', FALSE, $promotion, 0, 0, $service->id)) {
                    $service->data['promotions'][] = [
                        'name'  => $promotion[ \VSHM\Settings\Promotion\Name::ID ],
                        'type'  => $promotion[ \VSHM\Settings\Promotion\DiscountType::ID ],
                        'value' => $promotion[ \VSHM\Settings\Promotion\Value::ID ],
                    ];
                }
            }
        }

        return $services;
    }

    public static function get_user_prefs($prefs): array
    {
        return $prefs + (get_user_option('tbkUserPrefs') ?: []) + [
                'reservationsTableColumns'              => [
                    'db_id',
                    'date',
                    'service',
                    'customer',
                    'status',
                    'payment',
                    'provider',
                    'actions',
                    'settings'
                ],
                'reservationsTableIncludePast'          => FALSE,
                'reservationsTableIncludeExpired'       => FALSE,
                'reservationsTableDefaultSortingColumn' => 'date',
            ];
    }


    /**
     * @param $exporters
     *
     * @return mixed
     */
    public static function personal_data_exporter_register($exporters)
    {
        $exporters['team-booking'] = [
            'exporter_friendly_name' => 'TheBooking',
            'callback'               => [self::class, 'personal_data_exporter'],
        ];

        return $exporters;
    }

    /**
     * @param $erasers
     *
     * @return mixed
     */
    public static function personal_data_eraser_register($erasers)
    {
        $erasers['team-booking'] = [
            'eraser_friendly_name' => 'TheBooking',
            'callback'             => [self::class, 'personal_data_eraser'],
        ];

        return $erasers;
    }

    /**
     * @param string $email_address
     * @param int    $page
     *
     * @return array
     */
    public static function personal_data_exporter($email_address, $page = 1): array
    {
        $number       = 50;
        $page         = (int)$page;
        $export_items = [];
        $done         = TRUE;
        $customer     = Customers::provideBy(['email' => $email_address], TRUE);
        if ($customer) {
            $total   = Reservations::count(['customerId' => $customer['id']]);
            $current = Reservations::provideByPaginate(['customerId' => $customer['id']], $page, $number);
            foreach ($current as $reservation) {
                $service = Services::provideBy(['id' => $reservation->serviceId], TRUE);
                $data    = [
                    [
                        'name'  => __('Service', 'team-booking'),
                        'value' => $service->name ?? ''
                    ],
                    [
                        'name'  => __('Date', 'team-booking'),
                        'value' => DateTimeTbk::createFromFormatSilently(\DateTime::RFC3339, $reservation->start)->localized_date_time()
                    ],
                    [
                        'name'  => __('Created', 'team-booking'),
                        'value' => DateTimeTbk::createFromFormatSilently('U', $reservation->created)->localized_date_time()
                    ]
                ];

                $formEntries = FormEntries::provideBy(['reservationId' => $reservation->id]);
                foreach ($formEntries as $entry) {
                    $formField = FormFields::provideBy(['id' => $entry['id']], TRUE);
                    $data[]    = [
                        'name'  => $formField ? $formField['label'] : __('Form field', 'team-booking'),
                        'value' => $entry['value']
                    ];
                }

                $export_items[] = [
                    'group_id'    => 'tbk-reservations',
                    'group_label' => __('TheBooking reservations', 'team-booking'),
                    'item_id'     => 'tbk-reservation-' . $reservation->id,
                    'data'        => $data
                ];
            }

            if (count($current) + (($page - 1) * $number) < $total) {
                $done = FALSE;
            }
        }

        return [
            'data' => $export_items,
            'done' => $done,
        ];
    }

    /**
     * @param string $email_address
     * @param int    $page
     *
     * @return array
     */
    public static function personal_data_eraser($email_address, $page = 1): array
    {
        $number        = 50;
        $page          = (int)$page;
        $items_removed = FALSE;
        $done          = TRUE;
        $customer      = Customers::provideBy(['email' => $email_address], TRUE);
        if ($customer) {
            $total   = Reservations::count(['customerId' => $customer['id']]);
            $current = Reservations::provideByPaginate(['customerId' => $customer['id']], $page, $number);
            foreach ($current as $reservation) {
                vshm()->bus->dispatch(new DeleteReservation($reservation->id));
                $items_removed = TRUE;
            }

            if (count($current) + (($page - 1) * $number) < $total) {
                $done = FALSE;
            }
        }

        return [
            'items_removed'  => $items_removed,
            'items_retained' => FALSE,
            'messages'       => [],
            'done'           => $done,
        ];
    }

    /**
     * Cron jobs that should run daily.
     */
    public static function daily_jobs(): void
    {
        /**
         * Cleaning reservation orphan uploaded files older than 1 hour
         */
        vshm()->bus->dispatch(new CleanFiles(3600));
    }

    /**
     * Cron jobs that should run hourly.
     */
    public static function hourly_jobs(): void
    {

    }

    /**
     * Cron jobs that should run weekly.
     */
    public static function weekly_jobs(): void
    {
        vshm()->bus->dispatch(new CleanExpiredReservations(vshm()->settings->get(PaymentPendingTime::ID)));
        vshm()->bus->dispatch(new CleanLogs());
    }

    /**
     * @param bool        $expired
     * @param Reservation $reservation
     * @param int|null    $age
     *
     * @return bool
     */
    public static function _is_reservation_expired(bool $expired, Reservation $reservation, int $age = NULL): bool
    {
        $age                    = $age ?? vshm()->settings->get(PaymentPendingTime::ID);
        $paid                   = array_column(\VSHM\Providers\ReservationsData::provideBy(['key' => \VSHM\Settings\Reservation\Paid::ID]), 'value', 'reservation_id');
        $servicesPaymentSetting = array_column(
            \VSHM\Providers\ServicesData::provideBy(['key' => \VSHM\Settings\Service\PaymentRequirement::ID]),
            'value',
            'service_id'
        );

        if (!isset($servicesPaymentSetting[ $reservation->serviceId ])) {
            return $expired;
        }

        return (!isset($paid[ $reservation->id ]) || !$paid[ $reservation->id ])
            && $reservation->status === 'pending'
            && $servicesPaymentSetting[ $reservation->serviceId ] === PaymentRequirement::IMMEDIATE && time() > ((int)$reservation->created + $age);
    }

    /**
     * @param array            $reservations
     * @param \WP_REST_Request $request
     *
     * @return array
     */
    public static function computed_reservations(array $reservations, \WP_REST_Request $request): array
    {
        $servicesData = \VSHM\Providers\ServicesData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    \VSHM\Settings\Service\Approval::ID,
                    \VSHM\Settings\Service\UntilApproval::ID,
                    \VSHM\Settings\Service\PaymentRequirement::ID
                ]
            ]
        ]);
        $servicesData = self::organize_service_data($servicesData);

        $reservationsData = \VSHM\Providers\ReservationsData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    \VSHM\Settings\Reservation\Tickets::ID,
                    \VSHM\Settings\Reservation\AvailabilityId::ID,
                    \VSHM\Settings\Reservation\Paid::ID
                ]
            ]
        ]);
        $reservationsData = self::organize_reservations_data($reservationsData);

        foreach ($reservations as $key => $reservation) {

            /**@var $reservation Reservation * */

            $reservationData = $reservationsData[ $reservation->id ] ?? [];
            $serviceData     = $servicesData[ $reservation->serviceId ] ?? [];


            if ($reservation->status !== 'confirmed' && $reservation->status !== 'pending') {
                unset($reservations[ $key ]);
                continue;
            }

            if (apply_filters('tbk_is_reservation_expired', FALSE, $reservation)) {
                unset($reservations[ $key ]);
                continue;
            }

            if (isset(
                    $serviceData[ \VSHM\Settings\Service\Approval::ID ],
                    $serviceData[ \VSHM\Settings\Service\UntilApproval::ID ]
                )
                && $reservation->status === 'pending'
                && $serviceData[ \VSHM\Settings\Service\Approval::ID ] !== 'none'
                && $serviceData[ \VSHM\Settings\Service\UntilApproval::ID ]
                && (!isset($reservationData[ \VSHM\Settings\Reservation\Paid::ID ])
                    || $reservationData[ \VSHM\Settings\Reservation\Paid::ID ]) // TODO: explore possibilities about payments before approval
            ) {
                unset($reservations[ $key ]);
                continue;
            }

            if (isset($reservationData[ \VSHM\Settings\Reservation\Tickets::ID ])) {
                $reservation->data[ \VSHM\Settings\Reservation\Tickets::ID ] = (int)$reservationData[ \VSHM\Settings\Reservation\Tickets::ID ];
            }
            if (isset($reservationData[ \VSHM\Settings\Reservation\AvailabilityId::ID ])) {
                $reservation->data[ \VSHM\Settings\Reservation\AvailabilityId::ID ] = $reservationData[ \VSHM\Settings\Reservation\AvailabilityId::ID ];
            }
        }

        return $reservations;
    }

    /**
     * @param int    $provider_id
     * @param string $availability_id
     * @param string $service_id
     * @param int    $start_timestamp
     * @param int    $end_timestamp
     *
     * @return string
     */
    public static function determine_slot_id($id, int $provider_id, string $availability_id, string $service_id, int $start_timestamp, int $end_timestamp): string
    {
        return md5((int)$provider_id . $availability_id . $service_id . $start_timestamp . $end_timestamp);
    }

    /**
     * @param string $name
     * @param array  $formFields
     *
     * @return string
     */
    public static function determine_customer_phone($phone, array $formFields): string
    {
        return $phone;
    }

    /**
     * @param string $name
     * @param array  $formFields
     *
     * @return string
     */
    public static function determine_customer_name(string $name, array $formFields): string
    {
        if (isset($formFields['first_name'], $formFields['second_name'])) {
            $name = $formFields['first_name'] . ' ' . $formFields['second_name'];
        }
        if (empty($name) && isset($formFields['first_name'])) {
            $name = $formFields['first_name'];
        }
        if (empty($name) && isset($formFields['second_name'])) {
            $name = $formFields['second_name'];
        }

        // TODO: extract from meta-keys

        return $name;
    }

    /**
     * @param array $form
     *
     * @return array
     */
    public static function populating_form_frontend(array $form): array
    {
        $fields = \VSHM\Providers\FormFields::provideByMultiple('field_id', $form['fields']);
        // Enforce sorting
        $sorted = [];
        foreach ($form['fields'] as $fieldId) {

            if (!in_array($fieldId, $form['active'], TRUE)) {
                continue;
            }

            $fieldkey = array_search($fieldId, array_column($fields, 'id'), TRUE);

            // Replace "meta_key" with "prefill" if available.
            if (isset($fields[ $fieldkey ]['data']['meta_key'])
                && $fields[ $fieldkey ]['data']['meta_key']
                && vshm()->settings->get(PrepopulateBookingForm::ID)
                && get_current_user_id()
            ) {

                if ($fields[ $fieldkey ]['data']['meta_key'] === 'user_email') {
                    $meta = apply_filters('tbk_form_field_parse_user_meta', get_userdata(get_current_user_id())->user_email, $fields[ $fieldkey ]);
                } else {
                    $meta = apply_filters('tbk_form_field_parse_user_meta', get_user_meta(get_current_user_id(), $fields[ $fieldkey ]['data']['meta_key'], TRUE), $fields[ $fieldkey ]);
                }

                if (is_string($meta) || is_numeric($meta)) {
                    $fields[ $fieldkey ]['data']['prefill'] = $meta;
                }
            }

            unset($fields[ $fieldkey ]['data']['meta_key']);

            $fields[ $fieldkey ]['label'] = apply_filters('tbk_filtered_form_field_label', $fields[ $fieldkey ]['label'], $fieldId, $form['id']);
            if (isset($fields[ $fieldkey ]['data']['options'])) {
                $fields[ $fieldkey ]['data']['options'] = apply_filters('tbk_filtered_form_field_options', $fields[ $fieldkey ]['data']['options'], $fieldId, $form['id']);
            }

            $sorted[] = $fields[ $fieldkey ];
        }
        $form['fields'] = $sorted;

        return $form;
    }

    /**
     * @param array $form
     *
     * @return array
     */
    public static function populating_form(array $form): array
    {
        $fields = \VSHM\Providers\FormFields::provideByMultiple('field_id', $form['fields']);
        // Enforce sorting
        $sorted = [];
        foreach ($form['fields'] as $fieldId) {
            $fieldkey = array_search($fieldId, array_column($fields, 'id'), TRUE);
            $sorted[] = apply_filters('tbk_populating_form_single_field', $fields[ $fieldkey ]);
        }
        $form['fields'] = $sorted;

        return $form;
    }

    /**
     * @param RegisterPayment $command
     */
    public static function after_payment_received(RegisterPayment $command): void
    {
        $resIds = $command->getReservationsIds();
        foreach ($resIds as $resId) {
            $reservation = Reservations::provideBy(['id' => $resId], TRUE);
            if (!$reservation) {
                continue;
            }
            $serviceApproval = ServicesData::provideBy(['key' => Approval::ID, 'service_id' => $reservation->serviceId], TRUE) ?? Approval::getDefault();
            if ($serviceApproval === Approval::NONE && $reservation->status === 'pending') {
                vshm()->bus->dispatch(new ConfirmReservation($resId), vshm()->bus::AGENT_SYSTEM);
            }
        }

    }

    /**
     * When deleting a form field performs
     * cleaning tasks to remove possible presence
     * of the field in other form records
     *
     * @param DeleteFormField $command
     */
    public static function after_delete_form_field(DeleteFormField $command): void
    {
        /**
         * @NEXT when a Form Field can be shared across multiple form records.
         */
    }

    /**
     * @param CreateService $command
     */
    public static function post_create_service(CreateService $command): void
    {
        $service = Services::provideBy(['id' => $command->getId()], TRUE);

        if (!$service) {
            return;
        }

        /**
         * Populating default data
         */
        $default_service_data = apply_filters('vshm_default_service_settings', [], vshm()->plugin['SLUG'], $service);

        foreach ($default_service_data as $key => $value) {
            vshm()->bus->dispatch(new UpdateOrCreateServiceProperty($command->getId(), $key, $value));
        }

        /**
         * Populating default providers personal data
         */
        $default_service_provider_data = apply_filters('vshm_default_service_personal_settings', [], vshm()->plugin['SLUG'], $service);
        $providers                     = ServiceProviders::provide();
        foreach ($providers as $provider) {
            foreach ($default_service_provider_data as $key => $value) {
                vshm()->bus->dispatch(new UpdateOrCreateServicePersonalProperty($command->getId(), $provider['id'], $key, $value));
            }
        }

        /**
         * Set up a default reservation form
         */
        $default_fields_hook = ['email', 'first_name', 'second_name', 'address', 'phone', 'url'];
        $formId              = Tools::generate_token();
        $active              = [];
        $required            = [];
        $fields              = [];
        foreach ($default_fields_hook as $hook) {
            $fieldId  = Tools::generate_token();
            $fields[] = $fieldId;
            switch ($hook) {
                case 'email':
                    $active[]   = $fieldId;
                    $required[] = $fieldId;
                    vshm()->bus->dispatch(new CreateFormField($fieldId, 'text_field', $hook, __('Email', 'team-booking'), [
                        'meta_key'   => 'user_email',
                        'default'    => '',
                        'validation' => FALSE
                    ]));
                    break;
                case 'first_name':
                    $active[]   = $fieldId;
                    $required[] = $fieldId;
                    vshm()->bus->dispatch(new CreateFormField($fieldId, 'text_field', $hook, __('First name', 'team-booking'), [
                        'meta_key'   => 'first_name',
                        'default'    => '',
                        'validation' => FALSE
                    ]));
                    break;
                case 'second_name':
                    $active[]   = $fieldId;
                    $required[] = $fieldId;
                    vshm()->bus->dispatch(new CreateFormField($fieldId, 'text_field', $hook, __('Last name', 'team-booking'), [
                        'meta_key'   => 'last_name',
                        'default'    => '',
                        'validation' => FALSE
                    ]));
                    break;
                case 'address':
                    vshm()->bus->dispatch(new CreateFormField($fieldId, 'text_field', $hook, __('Address', 'team-booking'), [
                        'meta_key'   => 'address',
                        'default'    => '',
                        'validation' => FALSE
                    ]));
                    break;
                case 'phone':
                    vshm()->bus->dispatch(new CreateFormField($fieldId, 'text_field', $hook, __('Phone', 'team-booking'), [
                        'meta_key'   => 'phone',
                        'default'    => '',
                        'validation' => FALSE
                    ]));
                    break;
                case 'url':
                    vshm()->bus->dispatch(new CreateFormField($fieldId, 'text_field', $hook, __('Url', 'team-booking'), [
                        'meta_key'   => 'url',
                        'default'    => '',
                        'validation' => FALSE
                    ]));
                    break;
            }
        }
        vshm()->bus->dispatch(new UpdateOrCreateServiceProperty($command->getId(), ReservationFormId::ID, $formId));
        vshm()->bus->dispatch(new CreateForm($formId, $fields, $required, $active));
    }

    /**
     * @param DeleteService $command
     */
    public static function post_delete_service(DeleteService $command): void
    {
        /**
         * Removing personal service properties
         */
        $providers = ServiceProviders::provide();
        foreach ($providers as $provider) {
            vshm()->bus->dispatch(new DeleteServicePersonalProperties($command->getId(), $provider['id']));
        }

        /**
         * Removing forms
         */
        $formId = ServicesData::provideBy(['service_id' => $command->getId(), 'key' => ReservationFormId::ID]);

        if (isset($formId[0])) {
            vshm()->bus->dispatch(new DeleteForm($formId[0]['value']));
        }

        /**
         * Removing service properties
         */
        vshm()->bus->dispatch(new DeleteServiceProperties($command->getId()));
    }

    /**
     * @param DeleteForm $command
     */
    public static function before_delete_form(DeleteForm $command): void
    {
        $form = Forms::provideBy(['id' => $command->getId()]);
        if (isset($form[0])) {
            foreach ($form[0]['fields'] as $field) {
                vshm()->bus->dispatch(new DeleteFormField($field));
            }
        }
    }

    /**
     * @param Price  $price
     * @param string $discountId
     * @param string $discountType
     * @param        $discountValue
     * @param int    $ratio
     *
     * @return Price
     */
    public static function apply_discount_modifier(Price $price, string $discountId, string $discountType, $discountValue, int $ratio = 1): Price
    {
        $currency = $price->currency()->getCurrencyCode();
        $price->addModifier('promotion', static function (Modifier $modifier) use ($discountId, $discountType, $discountValue, $currency, $price, $ratio) {
            $modifier->setKey($discountId);
            if ($discountType === DiscountType::DIRECT) {
                $modifier->setPerUnit(TRUE);
                $modifier->subtract(Price::parse($discountValue, $currency)->inclusive());
            } elseif ($discountType === DiscountType::PERCENTAGE) {
                $modifier->setPerUnit(TRUE);

                // Percentages must be applied to the BASE PRICE!
                $toSubtract = $price->base()->getAmount()->toFloat() * $ratio * (min($discountValue, 100)) / 100;
                $modifier->subtract(Price::parse($toSubtract, $currency)->inclusive());

            } elseif ($discountType === 'increment') {
                $modifier->setPerUnit(TRUE);
                $modifier->add(Price::parse($discountValue, $currency)->inclusive());
            }
        });

        return $price;
    }

    /**
     * @param Price $price
     * @param array $formFields
     * @param array $formValues
     *
     * @return Price
     */
    public static function apply_extras_to_price(Price $price, array $formFields, array $formValues): Price
    {
        foreach ($formFields as $formFieldId) {
            $formField = FormFields::provideBy(['id' => $formFieldId], TRUE);
            if ($formField['type'] === 'checkbox') {
                if (isset($formValues[ $formField['id'] ], $formField['data']['price_increment'])
                    && $formValues[ $formField['id'] ]
                    && $formField['data']['price_increment']
                ) {
                    self::apply_discount_modifier($price, $formField['id'], 'increment', $formField['data']['price_increment']);
                }
            }
            if ($formField['type'] === 'select') {
                if (isset($formValues[ $formField['id'] ], $formField['data']['options'])
                ) {
                    foreach ($formField['data']['options'] as $key => $option) {
                        if (isset($option['price_increment']) && $option['price_increment'] && $key === (int)$formValues[ $formField['id'] ]) {
                            self::apply_discount_modifier($price, $formField['id'], 'increment', $option['price_increment']);
                        }
                    }
                }
            }
        }

        return $price;
    }

    /**
     * @param string     $reservation_id
     * @param bool       $formatted
     * @param array|null $reservationArray
     * @param array|null $dataArray
     *
     * @return string|Price
     */
    public static function reservation_get_final_price(string $reservation_id, bool $formatted = FALSE, ?array $reservationArray = NULL, ?array $dataArray = NULL)
    {
        Price::formatUsing(static function ($price, $currency, $locale = NULL) {
            /** @var $price Price */
            $amount = $price->inclusive()->getAmount()->toFloat();

            switch (vshm()->settings->get(PriceFormat::ID)) {
                case PriceFormat::COMMA_DOT:
                    $decimal_separator   = '.';
                    $thousands_separator = ',';
                    break;
                case PriceFormat::SPACE_COMMA:
                    $decimal_separator   = ',';
                    $thousands_separator = ' ';
                    break;
                case PriceFormat::SPACE_DOT:
                    $decimal_separator   = '.';
                    $thousands_separator = ' ';
                    break;
                case PriceFormat::QUOTE_DOT:
                    $decimal_separator   = '.';
                    $thousands_separator = "'";
                    break;
                case PriceFormat::DOT_COMMA:
                default:
                    $decimal_separator   = ',';
                    $thousands_separator = '.';
                    break;
            }

            $formatted_amount = number_format(
                $amount,
                vshm()->settings->get(PriceDecimals::ID ),
                $decimal_separator,
                $thousands_separator
            );

            switch (vshm()->settings->get(CurrencyFormat::ID)) {
                case CurrencyFormat::CURRENCY_AFTER:
                    return $formatted_amount . $currency;
                case CurrencyFormat::CURRENCY_BEFORE:
                    return $currency . $formatted_amount;
                case CurrencyFormat::CURRENCY_AFTER_SPACE:
                    return $formatted_amount . ' ' . $currency;
                case CurrencyFormat::CURRENCY_BEFORE_SPACE:
                default:
                    return $currency . ' ' . $formatted_amount;
            }
        });

        $reservation = Reservations::provideBy(['id' => $reservation_id], TRUE);
        if (!$reservation) {
            if (!$reservationArray) {
                return $formatted ? '' : Price::of(0, vshm()->settings->get(CurrencyCode::ID));
            }
            $reservation        = new Reservation();
            $reservation->start = $reservationArray['start'];
            $reservation->end   = $reservationArray['end'];
            $reservationData    = $dataArray ?? [];
        } else {
            $reservationData = ReservationsData::provideBy(['reservation_id' => $reservation_id]);
        }

        $mappedData = self::organize_reservations_data($reservationData)[ $reservation_id ] ?? [];

        $discounts = $mappedData[ Discount::ID ] ?? [];

        $priceVal     = $mappedData[ \VSHM\Settings\Reservation\Price::ID ] ?? NULL;
        $currencyCode = $mappedData[ CurrencyCode::ID ] ?? NULL;
        $currencyCode = $currencyCode ?? vshm()->settings->get(CurrencyCode::ID);
        $tickets      = (int)($mappedData[ Tickets::ID ] ?? NULL);

        if (NULL !== $priceVal) {

            $formEntries = FormEntries::provideBy(['reservationId' => $reservation_id]);

            $currencySettings = Tools::getCurrencies($currencyCode);
            $locale           = $currencySettings['locale'];
            $price            = Price::parse($priceVal, $currencyCode);
            $price->setUnits($tickets ?? 1);

            $fullDuration = $reservation->end - $reservation->start;

            foreach ($discounts as $discount) {
                if ($discount['type'] === DiscountType::PERCENTAGE && isset($discount['prev_buffer']) && $discount['prev_buffer']) {
                    $fullDuration -= $discount['prev_buffer'];
                }
            }

            foreach ($discounts as $discount) {
                // Percentage discounts must be applied only to the relevant portion of the price
                $ratio = 1;
                if ($discount['type'] === DiscountType::PERCENTAGE && $discount['end'] - $discount['start'] > 0) {
                    $discountDuration = $discount['end'] - $discount['start'];
                    $ratio            = $discountDuration / $fullDuration;
                }

                self::apply_discount_modifier($price, $discount['id'], $discount['type'], $discount['value'], $ratio);
            }

            foreach ($formEntries as $formEntry) {
                if (isset($formEntry['data']['priceIncrement']) && (int)$formEntry['data']['priceIncrement'] !== 0) {
                    self::apply_discount_modifier($price, $formEntry['id'], 'increment', $formEntry['data']['priceIncrement']);
                }
            }

            return $formatted ? Price::format($price, $currencySettings['symbol'], $locale) : $price;
        }

        return $formatted ? '' : Price::of(0, $currencyCode);
    }

    public static function organize_reservations_data($data)
    {
        return array_reduce($data, static function ($carry, $item) {

            $carry[ $item['reservation_id'] ][ $item['key'] ] = $item['value'];

            return $carry;
        }, []);
    }

    public static function organize_service_custom_data($data)
    {
        return array_reduce($data, static function ($carry, $item) {

            $carry[ $item['provider_id'] ][ $item['service_id'] ][ $item['key'] ] = $item['value'];

            return $carry;
        }, []);
    }

    public static function organize_service_data($data)
    {
        return array_reduce($data, static function ($carry, $item) {

            $carry[ $item['service_id'] ][ $item['key'] ] = apply_filters('tbk_service_data_value', $item['value'], $item['key'], $item['service_id']);

            return $carry;
        }, []);
    }

    /**
     * @param Reservation[] $reservations
     *
     * @return array
     */
    public static function reservations_get_final_prices(array $reservations): array
    {
        $reservation_ids  = array_column($reservations, 'id');
        $formEntries      = FormEntries::provideByMultipleReservations($reservation_ids);
        $reservationsData = self::organize_reservations_data(ReservationsData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    \VSHM\Settings\Reservation\Price::ID,
                    CurrencyCode::ID,
                    Tickets::ID,
                    Discount::ID
                ]
            ]
        ]));

        $prices = [];

        foreach ($reservations as $reservation) {
            $res_discounts = $reservationsData[ $reservation->id ][ Discount::ID ] ?? [];
            $priceVal      = $reservationsData[ $reservation->id ][ \VSHM\Settings\Reservation\Price::ID ] ?? NULL;
            $currencyCode  = $reservationsData[ $reservation->id ][ CurrencyCode::ID ] ?? NULL;
            $currencyCode  = $currencyCode ?? vshm()->settings->get(CurrencyCode::ID);
            $tickets       = (int)($reservationsData[ $reservation->id ][ Tickets::ID ] ?? 1);

            if (NULL !== $priceVal) {
                $price = Price::parse($priceVal, $currencyCode);
                $price->setUnits($tickets);

                $fullDuration = $reservation->end - $reservation->start;

                foreach ($res_discounts as $discount) {
                    if ($discount['type'] === DiscountType::PERCENTAGE && isset($discount['prev_buffer']) && $discount['prev_buffer']) {
                        $fullDuration -= $discount['prev_buffer'];
                    }
                }

                foreach ($res_discounts as $discount) {
                    // Percentage discounts must be applied only to the relevant portion of the price
                    $ratio = 1;
                    if ($discount['type'] === DiscountType::PERCENTAGE && $discount['end'] - $discount['start']) {
                        $discountDuration = $discount['end'] - $discount['start'];
                        $ratio            = $discountDuration / $fullDuration;
                    }

                    self::apply_discount_modifier($price, $discount['id'], $discount['type'], $discount['value'], $ratio);
                }

                if (isset($formEntries[ $reservation->id ])) {
                    foreach ($formEntries[ $reservation->id ] as $formEntry) {
                        if (isset($formEntry['data']['priceIncrement']) && (int)$formEntry['data']['priceIncrement'] !== 0) {
                            self::apply_discount_modifier($price, $formEntry['id'], 'increment', $formEntry['data']['priceIncrement']);
                        }
                    }
                }

                $prices[ $reservation->id ] = $price;
            } else {
                $prices[ $reservation->id ] = Price::of(0, $currencyCode);
            }
        }

        return $prices;
    }

    /**
     * @param int         $timestamp
     * @param string|null $tz_identifier
     *
     * @return array
     */
    public static function date_formatter(int $timestamp, string $tz_identifier = NULL): array
    {
        return [
            'date' => wp_date(get_option('date_format'), $timestamp, self::get_timezone($tz_identifier)),
            'time' => wp_date(get_option('time_format'), $timestamp, self::get_timezone($tz_identifier))
        ];
    }

    /**
     * Returns the site timezone object, or a defined timezone object.
     *
     * @param string $tz_identifier
     *
     * @return \DateTimeZone
     */
    public static function get_timezone($tz_identifier = NULL)
    {
        if (NULL === $tz_identifier) {
            $tz_identifier = get_option('timezone_string');
        }
        try {
            $timezone = new \DateTimeZone($tz_identifier);
        } catch (\Exception $ex) {
            $manual_offset = get_option('gmt_offset');
            if ($manual_offset === 0) {
                return new \DateTimeZone('UTC');
            }
            if ($manual_offset < 0) {
                return new \DateTimeZone(strval($manual_offset));
            }

            return new \DateTimeZone('+' . $manual_offset);
        }

        return $timezone;
    }

    public static function current_user_can_admin($andNotDemo = FALSE)
    {
        if ($andNotDemo && current_user_can('tbk_backend_demo')) {
            return new \WP_Error(
                'rest_forbidden',
                __('This is a demo :)'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return current_user_can('manage_options') || current_user_can(AllowedAdminWpRoles::ROLE);
    }

    public static function user_can_admin(int $id): bool
    {
        return user_can($id, 'manage_options') || user_can($id, AllowedAdminWpRoles::ROLE);
    }

    public static function current_user_is_provider($andNotDemo = FALSE)
    {
        if ($andNotDemo && current_user_can('tbk_backend_demo')) {
            return new \WP_Error(
                'rest_forbidden',
                __('This is a demo :)'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return current_user_can('manage_options') || current_user_can(AllowedAdminWpRoles::ROLE) || current_user_can(AllowedServiceProviderWpRoles::ROLE);
    }

    public static function user_is_provider(int $id): bool
    {
        return user_can($id, 'manage_options') || user_can($id, AllowedAdminWpRoles::ROLE) || user_can($id, AllowedServiceProviderWpRoles::ROLE);
    }

}
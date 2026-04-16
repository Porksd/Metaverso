<?php

namespace VSHM\Routes\Reservations;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;
use VSHM\Bus\CreateCustomer;
use VSHM\Bus\CreateReservation;
use VSHM\Bus\SaveFile;
use VSHM\Functions;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Providers\Customers;
use VSHM\Providers\FormEntries;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Promotion\PromotionType;
use VSHM\Settings\Provider\AllowedServices;
use VSHM\Settings\Provider\RestrictServices;
use VSHM\Settings\Reservation\AvailabilityId;
use VSHM\Settings\Reservation\CustomerTimezone;
use VSHM\Settings\Reservation\Discount;
use VSHM\Settings\Reservation\Tickets;
use VSHM\Settings\Service\Approval;
use VSHM\Settings\Service\AssignmentRule;
use VSHM\Settings\Service\DirectProvider;
use VSHM\Settings\Service\Location;
use VSHM\Settings\Service\PaymentRequirement;
use VSHM\Settings\Service\Personal_Participate;
use VSHM\Settings\Service\ReservationFormId;
use VSHM\Tools;
use Whitecube\Price\Price;

defined('ABSPATH') || exit;

/**
 * Class Create
 */
class Create implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . 'create/';
    }

    public static function get(): array
    {
        return apply_filters('tbk_route_cfg_' . self::getPath(), [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {
                $service_id = $request->get_param('serviceId');
                $service    = Services::provideBy(['id' => $service_id], TRUE);
                $slots      = is_array($request->get_param('slots'))
                    ? $request->get_param('slots')
                    : json_decode($request->get_param('slots'), TRUE);

                if (!$slots) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __('No timeslots!', 'team-booking')
                    ]);
                }

                $uploadedFiles = $request->get_file_params();
                $file_hashes   = [];

                if ($uploadedFiles && is_array($uploadedFiles)) {
                    $upload_overrides = [
                        'test_form' => FALSE,
                    ];
                    if (!function_exists('wp_handle_upload')) {
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    }
                    foreach ($uploadedFiles as $file_field_id => $file_array) {
                        $moveFile = wp_handle_upload($file_array, $upload_overrides);
                        if (isset($moveFile['error'])) {
                            return REST_Controller::get_error_response(self::getPath(), [
                                'message' => __('File upload failed', 'team-booking')
                            ]);
                        }
                        vshm()->bus->dispatch(new SaveFile($moveFile));
                        $file_hashes[ $file_field_id ] = Tools::file_hash($moveFile);
                    }
                }

                if (!$service) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __('Trying to book a service not found.', 'team-booking')
                    ], 404);
                }

                /**
                 * Multiple slots check TODO: ensure that the slots are adjacent?
                 */

                if (count($slots) > 1 && !vshm()->settings->get(\VSHM\Settings\AllowCart::ID)) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __('Booking of multiple slots at once is not allowed.', 'team-booking')
                    ], 403);
                }

                $access = \VSHM\Providers\ServicesData::provideBy(['key' => \VSHM\Settings\Service\Access::ID, 'service_id' => $service_id], TRUE);
                if (in_array($access, ['logged_only', 'nobody']) && !get_current_user_id()) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __("This service can't be booked by the current user.", 'team-booking')
                    ], 403);
                }

                $fieldEntries = is_array($request->get_param('bookingData'))
                    ? $request->get_param('bookingData')
                    : json_decode($request->get_param('bookingData'), TRUE);

                if (!is_array($fieldEntries)) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __("Unable to find booking form submission.", 'team-booking')
                    ]);
                }

                $serviceFormId = \VSHM\Providers\ServicesData::provideBy(['service_id' => $service_id, 'key' => ReservationFormId::ID], TRUE);
                if (!$serviceFormId) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'message' => __("Unable to find booking form data.", 'team-booking')
                    ]);
                }
                $forms = \VSHM\Providers\Forms::provideBy(['id' => $serviceFormId]);
                foreach ($forms as $key => $form) {
                    $forms[ $key ] = apply_filters('tbk_populating_form', $form);
                }
                $expectedFormFields = array_column($forms[0]['fields'], NULL, 'id');

                $formFieldHooks = array_column($expectedFormFields, 'hook', 'id');

                $emailFieldId = array_search('email', $formFieldHooks, TRUE);

                $formFieldValuesByHook = [];
                foreach ($fieldEntries as $fieldId => $fieldValue) {
                    $formFieldValuesByHook[ $formFieldHooks[ $fieldId ] ] = $fieldValue;
                }

                $customer_id   = NULL;
                $customerEmail = $fieldEntries[ $emailFieldId ] ?? NULL;

                /**
                 * Checking if the current user is also a customer
                 */
                if (get_current_user_id() && !$request->get_param('fromBackend')) {
                    $customer = Customers::provideBy(['wp_user' => get_current_user_id()], TRUE);
                    if ($customer) {
                        $customer_id = $customer['id'];
                    }
                }

                /**
                 * If this is a backend request, check if a customer is selected.
                 */
                if ($request->get_param('fromBackend')
                    && $request->get_param('customerId')
                    && Functions::current_user_is_provider(TRUE)) {
                    $customer = Customers::provideBy(['id' => $request->get_param('customerId')], TRUE);
                    if ($customer) {
                        $customer_id = $customer['id'];
                    }
                }

                if (!$customer_id) {
                    if (!$customerEmail) {
                        // Email not provided, hidden?
                        $hidden = $expectedFormFields[ $emailFieldId ]['data']['hide_from_registered'] ?? NULL;
                        if ($hidden && get_current_user_id()) {
                            $meta_key      = $expectedFormFields[ $emailFieldId ]['data']['meta_key'] ?? 'user_email'; // make sure
                            $customerEmail = apply_filters('tbk_form_field_parse_user_meta', get_user_meta(get_current_user_id(), $meta_key, TRUE), $expectedFormFields[ $emailFieldId ]);
                        }
                    }
                    if ($customerEmail) {
                        // Server-side email validation
                        $validator = new EmailValidator();
                        if (!$validator->isValid($customerEmail, new RFCValidation())) {
                            return REST_Controller::get_error_response(self::getPath(), [
                                'message' => __("Invalid customer email address.", 'team-booking')
                            ]);
                        }

                        // Find a customer by email
                        $customer = Customers::provideBy(['email' => $customerEmail], TRUE);
                        if ($customer) {
                            $customer_id = $customer['id'];
                        }
                    }
                }
                if (!$customer_id) {
                    // NEW CUSTOMER!!
                    if (!$customerEmail) {
                        return REST_Controller::get_error_response(self::getPath(), [
                            'message' => __("No customer email address provided.", 'team-booking')
                        ]);
                    }
                    $customer_id = Tools::generate_token();

                    // @NEXT make this optional
                    $mapped_id = get_current_user_id();
                    if ($mapped_id) {
                        $mappedCustomer = Customers::provideBy(['wp_user' => get_current_user_id()], TRUE);
                        if ($mappedCustomer) {
                            $mapped_id = 0;
                        }
                    }

                    vshm()->bus->dispatch(new CreateCustomer(
                        $customer_id,
                        apply_filters('tbk_determine_customer_name', __("New customer", 'team-booking'), $formFieldValuesByHook),
                        $customerEmail,
                        apply_filters('tbk_determine_customer_phone', '', $formFieldValuesByHook),
                        $mapped_id,
                        $request->get_param('customerTimezone') //TODO: not used
                    ));
                }


                $maxTickets          = \VSHM\Providers\ServicesData::provideBy([
                    'key'        => \VSHM\Settings\Service\TotalSlotTickets::ID,
                    'service_id' => $service_id
                ], TRUE) ?? \VSHM\Settings\Service\TotalSlotTickets::getDefault();
                $maxUserTickets      = \VSHM\Providers\ServicesData::provideBy([
                    'key'        => \VSHM\Settings\Service\TotalUserSlotTickets::ID,
                    'service_id' => $service_id
                ], TRUE) ?? \VSHM\Settings\Service\TotalUserSlotTickets::getDefault();
                $maxUserReservations = \VSHM\Providers\ServicesData::provideBy([
                    'key'        => \VSHM\Settings\Service\MaxUserReservations::ID,
                    'service_id' => $service_id
                ], TRUE) ?? \VSHM\Settings\Service\MaxUserReservations::getDefault();
                $redirect            = \VSHM\Providers\ServicesData::provideBy([
                    'key'        => \VSHM\Settings\Service\Redirect::ID,
                    'service_id' => $service_id
                ], TRUE) ?? \VSHM\Settings\Service\Redirect::getDefault();
                $redirectUrl         = \VSHM\Providers\ServicesData::provideBy([
                    'key'        => \VSHM\Settings\Service\RedirectUrl::ID,
                    'service_id' => $service_id
                ], TRUE) ?? \VSHM\Settings\Service\RedirectUrl::getDefault();

                if ($service->class === 'unscheduled' && (int)$maxUserReservations > 0) {
                    $userReservations = Reservations::provideBy([
                        'customerId' => $customer_id,
                        'serviceId'  => $service->id,
                        'status'     => [
                            'operator' => 'IN',
                            'value'    => ['pending', 'confirmed']
                        ]
                    ]);
                    foreach ($userReservations as $r_key => $user_reservation) {
                        if (apply_filters('tbk_is_reservation_expired', FALSE, $user_reservation)) {
                            unset($userReservations[ $r_key ]);
                        }
                    }
                    if (count($userReservations) > $maxUserReservations) {
                        return REST_Controller::get_error_response(self::getPath(), [
                            'message' => __("You have already reached the maximum number of reservations for this service.", 'team-booking')
                        ], 403);
                    }
                }

                $tickets = min(
                    max((int)$slots[0]['tickets'], 1),
                    (int)$maxUserTickets > 0
                        ? (int)$maxUserTickets
                        : (int)$maxTickets
                );

                $price              = \VSHM\Providers\ServicesData::provideBy(['key' => \VSHM\Settings\Service\Price::ID, 'service_id' => $service_id], TRUE);
                $location           = \VSHM\Providers\ServicesData::provideBy(['key' => \VSHM\Settings\Service\Location::ID, 'service_id' => $service_id], TRUE);
                $locationAssignedId = \VSHM\Providers\ServicesData::provideBy(['key' => \VSHM\Settings\Service\LocationAssigned::ID, 'service_id' => $service_id], TRUE);
                $approval_setting   = ServicesData::provideBy(['key' => Approval::ID, 'service_id' => $service_id], TRUE);
                $payment_setting    = ServicesData::provideBy(['key' => PaymentRequirement::ID, 'service_id' => $service_id], TRUE);

                /**
                 * Provider logic
                 */
                if ($service->class === 'unscheduled') {
                    $providerLogic = ServicesData::provideBy(['key' => AssignmentRule::ID, 'service_id' => $service_id], TRUE)
                        ?? AssignmentRule::getDefault();
                    $providers     = ServiceProviders::provide();
                    $participate   = array_column(ServiceProviderCustomData::provideBy(['key' => Personal_Participate::ID]), 'value', 'provider_id');
                    foreach ($providers as $key => $provider) {
                        if (isset($participate[ $provider['id'] ]) && !$participate) {
                            unset($providers[ $key ]);
                        }
                        if ($provider[ RestrictServices::ID ] && !in_array($service->id, $provider[ AllowedServices::ID ], TRUE)) {
                            unset($providers[ $key ]);
                        }
                    }
                    if (empty($providers)) {
                        return REST_Controller::get_error_response(self::getPath(), [
                            'message' => __("No available provider for this service.", 'team-booking')
                        ], 404);
                    }
                    if (count($providers) === 1) {
                        $provider_id = reset($providers)['id'];
                    } else {
                        switch ($providerLogic) {
                            case AssignmentRule::DIRECT:
                                $provider_id = (int)ServicesData::provideBy(['key' => DirectProvider::ID, 'service_id' => $service_id], TRUE);
                                break;
                            case AssignmentRule::RANDOM:
                                $provider_id = $providers[ array_rand($providers) ]['id'];
                                break;
                            case AssignmentRule::EQUAL:
                                $equalizer = [];
                                foreach ($providers as $provider) {
                                    $p_reservations = Reservations::provideBy([
                                        'providerId' => $provider['id'],
                                        'serviceId'  => $service_id,
                                        'status'     => [
                                            'operator' => 'IN',
                                            'value'    => ['pending', 'confirmed']
                                        ]
                                    ]);
                                    foreach ($p_reservations as $p_r_key => $p_reservation) {
                                        if (apply_filters('tbk_is_reservation_expired', FALSE, $p_reservation)) {
                                            unset($p_reservations[ $p_r_key ]);
                                        }
                                    }
                                    $equalizer[ $provider['id'] ] = count($p_reservations);
                                }
                                $provider_id = $providers[ min(array_keys($equalizer, min($equalizer))) ]['id'];
                                break;
                        }
                    }
                } else {
                    $provider_id = (int)$request->get_param('providerId');
                }

                // NEW LOOP

                $reservation_id = apply_filters('tbk_reservation_token_gen', Tools::generate_token('alnum', 32, 'r_'));
                $data           = [];

                $inherited_location = NULL;

                if (!empty($file_hashes)) {
                    self::addData('files', $file_hashes, $reservation_id, $data);
                }

                foreach ($fieldEntries as $id => $value) {

                    $price_increment = 0;
                    if ($expectedFormFields[ $id ]['type'] === 'checkbox' && $value) {
                        $price_increment = $expectedFormFields[ $id ]['data']['price_increment'] ?? 0;
                    }
                    if ($expectedFormFields[ $id ]['type'] === 'select') {
                        $price_increment = $expectedFormFields[ $id ]['data']['options'][ (int)$value ]['price_increment'] ?? 0;
                    }

                    if ($expectedFormFields[ $id ]['hook'] === 'address' && $value && $location === Location::INHERITED) {
                        $inherited_location = $value;
                    }

                    FormEntries::store([
                        'reservationId' => $reservation_id,
                        'id'            => $id,
                        'data'          => [
                            'priceIncrement' => $price_increment
                        ],
                        'value'         => $value
                    ]);
                }

                $currencyCode = vshm()->settings->get(\VSHM\Settings\CurrencyCode::ID);

                self::addData(\VSHM\Settings\Reservation\CurrencyCode::ID, $currencyCode, $reservation_id, $data);
                self::addData('reminderSent', 0, $reservation_id, $data);
                self::addData(\VSHM\Settings\Reservation\Paid::ID, \VSHM\Settings\Reservation\Paid::getDefault(), $reservation_id, $data);

                $resStart         = NULL;
                $resEnd           = NULL;
                $_internal_buffer = 0;
                $resBasePrice     = Price::parse('0', $currencyCode)->inclusive();

                self::addData(
                    \VSHM\Settings\Reservation\Tickets::ID,
                    $tickets,
                    $reservation_id,
                    $data
                );

                $overridden_locations = [];

                $discounts = [];

                foreach ($slots as $key => $slot) {

                    $start = (int)$slot['start'];
                    $end   = (int)$slot['end'];

                    $maxSlotTickets = (int)($slot['overrides']['tickets'] ?? $maxTickets);

                    if (NULL !== $resEnd) {
                        // It should be 0 if there is no buffer between slots
                        $_internal_buffer = $start - $resEnd;
                    }

                    if ($key === 0) {
                        $resStart = $start;
                        self::addData(
                            \VSHM\Settings\Reservation\AvailabilityId::ID,
                            $slot[ AvailabilityId::ID ] ?? '',
                            $reservation_id,
                            $data
                        );
                    }
                    $resEnd = $end;

                    //TODO: keep track of the override in res data?
                    $this_price = $slot['overrides']['price'] ?? $price;

                    if (isset($slot['overrides']['location'])) {
                        $overridden_locations[] = $slot['overrides']['location'];
                    }

                    $resBasePrice = $resBasePrice->plus(Price::parse($this_price, $currencyCode)->inclusive());

                    //TODO: makes sense to perform a get_availability now and find the slotId instead? (event_id)
                    $overlappingReservations = Reservations::provideBetween(
                        $start,
                        $end, [
                        'status'      => ['operator' => '!=', 'value' => 'cancelled'],
                        'service_id'  => $service_id,
                        'provider_id' => $provider_id
                    ]);

                    $alreadyBooked           = 0;
                    $alreadyBookedByCustomer = 0;

                    $slotPeriod = Period::make(
                        DateTimeTbk::createFromFormatSilently('U', $start),
                        DateTimeTbk::createFromFormatSilently('U', $end),
                        Precision::SECOND,
                        Boundaries::EXCLUDE_ALL
                    );

                    foreach ($overlappingReservations as $overlappingReservation) {
                        // TODO: more conditionals??

                        // TODO: what if pending && keep free until confirmed?

                        // Truly overlaps? We need to exclude boundaries
                        $reservationPeriod = Period::make(
                            DateTimeTbk::createFromFormatSilently('U', (int)$overlappingReservation->start),
                            DateTimeTbk::createFromFormatSilently('U', (int)$overlappingReservation->end),
                            Precision::SECOND,
                            Boundaries::EXCLUDE_ALL
                        );

                        if (!$slotPeriod->overlapsWith($reservationPeriod)) {
                            continue;
                        }

                        if (apply_filters('tbk_is_reservation_expired', FALSE, $overlappingReservation)) {
                            continue;
                        }

                        $r_tickets        = ReservationsData::provideBy(['reservation_id' => $overlappingReservation->id, 'key' => Tickets::ID], TRUE);
                        $r_availabilityId = ReservationsData::provideBy(['reservation_id' => $overlappingReservation->id, 'key' => AvailabilityId::ID], TRUE);

                        if ($r_availabilityId !== ($slot[ AvailabilityId::ID ] ?? '')) {
                            if (!apply_filters('tbk_overlapping_reservation_from_different_availability_adds_up', FALSE, $r_availabilityId, ($slot[ AvailabilityId::ID ] ?? ''), $provider_id)) {
                                continue;
                            }
                        }

                        if ($r_tickets) {
                            $alreadyBooked += (int)$r_tickets;

                            if ($overlappingReservation->customerId === $customer_id) {
                                $alreadyBookedByCustomer += (int)$r_tickets;
                            }
                        }
                    }

                    if ($tickets > ($maxSlotTickets - $alreadyBooked)) {

                        return REST_Controller::get_error_response(self::getPath(), [
                            'message' => $maxSlotTickets < 2
                                ? __("Those time slots are not available anymore, apparently someone else was very fast :(", 'team-booking')
                                : __("Trying to book more tickets than they're available.", 'team-booking')
                        ], 403);
                    }

                    if ($maxUserTickets > 0 && $tickets > ($maxUserTickets - $alreadyBookedByCustomer)) {

                        return REST_Controller::get_error_response(self::getPath(), [
                            'message' => __("Trying to book more tickets than you're allowed.", 'team-booking')
                        ], 403);
                    }

                    /**
                     * Discounts
                     */
                    $promotionsCampaigns = \VSHM\Providers\Promotions::provideBy(['status' => 1, PromotionType::ID => PromotionType::CAMPAIGN]);
                    foreach ($promotionsCampaigns as $promotion) {
                        if (apply_filters('tbk_is_promotion_applicable', FALSE, $promotion, $start, $end, $service_id)) {
                            $discounts[] = [
                                    'name'        => $promotion[ \VSHM\Settings\Promotion\Name::ID ],
                                    'type'        => $promotion[ \VSHM\Settings\Promotion\DiscountType::ID ],
                                    'value'       => $promotion[ \VSHM\Settings\Promotion\Value::ID ],
                                    'id'          => $promotion['id'],
                                    'start'       => $start,
                                    'end'         => $end,
                                    'prev_buffer' => $_internal_buffer
                                ] + Discount::getDefault();
                        }
                    }

                    if ($request->get_param('coupon')) {
                        $promotionsCoupons = \VSHM\Providers\Promotions::provideBy(['status' => 1, PromotionType::ID => PromotionType::COUPON]);
                        foreach ($promotionsCoupons as $promotion) {
                            if (apply_filters('tbk_is_coupon_valid', FALSE, $promotion, $request->get_param('coupon'))
                                && apply_filters('tbk_is_promotion_applicable', FALSE, $promotion, $start, $end, $service_id)) {
                                $discounts[] = [
                                        'name'        => $promotion[ \VSHM\Settings\Promotion\Name::ID ],
                                        'type'        => $promotion[ \VSHM\Settings\Promotion\DiscountType::ID ],
                                        'value'       => $promotion[ \VSHM\Settings\Promotion\Value::ID ],
                                        'id'          => $promotion['id'],
                                        'start'       => $start,
                                        'end'         => $end,
                                        'prev_buffer' => $_internal_buffer,
                                        'coupon'      => $request->get_param('coupon')
                                    ] + Discount::getDefault();
                            }
                        }
                    }

                }

                if ($discounts) {
                    self::addData(Discount::ID, $discounts, $reservation_id, $data);
                }

                $overridden_locations = array_unique($overridden_locations);

                if ($request->get_param(\VSHM\Settings\Reservation\Location::ID)) {
                    self::addData(\VSHM\Settings\Reservation\Location::ID, $request->get_param(\VSHM\Settings\Reservation\Location::ID), $reservation_id, $data);
                } else if ($inherited_location) {
                    self::addData(\VSHM\Settings\Reservation\Location::ID, $inherited_location, $reservation_id, $data);
                } else if (count($overridden_locations) === 1) {
                    self::addData(\VSHM\Settings\Reservation\LocationOverride::ID, $overridden_locations[0], $reservation_id, $data);
                }

                self::addData(\VSHM\Settings\Reservation\Price::ID, $resBasePrice->getAmount()->toFloat(), $reservation_id, $data);

                $final_price = Functions::reservation_get_final_price(
                    $reservation_id,
                    FALSE,
                    [
                        'start' => $resStart,
                        'end'   => $resEnd,
                    ],
                    $data
                );

                self::addData(CustomerTimezone::ID, $request->get_param('customerTimezone'), $reservation_id, $data);

                /**
                 * Determining the status
                 */
                $status = 'confirmed';

                if (!$request->get_param('fromBackend')
                    || !Functions::current_user_is_provider(TRUE)) {

                    if ($payment_setting === PaymentRequirement::IMMEDIATE) {
                        $status = 'pending';
                    }

                    if ($approval_setting !== 'none') {
                        $status = 'pending';
                    }

                    // Is the final price === 0? Let's change the status
                    if ($final_price->equals(0)) {
                        $status = $approval_setting !== 'none' ? 'pending' : 'confirmed';
                    }
                }

                vshm()->bus->dispatch(new CreateReservation(
                    $reservation_id,
                    $service_id,
                    $customer_id,
                    $provider_id,
                    $resStart,
                    $resEnd,
                    $data,
                    $status
                ), vshm()->bus::AGENT_USER, get_current_user_id() ?: $customer_id);

                // NEW LOOP END

                $customer = Customers::provideBy(['id' => $customer_id], TRUE);

                $responseArgs = [
                    'reservation'       => ReservationsRoute::prepare_for_frontend(Reservations::provideBy(['id' => $reservation_id]))[0],
                    'reservationId'     => $reservation_id,
                    'reservationStatus' => $status,
                    'hash'              => md5($reservation_id . $customer['access_token']),
                    'finalPrice'        => $final_price->inclusive()->getAmount()->toFloat()
                ];

                if ($redirect && filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
                    $responseArgs['redirect'] = $redirectUrl;
                }

                if ($request->get_param('fromBackend') && Functions::current_user_is_provider(TRUE)) {
                    $responseArgs['customers'] = \VSHM\Providers\Customers::provide();
                }

                return REST_Controller::get_ok_response(self::getPath(), $responseArgs);
            },
            'args'                => [
                'serviceId'        => [
                    'required' => TRUE,
                    'type'     => 'string'
                ],
                'customerId'       => [
                ],
                'customerTimezone' => [
                    'type' => 'string'
                ],
                'slots'            => [
                    'required' => TRUE,
                ],
                'fromBackend'      => [
                    'type' => 'bool'
                ]
            ],
            'permission_callback' => static function (\WP_REST_Request $request) {
                return apply_filters('tbk_route_permission_' . self::getPath(), TRUE, $request);
            }
        ]);
    }

    public static function addData($key, $value, $reservation_id, &$data): void
    {
        $data[] = [
            'key'            => $key,
            'value'          => $value,
            'reservation_id' => $reservation_id
        ];
    }
}
<?php

namespace VSHM\Modules;

use RRule\RSet;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;
use VSHM\Bus\DeleteService;
use VSHM\Bus\UpdateOrCreateServiceProperty;
use VSHM\Bus\UpdateProviderProperty;
use VSHM\Functions;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Plugin\ReservationsBlockingFactory;
use VSHM\Plugin\TimeSlotDurationFactory;
use VSHM\Plugin\TimeSlotFactory;
use VSHM\Plugin\TimeSlotReflowLogicFactory;
use VSHM\Providers\Customers;
use VSHM\Providers\Objects\Reservation;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\REST_Controller;
use VSHM\Settings\Provider\AllowedServices;
use VSHM\Settings\Reservation\AvailabilityId;
use VSHM\Settings\Service\BlockAvailabilityAfterOneReservation;
use VSHM\Settings\Service\DiscardedAvailableSlots;
use VSHM\Settings\Service\Personal_BufferRule;
use VSHM\Settings\Service\Personal_BufferTimespan;
use VSHM\Settings\Service\Personal_DiscardOverlappingWithPersonal;
use VSHM\Settings\Service\Personal_DiscardOverlappingWithSame;
use VSHM\Settings\Service\Personal_OverlappingWithSameDropTickets;
use VSHM\Settings\Service\Personal_Participate;
use VSHM\Settings\Service\ShowBookedSlots;
use VSHM\Settings\Service\ShowSlotCustomers;
use VSHM\Settings\Service\TotalSlotTickets;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class WorkingHours
 *
 * @author VonStroheim
 */
class WorkingHours
{
    /**
     * @var string
     */
    public static $route_path = '/working/hours/';

    public const ID = 'tbk_working_hours';

    public static function bootstrap(): void
    {
        add_action('tbk_availability', [self::class, 'getSlots'], 10, 7);
        add_filter('tbk_provider_meta_keys', [self::class, 'add_meta_keys'], 10, 2);
        add_filter('tbk_provider_meta_value', [self::class, 'provider_meta_value_integrity_check'], 10, 2);
        add_action('vshm_dispatched_DeleteService', [self::class, 'servicesCleanup']);
        add_action('vshm_dispatched_UpdateOrCreateServiceProperty', [self::class, 'servicesCleanup']);
        add_action('vshm_dispatched_UpdateProviderProperty', [self::class, 'servicesCleanup']);

        REST_Controller::register_routes([
            self::$route_path . 'remove/' => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'remove'],
                'args'                => [
                    'id' => [
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider(TRUE);
                }
            ],
            self::$route_path . 'add/'    => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'add'],
                'args'                => [
                    'name' => [
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider(TRUE);
                }
            ],
            self::$route_path . 'save/'   => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'save'],
                'args'                => [
                    'id' => [
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider(TRUE);
                }
            ],
            self::$route_path . 'get/'    => [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [self::class, 'get'],
                'permission_callback' => static function () {
                    return Functions::current_user_is_provider();
                }
            ]
        ]);
    }

    public static function add_meta_keys(array $metaKeys): array
    {
        $metaKeys[] = self::ID;

        return $metaKeys;
    }

    public static function provider_meta_value_integrity_check($value, $metaKey)
    {

        if ($metaKey !== self::ID) {
            return $value;
        }

        if (!is_array($value)) {
            return [];
        }


        foreach ($value as $plan_key => $plan) {

            foreach ($plan['rules'] as $rule_key => $rule) {

                $dtstart = new DateTimeTbk();

                if (!isset($rule['rrule'])) {
                    continue;
                }

                $rrule = new RSet($rule['rrule']);
                foreach ($rrule->getRRules() as $inner) {
                    $dtstart = $inner->getRule()['DTSTART'];
                    break;
                }

                $value[ $plan_key ]['rules'][ $rule_key ]['startOffset'] = $dtstart->getOffset();

            }


        }


        return $value;
    }

    /**
     * @param               $other_slots
     * @param               $min_timestamp
     * @param               $max_timestamp
     * @param Reservation[] $reservations
     * @param array|null    $services
     * @param array|null    $providers
     *
     * @return array
     */
    public static function getSlots($other_slots, $min_timestamp, $max_timestamp = NULL, array $reservations = [], array $services = NULL, array $providers = NULL): array
    {

        $slots     = [];
        $customers = array_column(Customers::provide(), NULL, 'id');

        $blockingEvents = apply_filters('tbk_working_hours_blocking_events', [], $min_timestamp, $max_timestamp, $services, $providers);

        $providersServiceData = \VSHM\Providers\ServiceProviderCustomData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    Personal_Participate::ID,
                    Personal_BufferTimespan::ID,
                    Personal_BufferRule::ID,
                    Personal_DiscardOverlappingWithPersonal::ID,
                    Personal_DiscardOverlappingWithSame::ID,
                    Personal_OverlappingWithSameDropTickets::ID
                ]
            ]
        ]);
        $providersServiceData = Functions::organize_service_custom_data($providersServiceData);

        $servicesData = \VSHM\Providers\ServicesData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    DiscardedAvailableSlots::ID,
                    TotalSlotTickets::ID,
                    BlockAvailabilityAfterOneReservation::ID,
                    ShowSlotCustomers::ID,
                    ShowBookedSlots::ID,
                ]
            ]
        ]);
        $servicesData = Functions::organize_service_data($servicesData);

        foreach (ServiceProviders::provide() as $provider) {

            $provider_id = $provider['id'];

            if (!empty($providers) && !in_array($provider_id, array_map('intval', $providers), TRUE)) {
                continue;
            }

            $reservationsBlockingFactory = new ReservationsBlockingFactory($reservations, $provider_id);

            $availability = $provider[ self::ID ] ?? [];

            foreach (Services::provideBy(['status' => 1, 'class' => 'appointment']) as $service) {

                $service_id = $service->id;

                if (!empty($services) && !in_array($service_id, $services, TRUE)) {
                    continue;
                }

                $providerServiceData = $providersServiceData[ $provider_id ][ $service_id ] ?? [];
                $serviceData         = $servicesData[ $service_id ] ?? [];
                $buffer              = (int)($providerServiceData[ Personal_BufferTimespan::ID ] ?? Personal_BufferTimespan::getDefault());

                if (!filter_var(
                    ($providerServiceData[ Personal_Participate::ID ] ?? Personal_Participate::getDefault()),
                    FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $observePersonal = isset($providerServiceData[ Personal_DiscardOverlappingWithPersonal::ID ], $blockingEvents[ $provider['id'] ][ $service->id ])
                    && $providerServiceData[ Personal_DiscardOverlappingWithPersonal::ID ];

                $p_blockingEvents = $observePersonal
                    ? $blockingEvents[ $provider['id'] ][ $service->id ]
                    : [];

                $collection = [];
                foreach ($p_blockingEvents as $p_blockingEvent) {
                    $collection[] = Period::make(
                        DateTimeTbk::createFromFormatSilently(DATE_RFC3339, $p_blockingEvent['start']),
                        DateTimeTbk::createFromFormatSilently(DATE_RFC3339, $p_blockingEvent['end']),
                        Precision::SECOND,
                        Boundaries::EXCLUDE_ALL
                    );
                }

                $blockingCollection = PeriodCollection::make(...$collection);

                $discardedSlotsTreatAs = $serviceData[ DiscardedAvailableSlots::ID ] ?? DiscardedAvailableSlots::getDefault();
                $maxServiceSlotTickets = $serviceData[ TotalSlotTickets::ID ] ?? TotalSlotTickets::getDefault();
                $blockAfterOne         = $serviceData[ BlockAvailabilityAfterOneReservation::ID ] ?? BlockAvailabilityAfterOneReservation::getDefault();
                $showCustomers         = $serviceData[ ShowSlotCustomers::ID ] ?? ShowSlotCustomers::getDefault();
                $showBookedSlots       = $serviceData[ ShowBookedSlots::ID ] ?? ShowBookedSlots::getDefault();

                $p_dropTickets = filter_var($providerServiceData[ Personal_OverlappingWithSameDropTickets::ID ]
                        ?? Personal_OverlappingWithSameDropTickets::getDefault(), FILTER_VALIDATE_BOOLEAN)
                    && filter_var($providerServiceData[ Personal_DiscardOverlappingWithSame::ID ]
                        ?? Personal_DiscardOverlappingWithSame::getDefault());

                $durationFactory = new TimeSlotDurationFactory($service_id, $provider_id);
                $timeslotFactory = new TimeSlotFactory($service_id, $provider_id);
                if (isset($providerServiceData[ Personal_BufferTimespan::ID ], $providerServiceData[ Personal_BufferRule::ID ])
                    && $providerServiceData[ Personal_BufferRule::ID ] === Personal_BufferRule::ALWAYS) {
                    $timeslotFactory->setBuffer((int)$providerServiceData[ Personal_BufferTimespan::ID ]);
                }

                $reflowLogicFactory = new TimeSlotReflowLogicFactory($service_id, $provider_id);

                //////NEW LOOP
                ///
                ///
                ///

                foreach ($availability as $plan) {

                    if (!in_array($service_id, $plan['services'], TRUE)) {
                        continue;
                    }

                    $availabilityId = $provider['id'] . '__' . $plan['id'];

                    $timeslotFactory->setAvailabilityId($availabilityId);

                    $instances = $plan['rules'] ?? [];

                    // Transforming recurrences into Period set
                    $collection = [];
                    foreach ($instances as $r_id => $recurrence) {

                        $rrule = new RSet($recurrence['rrule']);

                        foreach ($rrule->getOccurrencesBetween($min_timestamp, $max_timestamp, 10000) as $occurrence) {

                            /** @var $occurrence \DateTime */
                            $collection[] = Period::make(
                                $occurrence,
                                DateTimeTbk::createFromFormatSilently('U', $occurrence->getTimestamp() + ($recurrence['duration'] * 60)),
                                Precision::SECOND,
                                Boundaries::EXCLUDE_ALL
                            );
                        }
                    }

                    $workingHoursSchedulePeriodSet = PeriodCollection::make(...$collection);

                    $reservationsSameServicePeriod = $reservationsBlockingFactory->getAsPeriodSet(static function ($reservation) use ($service_id, $availabilityId, $p_dropTickets, $plan) {
                        return $reservation->serviceId === $service_id
                            && ($reservation->data[ AvailabilityId::ID ] === $availabilityId
                                || ($p_dropTickets && !$plan['independent']
                                ));
                    });

                    $reservationsOtherServicesPeriod = $reservationsBlockingFactory->getAsPeriodSet(static function ($reservation) use ($service_id, $availabilityId) {
                        return $reservation->serviceId !== $service_id
                            && $reservation->data[ AvailabilityId::ID ] === $availabilityId;
                    });

                    /**
                     * Setting up intervals
                     */
                    $intervals = $workingHoursSchedulePeriodSet;
                    if ($reflowLogicFactory->mustReflow()) {

                        if ($observePersonal) {
                            $intervals = Tools::periodSubtract($intervals, $blockingCollection);
                        }
                        // TODO: should this be conditional??
                        $intervals = Tools::periodSubtract($intervals, $reservationsOtherServicesPeriod);
                    }
                    $periodsBeforeReservationsSameService = $intervals;
                    $intervals                            = Tools::periodSubtract($intervals, $reservationsSameServicePeriod);

                    foreach ($intervals as $period) {

                        $wholeInterval = $period->getEnd()->getTimestamp() - $period->getStart()->getTimestamp();
                        $duration      = $durationFactory->get($wholeInterval);
                        $spoolStart    = $period->getStart()->getTimestamp();
                        $spoolEnd      = $spoolStart + $duration;

                        $safe = 100000;

                        while ($wholeInterval >= $duration && $safe > 0) {

                            $timeslotFactory->setBoundaries($spoolStart, $spoolEnd);

                            $reservationCount = 0;

                            if ($timeslotFactory->areOpenCloseConditionsMet()) {

                                $reservationsIds = [];
                                $slotCustomers   = [];
                                $dropSlot        = FALSE;

                                if (!$reflowLogicFactory->mustReflow()) {

                                    // Personal
                                    if ($observePersonal) {
                                        foreach ($blockingCollection as $item) {
                                            if ($timeslotFactory->getPeriod()->overlapsWith($item)) {
                                                if ($discardedSlotsTreatAs === DiscardedAvailableSlots::BOOKED) {
                                                    $reservationCount = (int)$maxServiceSlotTickets;
                                                } else {
                                                    $dropSlot = TRUE;
                                                }
                                                break;
                                            }
                                        }
                                    }

                                    // Reservations
                                    if (!$dropSlot) {
                                        foreach ($reservationsBlockingFactory->get() as $reservation) {

                                            $independenceCondition = !$plan['independent']
                                                || $reservation->data[ AvailabilityId::ID ] === $availabilityId;

                                            $isTheSameSlot = ($reservation->data[ AvailabilityId::ID ] === $availabilityId || $p_dropTickets)
                                                && $reservation->serviceId === $service_id;

                                            if ($independenceCondition &&
                                                !$isTheSameSlot
                                                && ($timeslotFactory->isOverlappingConditionSatisfied($reservation))
                                                && $timeslotFactory->overlapsWithReservation($reservation)
                                            ) {
                                                if ($discardedSlotsTreatAs === DiscardedAvailableSlots::BOOKED) {
                                                    $reservationCount = (int)$maxServiceSlotTickets;
                                                } else {
                                                    $dropSlot = TRUE;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }

                                if (!$dropSlot) {
                                    $slots[] = $timeslotFactory->getSlot($reservationCount, $reservationsIds, $slotCustomers);
                                }

                            }

                            if (isset($providerServiceData[ Personal_BufferRule::ID ])
                                && $buffer > 0) {
                                if ($providerServiceData[ Personal_BufferRule::ID ] === Personal_BufferRule::ALWAYS || ($reservationCount > 0)) {
                                    $wholeInterval -= $buffer;
                                    $spoolStart    += $buffer;
                                }
                            }

                            $wholeInterval -= $duration;
                            $spoolStart    += $duration;
                            $spoolEnd      = $spoolStart + $duration;
                            --$safe;

                        }
                    }

                    // Reservations are turned into booked slots
                    foreach ($reservationsSameServicePeriod as $reservationPeriod) {

                        /**
                         * If reservation periods don't overlap with the original availability period, skip them
                         */
                        if ($periodsBeforeReservationsSameService->overlapSingle(PeriodCollection::make($reservationPeriod))->isEmpty()) {
                            continue;
                        }

                        $wholeInterval = $reservationPeriod->getEnd()->getTimestamp() - $reservationPeriod->getStart()->getTimestamp();

                        // In this loop, we need to subtract any eventual buffer, as buffer can't be converted into available time.
                        if ($buffer > 0) {
                            $wholeInterval -= $buffer;
                        }

                        $duration = $durationFactory->get($wholeInterval);

                        $spoolStart = $reservationPeriod->getStart()->getTimestamp();
                        $spoolEnd   = $spoolStart + $duration;
                        $safe       = 100000;

                        while ($wholeInterval >= $duration && $safe > 0) {
                            $timeslotFactory->setBoundaries($spoolStart, $spoolEnd);

                            if ($timeslotFactory->areOpenCloseConditionsMet()) {
                                $reservationCount = 0;
                                $reservationsIds  = [];
                                $slotCustomers    = [];
                                $dropSlot         = FALSE;

                                foreach ($reservationsBlockingFactory->get() as $reservation) {

                                    $isSameSlot = ($reservation->data[ AvailabilityId::ID ] === $availabilityId
                                            || ($p_dropTickets && !$plan['independent']))
                                        && $reservation->serviceId === $service_id;

                                    if ($isSameSlot
                                        && $timeslotFactory->overlapsWithReservation($reservation)
                                    ) {

                                        if (!apply_filters('tbk_availability_request_is_frontend', FALSE)) {

                                            $reservationsIds[] = $reservation->id;
                                            $slotCustomers[]   = $customers[ $reservation->customerId ];
                                        } else {
                                            switch ($showCustomers) {
                                                case ShowSlotCustomers::NAME:
                                                    $slotCustomers[] = [
                                                        'name' => $customers[ $reservation->customerId ]['name']
                                                    ];
                                                    break;
                                                case ShowSlotCustomers::EMAIL:
                                                    $slotCustomers[] = [
                                                        'email' => $customers[ $reservation->customerId ]['email']
                                                    ];
                                                    break;
                                                case ShowSlotCustomers::NAME_EMAIL:
                                                    $slotCustomers[] = [
                                                        'email' => $customers[ $reservation->customerId ]['email'],
                                                        'name'  => $customers[ $reservation->customerId ]['name']
                                                    ];
                                                    break;
                                            }
                                        }

                                        if ($blockAfterOne) {
                                            $reservationCount = (int)$maxServiceSlotTickets;
                                            break;
                                        }

                                        $reservationCount += ($reservation->data[ \VSHM\Settings\Reservation\Tickets::ID ] ?: 1);

                                    }
                                }

                                if ($reservationCount === (int)$maxServiceSlotTickets && !$showBookedSlots) {
                                    $dropSlot = TRUE;
                                }

                                if (!$dropSlot) {
                                    $slots[] = $timeslotFactory->getSlot($reservationCount, $reservationsIds, $slotCustomers);
                                }

                            }

                            $wholeInterval -= $duration;
                            $spoolStart    += $duration;
                            $spoolEnd      = $spoolStart + $duration;
                            --$safe;
                        }

                    }

                }
                /////END NEW LOOP
            }
        }

        return array_merge($slots, $other_slots);

    }

    public static function save(\WP_REST_Request $request): \WP_REST_Response
    {
        $providerId = $request->get_param('providerId');
        if (!$providerId || !Functions::current_user_can_admin()) {
            $providerId = get_current_user_id();
        }

        $provider = ServiceProviders::provideBy(['id' => $providerId], TRUE);

        if (!$provider) {
            return REST_Controller::get_error_response(self::$route_path . 'get/', [
                'message' => __('Service provider not found.', 'team-booking')
            ], 404);
        }

        $availability = $provider[ self::ID ] ?? [];

        foreach ($availability as $key => $item) {

            if ($item['id'] !== $request->get_param('id')) {
                continue;
            }

            $rules = $request->get_param('rules') ?? $item['rules'];

            foreach ($rules as $rule_key => $rule) {


                $rules[ $rule_key ] = [
                    'duration' => $rule['duration'],
                    'rrule'    => $rule['rrule'],
                ];

            }

            $availability[ $key ] = [
                'name'        => $request->get_param('name') ?? $item['name'],
                'id'          => $item['id'],
                'rules'       => $rules,
                'services'    => $request->get_param('services') ?? $item['services'],
                'independent' => $request->get_param('independent') ?? $item['independent'],
                'personal'    => $request->get_param('personal') ?? $item['personal'],
                'destination' => $request->get_param('destination') ?? $item['destination'],
            ];
        }

        vshm()->bus->dispatch(new UpdateProviderProperty($providerId, self::ID, $availability));

        $provider     = ServiceProviders::provideBy(['id' => $providerId], TRUE);
        $availability = $provider[ self::ID ] ?? [];

        return REST_Controller::get_ok_response(self::$route_path . 'get/', [
            'availability' => array_values($availability)
        ]);
    }

    public static function get(\WP_REST_Request $request): \WP_REST_Response
    {
        $requestedProvider = $request->get_param('providerId');
        if (!$requestedProvider || !Functions::current_user_can_admin()) {
            $requestedProvider = get_current_user_id();
        }

        $provider = ServiceProviders::provideBy(['id' => $requestedProvider], TRUE);

        if (!$provider) {
            return REST_Controller::get_error_response(self::$route_path . 'get/', [
                'message' => __('Service provider not found.', 'team-booking')
            ], 404);
        }

        $availability = $provider[ self::ID ] ?? [];

        return REST_Controller::get_ok_response(self::$route_path . 'get/', [
            'availability' => array_values($availability)
        ]);
    }

    public static function add(\WP_REST_Request $request): \WP_REST_Response
    {
        $providerId = $request->get_param('providerId');
        if (!$providerId || !Functions::current_user_can_admin()) {
            $providerId = get_current_user_id();
        }

        $provider = ServiceProviders::provideBy(['id' => $providerId], TRUE);

        if (!$provider) {
            return REST_Controller::get_error_response(self::$route_path . 'get/', [
                'message' => __('Service provider not found.', 'team-booking')
            ], 404);
        }

        $availability   = $provider[ self::ID ] ?? [];
        $availability[] = [
            'name'        => $request->get_param('name'),
            'id'          => Tools::generate_token('alnum', 16),
            'rules'       => [

            ],
            'services'    => [],
            'independent' => FALSE,
            'personal'    => NULL,
            'destination' => NULL
        ];

        vshm()->bus->dispatch(new UpdateProviderProperty($providerId, self::ID, $availability));

        return REST_Controller::get_ok_response(self::$route_path . 'get/', [
            'availability' => $availability
        ]);

    }

    public static function remove(\WP_REST_Request $request): \WP_REST_Response
    {
        $providerId = $request->get_param('providerId');
        if (!$providerId || !Functions::current_user_can_admin()) {
            $providerId = get_current_user_id();
        }

        $provider = ServiceProviders::provideBy(['id' => $providerId], TRUE);

        if (!$provider) {
            return REST_Controller::get_error_response(self::$route_path . 'get/', [
                'message' => __('Service provider not found.', 'team-booking')
            ], 404);
        }

        $availability = $provider[ self::ID ] ?? [];

        foreach ($availability as $key => $item) {
            if ($item['id'] === $request->get_param('id')) {
                unset($availability[ $key ]);
            }
        }

        vshm()->bus->dispatch(new UpdateProviderProperty($providerId, self::ID, array_values($availability)));

        return REST_Controller::get_ok_response(self::$route_path . 'get/', [
            'availability' => array_values($availability)
        ]);
    }

    public static function servicesCleanup($command): void
    {
        $providers = ServiceProviders::provide();

        if ($command instanceof DeleteService) {
            foreach ($providers as $provider) {
                $availability = $provider[ self::ID ] ?? [];
                foreach ($availability as $key => $item) {
                    if (!isset($item['services'])) {
                        continue;
                    }
                    $service_key = array_search($command->getId(), $item['services'], TRUE);
                    if ($service_key !== FALSE) {
                        unset($availability[ $key ]['services'][ $service_key ]);
                    }
                }
                vshm()->bus->dispatch(new UpdateProviderProperty($provider['id'], self::ID, array_values($availability)));
            }
        } elseif ($command instanceof UpdateOrCreateServiceProperty) {
            if ($command->getKey() === 'class' && $command->getValue() === 'unscheduled') {
                foreach ($providers as $provider) {
                    $availability = $provider[ self::ID ] ?? [];
                    foreach ($availability as $key => $item) {
                        if (!isset($item['services'])) {
                            continue;
                        }
                        $service_key = array_search($command->getServiceId(), $item['services'], TRUE);
                        if ($service_key !== FALSE) {
                            unset($availability[ $key ]['services'][ $service_key ]);

                        }
                    }
                    vshm()->bus->dispatch(new UpdateProviderProperty($provider['id'], self::ID, $availability));
                }
            }
        } elseif ($command instanceof UpdateProviderProperty) {
            if ($command->getKey() === AllowedServices::ID) {
                foreach ($providers as $provider) {
                    if ($provider['id'] === $command->getProviderId()) {
                        $availability = $provider[ self::ID ] ?? [];
                        if (is_array($availability)) {
                            foreach ($availability as $key => $item) {
                                if (!isset($item['services'])) {
                                    continue;
                                }
                                $availability[ $key ]['services'] = array_values(array_intersect($command->getValue(), $item['services']));
                            }
                            vshm()->bus->dispatch(new UpdateProviderProperty($provider['id'], self::ID, $availability));
                        }
                    }
                }
            }
        }

    }
}
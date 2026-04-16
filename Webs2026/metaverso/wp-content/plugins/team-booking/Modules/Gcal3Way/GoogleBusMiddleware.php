<?php

namespace VSHM\Modules\Gcal3Way;

use VSHF\Bus\Middleware;
use VSHM\Bus\CancelReservation;
use VSHM\Bus\ChangeReservationCustomer;
use VSHM\Bus\ChangeReservationDate;
use VSHM\Bus\ChangeReservationProvider;
use VSHM\Bus\ChangeReservationService;
use VSHM\Bus\ChangeReservationStatus;
use VSHM\Bus\CreateReservation;
use VSHM\Bus\DeleteAllReservations;
use VSHM\Bus\DeleteReservation;
use VSHM\Bus\DeleteReservationProperty;
use VSHM\Bus\DeleteService;
use VSHM\Bus\SaveSettings;
use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Bus\UpdateOrCreateServiceProperty;
use VSHM\Bus\UpdateProviderProperty;
use VSHM\Modules\Gcal2Ways;
use VSHM\Modules\Gcal3Way\Settings\GoogleAllowSlotCommands;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiApplicationName;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiClientId;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiClientSecret;
use VSHM\Modules\Gcal3Way\Settings\GoogleFetchDelay;
use VSHM\Modules\Gcal3Way\Settings\GoogleSettingBase;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Settings\AllowCart;
use VSHM\Settings\Provider\AllowedServices;
use VSHM\Settings\Provider\GoogleApiToken;
use VSHM\Settings\Provider\GoogleCalendars;
use VSHM\Settings\Reservation\AvailabilityId;
use VSHM\Settings\Reservation\GoogleCalendarEventId;
use VSHM\Settings\Reservation\GoogleCalendarId;
use VSHM\Settings\Service\Personal_EventTitleBooked;
use VSHM\Settings\Service\Personal_GcalCreateEvent;
use VSHM\Tools;

defined('ABSPATH') || exit;

class GoogleBusMiddleware extends Middleware
{

    public function before(): void
    {

        switch (TRUE) {
            case $this->command instanceof SaveSettings:
                $this->save_settings();
                break;
            case $this->command instanceof DeleteReservation && Gcal2Ways::is_configured():
                $this->delete_event_callback();
                break;
            case $this->command instanceof DeleteAllReservations && Gcal2Ways::is_configured():
                // TODO
                break;
            case $this->command instanceof ChangeReservationStatus && Gcal2Ways::is_configured():
                $this->create_event_callback();
                break;
            case $this->command instanceof ChangeReservationService && Gcal2Ways::is_configured():
                $this->edit_event_service_callback();
                break;
            case $this->command instanceof ChangeReservationDate && Gcal2Ways::is_configured():
                $this->edit_event_date_callback();
                break;
            case $this->command instanceof ChangeReservationProvider && Gcal2Ways::is_configured():
                $this->edit_event_provider_callback();
                break;
            default:
                break;
        }

        $this->next();
    }

    public function after(): void
    {


        switch (TRUE) {
            case $this->command instanceof DeleteService:
                $this->delete_service();
                break;
            case $this->command instanceof UpdateOrCreateServiceProperty:
                $this->update_service_property();
                break;
            case $this->command instanceof UpdateProviderProperty:
                $this->update_provider_property();
                break;
            case $this->command instanceof ChangeReservationCustomer && Gcal2Ways::is_configured():
                $this->change_reservation_customer_callback();
                break;
            case $this->command instanceof CancelReservation:
                $this->delete_event_callback();
                break;
            case $this->command instanceof CreateReservation && Gcal2Ways::is_configured():
                $this->create_event_callback();
                break;
            default:
                break;
        }
    }

    public function edit_event_provider_callback(): void
    {
        /** @var $command ChangeReservationProvider */
        $command = $this->command;

        $reservation    = Reservations::provideBy(['id' => $command->getId()], TRUE);
        $originCalendar = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => AvailabilityId::ID], TRUE);
        $gcalEventId    = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => GoogleCalendarEventId::ID], TRUE);

        if (!$gcalEventId || !$originCalendar) {
            return;
        }

        $provider     = ServiceProviders::provideBy(['id' => $command->getProviderId()], TRUE);
        $old_provider = ServiceProviders::provideBy(['id' => $reservation->providerId], TRUE);
        $service      = Services::provideBy(['id' => $reservation->serviceId], TRUE);

        $destination = Gcal2Ways::get_destination_from_availability_id($originCalendar, $old_provider);

        if (!$old_provider || !$service || !$destination || !$old_provider[ GoogleApiToken::ID ]) {
            return;
        }

        /**
         * Removing old provider Gcal event
         */
        if ($otherReservations = Gcal2Ways::_are_there_other_reservations($command->getId())) {
            OperateEvent::removeReservation([
                'provider_id'  => $old_provider['id'],
                'destination'  => $destination,
                'eventId'      => $gcalEventId,
                'service_id'   => $service->id,
                'customer_id'  => $reservation->customerId,
                'reservations' => $otherReservations
            ]);

        } else {
            try {
                OperateEvent::remove([
                    'provider_id' => $old_provider['id'],
                    'eventId'     => $gcalEventId,
                    'destination' => $destination,
                ]);
            } catch (\Exception $e) {
                // Skipping
                Tools::log_dump($e->getMessage());
            }
        }
        vshm()->bus->dispatch(new DeleteReservationProperty($command->getId(), GoogleCalendarEventId::ID));
        vshm()->bus->dispatch(new DeleteReservationProperty($command->getId(), GoogleCalendarId::ID));
        vshm()->bus->dispatch(new DeleteReservationProperty($command->getId(), AvailabilityId::ID));

        /**
         * Adding new provider Gcal event
         */
        if (!$provider || !$provider[ GoogleApiToken::ID ] || !isset($provider[ GoogleCalendars::ID ])) {
            return;
        }
        foreach ($provider[ GoogleCalendars::ID ] as $calendarId => $calendar) {
            if (in_array($reservation->serviceId, $calendar['services'], TRUE)) {
                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($command->getId(), AvailabilityId::ID, $calendarId));

                if ($calendar[ Gcal2Ways::CALENDAR_DESTINATION ]) {
                    if ($otherReservations = Gcal2Ways::_are_there_other_reservations($command->getId())) {
                        $newGcalEventId      = ReservationsData::provideBy(['reservation_id' => $otherReservations[0]['id'], 'key' => GoogleCalendarEventId::ID], TRUE);
                        $otherReservations[] = $command->getId();
                        OperateEvent::addReservation([
                            'provider_id'  => $command->getProviderId(),
                            'customer_id'  => $reservation->customerId,
                            'service_id'   => $reservation->serviceId,
                            'destination'  => $calendar[ Gcal2Ways::CALENDAR_DESTINATION ],
                            'eventId'      => $newGcalEventId,
                            'reservations' => $otherReservations
                        ]);
                        vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($command->getId(), GoogleCalendarEventId::ID, $newGcalEventId));
                    } else {
                        OperateEvent::create([
                            'reservation_id' => $command->getId(),
                            'provider_id'    => $command->getProviderId(),
                            'service_id'     => $reservation->serviceId,
                            'customer_id'    => $reservation->customerId,
                            'start'          => $reservation->start,
                            'end'            => $reservation->end,
                            'destination'    => $calendar[ Gcal2Ways::CALENDAR_DESTINATION ],
                            'reservations'   => [$reservation->id]
                        ]);
                    }
                }

                break;
            }
        }
    }

    public function edit_event_date_callback(): void
    {
        /** @var $command ChangeReservationDate */
        $command = $this->command;

        $reservation    = Reservations::provideBy(['id' => $command->getId()], TRUE);
        $originCalendar = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => AvailabilityId::ID], TRUE);
        $gcalEventId    = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => GoogleCalendarEventId::ID], TRUE);

        if (!$gcalEventId || !$originCalendar) {
            return;
        }

        $provider = ServiceProviders::provideBy(['id' => $reservation->providerId], TRUE);
        $service  = Services::provideBy(['id' => $reservation->serviceId], TRUE);

        $destination = Gcal2Ways::get_destination_from_availability_id($originCalendar, $provider);

        if (!$provider || !$service || !$destination || !$provider[ GoogleApiToken::ID ]) {
            return;
        }

        if ($otherReservations = Gcal2Ways::_are_there_other_reservations($command->getId())) {

            OperateEvent::removeReservation([
                'provider_id'  => $reservation->providerId,
                'customer_id'  => $reservation->customerId,
                'service_id'   => $reservation->serviceId,
                'destination'  => $destination,
                'eventId'      => $gcalEventId,
                'reservations' => $otherReservations
            ]);

            if ($otherReservations = Gcal2Ways::_are_there_other_reservations($command->getId())) {
                $newGcalEventId      = ReservationsData::provideBy(['reservation_id' => $otherReservations[0]['id'], 'key' => GoogleCalendarEventId::ID], TRUE);
                $otherReservations[] = $command->getId();
                OperateEvent::addReservation([
                    'provider_id'  => $reservation->providerId,
                    'customer_id'  => $reservation->customerId,
                    'service_id'   => $reservation->serviceId,
                    'destination'  => $destination,
                    'eventId'      => $newGcalEventId,
                    'reservations' => $otherReservations
                ]);
                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($command->getId(), GoogleCalendarEventId::ID, $newGcalEventId));
            } else {
                OperateEvent::create([
                    'provider_id'    => $reservation->providerId,
                    'reservation_id' => $command->getId(),
                    'service_id'     => $reservation->serviceId,
                    'customer_id'    => $reservation->customerId,
                    'destination'    => $destination,
                    'start'          => $command->getReference() === 'start' ? $command->getUnixTime() : $reservation->start,
                    'end'            => $command->getReference() === 'end' ? $command->getUnixTime() : $reservation->end,
                    'reservations'   => [$reservation->id]
                ]);
            }


        } else {
            $client = Gcal2Ways::_client();
            $client->setAccessToken($provider[ GoogleApiToken::ID ]);
            $g_service = new \Google\Service\Calendar($client);

            try {
                $event = $g_service->events->get($destination, $gcalEventId);
                if ($event instanceof \Google\Service\Calendar\Event) {
                    $date = new \Google\Service\Calendar\EventDateTime();
                    $date->setDateTime(DateTimeTbk::createFromFormatSilently('U', $command->getUnixTime())->format(\DATE_RFC3339));
                    if ($command->getReference() === 'start') {
                        $event->setStart($date);
                    } else {
                        $event->setEnd($date);
                    }
                    $event->setTransparency('opaque');
                    $g_service->events->update($destination, $gcalEventId, $event);
                }
            } catch (\Exception $e) {
                // Skipping
                Tools::log_dump($e->getMessage());
            }
        }

    }

    public function edit_event_service_callback(): void
    {
        /** @var $command ChangeReservationService */
        $command = $this->command;

        $reservation    = Reservations::provideBy(['id' => $command->getId()], TRUE);
        $originCalendar = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => AvailabilityId::ID], TRUE);
        $gcalEventId    = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => GoogleCalendarEventId::ID], TRUE);

        if (!$gcalEventId || !$originCalendar) {
            return;
        }

        $provider = ServiceProviders::provideBy(['id' => $reservation->providerId], TRUE);
        $service  = Services::provideBy(['id' => $command->getServiceId()], TRUE);

        $destination = Gcal2Ways::get_destination_from_availability_id($originCalendar, $provider);

        if (!$provider || !$service || !$destination || !$provider[ GoogleApiToken::ID ]) {
            return;
        }

        if ($service->class === 'unscheduled') {
            if ($otherReservations = Gcal2Ways::_are_there_other_reservations($command->getId())) {
                OperateEvent::removeReservation([
                    'provider_id'  => $reservation->providerId,
                    'customer_id'  => $reservation->customerId,
                    'service_id'   => $reservation->serviceId, //old service!
                    'destination'  => $destination,
                    'eventId'      => $gcalEventId,
                    'reservations' => $otherReservations
                ]);
            } else {
                OperateEvent::remove([
                    'provider_id' => $reservation->providerId,
                    'destination' => $destination,
                    'eventId'     => $gcalEventId
                ]);
            }
            vshm()->bus->dispatch(new DeleteReservationProperty($command->getId(), GoogleCalendarEventId::ID));
            vshm()->bus->dispatch(new DeleteReservationProperty($command->getId(), GoogleCalendarId::ID));

        } else {
            if ($otherReservations = Gcal2Ways::_are_there_other_reservations($command->getId())) {
                OperateEvent::removeReservation([
                    'provider_id'  => $reservation->providerId,
                    'customer_id'  => $reservation->customerId,
                    'service_id'   => $reservation->serviceId, //old service!
                    'destination'  => $destination,
                    'eventId'      => $gcalEventId,
                    'reservations' => $otherReservations
                ]);

                $newOriginCalendar = NULL;
                $newDestination    = NULL;
                foreach ($provider[ GoogleCalendars::ID ] as $calendarId => $calendar) {
                    if (in_array($command->getServiceId(), $calendar['services'], TRUE)) {
                        $newOriginCalendar = $calendarId;
                        $newDestination    = $calendar[ Gcal2Ways::CALENDAR_DESTINATION ] ?? NULL;
                        break;
                    }
                }

                if ($newOriginCalendar) {
                    vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($command->getId(), AvailabilityId::ID, $newOriginCalendar));

                    if ($newDestination) {
                        if ($otherReservations = Gcal2Ways::_are_there_other_reservations($command->getId())) {
                            $newGcalEventId      = ReservationsData::provideBy(['reservation_id' => $otherReservations[0]['id'], 'key' => GoogleCalendarEventId::ID], TRUE);
                            $otherReservations[] = $command->getId();
                            OperateEvent::addReservation([
                                'provider_id'  => $reservation->providerId,
                                'customer_id'  => $reservation->customerId,
                                'service_id'   => $command->getServiceId(),
                                'destination'  => $newDestination,
                                'eventId'      => $newGcalEventId,
                                'reservations' => $otherReservations
                            ]);
                            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($command->getId(), GoogleCalendarId::ID, $newDestination));
                            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($command->getId(), GoogleCalendarEventId::ID, $newGcalEventId));
                        } else {
                            OperateEvent::create([
                                'provider_id'    => $reservation->providerId,
                                'reservation_id' => $command->getId(),
                                'service_id'     => $command->getServiceId(),
                                'customer_id'    => $reservation->customerId,
                                'destination'    => $newDestination,
                                'start'          => $reservation->start,
                                'end'            => $reservation->end,
                                'reservations'   => [$reservation->id]
                            ]);
                        }
                    }

                }


            } else {
                try {
                    $client = Gcal2Ways::_client();
                    $client->setAccessToken($provider[ GoogleApiToken::ID ]);
                    $g_service = new \Google\Service\Calendar($client);
                    $event     = $g_service->events->get($destination, $gcalEventId);
                    if ($event instanceof \Google\Service\Calendar\Event) {
                        $eventSummary = ServiceProviderCustomData::provideByWithDefault([
                            'provider_id' => $reservation->providerId,
                            'service_id'  => $command->getServiceId(),
                            'key'         => Personal_EventTitleBooked::ID
                        ], $service);
                        $event->setSummary(Gcal2Ways::parseDynamicHooks($eventSummary, $command->getId()));
                        $event->setTransparency('opaque');
                        $g_service->events->update($destination, $gcalEventId, $event);
                    }
                } catch (\Exception $e) {
                    // Skipping
                    Tools::log_dump($e->getMessage());
                }
            }
        }
    }

    public function create_event_callback(): void
    {
        /** @var $command CreateReservation|ChangeReservationStatus */
        $command = $this->command;

        // TODO: @NEXT, more reliable system to discriminate automated workflows
        if ($command instanceof ChangeReservationStatus && $this->agent_type !== vshm()->bus::AGENT_SYSTEM) {
            return;
        }
        $reservation_id = NULL;
        $providerId     = NULL;
        $serviceId      = NULL;
        $start          = NULL;
        $end            = NULL;
        $customerId     = NULL;
        $status         = NULL;
        if ($command instanceof CreateReservation) {
            $reservation_id = $command->getId();
            $providerId     = $command->getProviderId();
            $serviceId      = $command->getServiceId();
            $start          = $command->getStart();
            $end            = $command->getEnd();
            $customerId     = $command->getUserId();
            $status         = $command->getStatus();
        } elseif ($command instanceof ChangeReservationStatus) {
            $reservation_id = $command->getId();
            $reservation    = Reservations::provideBy(['id' => $command->getId()], TRUE);
            $providerId     = $reservation->providerId;
            $serviceId      = $reservation->serviceId;
            $start          = $reservation->start;
            $end            = $reservation->end;
            $customerId     = $reservation->customerId;
            $status         = $command->getStatus();
        }

        self::createEvent($reservation_id, $providerId, $serviceId, $start, $end, $customerId, $status);
    }


    public function delete_event_callback(): void
    {
        /** @var $command DeleteReservation|CancelReservation */
        $command = $this->command;

        $reservation = Reservations::provideBy(['id' => $command->getId()], TRUE);
        $gcalEventId = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => GoogleCalendarEventId::ID], TRUE);
        $destination = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => GoogleCalendarId::ID], TRUE);
        $provider    = ServiceProviders::provideBy(['id' => $reservation->providerId], TRUE);
        $service     = Services::provideBy(['id' => $reservation->serviceId], TRUE);

        if ($provider
            && $destination
            && $provider[ GoogleApiToken::ID ]
            && $service
            && $gcalEventId
            && Gcal2Ways::is_configured()
        ) {

            if ($otherReservations = Gcal2Ways::_are_there_other_reservations_by_event_id($command->getId())) {
                OperateEvent::removeReservation([
                    'provider_id'  => $reservation->providerId,
                    'customer_id'  => $reservation->customerId,
                    'service_id'   => $reservation->serviceId,
                    'destination'  => $destination,
                    'eventId'      => $gcalEventId,
                    'reservations' => $otherReservations
                ]);
            } else {
                OperateEvent::remove([
                    'provider_id' => $reservation->providerId,
                    'destination' => $destination,
                    'eventId'     => $gcalEventId
                ]);
            }

        }
        vshm()->bus->dispatch(new DeleteReservationProperty($command->getId(), GoogleCalendarEventId::ID));
        vshm()->bus->dispatch(new DeleteReservationProperty($command->getId(), GoogleCalendarId::ID));
    }

    public function change_reservation_customer_callback(): void
    {
        /** @var $command ChangeReservationCustomer */
        $command     = $this->command;
        $reservation = Reservations::provideBy(['id' => $command->getId()], TRUE);

        if (!$reservation) {
            return;
        }

        $originCalendar = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => AvailabilityId::ID], TRUE);
        $gcalEventId    = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => GoogleCalendarEventId::ID], TRUE);

        if (!$gcalEventId || !$originCalendar) {
            return;
        }

        $provider = ServiceProviders::provideBy(['id' => $reservation->providerId], TRUE);

        $destination = Gcal2Ways::get_destination_from_availability_id($originCalendar, $provider);

        $otherReservations = Gcal2Ways::_are_there_other_reservations($reservation->id);
        if (!$otherReservations) {
            $otherReservations = [$command->getId()];
        } else {
            $otherReservations[] = $command->getId();
        }
        OperateEvent::addReservation([
            'reservations' => $otherReservations,
            'provider_id'  => $reservation->providerId,
            'service_id'   => $reservation->serviceId,
            'customer_id'  => $command->getCustomerId(),
            'eventId'      => $gcalEventId,
            'destination'  => $destination
        ]);
    }

    public function delete_service(): void
    {
        /** @var $command DeleteService */
        $command   = $this->command;
        $providers = ServiceProviders::provide();
        foreach ($providers as $provider) {
            $calendars = $provider[ GoogleCalendars::ID ];
            foreach ($calendars as $key => $calendar) {
                if (!isset($calendar['services'])) {
                    continue;
                }
                $service_key = array_search($command->getId(), $calendar['services'], TRUE);
                if ($service_key !== FALSE) {
                    unset($calendars[ $key ]['services'][ $service_key ]);
                    vshm()->bus->dispatch(new UpdateProviderProperty($provider['id'], GoogleCalendars::ID, $calendars));
                }
            }
        }
    }

    public function update_service_property(): void
    {
        /** @var $command UpdateOrCreateServiceProperty */
        $command   = $this->command;
        $providers = ServiceProviders::provide();

        if ($command->getKey() === 'class' && $command->getValue() === 'unscheduled') {
            foreach ($providers as $provider) {
                $calendars = $provider[ GoogleCalendars::ID ];
                foreach ($calendars as $key => $calendar) {
                    if (!isset($calendar['services'])) {
                        continue;
                    }
                    $service_key = array_search($command->getServiceId(), $calendar['services'], TRUE);
                    if ($service_key !== FALSE) {
                        unset($calendars[ $key ]['services'][ $service_key ]);
                        vshm()->bus->dispatch(new UpdateProviderProperty($provider['id'], GoogleCalendars::ID, $calendars));
                    }
                }
            }
        }
    }

    public function update_provider_property(): void
    {
        /** @var $command UpdateProviderProperty */
        $command   = $this->command;
        $providers = ServiceProviders::provide();

        if ($command->getKey() === AllowedServices::ID) {
            foreach ($providers as $provider) {
                if ($provider['id'] === $command->getProviderId()) {
                    $calendars = $provider[ GoogleCalendars::ID ];
                    if (is_array($calendars)) {
                        foreach ($calendars as $key => $calendar) {
                            if (!isset($calendar['services'])) {
                                continue;
                            }
                            $calendars[ $key ]['services'] = array_values(array_intersect($command->getValue(), $calendar['services']));
                        }
                        vshm()->bus->dispatch(new UpdateProviderProperty($provider['id'], GoogleCalendars::ID, $calendars));
                    }
                }
            }
        }
    }

    public function save_settings(): void
    {
        /** @var $command SaveSettings */
        $command  = $this->command;
        $settings = $command->getSettings();
        $to_save  = [];
        foreach ($settings as $key => $setting) {
            if (
                $key === GoogleApiClientId::ID
                || $key === GoogleApiClientSecret::ID
                || $key === GoogleApiApplicationName::ID
                || $key === GoogleAllowSlotCommands::ID
                || $key === GoogleFetchDelay::ID
            ) {
                $to_save[ $key ] = $setting;
                unset($settings[ $key ]);
            }
        }
        $command->setSettings($settings);
        $options = $to_save + vshm()->settings->getAllByContextRaw(GoogleSettingBase::CONTEXT);
        update_option(Gcal2Ways::OPTIONS_TAG, $options);
    }

    /**
     * @param $reservation_id
     * @param $providerId
     * @param $serviceId
     * @param $start
     * @param $end
     * @param $customerId
     * @param $status
     * @param $force
     *
     * @return bool
     */
    public static function createEvent($reservation_id, $providerId, $serviceId, $start, $end, $customerId, $status, $force = FALSE): bool
    {
        $originCalendar = ReservationsData::provideBy(['reservation_id' => $reservation_id, 'key' => AvailabilityId::ID], TRUE);
        $gcalEventId    = ReservationsData::provideBy(['reservation_id' => $reservation_id, 'key' => GoogleCalendarEventId::ID], TRUE);
        $provider       = ServiceProviders::provideBy(['id' => $providerId], TRUE);
        $service        = Services::provideBy(['id' => $serviceId], TRUE);

        if (!ServiceProviderCustomData::provideByWithDefault([
            'provider_id' => $providerId,
            'service_id'  => $serviceId,
            'key'         => Personal_GcalCreateEvent::ID
        ], $service)) {
            return FALSE;
        }

        $destination = Gcal2Ways::get_destination_from_availability_id($originCalendar, $provider);

        if ($originCalendar
            && $provider
            && $destination
            && $provider[ GoogleApiToken::ID ]
            && $service
            && $service->class !== 'unscheduled'
            && (!$gcalEventId || $force)
            && $status === 'confirmed'
        ) {
            $to_be_created = [];
            $to_be_updated = [];
            $create        = TRUE;

            $otherReservations = Gcal2Ways::_are_there_other_reservations($reservation_id);

            /*
             * @NEXT: at the moment, if multiple slot selection is active, one event per reservation is created to keep things simple.
             */
            if (!empty($otherReservations) && !vshm()->settings->get(AllowCart::ID)) {
                $existingEventId = ReservationsData::provideBy(['reservation_id' => $otherReservations[0], 'key' => GoogleCalendarEventId::ID], TRUE);
                if ($existingEventId) {
                    $create = FALSE;
                }
            }

            if ($create) {
                OperateEvent::create([
                    'reservation_id' => $reservation_id,
                    'provider_id'    => $providerId,
                    'service_id'     => $serviceId,
                    'customer_id'    => $customerId,
                    'start'          => $start,
                    'end'            => $end,
                    'destination'    => $destination,
                    'reservations'   => [$reservation_id]
                ]);
            } else {
                $otherReservations[] = $reservation_id;
                OperateEvent::addReservation([
                    'reservations' => $otherReservations,
                    'provider_id'  => $providerId,
                    'service_id'   => $serviceId,
                    'customer_id'  => $customerId,
                    'destination'  => $destination,
                    'eventId'      => $existingEventId
                ]);
                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservation_id, GoogleCalendarEventId::ID, $existingEventId));
                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservation_id, GoogleCalendarId::ID, $destination));
            }

            return TRUE;

        }

        return FALSE;
    }
}
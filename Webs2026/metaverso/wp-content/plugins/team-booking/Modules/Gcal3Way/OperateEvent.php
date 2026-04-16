<?php

namespace VSHM\Modules\Gcal3Way;

use VSHM\Bus\DeleteReservationProperty;
use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Modules\Gcal2Ways;
use VSHM\Modules\Notifications;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\Settings\Customer\CustomerSettingBase;
use VSHM\Settings\Customer\Email;
use VSHM\Settings\Customer\Name;
use VSHM\Settings\Customer\Phone;
use VSHM\Settings\Location\Address;
use VSHM\Settings\Location\LocationSettingBase;
use VSHM\Settings\Provider\GoogleApiToken;
use VSHM\Settings\Reservation\GoogleCalendarEventId;
use VSHM\Settings\Reservation\GoogleCalendarId;
use VSHM\Settings\Reservation\GoogleMeetId;
use VSHM\Settings\Reservation\Location;
use VSHM\Settings\Reservation\LocationOverride;
use VSHM\Settings\Reservation\Tickets;
use VSHM\Settings\Service\Personal_EventColorBooked;
use VSHM\Settings\Service\Personal_EventTitleBooked;
use VSHM\Settings\Service\Personal_GcalAddGuests;
use VSHM\Settings\Service\Personal_GcalCreateMeet;
use VSHM\Settings\Service\Personal_GcalEventDescriptionContent;
use VSHM\Settings\Service\Personal_GcalEventDescriptionCustomContent;
use VSHM\Tools;

defined('ABSPATH') || exit;

class OperateEvent
{

    /**
     * @param array $config
     *
     * @return void
     */
    public static function create(array $config): void
    {
        $provider = ServiceProviders::provideBy(['id' => $config['provider_id']], TRUE);
        $service  = Services::provideBy(['id' => $config['service_id']], TRUE);

        $client = Gcal2Ways::_client();
        $client->setAccessToken($provider[ GoogleApiToken::ID ]);
        $g_service    = new \Google\Service\Calendar($client);
        $eventSummary = ServiceProviderCustomData::provideByWithDefault([
            'provider_id' => $config['provider_id'],
            'service_id'  => $config['service_id'],
            'key'         => Personal_EventTitleBooked::ID
        ], $service);

        $eventColor = ServiceProviderCustomData::provideByWithDefault([
            'provider_id' => $config['provider_id'],
            'service_id'  => $config['service_id'],
            'key'         => Personal_EventColorBooked::ID
        ], $service);

        $g_start = new \Google\Service\Calendar\EventDateTime();
        $g_start->setDateTime(DateTimeTbk::createFromFormatSilently('U', $config['start'])->format(\DATE_RFC3339));
        $g_end = new \Google\Service\Calendar\EventDateTime();
        $g_end->setDateTime(DateTimeTbk::createFromFormatSilently('U', $config['end'])->format(\DATE_RFC3339));
        $event = new \Google\Service\Calendar\Event();
        $event->setSummary(Gcal2Ways::parseDynamicHooks($eventSummary, $config['reservation_id']));
        $event->setStart($g_start);
        $event->setEnd($g_end);
        $event->setTransparency('opaque');

        if ($eventColor) {
            $event->setColorId($eventColor);
        }

        $location = ReservationsData::provideBy(['reservation_id' => $config['reservation_id'], 'key' => LocationOverride::ID], TRUE);
        if (!$location) {
            $location = ReservationsData::provideBy(['reservation_id' => $config['reservation_id'], 'key' => Location::ID], TRUE);
        }
        if (!$location) {
            $locationId = ServicesData::provideBy(['service_id' => $config['service_id'], 'key' => \VSHM\Settings\Service\Location::ID], TRUE);
            if ($locationId) {
                try {
                    $locationAddress = vshm()->settings->getProperty(
                        Address::ID,
                        LocationSettingBase::CONTEXT,
                        $locationId
                    );
                    $location        = $locationAddress;
                } catch (\UnexpectedValueException $e) {
                    // LocationId not found
                }
            }
        }
        if ($location) {
            $event->setLocation($location);
        }

        $descriptionSetting = ServiceProviderCustomData::provideByWithDefault([
            'provider_id' => $config['provider_id'],
            'service_id'  => $config['service_id'],
            'key'         => Personal_GcalEventDescriptionContent::ID
        ], $service);

        if ($descriptionSetting === Personal_GcalEventDescriptionContent::CUSTOMER_DATA) {
            $tickets = ReservationsData::provideBy(['reservation_id' => $config['reservation_id'], 'key' => Tickets::ID], TRUE);
            try {
                $customerName  = vshm()->settings->getProperty(
                    Name::ID,
                    CustomerSettingBase::CONTEXT,
                    $config['customer_id']
                );
                $customerEmail = vshm()->settings->getProperty(
                    Email::ID,
                    CustomerSettingBase::CONTEXT,
                    $config['customer_id']
                );
                $customerPhone = vshm()->settings->getProperty(
                    Phone::ID,
                    CustomerSettingBase::CONTEXT,
                    $config['customer_id']
                );
                $event->setDescription(
                    $customerName . ' '
                    . ($customerPhone ? ($customerPhone . ' ') : '')
                    . $customerEmail . ' '
                    . ($tickets ? ('+' . $tickets . ' ') : '')
                    . "\n"
                );
            } catch (\UnexpectedValueException $e) {
                // Only possible if the customer is not found.
            }

        } elseif ($descriptionSetting === Personal_GcalEventDescriptionContent::CUSTOM_CONTENT) {
            $description = '';

            $descriptionContent = ServiceProviderCustomData::provideByWithDefault([
                'provider_id' => $config['provider_id'],
                'service_id'  => $config['service_id'],
                'key'         => Personal_GcalEventDescriptionCustomContent::ID
            ], $service);

            if (isset($config['reservations']) && is_array($config['reservations'])) {
                foreach ($config['reservations'] as $reservationId) {
                    $placeholders = Notifications::prepare_placeholders($reservationId);
                    $description  .= Notifications::find_and_replace_hooks($descriptionContent, $placeholders) . "\n\n";
                }
            }

            $event->setDescription(strip_tags($description, '<a>'));
        }

        $event->setAnyoneCanAddSelf(FALSE);
        $event->setGuestsCanInviteOthers(FALSE);
        $event->setGuestsCanModify(FALSE);
        $event->setGuestsCanSeeOtherGuests(FALSE);

        $guestSetting = ServiceProviderCustomData::provideByWithDefault([
            'provider_id' => $config['provider_id'],
            'service_id'  => $config['service_id'],
            'key'         => Personal_GcalAddGuests::ID
        ], $service);

        if ($guestSetting) {
            try {
                $customerName  = vshm()->settings->getProperty(
                    Name::ID,
                    CustomerSettingBase::CONTEXT,
                    $config['customer_id']
                );
                $customerEmail = vshm()->settings->getProperty(
                    Email::ID,
                    CustomerSettingBase::CONTEXT,
                    $config['customer_id']
                );
                $guest         = new \Google\Service\Calendar\EventAttendee();
                $guest->setDisplayName($customerName);
                $guest->setEmail($customerEmail);
                $guest->setResponseStatus('accepted');
                $guest->setComment($customerName);
                $event->setAttendees([$guest]);
            } catch (\UnexpectedValueException $e) {
                // Only possible if the customer is not found.
            }
        }

        try {
            if (ServiceProviderCustomData::provideByWithDefault([
                'provider_id' => $config['provider_id'],
                'service_id'  => $config['service_id'],
                'key'         => Personal_GcalCreateMeet::ID
            ], $service)) {
                $conference        = new \Google\Service\Calendar\ConferenceData();
                $conferenceRequest = new \Google\Service\Calendar\CreateConferenceRequest();
                $conferenceRequest->setRequestId(Tools::generate_token());
                $conference->setCreateRequest($conferenceRequest);
                $event->setConferenceData($conference);
                $event = $g_service->events->insert($config['destination'], $event, ['conferenceDataVersion' => 1]);
                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($config['reservation_id'], GoogleMeetId::ID, $event->getHangoutLink()));
            } else {
                $event = $g_service->events->insert($config['destination'], $event);
            }
            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($config['reservation_id'], GoogleCalendarEventId::ID, $event->getId()));
            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($config['reservation_id'], GoogleCalendarId::ID, $config['destination']));
        } catch (\Google\Service\Exception $e) {
            // Skipping
            Tools::log_dump($e->getErrors());
        }
    }

    public static function remove(array $config): void
    {
        $provider = ServiceProviders::provideBy(['id' => $config['provider_id']], TRUE);
        $client   = Gcal2Ways::_client();
        $client->setAccessToken($provider[ GoogleApiToken::ID ]);
        $g_service = new \Google\Service\Calendar($client);
        try {
            $g_service->events->delete($config['destination'], $config['eventId']);
        } catch (\Exception $e) {
            // Skipping
            Tools::log_dump($e->getMessage());
        }
    }

    /**
     * [provider_id]
     * [eventId]
     * [destination]
     * [service_id]
     * [reservations]
     * [customer_id]
     *
     * @param array $config
     *
     * @return void
     */
    public static function addReservation(array $config): void
    {
        $provider = ServiceProviders::provideBy(['id' => $config['provider_id']], TRUE);
        $service  = Services::provideBy(['id' => $config['service_id']], TRUE);

        if (!$provider[ GoogleApiToken::ID ]) {
            error_log('Google API token cannot be found for provider ' . $config['provider_id']);

            return;
        }

        $client = Gcal2Ways::_client();
        $client->setAccessToken($provider[ GoogleApiToken::ID ]);
        $g_service = new \Google\Service\Calendar($client);

        try {
            $event = $g_service->events->get($config['destination'], $config['eventId']);
            $event->setLocation(NULL);

            foreach ($config['reservations'] as $reservationId) {
                $command = $event->getHangoutLink()
                    ? new UpdateOrCreateReservationProperty($reservationId, GoogleMeetId::ID, $event->getHangoutLink())
                    : new DeleteReservationProperty($reservationId, GoogleMeetId::ID);
                vshm()->bus->dispatch($command);
            }

            $descriptionSetting = ServiceProviderCustomData::provideByWithDefault([
                'provider_id' => $config['provider_id'],
                'service_id'  => $config['service_id'],
                'key'         => Personal_GcalEventDescriptionContent::ID
            ], $service);

            if ($descriptionSetting === Personal_GcalEventDescriptionContent::CUSTOMER_DATA) {
                $description = '';
                foreach ($config['reservations'] as $reservationId) {
                    $reservation = Reservations::provideBy(['id' => $reservationId], TRUE);
                    $tickets     = ReservationsData::provideBy(['reservation_id' => $reservationId, 'key' => Tickets::ID], TRUE);

                    try {
                        $customerName  = vshm()->settings->getProperty(
                            Name::ID,
                            CustomerSettingBase::CONTEXT,
                            $reservation->customerId
                        );
                        $customerEmail = vshm()->settings->getProperty(
                            Email::ID,
                            CustomerSettingBase::CONTEXT,
                            $reservation->customerId
                        );
                        $customerPhone = vshm()->settings->getProperty(
                            Phone::ID,
                            CustomerSettingBase::CONTEXT,
                            $reservation->customerId
                        );
                        $description   .= $customerName . ' '
                            . ($customerPhone ? ($customerPhone . ' ') : '')
                            . $customerEmail . ' '
                            . ($tickets ? ('+' . $tickets . ' ') : '')
                            . "\n";
                    } catch (\UnexpectedValueException $e) {
                        // Only possible if the customer is not found.
                    }
                }
                $event->setDescription($description);
            } elseif ($descriptionSetting === Personal_GcalEventDescriptionContent::CUSTOM_CONTENT) {
                $description = '';

                $descriptionContent = ServiceProviderCustomData::provideByWithDefault([
                    'provider_id' => $config['provider_id'],
                    'service_id'  => $config['service_id'],
                    'key'         => Personal_GcalEventDescriptionCustomContent::ID
                ], $service);

                foreach ($config['reservations'] as $reservationId) {
                    $placeholders = Notifications::prepare_placeholders($reservationId);
                    $description  .= Notifications::find_and_replace_hooks($descriptionContent, $placeholders) . "\n\n";
                }

                $event->setDescription(strip_tags($description, '<a>'));
            }

            $guestSetting = ServiceProviderCustomData::provideByWithDefault([
                'provider_id' => $config['provider_id'],
                'service_id'  => $config['service_id'],
                'key'         => Personal_GcalAddGuests::ID
            ], $service);

            if ($guestSetting) {

                try {
                    $customerName  = vshm()->settings->getProperty(
                        Name::ID,
                        CustomerSettingBase::CONTEXT,
                        $config['customer_id']
                    );
                    $customerEmail = vshm()->settings->getProperty(
                        Email::ID,
                        CustomerSettingBase::CONTEXT,
                        $config['customer_id']
                    );
                    $guest         = new \Google\Service\Calendar\EventAttendee();
                    $guest->setDisplayName($customerName);
                    $guest->setEmail($customerEmail);
                    $guest->setResponseStatus('accepted');
                    $guest->setComment($customerName);
                    $attendees   = $event->getAttendees();
                    $attendees[] = $guest;
                    $event->setAttendees($attendees);
                } catch (\UnexpectedValueException $e) {
                    // Only possible if the customer is not found.
                }
            }

            $g_service->events->update($config['destination'], $config['eventId'], $event);

        } catch (\Exception $e) {
            // Skipping
            Tools::log_dump($e->getMessage());
        }
    }

    /**
     * [provider_id]
     * [eventId]
     * [destination]
     * [service_id]
     * [reservations]
     * [customer_id]
     *
     * @param array $config
     *
     * @return void
     */
    public static function removeReservation(array $config): void
    {
        $provider = ServiceProviders::provideBy(['id' => $config['provider_id']], TRUE);
        $service  = Services::provideBy(['id' => $config['service_id']], TRUE);
        $client   = Gcal2Ways::_client();
        $client->setAccessToken($provider[ GoogleApiToken::ID ]);
        $g_service = new \Google\Service\Calendar($client);
        try {
            $event = $g_service->events->get($config['destination'], $config['eventId']);
            $event->setLocation(NULL);
            $descriptionSetting = ServiceProviderCustomData::provideByWithDefault([
                'provider_id' => $config['provider_id'],
                'service_id'  => $config['service_id'],
                'key'         => Personal_GcalEventDescriptionContent::ID
            ], $service);

            if ($descriptionSetting === Personal_GcalEventDescriptionContent::CUSTOMER_DATA) {
                $description = '';
                foreach ($config['reservations'] as $reservationId) {
                    $reservation = Reservations::provideBy(['id' => $reservationId], TRUE);
                    $tickets     = ReservationsData::provideBy(['reservation_id' => $reservationId, 'key' => Tickets::ID], TRUE);

                    try {
                        $customerName  = vshm()->settings->getProperty(
                            Name::ID,
                            CustomerSettingBase::CONTEXT,
                            $reservation->customerId
                        );
                        $customerEmail = vshm()->settings->getProperty(
                            Email::ID,
                            CustomerSettingBase::CONTEXT,
                            $reservation->customerId
                        );
                        $customerPhone = vshm()->settings->getProperty(
                            Phone::ID,
                            CustomerSettingBase::CONTEXT,
                            $reservation->customerId
                        );
                        $description   .= $customerName . ' '
                            . ($customerPhone ? ($customerPhone . ' ') : '')
                            . $customerEmail . ' '
                            . ($tickets ? ('+' . $tickets . ' ') : '')
                            . "\n";
                    } catch (\UnexpectedValueException $e) {
                        // Only possible if the customer is not found.
                    }

                }
                $event->setDescription($description);
            } elseif ($descriptionSetting === Personal_GcalEventDescriptionContent::CUSTOM_CONTENT) {
                $description = '';

                $descriptionContent = ServiceProviderCustomData::provideByWithDefault([
                    'provider_id' => $config['provider_id'],
                    'service_id'  => $config['service_id'],
                    'key'         => Personal_GcalEventDescriptionCustomContent::ID
                ], $service);

                foreach ($config['reservations'] as $reservationId) {
                    $placeholders = Notifications::prepare_placeholders($reservationId);
                    $description  .= Notifications::find_and_replace_hooks($descriptionContent, $placeholders) . "\n\n";
                }

                $event->setDescription(strip_tags($description, '<a>'));
            }

            $guestSetting = ServiceProviderCustomData::provideByWithDefault([
                'provider_id' => $config['provider_id'],
                'service_id'  => $config['service_id'],
                'key'         => Personal_GcalAddGuests::ID
            ], $service);

            if ($guestSetting) {
                try {
                    $customerEmail = vshm()->settings->getProperty(
                        Email::ID,
                        CustomerSettingBase::CONTEXT,
                        $config['customer_id']
                    );
                    $attendees     = $event->getAttendees();
                    foreach ($attendees as $key => $attendee) {
                        if ($attendee->getEmail() === $customerEmail) {
                            unset($attendees[ $key ]);
                        }
                    }
                    $event->setAttendees($attendees);
                } catch (\UnexpectedValueException $e) {
                    // Only possible if the customer is not found.
                }
            }

            $g_service->events->update($config['destination'], $config['eventId'], $event);

        } catch (\Exception $e) {
            // Skipping
            Tools::log_dump($e->getMessage());
        }
    }

}
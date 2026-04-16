<?php

namespace VSHM\Modules\Zoom;

use VSHF\Bus\Middleware;
use VSHM\Bus\ChangeReservationCustomer;
use VSHM\Bus\ChangeReservationDate;
use VSHM\Bus\ChangeReservationService;
use VSHM\Bus\ChangeReservationStatus;
use VSHM\Bus\CreateReservation;
use VSHM\Bus\DeleteReservation;
use VSHM\Bus\DeleteReservationProperty;
use VSHM\Bus\SaveSettings;
use VSHM\Modules\Zoom;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Settings\Customer\CustomerSettingBase;
use VSHM\Settings\Customer\Name;
use VSHM\Settings\Reservation\ZoomMeetingId;

defined('ABSPATH') || exit;

class ZoomBusMiddleware extends Middleware
{

    public function before(): void
    {

        switch (TRUE) {
            case $this->command instanceof SaveSettings:
                $this->save_settings();
                break;
            case $this->command instanceof DeleteReservation && Zoom::isConfigured():
                $this->delete_meeting_after_removing_res();
                break;
            default:
                break;
        }

        $this->next();
    }

    public function after(): void
    {
        if (!Zoom::isConfigured()) {
            return;
        }

        switch (TRUE) {
            case $this->command instanceof CreateReservation:
                $this->create_meeting_after_res();
                break;
            case $this->command instanceof ChangeReservationStatus:
                $this->change_reservation_status_callback();
                break;
            case $this->command instanceof ChangeReservationService:
                $this->change_reservation_service_callback();
                break;
            case $this->command instanceof ChangeReservationCustomer:
                $this->change_reservation_customer_callback();
                break;
            case $this->command instanceof ChangeReservationDate:
                $this->change_reservation_date_callback();
                break;
            default:
                break;
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
                $key === Zoom\Settings\ZoomJWTApiSecret::ID
                || $key === Zoom\Settings\ZoomJWTApiKey::ID
                || $key === Zoom\Settings\ZoomApiKey::ID
                || $key === Zoom\Settings\ZoomApiSecret::ID
                || $key === Zoom\Settings\ZoomAccountId::ID
                || $key === Zoom\Settings\ZoomAccessToken::ID
            ) {
                $to_save[ $key ] = $setting;
                unset($settings[ $key ]);
            }
        }
        $command->setSettings($settings);
        $options = $to_save + vshm()->settings->getAllByContextRaw(Zoom\Settings\ZoomSettingBase::CONTEXT);

        /**
         * Resetting the access token
         */
        $options[ Zoom\Settings\ZoomAccessToken::ID ] = Zoom\Settings\ZoomAccessToken::default();
        update_option(Zoom::OPTIONS_TAG, $options);
    }

    /**
     * @return void
     */
    public function create_meeting_after_res(): void
    {
        /** @var $command CreateReservation */
        $command     = $this->command;
        $reservation = Reservations::provideBy(['id' => $command->getId()], TRUE);
        if ($reservation && $reservation->status === 'confirmed') {
            Zoom::createMeeting($reservation);
        }
    }

    /**
     * @return void
     */
    public function delete_meeting_after_removing_res(): void
    {
        /** @var $command DeleteReservation */
        $command   = $this->command;
        $meetingId = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => ZoomMeetingId::ID], TRUE);
        if ($meetingId) {
            Zoom::deleteMeeting($meetingId);
        }
    }

    /**
     * @return void
     */
    public function change_reservation_status_callback(): void
    {
        /** @var $command ChangeReservationStatus */
        $command = $this->command;
        if ($this->agent_type === vshm()->bus::AGENT_USER) {
            // When AGENT_USER, we are facing "admin" changes of the status not in consequence of a specific action.
            return;
        }
        $reservation = Reservations::provideBy(['id' => $command->getId()], TRUE);
        if (!$reservation) {
            return;
        }
        $meetingId = ReservationsData::provideBy(['reservation_id' => $reservation->id, 'key' => ZoomMeetingId::ID], TRUE);
        switch ($reservation->status) {
            case 'confirmed':
                if (!$meetingId) {
                    Zoom::createMeeting($reservation);
                }
                break;
            case 'cancelled':
                if ($meetingId) {
                    Zoom::deleteMeeting($meetingId);
                    vshm()->bus->dispatch(new DeleteReservationProperty($reservation->id, ZoomMeetingId::ID));
                }
                break;
            default:
                break;
        }
    }

    /**
     * @return void
     */
    public function change_reservation_service_callback(): void
    {
        /** @var $command ChangeReservationService */
        $command     = $this->command;
        $reservation = Reservations::provideBy(['id' => $command->getId()], TRUE);
        if (!$reservation) {
            return;
        }
        $meetingId = ReservationsData::provideBy(['reservation_id' => $reservation->id, 'key' => ZoomMeetingId::ID], TRUE);

        if ($meetingId) {
            Zoom::deleteMeeting($meetingId);
            vshm()->bus->dispatch(new DeleteReservationProperty($reservation->id, ZoomMeetingId::ID));
        }
        Zoom::createMeeting($reservation);
    }

    /**
     * @return void
     */
    public function change_reservation_customer_callback(): void
    {
        /** @var $command ChangeReservationCustomer */
        $command = $this->command;

        $reservation = Reservations::provideBy(['id' => $command->getId()], TRUE);
        if (!$reservation) {
            return;
        }

        $meetingId = ReservationsData::provideBy(['reservation_id' => $reservation->id, 'key' => ZoomMeetingId::ID], TRUE);

        if ($meetingId) {

            try {
                Zoom::updateMeeting($meetingId, [
                    'agenda' => sprintf(
                    /* translators: %s: Name of a customer */
                        __('Meeting with %s', 'team-booking'),
                        vshm()->settings->getProperty(
                            Name::ID,
                            CustomerSettingBase::CONTEXT,
                            $reservation->customerId
                        )
                    ),
                ]);
            } catch (\UnexpectedValueException $e) {

            }
        }
    }

    /**
     * @return void
     */
    public function change_reservation_date_callback(): void
    {
        /** @var $command ChangeReservationDate */
        $command = $this->command;

        $reservation = Reservations::provideBy(['id' => $command->getId()], TRUE);
        if (!$reservation) {
            return;
        }

        $meetingId = ReservationsData::provideBy(['reservation_id' => $reservation->id, 'key' => ZoomMeetingId::ID], TRUE);

        if ($meetingId) {
            $startDate = DateTimeTbk::createFromFormatSilently('U', $reservation->start);
            Zoom::updateMeeting($meetingId, [
                'start_time' => $startDate->format('Y-m-d\TH:i:s\Z'),
                'duration'   => round(($reservation->end - $reservation->start) / 60),
            ]);
        }
    }
}
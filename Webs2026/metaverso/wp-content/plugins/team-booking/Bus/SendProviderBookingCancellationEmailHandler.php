<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Modules\Notifications;
use VSHM\Providers\Reservations;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Settings\Customer\CustomerSettingBase;
use VSHM\Settings\Customer\Email;
use VSHM\Settings\Customer\Name;
use VSHM\Settings\SenderEmail;
use VSHM\Settings\Service\Personal_CancellationEmailToProvider;

defined('ABSPATH') || exit;

/**
 * SendProviderBookingCancellationEmailHandler
 */
class SendProviderBookingCancellationEmailHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command SendProviderBookingCancellationEmail */

        $reservation = Reservations::provideBy(['id' => $command->getReservationId()], TRUE);

        if (!$reservation) {
            return;
        }

        try {
            $customerName  = vshm()->settings->getProperty(Name::ID, CustomerSettingBase::CONTEXT, $reservation->customerId);
            $customerEmail = vshm()->settings->getProperty(Email::ID, CustomerSettingBase::CONTEXT, $reservation->customerId);
        } catch (\UnexpectedValueException $e) {
            // Customer not found!

            return;
        }

        $service = Services::provideBy(['id' => $reservation->serviceId], TRUE);

        if (!$service) {
            return;
        }

        $subject_raw = ServiceProviderCustomData::provideByWithDefault([
            'service_id'  => $reservation->serviceId,
            'provider_id' => $reservation->providerId,
            'key'         => Personal_CancellationEmailToProvider::ID_SUBJECT
        ], $service);

        $body_raw = ServiceProviderCustomData::provideByWithDefault([
            'service_id'  => $reservation->serviceId,
            'provider_id' => $reservation->providerId,
            'key'         => Personal_CancellationEmailToProvider::ID_BODY
        ], $service);

        $provider = ServiceProviders::provideBy(['id' => $reservation->providerId], TRUE);

        $from = [
            'name'    => $customerName,
            'address' => !vshm()->settings->get(SenderEmail::ID)
                ? get_option('admin_email')
                : vshm()->settings->get(SenderEmail::ID)
        ];

        $preparedValues = Notifications::prepare_placeholders($command->getReservationId(), Personal_CancellationEmailToProvider::ID);

        $subject = wp_strip_all_tags(Notifications::find_and_replace_hooks($subject_raw, $preparedValues));
        $body    = Notifications::find_and_replace_hooks($body_raw, $preparedValues);

        $send_command = new SendEmail(
            $subject,
            $body,
            [$provider['email']],
            $from,
            [],
            [],
            [],
            [$customerEmail]
        );

        do_action('tbk_send_provider_booking_cancellation_email', $reservation, $send_command);

        vshm()->bus->dispatch($send_command);
    }
}
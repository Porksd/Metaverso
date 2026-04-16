<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Modules\Notifications;
use VSHM\Providers\Reservations;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\ServicesData;
use VSHM\Settings\Customer\CustomerSettingBase;
use VSHM\Settings\Customer\Email;
use VSHM\Settings\SenderEmail;
use VSHM\Settings\Service\CancellationEmailToCustomer;

defined('ABSPATH') || exit;

/**
 * SendCustomerBookingCancellationEmailHandler
 */
class SendCustomerBookingCancellationEmailHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command SendCustomerBookingCancellationEmail */

        $reservation = Reservations::provideBy(['id' => $command->getReservationId()], TRUE);

        if (!$reservation) {
            return;
        }

        try {
            $customerEmail = vshm()->settings->getProperty(Email::ID, CustomerSettingBase::CONTEXT, $reservation->customerId);
        } catch (\UnexpectedValueException $e) {
            // Customer not found!

            return;
        }

        $send_from   = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => CancellationEmailToCustomer::ID_SEND_FROM], TRUE);
        $subject_raw = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => CancellationEmailToCustomer::ID_SUBJECT], TRUE);
        $body_raw    = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => CancellationEmailToCustomer::ID_BODY], TRUE);

        $subject_raw = apply_filters('tbk_send_email_pre_content', $subject_raw, CancellationEmailToCustomer::ID_SUBJECT, $command->getReservationId());
        $body_raw    = apply_filters('tbk_send_email_pre_content', $body_raw, CancellationEmailToCustomer::ID_BODY, $command->getReservationId());

        $provider = ServiceProviders::provideBy(['id' => $reservation->providerId], TRUE);

        if ($send_from === CancellationEmailToCustomer::SEND_FROM_ADMIN) {
            $from = [
                'name'    => get_option('blogname'),
                'address' => !vshm()->settings->get(SenderEmail::ID)
                    ? get_option('admin_email')
                    : vshm()->settings->get(SenderEmail::ID)
            ];
        } else {
            $from = [
                'name'    => $provider['name'],
                'address' => $provider['email']
            ];
        }

        $preparedValues = Notifications::prepare_placeholders($command->getReservationId(), CancellationEmailToCustomer::ID);

        $subject = wp_strip_all_tags(Notifications::find_and_replace_hooks($subject_raw, $preparedValues));
        $body    = Notifications::find_and_replace_hooks($body_raw, $preparedValues);

        $send_command = new SendEmail(
            $subject,
            $body,
            [$customerEmail],
            $from
        );

        do_action('tbk_send_customer_booking_cancellation_email', $reservation, $send_command);

        vshm()->bus->dispatch($send_command);

    }
}
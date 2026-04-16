<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Modules\Notifications;
use VSHM\Providers\Files;
use VSHM\Providers\Reservations;
use VSHM\Providers\ServicesData;
use VSHM\Settings\Customer\CustomerSettingBase;
use VSHM\Settings\Customer\Email;
use VSHM\Settings\Customer\Name;
use VSHM\Settings\SenderEmail;
use VSHM\Settings\Service\ConfirmationEmailToAdmin;

defined('ABSPATH') || exit;

/**
 * SendAdminBookingNotificationEmailHandler
 */
class SendAdminBookingNotificationEmailHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command SendAdminBookingNotificationEmail */

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

        $send_to          = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => ConfirmationEmailToAdmin::ID_SEND_TO], TRUE);
        $send_attachments = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => ConfirmationEmailToAdmin::ID_ATTACHMENTS], TRUE);
        $subject_raw      = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => ConfirmationEmailToAdmin::ID_SUBJECT], TRUE);
        $body_raw         = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => ConfirmationEmailToAdmin::ID_BODY], TRUE);

        $attachments = [];

        if ($send_attachments) {
            $files = Files::provideByReservationId($command->getReservationId());
            foreach ($files as $hash => $file) {
                $attachments[] = $file['file'];
            }
        }

        $preparedValues = Notifications::prepare_placeholders($command->getReservationId(), ConfirmationEmailToAdmin::ID);

        $subject = strip_tags(Notifications::find_and_replace_hooks($subject_raw, $preparedValues));
        $body    = Notifications::find_and_replace_hooks($body_raw, $preparedValues);

        $recipients = explode(',', $send_to);

        foreach ($recipients as $recipient) {
            $recipient = trim($recipient);
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                error_log('Invalid email address: ' . $recipient);
                continue;
            }

            $from = [
                'name'    => $customerName,
                'address' => !vshm()->settings->get(SenderEmail::ID)
                    ? get_option('admin_email')
                    : vshm()->settings->get(SenderEmail::ID)
            ];

            $send_command = new SendEmail(
                $subject,
                $body,
                [$recipient],
                $from,
                $attachments,
                [],
                [],
                [$customerEmail]
            );

            do_action('tbk_send_admin_booking_notification_email', $reservation, $send_command);

            vshm()->bus->dispatch($send_command);

        }
    }
}
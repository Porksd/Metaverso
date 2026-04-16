<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\DB;
use VSHM\Modules\EventLogger;
use VSHM\Providers\Reservations;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * CleanLogsHandler
 *
 * @package VSHM\Bus
 */
class CleanLogsHandler implements HandlerInterface
{

    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CleanLogs */

        $reservation_ids = array_column(Reservations::provide(), 'id');
        if (!$reservation_ids) {
            return;
        }
        DB::deleteWhere(EventLogger::TABLE_NAME, [
            'event_type'   => [
                'operator' => 'IN',
                'value'    => [
                    Tools::get_short_classname(SendCustomerBookingConfirmationEmail::class),
                    Tools::get_short_classname(SendCustomerBookingReminderEmail::class),
                    Tools::get_short_classname(SendCustomerBookingCancellationEmail::class),
                    Tools::get_short_classname(SendAdminBookingCancellationEmail::class),
                    Tools::get_short_classname(SendAdminBookingNotificationEmail::class),
                    Tools::get_short_classname(SendProviderBookingConfirmationEmail::class),
                    Tools::get_short_classname(SendProviderBookingCancellationEmail::class),
                    Tools::get_short_classname(ChangeReservationProvider::class),
                    Tools::get_short_classname(ChangeReservationDate::class),
                    Tools::get_short_classname(ChangeReservationService::class),
                    Tools::get_short_classname(ChangeReservationStatus::class),
                    Tools::get_short_classname(ChangeReservationCustomer::class),
                    Tools::get_short_classname(RefundReservation::class),
                    Tools::get_short_classname(ConfirmReservation::class),
                    Tools::get_short_classname(DenyReservation::class),
                    Tools::get_short_classname(ApproveReservation::class),
                    Tools::get_short_classname(CancelReservation::class),
                ]
            ],
            'resource_ref' => [
                'operator' => 'NOT IN',
                'value'    => $reservation_ids
            ]
        ]);
    }
}
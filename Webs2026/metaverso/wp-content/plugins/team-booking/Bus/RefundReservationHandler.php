<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Settings\Reservation\Paid;
use VSHM\Settings\Reservation\Refund;

defined('ABSPATH') || exit;

/**
 * RefundReservationHandler
 *
 * @package VSHM\Bus
 */
class RefundReservationHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command RefundReservation */
        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);
        if ($reservation && !empty($command->getRefundData())) {
            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($command->getId(), Refund::ID, $command->getRefundData()));
            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($command->getId(), Paid::ID, FALSE));
        }
    }
}
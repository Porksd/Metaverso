<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Providers\Objects\Reservation;

defined('ABSPATH') || exit;

/**
 * DeleteReservationHandler
 *
 * @package VSHM\Bus
 */
class DeleteReservationHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteReservation */

        \VSHM\Providers\ReservationsData::removeBy(['reservation_id' => $command->getId()]);
        \VSHM\Providers\Reservations::remove(new Reservation([
            'id' => $command->getId()
        ]));
        \VSHM\Providers\FormEntries::removeBy(['reservation_id' => $command->getId()]);
    }
}
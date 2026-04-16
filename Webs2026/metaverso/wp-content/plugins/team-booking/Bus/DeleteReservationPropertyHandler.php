<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteReservationPropertyHandler
 *
 * @package VSHM\Bus
 */
class DeleteReservationPropertyHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteReservationProperty */

        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getReservationId()], TRUE);

        if ($reservation) {
            \VSHM\Providers\ReservationsData::removeBy(['reservation_id' => $command->getReservationId(), 'key' => $command->getKey()]);
        }
    }
}
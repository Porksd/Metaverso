<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * ChangeReservationDateHandler
 *
 * @package VSHM\Bus
 */
class ChangeReservationDateHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command ChangeReservationDate */
        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);
        if ($reservation) {
            if ($command->getReference() === 'start') {
                $reservation->start = $command->getUnixTime();
            }
            if ($command->getReference() === 'end') {
                $reservation->end = $command->getUnixTime();
            }

            \VSHM\Providers\Reservations::update($reservation);
        }
    }
}
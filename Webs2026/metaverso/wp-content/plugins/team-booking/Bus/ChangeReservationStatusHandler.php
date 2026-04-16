<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * ChangeReservationStatusHandler
 *
 * @package VSHM\Bus
 */
class ChangeReservationStatusHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command ChangeReservationStatus */
        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);
        if ($reservation) {
            $reservation->status = $command->getStatus();
            \VSHM\Providers\Reservations::update($reservation);
        }
    }
}
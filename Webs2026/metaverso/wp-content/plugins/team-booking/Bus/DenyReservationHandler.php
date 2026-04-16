<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DenyReservationHandler
 *
 * @package VSHM\Bus
 */
class DenyReservationHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DenyReservation */
        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);
        if ($reservation) {
            vshm()->bus->dispatch(new ChangeReservationStatus($command->getId(), 'cancelled'), vshm()->bus::AGENT_SYSTEM);
        }
    }
}
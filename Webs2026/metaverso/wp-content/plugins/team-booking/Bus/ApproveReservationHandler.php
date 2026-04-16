<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * ApproveReservationHandler
 *
 * @package VSHM\Bus
 */
class ApproveReservationHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command ApproveReservation */
        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);
        if ($reservation) {
            vshm()->bus->dispatch(new ChangeReservationStatus($command->getId(), 'confirmed'), vshm()->bus::AGENT_SYSTEM);
        }
    }
}
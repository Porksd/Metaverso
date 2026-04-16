<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * ChangeReservationServiceHandler
 *
 * @package VSHM\Bus
 */
class ChangeReservationServiceHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command ChangeReservationService */
        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);
        $service     = \VSHM\Providers\Services::provideBy(['id' => $command->getServiceId()], TRUE);
        if ($reservation && $service) {
            $reservation->serviceId = $command->getServiceId();
            \VSHM\Providers\Reservations::update($reservation);
        }
    }
}
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * ChangeReservationProviderHandler
 *
 * @package VSHM\Bus
 */
class ChangeReservationProviderHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command ChangeReservationProvider */
        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);
        if ($reservation) {
            $reservation->providerId = $command->getProviderId();
            \VSHM\Providers\Reservations::update($reservation);
        }
    }
}
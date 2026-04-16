<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Providers\Reservations;

defined('ABSPATH') || exit;

/**
 * CleanExpiredReservationsHandler
 *
 * @package VSHM\Bus
 */
class CleanExpiredReservationsHandler implements HandlerInterface
{

    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CleanExpiredReservations */
        foreach (Reservations::provideBy(['status' => 'pending']) as $reservation) {

            if (apply_filters('tbk_is_reservation_expired', FALSE, $reservation, $command->getData())) {
                vshm()->bus->dispatch(new DeleteReservation($reservation->id));
            }
        }


    }
}
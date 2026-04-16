<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * ChangeReservationCustomerHandler
 *
 * @package VSHM\Bus
 */
class ChangeReservationCustomerHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command ChangeReservationCustomer */
        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);
        if ($reservation) {
            $reservation->customerId = $command->getCustomerId();
            \VSHM\Providers\Reservations::update($reservation);
        }
    }
}
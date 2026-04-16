<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Settings\Reservation\Paid;

defined('ABSPATH') || exit;

/**
 * RegisterPaymentHandler
 *
 * @package VSHM\Bus
 */
class RegisterPaymentHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command RegisterPayment */
        foreach ($command->getReservationsIds() as $reservationsId) {
            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservationsId, 'paymentDetails', $command->getDetails()));
            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservationsId, Paid::ID, TRUE));
        }
    }
}
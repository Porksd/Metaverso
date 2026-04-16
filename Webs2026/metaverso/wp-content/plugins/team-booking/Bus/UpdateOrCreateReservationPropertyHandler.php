<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * UpdateOrCreateReservationPropertyHandler
 *
 * @package VSHM\Bus
 */
class UpdateOrCreateReservationPropertyHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command UpdateOrCreateReservationProperty */

        $reservation = \VSHM\Providers\Reservations::provideBy(['id' => $command->getId()], TRUE);

        if (!$reservation) {
            // Something is wrong with the command
            return;
        }

        $currentData = \VSHM\Providers\ReservationsData::provideBy([
            'key'            => $command->getPropKey(),
            'reservation_id' => $command->getId()
        ]);

        $record = [
            'reservation_id' => $command->getId(),
            'key'            => $command->getPropKey(),
            'value'          => $command->getPropValue(),
        ];

        if (count($currentData) > 0) {
            \VSHM\Providers\ReservationsData::update($record);
        } else {
            \VSHM\Providers\ReservationsData::store($record);
        }
    }
}
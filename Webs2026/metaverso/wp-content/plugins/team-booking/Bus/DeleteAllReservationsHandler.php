<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteAllReservationsHandler
 *
 * @package VSHM\Bus
 */
class DeleteAllReservationsHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteAllReservations */

        \VSHM\Providers\ReservationsData::removeAll();
        \VSHM\Providers\Reservations::removeAll($command->getReset());
        \VSHM\Providers\FormEntries::removeAll(TRUE);
    }
}
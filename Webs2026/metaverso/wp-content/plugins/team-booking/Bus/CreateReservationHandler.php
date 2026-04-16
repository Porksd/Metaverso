<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Providers\Objects\Reservation;
use VSHM\Providers\ReservationsData;

defined('ABSPATH') || exit;

/**
 * CreateReservationHandler
 *
 * @package VSHM\Bus
 */
class CreateReservationHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CreateReservation */
        \VSHM\Providers\Reservations::store(new Reservation(
            [
                'id'         => $command->getId(),
                'serviceId'  => $command->getServiceId(),
                'customerId' => $command->getUserId(),
                'providerId' => $command->getProviderId(),
                'start'      => $command->getStart(),
                'end'        => $command->getEnd(),
                'status'     => $command->getStatus()
            ]
        ));
        if (is_array($command->getData())) {
            foreach ($command->getData() as $data) {
                ReservationsData::store([
                    'reservation_id' => $command->getId(),
                    'key'            => $data['key'],
                    'value'          => $data['value']
                ]);
            }
        }

    }
}
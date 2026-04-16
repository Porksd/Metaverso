<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * CreateLocationHandler
 *
 * @package VSHM\Bus
 */
class CreateLocationHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CreateLocation */
        \VSHM\Providers\Locations::store([
            'name'     => $command->getName(),
            'id'       => $command->getId(),
            'address'  => $command->getAddress(),
            'status'   => $command->getStatus(),
            'capacity' => $command->getCapacity(),
            'lat'      => $command->getLat(),
            'long'     => $command->getLong(),
        ]);
    }
}
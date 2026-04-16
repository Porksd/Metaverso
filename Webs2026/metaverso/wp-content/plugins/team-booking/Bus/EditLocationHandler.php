<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * EditLocationHandler
 *
 * @package VSHM\Bus
 */
class EditLocationHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command EditLocation */
        \VSHM\Providers\Locations::update([
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
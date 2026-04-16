<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteLocationHandler
 *
 * @package VSHM\Bus
 */
class DeleteLocationHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteLocation */
        \VSHM\Providers\Locations::remove([
            'id' => $command->getId(),
        ]);
    }
}
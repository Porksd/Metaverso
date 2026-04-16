<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteServiceHandler
 *
 * @package VSHM\Bus
 */
class DeleteServiceHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteService */
        \VSHM\Providers\Services::removeBy(['id' => $command->getId()]);
    }
}
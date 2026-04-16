<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteFormHandler
 *
 * @package VSHM\Bus
 */
class DeleteFormHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteForm */
        \VSHM\Providers\Forms::remove(['id' => $command->getId()]);
    }
}
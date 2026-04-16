<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * UpdateFormHandler
 *
 * @package VSHM\Bus
 */
class UpdateFormHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command UpdateForm */
        \VSHM\Providers\Forms::update([
            'id'       => $command->getId(),
            'fields'   => $command->getFields(),
            'required' => $command->getRequired(),
            'active'   => $command->getActive(),
            'logic'    => $command->getLogic(),
        ]);
    }
}
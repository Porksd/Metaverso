<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * CreateFormHandler
 *
 * @package VSHM\Bus
 */
class CreateFormHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CreateForm */
        \VSHM\Providers\Forms::store([
            'id'       => $command->getId(),
            'fields'   => $command->getFields(),
            'required' => $command->getRequired(),
            'active'   => $command->getActive(),
            'logic'    => $command->getLogic(),
        ]);
    }
}
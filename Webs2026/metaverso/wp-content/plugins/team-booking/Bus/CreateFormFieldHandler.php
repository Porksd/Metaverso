<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * CreateFormFieldHandler
 *
 * @package VSHM\Bus
 */
class CreateFormFieldHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CreateFormField */
        \VSHM\Providers\FormFields::store([
            'id'          => $command->getId(),
            'type'        => $command->getType(),
            'hook'        => $command->getHook(),
            'label'       => $command->getLabel(),
            'description' => $command->getDescription(),
            'data'        => $command->getData(),
        ]);
    }
}
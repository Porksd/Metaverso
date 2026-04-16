<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * UpdateFormFieldHandler
 *
 * @package VSHM\Bus
 */
class UpdateFormFieldHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command UpdateFormField */
        \VSHM\Providers\FormFields::update([
            'id'          => $command->getId(),
            'type'        => $command->getType(),
            'hook'        => $command->getHook(),
            'label'       => $command->getLabel(),
            'description' => $command->getDescription(),
            'data'        => $command->getData(),
        ]);
    }
}
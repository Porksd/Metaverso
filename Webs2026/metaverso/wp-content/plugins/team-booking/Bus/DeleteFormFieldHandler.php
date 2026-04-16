<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteFormFieldHandler
 *
 * @package VSHM\Bus
 */
class DeleteFormFieldHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteFormField */
        \VSHM\Providers\FormFields::remove(['id' => $command->getId()]);
    }
}
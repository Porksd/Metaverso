<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteServicePropertiesHandler
 *
 * @package VSHM\Bus
 */
class DeleteServicePropertiesHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteServiceProperties */
        \VSHM\Providers\ServicesData::removeBy(['service_id' => $command->getId()]);
    }
}
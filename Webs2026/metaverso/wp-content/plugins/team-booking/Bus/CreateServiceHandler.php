<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Providers\Objects\Service;

defined('ABSPATH') || exit;

/**
 * CreateServiceHandler
 *
 * @package VSHM\Bus
 */
class CreateServiceHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CreateService */
        $service              = new Service();
        $service->id          = $command->getId();
        $service->name        = $command->getName();
        $service->description = $command->getDescription();
        $service->color       = $command->getColor();
        $service->status      = apply_filters('tbk_default_service_status', 1, $command);
        $service->class       = $command->getClass();

        \VSHM\Providers\Services::store($service);
    }
}
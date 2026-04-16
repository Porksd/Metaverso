<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * UpdateOrCreateServicePropertyHandler
 *
 * @package VSHM\Bus
 */
class UpdateOrCreateServicePropertyHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command UpdateOrCreateServiceProperty */

        $service = \VSHM\Providers\Services::provideBy(['id' => $command->getServiceId()], TRUE);

        if (!$service) {
            // Something is wrong with the command
            return;
        }

        $property = $command->getKey();

        if (
            $property !== 'id'
            && property_exists($service, $property)
            && $service->$property !== $command->getValue()
        ) {
            $service->$property = $command->getValue();
            \VSHM\Providers\Services::update($service);

            return;
        }

        $currentData = \VSHM\Providers\ServicesData::provideBy([
            'key'        => $command->getKey(),
            'service_id' => $command->getServiceId()
        ], FALSE, FALSE);

        $record = [
            'service_id' => $command->getServiceId(),
            'key'        => $command->getKey(),
            'value'      => $command->getValue(),
        ];

        if (count($currentData) > 0) {
            \VSHM\Providers\ServicesData::update($record);
        } else {
            \VSHM\Providers\ServicesData::store($record);
        }
    }
}
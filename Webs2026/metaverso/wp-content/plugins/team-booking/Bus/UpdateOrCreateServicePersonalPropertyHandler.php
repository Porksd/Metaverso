<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * UpdateOrCreateServicePersonalPropertyHandler
 *
 * @package VSHM\Bus
 */
class UpdateOrCreateServicePersonalPropertyHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command UpdateOrCreateServicePersonalProperty */

        $currentData = \VSHM\Providers\ServiceProviderCustomData::provideBy([
            'key'         => $command->getKey(),
            'provider_id' => $command->getProviderId(),
            'service_id'  => $command->getServiceId()
        ]);

        $record = [
            'service_id'  => $command->getServiceId(),
            'key'         => $command->getKey(),
            'provider_id' => $command->getProviderId(),
            'value'       => $command->getValue(),
        ];

        if (count($currentData) > 0) {
            \VSHM\Providers\ServiceProviderCustomData::update($record);
        } else {
            \VSHM\Providers\ServiceProviderCustomData::store($record);
        }
    }
}
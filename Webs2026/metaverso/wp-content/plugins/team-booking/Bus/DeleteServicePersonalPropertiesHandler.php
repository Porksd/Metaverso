<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteServicePersonalPropertiesHandler
 *
 * @package VSHM\Bus
 */
class DeleteServicePersonalPropertiesHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteServicePersonalProperties */
        if (NULL !== $command->getProviderId() && NULL !== $command->getServiceId()) {
            \VSHM\Providers\ServiceProviderCustomData::removeBy(['service_id' => $command->getServiceId(), 'provider_id' => $command->getProviderId()]);
        } elseif (NULL !== $command->getProviderId()) {
            \VSHM\Providers\ServiceProviderCustomData::removeBy(['provider_id' => $command->getProviderId()]);
        } elseif (NULL !== $command->getServiceId()) {
            \VSHM\Providers\ServiceProviderCustomData::removeBy(['service_id' => $command->getServiceId()]);
        }
    }
}
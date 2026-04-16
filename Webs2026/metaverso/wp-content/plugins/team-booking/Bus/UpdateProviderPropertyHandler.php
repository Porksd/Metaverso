<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * UpdateProviderPropertyHandler
 *
 * @package VSHM\Bus
 */
class UpdateProviderPropertyHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command UpdateProviderProperty */

        $provider = \VSHM\Providers\ServiceProviders::provideBy(['id' => $command->getProviderId()], TRUE);

        if (!$provider) {
            // Something is wrong with the command
            return;
        }

        if (isset($provider[ $command->getKey() ]) && $provider[ $command->getKey() ] !== $command->getValue()) {
            $provider[ $command->getKey() ] = $command->getValue();
            \VSHM\Providers\ServiceProviders::update($provider);
        }
    }
}
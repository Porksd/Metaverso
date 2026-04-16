<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * CreateApiTokenHandler
 *
 * @package VSHM\Bus
 */
class CreateApiTokenHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CreateApiToken */
        \VSHM\Providers\ApiTokens::store([
            'name'     => $command->getName(),
            'token'    => $command->getToken(),
            'usages'   => 0,
            'readonly' => $command->getReadOnly(),
        ]);
    }
}
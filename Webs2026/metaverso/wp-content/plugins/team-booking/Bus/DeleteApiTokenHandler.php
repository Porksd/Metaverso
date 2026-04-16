<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteApiTokenHandler
 *
 * @package VSHM\Bus
 */
class DeleteApiTokenHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteApiToken */
        \VSHM\Providers\ApiTokens::remove(['token' => $command->getToken()]);
    }
}
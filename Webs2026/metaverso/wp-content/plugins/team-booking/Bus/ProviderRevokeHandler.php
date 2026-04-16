<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * ProviderRevokeHandler
 *
 * @package VSHM\Bus
 */
class ProviderRevokeHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command ProviderRevoke */
        // TODO
    }
}
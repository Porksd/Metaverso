<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * UseApiTokenHandler
 *
 * @package VSHM\Bus
 */
class UseApiTokenHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command UseApiToken */

        $token           = $command->getToken();
        $token['usages'] = (int)$token['usages'] + 1;
        \VSHM\Providers\ApiTokens::update($token);
    }
}
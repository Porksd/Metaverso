<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeleteCustomerHandler
 *
 * @package VSHM\Bus
 */
class DeleteCustomerHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteCustomer */
        \VSHM\Providers\Customers::remove(['id' => $command->getId()]);
    }
}
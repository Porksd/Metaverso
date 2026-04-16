<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * EditCustomerHandler
 *
 * @package VSHM\Bus
 */
class EditCustomerHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command EditCustomer */
        \VSHM\Providers\Customers::update([
            'id'           => $command->getId(),
            'name'         => $command->getName(),
            'email'        => $command->getEmail(),
            'phone'        => $command->getPhone(),
            'wp_user'      => $command->getWpUserId(),
            'access_token' => $command->getToken(),
            'status'       => $command->getStatus()
        ]);
    }
}
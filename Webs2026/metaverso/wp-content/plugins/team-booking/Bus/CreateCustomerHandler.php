<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * CreateCustomerHandler
 *
 * @package VSHM\Bus
 */
class CreateCustomerHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CreateCustomer */
        \VSHM\Providers\Customers::store([
            'id'           => $command->getId(),
            'name'         => $command->getName(),
            'email'        => $command->getEmail(),
            'phone'        => $command->getPhone(),
            'wp_user'      => $command->getWpUserId(),
            'access_token' => Tools::generate_token(),
            'status'       => apply_filters('tbk_default_customer_status', 1, $command),
        ]);
    }
}
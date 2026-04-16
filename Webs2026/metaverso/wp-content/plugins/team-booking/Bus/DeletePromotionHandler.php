<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * DeletePromotionHandler
 *
 * @package VSHM\Bus
 */
class DeletePromotionHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeletePromotion */
        \VSHM\Providers\Promotions::remove(['id' => $command->getId()]);
    }
}
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * EditPromotionHandler
 *
 * @package VSHM\Bus
 */
class EditPromotionHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command EditPromotion */
        \VSHM\Providers\Promotions::update([
            'id'                    => $command->getId(),
            'promotionPeriod_start' => $command->getStart(),
            'promotionPeriod_end'   => $command->getEnd(),
            'promotionName'         => $command->getName(),
            'promotionType'         => $command->getType(),
            'discountType'          => $command->getDiscountType(),
            'promotionValue'        => $command->getDiscountValue(),
            'status'                => $command->getStatus(),
            'data'                  => $command->getData(),
        ]);
    }
}
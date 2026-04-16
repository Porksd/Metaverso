<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Settings\Promotion\CouponMode;
use VSHM\Settings\Promotion\DiscountType;
use VSHM\Settings\Promotion\MaximumUses;
use VSHM\Settings\Promotion\Name;
use VSHM\Settings\Promotion\PromotionPeriod;
use VSHM\Settings\Promotion\PromotionServices;
use VSHM\Settings\Promotion\PromotionType;
use VSHM\Settings\Promotion\TimeslotMaxEnd;
use VSHM\Settings\Promotion\TimeslotMaxEndActive;
use VSHM\Settings\Promotion\TimeslotMinStart;
use VSHM\Settings\Promotion\TimeslotMinStartActive;
use VSHM\Settings\Promotion\Value;

defined('ABSPATH') || exit;

/**
 * CreatePromotionHandler
 *
 * @package VSHM\Bus
 */
class CreatePromotionHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CreatePromotion */
        \VSHM\Providers\Promotions::store([
            PromotionType::ID              => $command->getPromotionType(),
            Name::ID                       => $command->getName(),
            Value::ID                      => $command->getDiscountValue(),
            DiscountType::ID               => $command->getDiscountType(),
            'id'                           => $command->getId(),
            PromotionPeriod::ID . '_start' => $command->getStart(),
            PromotionPeriod::ID . '_end'   => $command->getEnd(),
            'status'                       => 1,
            'data'                         => [
                PromotionServices::ID      => PromotionServices::default($command->getId()),
                TimeslotMinStart::ID       => TimeslotMinStart::default($command->getId()),
                TimeslotMinStartActive::ID => TimeslotMinStartActive::default($command->getId()),
                TimeslotMaxEnd::ID         => TimeslotMaxEnd::default($command->getId()),
                TimeslotMaxEndActive::ID   => TimeslotMaxEndActive::default($command->getId()),
                CouponMode::ID             => CouponMode::default($command->getId()),
                MaximumUses::ID            => MaximumUses::default($command->getId())
            ]
        ]);
    }
}
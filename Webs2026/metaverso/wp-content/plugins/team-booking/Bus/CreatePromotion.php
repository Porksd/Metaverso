<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * CreatePromotion
 *
 * @package VSHM\Bus
 */
class CreatePromotion implements CommandInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $promotionType;

    /**
     * @var string
     */
    private $discountType;

    /**
     * @var string
     */
    private $discountValue;

    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $end;

    /**
     * @param $name
     * @param $promotionType
     * @param $discountType
     * @param $discountValue
     * @param $id
     * @param $start
     * @param $end
     */
    public function __construct($name, $promotionType, $discountType, $discountValue, $id, $start, $end)
    {
        $this->name          = $name;
        $this->promotionType = $promotionType;
        $this->discountType  = $discountType;
        $this->discountValue = $discountValue;
        $this->id            = $id;
        $this->start         = $start;
        $this->end           = $end;
    }

    /**
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPromotionType(): string
    {
        return $this->promotionType;
    }

    /**
     * @return string
     */
    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    /**
     * @return string
     */
    public function getDiscountValue(): string
    {
        return $this->discountValue;
    }


    public function getValue(): array
    {
        return [
            'name'          => $this->name,
            'id'            => $this->id,
            'promotionType' => $this->promotionType,
            'discountType'  => $this->discountType,
            'discountValue' => $this->discountValue,
            'start'         => $this->start,
            'end'           => $this->end,
        ];
    }

}
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * EditPromotion
 *
 * @package VSHM\Bus
 */
class EditPromotion implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $end;

    /**
     * @var int
     */
    private $discountValue;

    /**
     * @var string
     */
    private $discountType;

    /**
     * @var int
     */
    private $status;

    /**
     * @var array
     */
    private $data;

    /**
     * @param      $id
     * @param      $name
     * @param      $type
     * @param      $start
     * @param      $end
     * @param      $discountValue
     * @param      $discountType
     * @param      $status
     * @param      $data
     */
    public function __construct($id, $name, $type, $start, $end, $discountValue, $discountType, $status, $data)
    {
        $this->id            = $id;
        $this->name          = $name;
        $this->type          = $type;
        $this->start         = $start;
        $this->end           = $end;
        $this->discountValue = $discountValue;
        $this->discountType  = $discountType;
        $this->status        = $status;
        $this->data          = $data;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    /**
     * @return int
     */
    public function getDiscountValue(): int
    {
        return $this->discountValue;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    public function getValue(): array
    {
        return [
            'name'          => $this->name,
            'id'            => $this->id,
            'start'         => $this->start,
            'end'           => $this->end,
            'discountValue' => $this->discountValue,
            'discountType'  => $this->discountType,
            'type'          => $this->type,
            'status'        => $this->status,
            'data'          => $this->data
        ];
    }

}
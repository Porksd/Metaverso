<?php

namespace VSHM\Bus;

defined('ABSPATH') || exit;

/**
 * UpdateOrCreateReservationProperty
 *
 * @package VSHM\Bus
 */
class UpdateOrCreateReservationProperty extends ReservationAction
{
    /**
     * @var string
     */
    private $key;


    /**
     * @var mixed
     */
    private $value;

    /**
     * @param $reservationId
     * @param $key
     * @param $value
     */
    public function __construct($reservationId, $key, $value)
    {
        parent::__construct($reservationId);
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getPropKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getPropValue()
    {
        return $this->value;
    }

}
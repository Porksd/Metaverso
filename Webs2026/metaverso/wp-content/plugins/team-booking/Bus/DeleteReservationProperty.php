<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * DeleteReservationProperty
 *
 * @package VSHM\Bus
 */
class DeleteReservationProperty implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $key;

    /**
     * @param $reservationId
     * @param $key
     */
    public function __construct($reservationId, $key)
    {
        $this->id    = $reservationId;
        $this->key   = $key;
    }

    /**
     * @return string
     */
    public function getReservationId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

}
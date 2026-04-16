<?php

namespace VSHM\Bus;

defined('ABSPATH') || exit;

/**
 * ChangeReservationStatus
 *
 * @package VSHM\Bus
 */
class ChangeReservationStatus extends ReservationAction
{
    /**
     * @var string
     */
    private $status;

    /**
     * @param      $id
     * @param      $status
     */
    public function __construct($id, $status)
    {
        parent::__construct($id);
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getValue(): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,
        ];
    }

}
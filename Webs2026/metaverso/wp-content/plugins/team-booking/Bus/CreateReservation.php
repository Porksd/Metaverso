<?php

namespace VSHM\Bus;

defined('ABSPATH') || exit;

/**
 * CreateReservation
 *
 * @package VSHM\Bus
 */
class CreateReservation extends ReservationAction
{
    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var int
     */
    private $providerId;

    /**
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $end;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $status;

    /**
     * @param $id
     * @param $serviceId
     * @param $userId
     * @param $providerId
     * @param $start
     * @param $end
     * @param $data
     * @param $status
     */
    public function __construct($id, $serviceId, $userId, $providerId, $start, $end, $data, $status)
    {
        parent::__construct($id);
        $this->serviceId  = $serviceId;
        $this->userId     = $userId;
        $this->providerId = $providerId;
        $this->data       = $data;
        $this->status     = $status;
        $this->start      = $start;
        $this->end        = $end;
    }

    /**
     * @return string
     */
    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    /**
     * @return int
     */
    public function getProviderId(): int
    {
        return $this->providerId;
    }


    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
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
    public function getStatus(): string
    {
        return $this->status;
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
}
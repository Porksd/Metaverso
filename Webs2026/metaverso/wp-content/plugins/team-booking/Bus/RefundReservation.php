<?php

namespace VSHM\Bus;

defined('ABSPATH') || exit;

/**
 * RefundReservation
 *
 * @package VSHM\Bus
 */
class RefundReservation extends ReservationAction
{
    /**
     * @var array
     */
    protected $refundData;

    /**
     * @var string
     */
    protected $gatewayId;

    public function __construct(string $id, string $gatewayId, array $refundData = [])
    {
        parent::__construct($id);
        $this->refundData = $refundData;
        $this->gatewayId  = $gatewayId;
    }

    /**
     * @return array
     */
    public function getRefundData(): array
    {
        return $this->refundData;
    }

    /**
     * @param array $refundData
     */
    public function setRefundData(array $refundData): void
    {
        $this->refundData = $refundData;
    }

    /**
     * @return string
     */
    public function getGatewayId(): string
    {
        return $this->gatewayId;
    }
}
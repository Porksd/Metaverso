<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * RegisterPayment
 *
 * @package VSHM\Bus
 */
class RegisterPayment implements CommandInterface
{
    /**
     * @var array
     */
    private $reservations_ids;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $gateway_id;

    /**
     * @var array
     */
    private $details;

    /**
     * @param string $amount
     * @param string $gateway_id
     * @param array  $reservations_ids
     * @param array  $details
     */
    public function __construct(string $amount, string $gateway_id, array $reservations_ids = [], array $details = [])
    {
        $this->reservations_ids = $reservations_ids;
        $this->amount           = $amount;
        $this->gateway_id       = $gateway_id;
        $this->details          = $details;
    }

    /**
     * @return array
     */
    public function getReservationsIds(): array
    {
        return $this->reservations_ids;
    }

    /**
     * @return string
     */
    public function getGatewayId(): string
    {
        return $this->gateway_id;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    public function getValue(): array
    {
        return [
            'reservationsIds' => $this->reservations_ids,
            'gatewayId'       => $this->gateway_id,
            'amount'          => $this->amount,
            'details'         => $this->details,
        ];
    }
}
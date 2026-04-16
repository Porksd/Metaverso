<?php

namespace VSHM\Bus;

defined('ABSPATH') || exit;

/**
 * ChangeReservationCustomer
 *
 * @package VSHM\Bus
 */
class ChangeReservationCustomer extends ReservationAction
{
    /**
     * @var string
     */
    private $customer_id;

    /**
     * @param      $id
     * @param      $customerId
     */
    public function __construct($id, $customerId)
    {
        parent::__construct($id);
        $this->customer_id = $customerId;
    }

    /**
     * @return string
     */
    public function getCustomerId(): string
    {
        return $this->customer_id;
    }

    public function getValue(): array
    {
        return [
            'id'         => $this->id,
            'customerId' => $this->customer_id,
        ];
    }

}
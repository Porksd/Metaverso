<?php

namespace VSHM\Providers\Objects;

defined('ABSPATH') || exit;

class Reservation
{
    public function __construct($data = [])
    {
        foreach ($data as $key => $datum) {
            $this->$key = $datum;
        }
    }

    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $db_id;

    /**
     * @var string
     */
    public $serviceId;

    /**
     * @var string
     */
    public $providerId;

    /**
     * @var string
     */
    public $customerId;

    /**
     * @var int
     */
    public $start;

    /**
     * @var int
     */
    public $end;

    /**
     * @var string
     */
    public $status;

    /**
     * @var int
     */
    public $created;

    /**
     * @var array
     */
    public $data = [];
}
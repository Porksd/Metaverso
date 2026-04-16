<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * EditLocation
 *
 * @package VSHM\Bus
 */
class EditLocation implements CommandInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $lat;

    /**
     * @var string
     */
    private $long;

    /**
     * @var int
     */
    private $status;

    /**
     * @var int
     */
    private $capacity;

    /**
     * @var string
     */
    private $id;

    /**
     * @param      $id
     * @param      $name
     * @param      $address
     * @param      $status
     * @param      $lat
     * @param      $long
     * @param      $capacity
     */
    public function __construct($id, $name, $address, $status, $lat, $long, $capacity)
    {
        $this->id       = (string)$id;
        $this->name     = (string)$name;
        $this->address  = (string)$address;
        $this->status   = (int)$status;
        $this->lat      = (string)$lat;
        $this->long     = (string)$long;
        $this->capacity = (int)$capacity;
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
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getLat(): string
    {
        return $this->lat;
    }

    /**
     * @return string
     */
    public function getLong(): string
    {
        return $this->long;
    }

    /**
     * @return int
     */
    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getValue(): array
    {
        return [
            'name'     => $this->name,
            'address'  => $this->address,
            'status'   => $this->status,
            'lat'      => $this->lat,
            'long'     => $this->long,
            'capacity' => $this->capacity,
            'id'       => $this->id,
        ];
    }

}
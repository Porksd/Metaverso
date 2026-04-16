<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * ReservationAction
 *
 * @package VSHM\Bus
 */
abstract class ReservationAction implements CommandInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @param string|int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function getValue(): array
    {
        return [
            'id' => $this->id
        ];
    }
}
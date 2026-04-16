<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * DeleteAllReservations
 *
 * @package VSHM\Bus
 */
class DeleteAllReservations implements CommandInterface
{
    /**
     * @var bool
     */
    private $reset;

    public function __construct($reset)
    {
        $this->reset = (bool)$reset;
    }

    /**
     * @return bool
     */
    public function getReset(): bool
    {
        return $this->reset;
    }

}
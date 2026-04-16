<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * SendNotificationEmail
 *
 * @package VSHM\Bus
 */
abstract class SendNotificationEmail implements CommandInterface
{
    /**
     * @var string
     */
    private $reservation_id;

    public function __construct($reservation_id)
    {
        $this->reservation_id = $reservation_id;
    }

    /**
     * @return string
     */
    public function getReservationId(): string
    {
        return $this->reservation_id;
    }
}
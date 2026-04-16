<?php

namespace VSHM\Bus;

defined('ABSPATH') || exit;

/**
 * ChangeReservationDate
 *
 * @package VSHM\Bus
 */
class ChangeReservationDate extends ReservationAction
{
    /**
     * @var int
     */
    private $unixTime;

    /**
     * @var string
     */
    private $reference;

    /**
     * @param        $id
     * @param int    $unixTime
     * @param string $ref
     */
    public function __construct($id, int $unixTime, string $ref = 'start')
    {
        parent::__construct($id);
        $this->unixTime  = $unixTime;
        $this->reference = $ref;
    }

    /**
     * @return int
     */
    public function getUnixTime(): int
    {
        return $this->unixTime;
    }

    /**
     * @return string
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    public function getValue(): array
    {
        return [
            'id'        => $this->id,
            'unixTime'  => $this->unixTime,
            'reference' => $this->reference,
        ];
    }

}
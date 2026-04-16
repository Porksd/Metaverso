<?php

namespace VSHM\Bus;

defined('ABSPATH') || exit;

/**
 * ChangeReservationService
 *
 * @package VSHM\Bus
 */
class ChangeReservationService extends ReservationAction
{
    /**
     * @var string
     */
    private $service_id;

    /**
     * @param      $id
     * @param      $service_id
     */
    public function __construct($id, $service_id)
    {
        parent::__construct($id);
        $this->service_id = $service_id;
    }

    /**
     * @return string
     */
    public function getServiceId(): string
    {
        return $this->service_id;
    }

    public function getValue(): array
    {
        return [
            'id'        => $this->id,
            'serviceId' => $this->service_id,
        ];
    }

}
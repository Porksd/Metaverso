<?php

namespace VSHM\Bus;

defined('ABSPATH') || exit;

/**
 * ChangeReservationProvider
 *
 * @package VSHM\Bus
 */
class ChangeReservationProvider extends ReservationAction
{
    /**
     * @var string
     */
    private $provider_id;

    /**
     * @param      $id
     * @param      $provider_id
     */
    public function __construct($id, $provider_id)
    {
        parent::__construct($id);
        $this->provider_id = $provider_id;
    }

    /**
     * @return string
     */
    public function getProviderId(): string
    {
        return $this->provider_id;
    }

    public function getValue(): array
    {
        return [
            'id'         => $this->id,
            'providerId' => $this->provider_id,
        ];
    }

}
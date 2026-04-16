<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * DeleteServicePersonalProperties
 *
 * @package VSHM\Bus
 */
class DeleteServicePersonalProperties implements CommandInterface
{
    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var string
     */
    private $providerId;

    public function __construct($serviceId = NULL, $providerId = NULL)
    {
        $this->serviceId  = $serviceId;
        $this->providerId = $providerId;
    }

    /**
     * @return string
     */
    public function getServiceId(): ?string
    {
        return $this->serviceId;
    }

    /**
     * @return string
     */
    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

}
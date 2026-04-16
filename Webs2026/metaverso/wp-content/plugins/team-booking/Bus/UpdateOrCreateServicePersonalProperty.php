<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * UpdateOrCreateServicePersonalProperty
 *
 * @package VSHM\Bus
 */
class UpdateOrCreateServicePersonalProperty implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $providerId;

    /**
     * @var string
     */
    private $key;


    /**
     * @var mixed
     */
    private $value;

    /**
     * @param $serviceId
     * @param $providerId
     * @param $key
     * @param $value
     */
    public function __construct($serviceId, $providerId, $key, $value)
    {
        $this->id         = $serviceId;
        $this->providerId = $providerId;
        $this->key        = $key;
        $this->value      = $value;
    }

    /**
     * @return string
     */
    public function getServiceId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getProviderId(): string
    {
        return $this->providerId;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

}
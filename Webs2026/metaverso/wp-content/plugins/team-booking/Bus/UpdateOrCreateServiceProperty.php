<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * UpdateOrCreateServiceProperty
 *
 * @package VSHM\Bus
 */
class UpdateOrCreateServiceProperty implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param string $serviceId
     * @param string $key
     * @param        $value
     */
    public function __construct(string $serviceId, string $key, $value)
    {
        $this->id    = $serviceId;
        $this->key   = $key;
        $this->value = $value;
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
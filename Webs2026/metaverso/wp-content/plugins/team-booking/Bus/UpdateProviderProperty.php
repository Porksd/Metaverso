<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * UpdateProviderProperty
 *
 * @package VSHM\Bus
 */
class UpdateProviderProperty implements CommandInterface
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
     * @param $providerId
     * @param $key
     * @param $value
     */
    public function __construct($providerId, $key, $value)
    {
        $this->id    = $providerId;
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getProviderId(): string
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
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * SaveUserPrefs
 *
 * @package VSHM\Bus
 */
class SaveUserPrefs implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var mixed
     */
    private $value;


    public function __construct($id, $value)
    {
        $this->id    = $id;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param array $value
     */
    public function setValue(array $value): void
    {
        $this->value = $value;
    }
}
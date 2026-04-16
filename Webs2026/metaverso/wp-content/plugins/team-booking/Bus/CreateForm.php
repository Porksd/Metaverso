<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * CreateForm
 *
 * @package VSHM\Bus
 */
class CreateForm implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var array
     */
    private $required;

    /**
     * @var array
     */
    private $active;

    /**
     * @var array
     */
    private $logic;

    /**
     * @param $id
     * @param $fields
     * @param $required
     * @param $active
     * @param $logic
     */
    public function __construct($id, $fields = [], $required = [], $active = [], $logic = [])
    {
        $this->id       = $id;
        $this->fields   = $fields;
        $this->required = $required;
        $this->active   = $active;
        $this->logic    = $logic;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getRequired(): array
    {
        return $this->required;
    }

    /**
     * @return array
     */
    public function getActive(): array
    {
        return $this->active;
    }

    /**
     * @return array
     */
    public function getLogic(): array
    {
        return $this->logic;
    }

    public function getValue(): array
    {
        return [
            'id'       => $this->id,
            'fields'   => $this->fields,
            'required' => $this->required,
            'active'   => $this->active,
            'logic'    => $this->logic,
        ];
    }

}
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * CreateFormField
 *
 * @package VSHM\Bus
 */
class CreateFormField implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $hook;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $description;

    /**
     * @param $id
     * @param $type
     * @param $hook
     * @param $label
     * @param $data
     * @param $description
     */
    public function __construct($id, $type, $hook, $label, $data = [], $description = NULL)
    {
        $this->id          = $id;
        $this->type        = $type;
        $this->hook        = $hook;
        $this->label       = $label;
        $this->data        = $data;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getHook(): string
    {
        return $this->hook;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getValue(): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'hook'        => $this->hook,
            'label'       => $this->label,
            'description' => $this->description,
            'data'        => $this->data,
        ];
    }

}
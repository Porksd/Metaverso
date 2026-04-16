<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * CreateService
 *
 * @package VSHM\Bus
 */
class CreateService implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $color;

    /**
     * @param $id
     * @param $name
     * @param $description
     * @param $class
     * @param $color
     */
    public function __construct($id, $name, $description, $class, $color)
    {
        $this->id          = $id;
        $this->name        = $name;
        $this->description = $description;
        $this->class       = $class;
        $this->color       = $color;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }


    public function getValue(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'class'       => $this->class,
            'color'       => $this->color,
        ];
    }

}
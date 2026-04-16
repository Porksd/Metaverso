<?php

namespace VSHM\Providers\Objects;

defined('ABSPATH') || exit;

class Service
{
    public function __construct($data = [])
    {
        foreach ($data as $key => $datum) {
            $this->$key = $datum;
        }
    }

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $color;

    /**
     * @var int
     */
    public $status;

    /**
     * @var string
     */
    public $class;

    /**
     * @var int
     */
    public $created;

    /**
     * @var array
     */
    public $data = [];
}
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * DeleteCustomer
 *
 * @package VSHM\Bus
 */
class DeleteCustomer implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}
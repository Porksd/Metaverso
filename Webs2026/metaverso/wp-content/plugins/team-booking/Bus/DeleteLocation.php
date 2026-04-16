<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * DeleteLocation
 *
 * @package VSHM\Bus
 */
class DeleteLocation implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @param      $id
     */
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
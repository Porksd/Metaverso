<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * CleanFiles
 *
 * @package VSHM\Bus
 */
class CleanFiles implements CommandInterface
{
    /**
     * @var int
     */
    private $age;

    public function __construct($age)
    {
        $this->age = $age;
    }

    /**
     * @return int
     */
    public function getData(): int
    {
        return $this->age;
    }
}
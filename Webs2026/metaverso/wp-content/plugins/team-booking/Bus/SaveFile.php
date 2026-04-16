<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * SaveFile
 *
 * @package VSHM\Bus
 */
class SaveFile implements CommandInterface
{
    /**
     * @var array
     */
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->file;
    }
}
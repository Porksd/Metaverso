<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * DeleteFile
 *
 * @package VSHM\Bus
 */
class DeleteFile implements CommandInterface
{
    /**
     * @var string
     */
    private $fileHash;

    public function __construct($fileHash)
    {
        $this->fileHash = $fileHash;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->fileHash;
    }
}
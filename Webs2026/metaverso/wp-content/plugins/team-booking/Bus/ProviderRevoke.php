<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * ProviderRevoke
 *
 * @package VSHM\Bus
 */
class ProviderRevoke implements CommandInterface
{
    /**
     * @var int
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }


    public function getValue(): array
    {
        return [
            'id' => $this->id
        ];
    }

}
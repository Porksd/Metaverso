<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * DeleteFormField
 *
 * @package VSHM\Bus
 */
class DeleteFormField implements CommandInterface
{
    /**
     * @var string
     */
    private $id;


    /**
     * @param $id
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

    public function getValue(): array
    {
        return [
            'id' => $this->id
        ];
    }

}
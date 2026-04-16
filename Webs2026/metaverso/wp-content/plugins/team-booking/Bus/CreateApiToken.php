<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * CreateApiToken
 *
 * @package VSHM\Bus
 */
class CreateApiToken implements CommandInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $token;

    /**
     * @var bool
     */
    private $readOnly;

    /**
     * @param      $token
     * @param      $name
     * @param      $readOnly
     */
    public function __construct($token, $name, $readOnly)
    {
        $this->token    = $token;
        $this->name     = $name;
        $this->readOnly = $readOnly;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getReadOnly(): bool
    {
        return $this->readOnly;
    }


    public function getValue(): array
    {
        return [
            'token' => $this->token,
            'name'  => $this->name,
            'email' => $this->readOnly
        ];
    }

}
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * UseApiToken
 *
 * @package VSHM\Bus
 */
class UseApiToken implements CommandInterface
{
    /**
     * @var array
     */
    private $token;

    public function __construct(array $token)
    {
        $this->token = $token;
    }

    /**
     * @return array
     */
    public function getToken(): array
    {
        return $this->token;
    }

}
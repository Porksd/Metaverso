<?php

namespace VSHF\Bus;

/**
 * Class Middleware
 *
 * @package VSHF
 */
abstract class Middleware implements MiddlewareInterface
{
    /**
     * @var bool
     */
    private $next = FALSE;

    /**
     * @var CommandInterface
     */
    protected $command;

    /**
     * @var string
     */
    protected $agent_type;

    /**
     * @var string
     */
    protected $agent_id;

    /**
     * @param CommandInterface $command
     * @param string|NULL      $agent_type
     * @param string|NULL      $agent_id
     */
    public function __construct(
        CommandInterface $command,
        string           $agent_type = NULL,
        string           $agent_id = NULL
    )
    {
        $this->command    = $command;
        $this->agent_type = $agent_type;
        $this->agent_id   = $agent_id;
    }

    public function next(): void
    {
        $this->next = TRUE;
    }

    public function isNext(): bool
    {
        return $this->next;
    }

    public function before(): void
    {
        $this->next();
    }

    public function after(): void
    {
    }
}
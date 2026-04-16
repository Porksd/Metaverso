<?php

namespace VSHF\Bus;

/**
 * Class HandlerInterface
 *
 * @package VSHF
 */
interface HandlerInterface
{
    /**
     * @param CommandInterface $command
     *
     * @return mixed
     */
    public function dispatch(CommandInterface $command): void;
}
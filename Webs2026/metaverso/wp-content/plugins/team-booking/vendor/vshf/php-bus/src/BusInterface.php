<?php

namespace VSHF\Bus;

/**
 * Class BusInterface
 *
 * @package VSHF
 */
interface BusInterface
{
    /**
     * @param string $commandClassName
     * @param string $handlerClassName
     *
     * @return void
     */
    public function subscribe(string $commandClassName, string $handlerClassName): void;

    /**
     * @param CommandInterface $command
     *
     * @return bool
     */
    public function dispatch(CommandInterface $command): bool;
}
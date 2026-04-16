<?php

namespace VSHF\Bus;

/**
 * Class MiddlewareInterface
 *
 * @package VSHF
 */
interface MiddlewareInterface
{
    /**
     * The function that allows the command execution to continue
     *
     * @return void
     */
    public function next(): void;

    /**
     * Checks if the command execution must continue
     *
     * @return bool
     */
    public function isNext(): bool;

    /**
     * Side effects to run before the command execution
     *
     * @return void
     */
    public function before(): void;

    /**
     * Side effects to run after the command execution
     *
     * @return void
     */
    public function after(): void;
}
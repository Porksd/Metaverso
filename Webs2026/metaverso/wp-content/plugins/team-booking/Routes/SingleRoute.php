<?php

namespace VSHM\Routes;

defined('ABSPATH') || exit;

/**
 * Interface SingleRoute
 *
 * @package VSHM\Routes
 */
interface SingleRoute
{
    /**
     * @return array
     */
    public static function get(): array;

    /**
     * @return string
     */
    public static function getPath(): string;
}
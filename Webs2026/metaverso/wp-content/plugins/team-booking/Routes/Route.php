<?php

namespace VSHM\Routes;

defined('ABSPATH') || exit;

/**
 * Interface Route
 *
 * @package VSHM\Routes
 */
interface Route
{
    /**
     * @return void
     */
    public static function register();

    /**
     * @return string
     */
    public static function getPath();
}
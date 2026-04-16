<?php

namespace VSHM;

defined('ABSPATH') || exit;

/**
 * Interface Single
 *
 * @package VSHM
 */
abstract class Single
{
    /**
     * The single instances of the class.
     *
     * @var static[]
     */
    private static $instances = [];

    /**
     * Main Instance.
     *
     * Ensures only one instance is loaded or can be loaded.
     *
     * @param string $id
     * @param mixed  $cfg
     *
     * @return static - Main instance.
     */
    public static function instance($id, $cfg = NULL)
    {
        if (!isset(self::$instances[ $id ])) {
            self::$instances[ $id ] = new static($cfg);
        }

        return self::$instances[ $id ];
    }
}
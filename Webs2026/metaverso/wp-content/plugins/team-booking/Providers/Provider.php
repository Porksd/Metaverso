<?php

namespace VSHM\Providers;

defined('ABSPATH') || exit;

/**
 * Interface Provider
 *
 * @package VSHM\Providers
 */
interface Provider
{
    /**
     * @return mixed
     */
    public static function provide();

    /**
     * @return mixed
     */
    public static function subscribe();

    /**
     * @param array $conditions
     * @param bool  $single
     *
     * @return mixed
     */
    public static function provideBy(array $conditions, bool $single = FALSE);

    /**
     * @param mixed $data
     *
     * @return void
     */
    public static function store($data): void;

    /**
     * @param mixed $data
     *
     * @return void
     */
    public static function update($data): void;

    /**
     * @param mixed $data
     *
     * @return void
     */
    public static function remove($data): void;

    /**
     * @param array $conditions
     *
     * @return void
     */
    public static function removeBy(array $conditions): void;
}
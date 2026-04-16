<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Element_Setting
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
interface Element_Setting extends Element
{
    /**
     * @param string      $label
     * @param string|null $id
     *
     * @return static
     */
    public static function get(string $label, string $id = NULL);

    /**
     * @param string $description
     *
     * @return void
     */
    public function setDescription(string $description): void;

    /**
     * @param Alert $alert
     *
     * @return void
     */
    public function setAlert(Alert $alert): void;
}
<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class VSHM
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
interface Element
{
    /**
     * @return array
     */
    public function get_structure(): array;
}
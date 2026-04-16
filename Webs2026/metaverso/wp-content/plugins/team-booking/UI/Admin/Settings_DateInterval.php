<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_DateInterval
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_DateInterval extends Settings_Base
{
    private $showTimes = FALSE;

    /**
     * @param boolean $bool
     */
    public function showTimes($bool)
    {
        $this->showTimes = (bool)$bool;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = parent::get_structure();

        $structure['type']      = 'dateInterval';
        $structure['showTimes'] = $this->showTimes;

        return $structure;
    }
}
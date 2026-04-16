<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_DHM
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_DHM extends Settings_Base
{
    private $showDays    = TRUE;
    private $showHours   = TRUE;
    private $showMinutes = TRUE;
    private $maxDays     = 90;

    /**
     * @param boolean $bool
     */
    public function showDays($bool)
    {
        $this->showDays = (bool)$bool;
    }

    /**
     * @param boolean $bool
     */
    public function showMinutes($bool)
    {
        $this->showMinutes = (bool)$bool;
    }

    /**
     * @param boolean $bool
     */
    public function showHours($bool)
    {
        $this->showHours = (bool)$bool;
    }

    /**
     * @param int $days
     */
    public function maxDays($days)
    {
        $this->maxDays = $days;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = parent::get_structure();

        $structure['type']        = 'dhm';
        $structure['showDays']    = $this->showDays;
        $structure['showHours']   = $this->showHours;
        $structure['showMinutes'] = $this->showMinutes;
        $structure['maxDays']     = $this->maxDays;

        return $structure;
    }
}
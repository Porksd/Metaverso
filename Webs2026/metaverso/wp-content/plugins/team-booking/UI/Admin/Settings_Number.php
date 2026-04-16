<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Number
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Number extends Settings_Base
{
    private $prefix;
    private $step = 1;
    private $max;
    private $min  = 0;

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param int $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * @param int $max
     */
    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
     * @param int $min
     */
    public function setMin($min)
    {
        $this->min = $min;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = parent::get_structure();

        $structure['type'] = 'number';
        $structure['min']  = $this->min;
        $structure['step'] = $this->step;

        if ($this->prefix) {
            $structure['before'] = $this->prefix;
        }

        if ($this->max) {
            $structure['max'] = $this->max;
        }

        return $structure;
    }
}
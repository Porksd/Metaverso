<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Radios
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Radios extends Settings_Base
{
    private $options = [];
    private $color   = FALSE;

    /**
     * @param Settings_Option $option
     */
    public function addOption(Settings_Option $option)
    {
        $this->options[] = $option->get_structure();
    }

    /**
     * @param bool $color
     */
    public function setColor(bool $color): void
    {
        $this->color = $color;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = parent::get_structure();

        $structure['type']    = 'radios';
        $structure['options'] = $this->options;
        $structure['color']   = $this->color;

        return $structure;
    }
}
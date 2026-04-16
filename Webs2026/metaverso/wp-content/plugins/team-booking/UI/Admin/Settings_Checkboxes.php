<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Checkboxes
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Checkboxes extends Settings_Base
{
    private $options = [];

    /**
     * @param Settings_Option $option
     */
    public function addOption(Settings_Option $option)
    {
        $this->options[] = $option->get_structure();
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = parent::get_structure();

        $structure['type']    = 'checkboxes';
        $structure['options'] = $this->options;

        return $structure;
    }

}
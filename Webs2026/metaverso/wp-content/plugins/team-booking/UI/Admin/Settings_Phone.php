<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Phone
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Phone extends Settings_Base
{
    private $placeholder;


    /**
     * @param string $placeholder
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure         = parent::get_structure();
        $structure['type'] = 'phone';

        if ($this->placeholder) {
            $structure['placeholder'] = $this->placeholder;
        }

        return $structure;
    }
}
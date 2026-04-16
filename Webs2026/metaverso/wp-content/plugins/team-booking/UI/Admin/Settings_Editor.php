<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Editor
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Editor extends Settings_Base
{
    private $placeholder;
    private $useTags = FALSE;

    /**
     * @param string $placeholder
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
    }

    public function setUseTags($bool)
    {
        $this->useTags = (bool)$bool;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = parent::get_structure();

        $structure['type']    = 'editor';
        $structure['useTags'] = $this->useTags;

        if ($this->placeholder) {
            $structure['placeholder'] = $this->placeholder;
        }

        return $structure;
    }
}
<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Input
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Input extends Settings_Base
{
    private $placeholder;
    private $readonly = FALSE;

    /**
     * @param string|null $placeholder
     */
    public function setPlaceholder(?string $placeholder): void
    {
        $this->placeholder = $placeholder;
    }

    public function isReadOnly(bool $readonly): void
    {
        $this->readonly = $readonly;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure         = parent::get_structure();
        $structure['type'] = 'input';

        if ($this->placeholder) {
            $structure['placeholder'] = $this->placeholder;
        }

        if ($this->readonly) {
            $structure['readonly'] = TRUE;
        }

        return $structure;
    }
}
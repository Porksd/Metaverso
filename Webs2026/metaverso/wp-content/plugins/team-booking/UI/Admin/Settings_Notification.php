<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Notification
 *
 * _Send, _Body, _Subject are automatically appended to the ID
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Notification extends Settings_Base
{
    private $extra = [];

    /**
     * @param Element_Setting $setting
     */
    public function addExtra(Element_Setting $setting)
    {
        $this->extra[] = $setting->get_structure();
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = parent::get_structure();

        $structure['type']  = 'notification';
        $structure['extra'] = $this->extra;

        return $structure;
    }
}
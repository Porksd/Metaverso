<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_SelectWpUserEmail
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_SelectWpUserEmail extends Settings_Base
{
    private $role;

    /**
     * @param mixed $role
     */
    public function setRole($role): void
    {
        $this->role = $role;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = parent::get_structure();

        $structure['type'] = 'selectWpUserEmail';
        if ($this->role) {
            $structure['role'] = $this->role;
        }

        return $structure;
    }
}
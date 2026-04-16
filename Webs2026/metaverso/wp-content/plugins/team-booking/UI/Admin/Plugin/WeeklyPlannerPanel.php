<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Settings_Base;

defined('ABSPATH') || exit;

/**
 * Class WeeklyPlannerPanel
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class WeeklyPlannerPanel extends Settings_Base
{
    /**
     * @param string      $label
     * @param string|null $id
     *
     * @return self
     */
    public static function get(string $label, string $id = NULL): WeeklyPlannerPanel
    {
        $element        = new self();
        $element->id    = $id;
        $element->label = $label;

        return $element;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'layout'       => 'WeeklyPlannerPanel',
            'type'         => 'WeeklyPlannerPanel',
            'label'        => $this->label,
            'description'  => $this->description,
            'alert'        => $this->alert,
            'dependencies' => $this->dependencies,
            'id'           => $this->id,
        ];

        return $structure;
    }

    public function setAlert(Alert $alert): void
    {
        $this->alert = $alert;
    }
}
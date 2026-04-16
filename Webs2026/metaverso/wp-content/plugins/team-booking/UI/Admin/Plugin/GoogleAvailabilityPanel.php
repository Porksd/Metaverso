<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class GoogleAvailabilityPanel
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class GoogleAvailabilityPanel implements Element_Setting
{
    private $description;
    private $label;
    private $alert;

    /**
     * @param string      $label
     * @param string|null $id
     *
     * @return self
     */
    public static function get(string $label, string $id = NULL): GoogleAvailabilityPanel
    {
        $element        = new self();
        $element->label = $label;

        return $element;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'layout'      => 'GoogleAvailabilityPanel',
            'type'        => 'GoogleAvailabilityPanel',
            'label'       => $this->label,
            'description' => $this->description,
            'alert'       => $this->alert,
        ];

        return $structure;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setAlert(Alert $alert): void
    {
        $this->alert = $alert;
    }
}
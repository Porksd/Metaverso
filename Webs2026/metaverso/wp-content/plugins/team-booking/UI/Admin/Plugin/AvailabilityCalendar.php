<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class AvailabilityCalendar
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class AvailabilityCalendar implements Element_Setting
{
    private $description;
    private $label;
    private $alert;

    /**
     * @var int
     */
    private $provider;

    /**
     * @return self
     */
    public static function get(string $label, string $id = NULL)
    {
        $element        = new self();
        $element->label = $label;

        return $element;
    }

    /**
     * @param $id
     */
    public function setProviderId($id): void
    {
        $this->provider = $id;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'layout'      => 'AvailabilityCalendar',
            'type'        => 'AvailabilityCalendar',
            'label'       => $this->label,
            'description' => $this->description,
            'alert'       => $this->alert,
            'provider'    => $this->provider,
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
<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class DataTableReservations
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class DataTableReservations implements Element_Setting
{

    private $description;
    private $label;
    private $alert;

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
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'layout'      => 'DataTableReservations',
            'type'        => 'DataTableReservations',
            'label'       => $this->label,
            'description' => $this->description,
            'alert'       => $this->alert
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
<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class DataTablePromotions
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class DataTablePromotions implements Element_Setting
{
    private $description;
    private $label;
    private $alert;
    private $items = [];

    /**
     * @return self
     */
    public static function get(string $label, string $id = NULL)
    {
        $element        = new self();
        $element->label = $label;

        return $element;
    }

    public function addSettingItems($items): void
    {
        $this->items = $items;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'layout'      => 'DataTablePromotions',
            'type'        => 'DataTablePromotions',
            'label'       => $this->label,
            'description' => $this->description,
            'alert'       => $this->alert,
            'items'       => $this->items,
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
<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class DataTableLocations
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class DataTableLocations implements Element_Setting
{

    private $description;
    private $label;
    private $alert;

    private $items = [];

    private $columns = [];

    private $endpoint = '';

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

    public function addColumn($title, $key, $dataIndex = NULL): void
    {
        $this->columns[] = [
            'title'     => $title,
            'key'       => $key,
            'dataIndex' => $dataIndex
        ];
    }

    public function setEndpoint($endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'layout'      => 'DataTableLocations',
            'type'        => 'DataTableLocations',
            'label'       => $this->label,
            'description' => $this->description,
            'alert'       => $this->alert,
            'items'       => $this->items,
            'columns'     => $this->columns,
            'endpoint'    => $this->endpoint,
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
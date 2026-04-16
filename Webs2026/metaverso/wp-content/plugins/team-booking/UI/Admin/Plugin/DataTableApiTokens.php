<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class DataTableApiTokens
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class DataTableApiTokens implements Element_Setting
{
    private $items = [];

    private $columns = [];

    private $endpoint = '';

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

    public function addItems($items)
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
            'layout'      => 'DataTableApiTokens',
            'type'        => 'DataTableApiTokens',
            'label'       => $this->label,
            'description' => $this->description,
            'alert'       => $this->alert,
            'items'       => $this->items,
            'columns'     => $this->columns,
            'endpoint'    => $this->endpoint
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
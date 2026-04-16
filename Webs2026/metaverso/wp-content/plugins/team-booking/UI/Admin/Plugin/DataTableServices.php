<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class DataTableServices
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class DataTableServices implements Element_Setting
{
    private $items             = [];
    private $notificationItems = [];
    private $personalItems     = [];

    private $columns = [];

    private $endpoint = '';

    private $description;
    private $label;
    private $alert;

    /**
     * @param string      $label
     * @param string|null $id
     *
     * @return self
     */
    public static function get(string $label, string $id = NULL): DataTableServices
    {
        $element        = new self();
        $element->label = $label;

        return $element;
    }

    public function addSettingItems($items): void
    {
        $this->items = $items;
    }

    public function addNotificationItems($items): void
    {
        $this->notificationItems = $items;
    }

    public function addPersonalItems($items): void
    {
        $this->personalItems = $items;
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
            'layout'            => 'DataTableServices',
            'type'              => 'DataTableServices',
            'label'             => $this->label,
            'description'       => $this->description,
            'alert'             => $this->alert,
            'items'             => $this->items,
            'notificationItems' => $this->notificationItems,
            'personalItems'     => $this->personalItems,
            'columns'           => $this->columns,
            'endpoint'          => $this->endpoint
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
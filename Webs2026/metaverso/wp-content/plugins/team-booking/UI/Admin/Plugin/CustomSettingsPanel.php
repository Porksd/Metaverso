<?php

namespace VSHM\UI\Admin\Plugin;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class CustomSettingsPanel
 *
 * @package VSHM\UI\Admin\Plugin
 * @author  VonStroheim
 */
class CustomSettingsPanel implements Element_Setting
{
    private $subItems = [];
    private $endpoint = '';

    private $description;
    private $label;
    private $id;
    private $alert;
    private $iconUrl = '';

    /**
     * @param string      $label
     * @param string|null $id
     *
     * @return self
     */
    public static function get(string $label, string $id = NULL): CustomSettingsPanel
    {
        $element        = new self();
        $element->label = $label;
        $element->id    = $id;

        return $element;
    }

    public function setSubItems($items)
    {
        $this->subItems = $items;
    }

    public function setEndpoint($endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @param Element $item
     */
    public function addSubItem(Element $item): void
    {
        $this->subItems[] = $item->get_structure();
    }

    /**
     * @param string $iconUrl
     */
    public function setIconUrl(string $iconUrl): void
    {
        $this->iconUrl = $iconUrl;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'layout'      => 'CustomSettingsPanel',
            'type'        => 'CustomSettingsPanel',
            'label'       => $this->label,
            'description' => $this->description,
            'alert'       => $this->alert,
            'subItems'    => $this->subItems,
            'endpoint'    => $this->endpoint,
            'id'          => $this->id,
            'iconUrl'     => $this->iconUrl
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
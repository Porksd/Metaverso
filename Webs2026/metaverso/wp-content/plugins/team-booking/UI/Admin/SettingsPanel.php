<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class SettingsPanel
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class SettingsPanel implements Element
{
    private $items       = [];
    private $title       = '';
    private $description = '';
    private $iconUrl     = '';

    /**
     * @return SettingsPanel
     */
    public static function get(): SettingsPanel
    {
        return new self();
    }

    /**
     * @param Element $item
     */
    public function addItem(Element $item): void
    {
        $this->items[] = $item->get_structure();
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
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
            'layout'      => 'settings-panel',
            'items'       => $this->items,
            'title'       => $this->title,
            'description' => $this->description,
            'iconUrl'     => $this->iconUrl,
        ];

        return $structure;
    }
}
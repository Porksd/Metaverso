<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class SidebarItem
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class SidebarItem implements Element
{

    public const TYPE__SUBMENU = 'submenu';
    public const TYPE__OPTION  = 'option';

    private $type;
    private $id;
    private $icon;
    private $label;
    private $content;
    private $items = [];

    /**
     * @param string      $label
     * @param string      $id
     * @param string|null $icon
     *
     * @return SidebarItem
     */
    public static function submenu(string $label, string $id, string $icon = NULL): SidebarItem
    {
        $item        = new self();
        $item->type  = self::TYPE__SUBMENU;
        $item->label = $label;
        $item->id    = $id;
        $item->icon  = $icon;

        return $item;
    }

    /**
     * @param string      $label
     * @param string      $id
     * @param string|null $icon
     *
     * @return SidebarItem
     */
    public static function option(string $label, string $id, string $icon = NULL): SidebarItem
    {
        $item        = new self();
        $item->type  = self::TYPE__OPTION;
        $item->label = $label;
        $item->id    = $id;
        $item->icon  = $icon;

        return $item;
    }

    /**
     * @param Element $content
     */
    public function setContent(Element $content): void
    {
        $this->content = $content->get_structure();
    }

    /**
     * @param SidebarItem $item
     */
    public function addItem(SidebarItem $item): void
    {
        $this->items[] = $item->get_structure();
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'type'  => $this->type,
            'label' => $this->label,
            'id'    => $this->id
        ];

        if ($this->type === self::TYPE__SUBMENU) {
            $structure['items'] = $this->items;
        }

        if ($this->type === self::TYPE__OPTION) {
            $structure['content'] = $this->content;
        }

        if ($this->icon) {
            $structure['icon'] = $this->icon;
        }

        return $structure;
    }
}
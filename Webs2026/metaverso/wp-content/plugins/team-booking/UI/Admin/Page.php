<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Page
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Page implements Element
{
    public const LAYOUT__SIDEBAR_LEFT = 'sidebar-left';
    public const LAYOUT__FULL_WIDTH   = 'full-width';

    private $layout = '';

    private $sidebarItems = [];

    private $content;

    /**
     * @return Page
     */
    public static function sidebar_left(): Page
    {
        $page         = new self();
        $page->layout = self::LAYOUT__SIDEBAR_LEFT;

        return $page;
    }

    /**
     * @return Page
     */
    public static function full_width(): Page
    {
        $page         = new self();
        $page->layout = self::LAYOUT__FULL_WIDTH;

        return $page;
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
     * @param int|null    $position
     *
     * @return void
     */
    public function addSidebarItem(SidebarItem $item, int $position = NULL): void
    {
        if (NULL === $position) {
            $this->sidebarItems[] = $item->get_structure();
        } else {
            array_splice($this->sidebarItems, $position - 1, 0, array($item->get_structure()));
        }

    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'layout'  => $this->layout,
            'content' => $this->content
        ];

        if ($this->layout === self::LAYOUT__SIDEBAR_LEFT) {
            $structure['sidebarItems'] = array_values($this->sidebarItems);
        }

        return $structure;
    }
}
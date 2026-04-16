<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class MenuItem
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class MenuItem implements Element
{
    const TYPE__TAB  = 'tab';
    const TYPE__LINK = 'link';

    private $label;
    private $type;
    private $content;
    private $url;
    private $icon;
    private $key;

    /**
     * @return self
     */
    public static function tab($label, Element $content, $icon = NULL, $key = NULL)
    {
        $item          = new self();
        $item->type    = self::TYPE__TAB;
        $item->label   = $label;
        $item->content = $content->get_structure();
        $item->icon    = $icon;
        $item->key     = $key;

        return $item;
    }

    /**
     * @return self
     */
    public static function link($label, $url, $icon = NULL, $key = NULL)
    {
        $item       = new self();
        $item->type = self::TYPE__LINK;
        $item->url  = $url;
        $item->icon = $icon;
        $item->key  = $key;

        return $item;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'type'  => $this->type,
            'label' => $this->label,
        ];

        if ($this->type === self::TYPE__LINK) {
            $structure['url'] = $this->url;
        }

        if ($this->type === self::TYPE__TAB) {
            $structure['content'] = $this->content;
        }

        if ($this->icon) {
            $structure['icon'] = $this->icon;
        }

        if ($this->key) {
            $structure['key'] = $this->key;
        }

        return $structure;
    }
}
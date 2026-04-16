<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Content
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Content
{
    private $text;
    private $href;
    private $type;
    private $data = [];
    /**
     * @var self[]
     */
    private $items = [];

    /**
     * @param $text
     *
     * @return self
     */
    public static function Text($text): Settings_Content
    {
        $element       = new self();
        $element->text = $text;
        $element->type = 'text';

        return $element;
    }

    /**
     * @param $text
     * @param $href
     *
     * @return self
     */
    public static function Link($text, $href): Settings_Content
    {
        $element       = new self();
        $element->text = $text;
        $element->href = $href;
        $element->type = 'link';

        return $element;
    }

    /**
     * @param $items
     *
     * @return self
     */
    public static function UnorderedList($items): Settings_Content
    {
        $element        = new self();
        $element->items = $items;
        $element->type  = 'list';

        return $element;
    }

    /**
     * @param string $type
     * @param array  $data
     *
     * @return self
     */
    public static function CustomType(string $type, array $data = []): Settings_Content
    {
        $element       = new self();
        $element->type = $type;
        $element->data = $data;

        return $element;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'type' => $this->type,
        ];

        if ($this->text) {
            $structure['text'] = $this->text;
        }

        if ($this->items) {
            $structure['items'] = array_map(static function ($item) {
                return $item->get_structure();
            }, $this->items);
        }

        if ($this->href) {
            $structure['href'] = $this->href;
        }

        $structure['data'] = $this->data;

        return $structure;
    }
}
<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class InfoMessage
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class InfoMessage implements Element
{
    private $title;
    private $subtitle;

    private $actions = [];

    public function __construct($title = '', $subtitle = '')
    {
        $this->title    = $title;
        $this->subtitle = $subtitle;
    }

    public function addAction($label, $url = '', $type = '')
    {
        $this->actions[] = [
            'label' => $label,
            'url'   => $url,
            'type'  => $type
        ];
    }

    public function get_structure(): array
    {
        return [
            'type'     => 'result-message',
            'title'    => $this->title,
            'subtitle' => $this->subtitle,
            'actions'  => $this->actions
        ];
    }
}
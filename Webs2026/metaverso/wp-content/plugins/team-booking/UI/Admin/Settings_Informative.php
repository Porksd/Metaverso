<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Informative
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Informative implements Element_Setting
{
    private $label;
    private $id;
    private $alert;
    private $description;
    private $content      = [];
    private $dependencies = [];

    /**
     * @param string      $label
     * @param string|null $id
     *
     * @return self
     */
    public static function get(string $label, string $id = NULL): Settings_Informative
    {
        $element        = new self();
        $element->label = $label;
        $element->id    = $id;

        return $element;
    }

    /**
     * @param Settings_Dependency $dep
     */
    public function addDependency(Settings_Dependency $dep): void
    {
        $this->dependencies[] = $dep->get_structure();
    }

    /**
     * @param Settings_Content $content
     */
    public function addContent(Settings_Content $content): void
    {
        $this->content[] = $content->get_structure();
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'type'    => 'informative',
            'id'      => $this->id,
            'label'   => $this->label,
            'content' => $this->content,
        ];

        if ($this->description) {
            $structure['description'] = $this->description;
        }

        if ($this->dependencies) {
            $structure['dependencies'] = $this->dependencies;
        }

        if ($this->alert) {
            $structure['alert'] = $this->alert;
        }

        return $structure;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setAlert(Alert $alert): void
    {
        $this->alert = $alert->get_structure();
    }
}
<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Input
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
abstract class Settings_Base implements Element_Setting
{
    protected $label;
    protected $id;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var array
     */
    protected $alert;

    protected $dependencies = [];

    /**
     * @return static
     */
    public static function get(string $label, string $id = NULL)
    {
        $element        = new static();
        $element->label = $label;
        $element->id    = $id;

        return $element;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setAlert(Alert $alert): void
    {
        $this->alert = $alert->get_structure();
    }

    /**
     * @param Settings_Dependency $dep
     */
    public function addDependency(Settings_Dependency $dep): void
    {
        $this->dependencies[] = $dep->get_structure();
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'id'    => $this->id,
            'label' => $this->label,
        ];

        if ($this->description) {
            $structure['description'] = $this->description;
        }

        if ($this->alert) {
            $structure['alert'] = $this->alert;
        }

        if ($this->dependencies) {
            $structure['dependencies'] = $this->dependencies;
        }

        $customContent = apply_filters('vshm_setting_element_custom_content', NULL, $this->id);
        if (NULL !== $customContent) {
            $structure['customContent'] = $customContent;
        }

        return $structure;
    }
}
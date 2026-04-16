<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Option
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Option implements Element_Setting
{
    private $label;
    private $value;
    private $description;
    private $alert;
    private $color;

    /**
     * @param string          $label
     * @param string|int|null $id
     *
     * @return self
     */
    public static function get(string $label, string $id = NULL): Settings_Option
    {
        $option        = new self();
        $option->value = filter_var($id, FILTER_VALIDATE_INT) !== FALSE
            ? filter_var($id, FILTER_VALIDATE_INT)
            : $id;
        $option->label = $label;

        return $option;
    }

    /**
     * @param string $color
     */
    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'value' => $this->value,
            'label' => $this->label,
        ];

        if ($this->description) {
            $structure['description'] = $this->description;
        }

        if ($this->color) {
            $structure['color'] = $this->color;
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
<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_Dependency
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_Dependency
{
    private $on;
    private $being;
    private $to;

    /** @var self[] */
    private $dependencies;

    private function __construct($on, $being, $to = NULL, array $dependencies = [])
    {
        $this->on           = $on;
        $this->being        = $being;
        $this->to           = $to;
        $this->dependencies = $dependencies;
    }

    /**
     * @param string $settingId
     * @param string $value
     *
     * @return self
     */
    public static function EQUAL(string $settingId, string $value): Settings_Dependency
    {
        return new self($settingId, '=', $value);
    }

    /**
     * @param string $settingId
     * @param array  $value
     *
     * @return self
     */
    public static function INCLUDED(string $settingId, array $value): Settings_Dependency
    {
        return new self($settingId, 'IN', $value);
    }

    /**
     * @param string $settingId
     * @param array  $value
     *
     * @return self
     */
    public static function NOT_INCLUDED(string $settingId, array $value): Settings_Dependency
    {
        return new self($settingId, '!IN', $value);
    }

    /**
     * @param string $settingId
     * @param string $value
     *
     * @return self
     */
    public static function NOT_EQUAL(string $settingId, string $value): Settings_Dependency
    {
        return new self($settingId, '!=', $value);
    }

    /**
     * @param string $settingId
     *
     * @return self
     */
    public static function NOT_EMPTY(string $settingId): Settings_Dependency
    {
        return new self($settingId, 'NOT_EMPTY');
    }

    /**
     * @param string $settingId
     *
     * @return self
     */
    public static function IS_EMPTY(string $settingId): Settings_Dependency
    {
        return new self($settingId, 'EMPTY');
    }

    /**
     * @param string $settingId
     *
     * @return self
     */
    public static function FALSY(string $settingId): Settings_Dependency
    {
        return new self($settingId, 'FALSY');
    }

    /**
     * @param string $settingId
     *
     * @return self
     */
    public static function TRUTHY(string $settingId): Settings_Dependency
    {
        return new self($settingId, 'TRUTHY');
    }

    /**
     * @param array $dependencies
     *
     * @return self
     */
    public static function OR_GROUP(array $dependencies): Settings_Dependency
    {
        return new self(TRUE, 'OR_GROUP', FALSE, $dependencies);
    }

    /**
     * @param array $dependencies
     *
     * @return self
     */
    public static function AND_GROUP(array $dependencies): Settings_Dependency
    {
        return new self(TRUE, 'AND_GROUP', FALSE, $dependencies);
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'on'    => $this->on,
            'being' => $this->being
        ];

        if (NULL !== $this->to) {
            $structure['to'] = $this->to;
        }

        if ($this->dependencies) {
            foreach ($this->dependencies as $dependency) {
                if ($dependency instanceof self) {
                    $structure['dependencies'][] = $dependency->get_structure();
                }
            }

        }

        return $structure;
    }
}
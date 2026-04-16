<?php

namespace VSHF\Config;

class DependencyCondition
{

    public const EQUAL     = "=";
    public const NOT_EQUAL = "!=";
    public const TRUTHY    = "TRUTHY";
    public const FALSY     = "FALSY";
    public const IS_EMPTY  = "EMPTY";
    public const NOT_EMPTY = "!EMPTY";
    public const IN        = "IN";
    public const NOT_IN    = "!IN";

    /**
     * @var string
     */
    private $settingId;

    /**
     * @var string
     */
    private $context;

    /**
     * @var string
     */
    private $operator;

    private $value;

    /**
     * @var Dependency
     */
    private $parentDependency;

    public function __construct(Dependency $parentDependency, string $settingId, $context = Config::CONTEXT_APP)
    {
        $this->settingId        = $settingId;
        $this->context          = $context;
        $this->parentDependency = $parentDependency;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function verified($value): bool
    {
        switch ($this->operator) {
            case self::EQUAL:
                return $value === $this->value;
            case self::NOT_EQUAL:
                return $value !== $this->value;
            case self::TRUTHY:
                return (bool)$value;
            case self::FALSY:
                return !$value;
            case self::IS_EMPTY:
                return empty($value);
            case self::NOT_EMPTY:
                return !empty($value);
            case self::IN:
                return in_array($value, $this->value, TRUE);
            case self::NOT_IN:
                return !in_array($value, $this->value, TRUE);
            default:
                return TRUE;
        }
    }

    public function getSettingId(): string
    {
        return $this->settingId;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function beingEqualTo($value): Dependency
    {
        $this->operator = self::EQUAL;
        $this->value    = $value;

        return $this->add_self_to_dep_and_return_it();
    }

    public function beingNotEqualTo($value): Dependency
    {
        $this->operator = self::NOT_EQUAL;
        $this->value    = $value;

        return $this->add_self_to_dep_and_return_it();
    }

    public function beingTruthy(): Dependency
    {
        $this->operator = self::TRUTHY;

        return $this->add_self_to_dep_and_return_it();
    }

    public function beingFalsy(): Dependency
    {
        $this->operator = self::FALSY;

        return $this->add_self_to_dep_and_return_it();
    }

    public function beingIn(array $values): Dependency
    {
        $this->operator = self::IN;
        $this->value    = $values;

        return $this->add_self_to_dep_and_return_it();
    }

    public function beingNotIn(array $values): Dependency
    {
        $this->operator = self::NOT_IN;
        $this->value    = $values;

        return $this->add_self_to_dep_and_return_it();
    }

    public function beingEmpty(): Dependency
    {
        $this->operator = self::IS_EMPTY;

        return $this->add_self_to_dep_and_return_it();
    }

    public function beingNotEmpty(): Dependency
    {
        $this->operator = self::NOT_EMPTY;

        return $this->add_self_to_dep_and_return_it();
    }

    private function add_self_to_dep_and_return_it(): Dependency
    {
        $this->parentDependency->addCondition($this);

        return $this->parentDependency;
    }

}
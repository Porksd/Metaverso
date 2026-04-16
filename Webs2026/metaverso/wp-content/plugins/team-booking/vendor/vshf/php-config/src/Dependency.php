<?php

namespace VSHF\Config;

class Dependency
{
    private $conditions = [];

    private $groupLogic = '';

    /**
     * @var Dependency
     */
    private $parentDependency;

    /**
     * @param Dependency $parentDependency
     *
     * @return void
     */
    private function add_parent_dependency(Dependency $parentDependency): void
    {
        $this->parentDependency = $parentDependency;
    }

    /**
     * @param string $settingId
     * @param string $context
     *
     * @return DependencyCondition
     */
    public function on(string $settingId, string $context = Config::CONTEXT_APP): DependencyCondition
    {
        return new DependencyCondition($this, $settingId, $context);
    }

    public function and(string $settingId, string $context = Config::CONTEXT_APP): DependencyCondition
    {
        if ($this->groupLogic === '') {
            $this->groupLogic = 'AND';
        }
        if ($this->groupLogic !== 'AND') {
            throw new \BadMethodCallException('called and() when the group logic is OR');
        }

        return new DependencyCondition($this, $settingId, $context);
    }

    public function or(string $settingId, string $context = Config::CONTEXT_APP): DependencyCondition
    {
        if ($this->groupLogic === '') {
            $this->groupLogic = 'OR';
        }
        if ($this->groupLogic !== 'OR') {
            throw new \BadMethodCallException('called or() when the group logic is AND');
        }

        return new DependencyCondition($this, $settingId, $context);
    }

    public function andGroup(): Dependency
    {
        if ($this->groupLogic === '') {
            $this->groupLogic = 'AND';
        }
        if ($this->groupLogic !== 'AND') {
            throw new \BadMethodCallException('called andGroup() when the group logic is OR');
        }

        $groupDependency = new self();
        $groupDependency->add_parent_dependency($this);

        return $groupDependency;
    }

    public function orGroup(): Dependency
    {
        if ($this->groupLogic === '') {
            $this->groupLogic = 'OR';
        }
        if ($this->groupLogic !== 'OR') {
            throw new \BadMethodCallException('called orGroup() when the group logic is AND');
        }

        $groupDependency = new self();
        $groupDependency->add_parent_dependency($this);

        return $groupDependency;
    }

    public function endGroup(): Dependency
    {
        if (NULL === $this->parentDependency) {
            throw new \BadMethodCallException('called endGroup() without calling group() first');
        }

        $this->parentDependency->addCondition($this);

        return $this->parentDependency;
    }

    /**
     * @param self | DependencyCondition $condition
     *
     * @return void
     */
    public function addCondition($condition): void
    {
        $this->conditions[] = $condition;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getGroupLogic(): string
    {
        if ($this->groupLogic === '') {
            return 'AND';
        }

        return $this->groupLogic;
    }

}
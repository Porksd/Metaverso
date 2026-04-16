<?php

namespace VSHF\Config;

/**
 * Class Config
 */
final class Config
{
    public const CONTEXT_APP = 'app';

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var array
     */
    private $observers = [];

    /**
     * @var array
     */
    private $propertyObservers = [];

    /**
     * @param array $hydrateApp
     */
    public function __construct(array $hydrateApp = [])
    {
        $this->settings[ self::CONTEXT_APP ] = $hydrateApp;
    }

    /**
     * @param array  $data
     * @param string $context
     *
     * @return void
     */
    public function hydrate(array $data, string $context = self::CONTEXT_APP): void
    {
        $this->settings[ $context ] = $data;
    }

    /**
     * @param array  $data
     * @param string $context
     * @param string $resourceId
     *
     * @return void
     */
    public function hydrateResource(array $data, string $context, string $resourceId): void
    {
        $this->properties[ $context ][ $resourceId ] = $data;
    }

    public function registerObserver(string $settingId, string $observerClassName, $context = self::CONTEXT_APP): void
    {
        if (isset($this->observers[ $context ][ $settingId ])) {
            throw new \UnexpectedValueException("$settingId is already registered.");
        }

        $this->observers[ $context ][ $settingId ] = $observerClassName;
    }

    public function registerPropertyObserver(string $settingId, string $observerClassName, string $context): void
    {
        if (isset($this->propertyObservers[ $context ][ $settingId ])) {
            throw new \UnexpectedValueException("$settingId is already registered.");
        }

        $this->propertyObservers[ $context ][ $settingId ] = $observerClassName;
    }

    public function getAllByContextRaw(string $context = self::CONTEXT_APP): array
    {
        return $this->settings[ $context ] ?? [];
    }

    public function getAllPropertiesByContextRaw(string $context): array
    {
        return $this->properties[ $context ] ?? [];
    }

    public function getAllByContext(string $context = self::CONTEXT_APP): array
    {
        $settings = [];
        if (!isset($this->observers[ $context ])) {
            return [];
        }
        foreach ($this->observers[ $context ] as $settingId => $observer) {
            $settings[ $settingId ] = $this->get($settingId, $context);
        }

        return $settings;
    }

    public function getAllPropertiesByContext(string $context): array
    {
        $settings = [];
        if (!isset($this->propertyObservers[ $context ], $this->properties[ $context ])) {
            return [];
        }
        foreach ($this->propertyObservers[ $context ] as $settingId => $observer) {
            foreach ($this->properties[ $context ] as $resourceId => $properties) {
                $settings[ $resourceId ][ $settingId ] = $this->getProperty($settingId, $context, $resourceId);
            }
        }

        return $settings;
    }

    public function resourceExists(string $context, string $resourceId): bool
    {
        return isset($this->properties[ $context ][ $resourceId ]);
    }

    public function getResourceProperties(string $context, string $resourceId): array
    {
        $resource = [];
        if (!isset($this->propertyObservers[ $context ], $this->properties[ $context ])) {
            return [];
        }
        foreach ($this->propertyObservers[ $context ] as $settingId => $observer) {
            $resource[ $settingId ] = $this->getProperty($settingId, $context, $resourceId);
        }

        return $resource;
    }

    public function getResourcePropertiesRaw(string $context, string $resourceId): array
    {
        $resource = [];

        return $this->properties[ $context ][ $resourceId ] ?? [];
    }

    public function getAllRaw(): array
    {
        return $this->settings;
    }

    public function getAllPropertiesRaw(): array
    {
        return $this->properties;
    }

    public function getAll(): array
    {
        $settings = [];
        foreach ($this->observers as $context => $observers) {
            foreach ($observers as $settingId => $observer) {
                $settings[ $context ][ $settingId ] = $this->get($settingId, $context);
            }
        }

        return $settings;
    }

    public function getAllProperties(): array
    {
        $settings = [];
        foreach ($this->propertyObservers as $context => $observers) {
            foreach ($observers as $settingId => $observer) {
                $props = $this->properties[ $context ] ?? [];
                foreach ($props as $resourceId => $properties) {
                    $settings[ $context ][ $resourceId ][ $settingId ] = $this->getProperty($settingId, $context, $resourceId);
                }
            }
        }

        return $settings;
    }

    public function get(string $settingId, string $context = self::CONTEXT_APP)
    {
        if (!isset($this->observers[ $context ][ $settingId ])) {
            throw new \UnexpectedValueException("$settingId is not a registered setting.");
        }

        /** @var ObserverInterface $observerClass */
        $observerClass = $this->observers[ $context ][ $settingId ];

        $observerClass::onBeforeGet();

        if (!isset($this->settings[ $context ][ $settingId ])) {
            return $this->resolveDependencies($observerClass::default(), $observerClass::dependencies());
        }

        $settingValue = $this->settings[ $context ][ $settingId ];

        if (!$observerClass::validate($settingValue)) {
            throw new \UnexpectedValueException("$settingId has an invalid value: $settingValue");
        }

        $observerClass::onGet($settingValue);

        return $this->resolveDependencies(
            $observerClass::sanitize($settingValue),
            $observerClass::dependencies()
        );

    }

    public function getProperty(string $settingId, string $context, string $resourceId)
    {
        if (!isset($this->propertyObservers[ $context ][ $settingId ])) {
            throw new \UnexpectedValueException("$settingId is not a registered property.");
        }

        /** @var PropertyObserverInterface $observerClass */
        $observerClass = $this->propertyObservers[ $context ][ $settingId ];

        $observerClass::onBeforeGet($resourceId);

        if (!isset($this->properties[ $context ][ $resourceId ])) {
            throw new \UnexpectedValueException("Resource $resourceId is not found in the $context context.");
        }

        if (!isset($this->properties[ $context ][ $resourceId ][ $settingId ])) {
            return $this->resolveDependencies($observerClass::default($resourceId), $observerClass::dependencies($resourceId));
        }

        $settingValue = $this->properties[ $context ][ $resourceId ][ $settingId ];

        if (!$observerClass::validate($settingValue, $resourceId)) {
            throw new \UnexpectedValueException("$settingId has an invalid value: $settingValue");
        }

        $observerClass::onGet($settingValue, $resourceId);

        return $this->resolveDependencies(
            $observerClass::sanitize($settingValue, $resourceId),
            $observerClass::dependencies($resourceId)
        );

    }

    public function save(string $settingId, $value, string $context = self::CONTEXT_APP): void
    {
        if (!isset($this->observers[ $context ][ $settingId ])) {
            throw new \UnexpectedValueException("$settingId is not a registered setting.");
        }

        /** @var ObserverInterface $observerClass */
        $observerClass = $this->observers[ $context ][ $settingId ];

        if (!$observerClass::validate($value)) {
            throw new \UnexpectedValueException("$settingId has an invalid value: $value");
        }

        $observerClass::onSave($value);

        $this->settings[ $context ][ $settingId ] = $observerClass::sanitize($value);
    }

    public function saveProperty(string $settingId, $value, string $context, string $resourceId): void
    {
        if (!isset($this->propertyObservers[ $context ][ $settingId ])) {
            throw new \UnexpectedValueException("$settingId is not a registered property.");
        }

        /** @var PropertyObserverInterface $observerClass */
        $observerClass = $this->propertyObservers[ $context ][ $settingId ];

        if (!$observerClass::validate($value, $resourceId)) {
            throw new \UnexpectedValueException("$settingId has an invalid value: $value");
        }

        $observerClass::onSave($value, $resourceId);

        $this->properties[ $context ][ $resourceId ][ $settingId ] = $observerClass::sanitize($value, $resourceId);
    }

    /**
     * Dependencies must be resolved only when querying, never when saving.
     *
     * @param mixed           $value
     * @param Dependency|null $dependencies
     *
     * @return mixed
     */
    private function resolveDependencies($value, ?Dependency $dependencies)
    {
        if (NULL === $dependencies) {
            return $value;
        }

        $pass = $dependencies->getGroupLogic() === 'AND';

        foreach ($dependencies->getConditions() as $condition) {

            if ($condition instanceof DependencyCondition) {
                $settingId = $condition->getSettingId();
                if (!isset($this->observers[ $condition->getContext() ][ $settingId ])) {
                    throw new \UnexpectedValueException("$settingId is not a registered setting.");
                }

                /** @var ObserverInterface $observerClass */
                $observerClass = $this->observers[ $condition->getContext() ][ $settingId ];

                $settingValue = $this->settings[ $condition->getContext() ][ $settingId ] ?? $observerClass::default();

                if (!$observerClass::validate($settingValue)) {
                    throw new \UnexpectedValueException("$settingId has an invalid value: $settingValue");
                }

                if ($dependencies->getGroupLogic() === 'AND' && !$condition->verified($settingValue)) {
                    $pass = FALSE;
                }
                if ($dependencies->getGroupLogic() === 'OR' && $condition->verified($settingValue)) {
                    $pass = TRUE;
                }
            } else {
                $settingValue = $this->resolveDependencies($value, $condition);
                if (NULL === $settingValue && $dependencies->getGroupLogic() === 'AND') {
                    $pass = FALSE;
                }
                if (NULL !== $settingValue && $dependencies->getGroupLogic() === 'OR') {
                    $pass = TRUE;
                }
            }

        }

        return $pass ? $value : NULL;
    }

}
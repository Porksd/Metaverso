<?php

namespace VSHF\Config;

/**
 * Interface PropertyObserverInterface
 */
interface PropertyObserverInterface
{
    public static function validate($value, $resourceId): bool;

    public static function sanitize($value, $resourceId);

    public static function default($resourceId);

    public static function onSave($value, $resourceId): void;

    public static function onGet($value, $resourceId): void;

    public static function onBeforeGet($resourceId): void;

    public static function dependencies($resourceId): ?Dependency;
}
<?php

namespace VSHF\Config;

/**
 * Interface ObserverInterface
 */
interface ObserverInterface
{
    public static function validate($value): bool;

    public static function sanitize($value);

    public static function default();

    public static function onSave($value): void;

    public static function onGet($value): void;

    public static function onBeforeGet(): void;

    public static function dependencies(): ?Dependency;
}
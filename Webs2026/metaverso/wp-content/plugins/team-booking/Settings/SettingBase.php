<?php

namespace VSHM\Settings;

use VSHF\Config\Dependency;
use VSHM\Bus\SaveSettings;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic backend setting.
 */
abstract class SettingBase implements \VSHF\Config\ObserverInterface, Setting
{
    public static function subscribe(): void
    {
        vshm()->settings->registerObserver(static::ID, static::class);

        add_filter('vshm_export_settings', static function ($settings) {
            $settings[ static::ID ] = vshm()->settings->get(static::ID);

            return $settings;
        });
        add_filter('vshm_import_settings', static function ($toSave, $settings, $version) {
            if (isset($settings[ static::ID ])) {
                $toSave[ static::ID ] = static::sanitize($settings[ static::ID ]);
            }

            return $toSave;
        }, 10, 3);
        add_action('vshm_dispatching_SaveSettings', static function (SaveSettings $command) {
            $settings = $command->getSettings();

            if (isset($settings[ static::ID ]) || array_key_exists(static::ID, $settings)) {
                $settings[ static::ID ] = static::sanitize($settings[ static::ID ]);
                $command->setSettings($settings);
            }
        });
    }

    public static function onSave($value): void
    {
        // TODO: Implement onSave() method.
    }

    public static function onGet($value): void
    {
        // TODO: Implement onGet() method.
    }

    public static function onBeforeGet(): void
    {
        // TODO: Implement onBeforeGet() method.
    }

    public static function sanitize($value)
    {
        return $value;
    }

    public static function validate($value): bool
    {
        return TRUE;
    }

    public static function dependencies(): ?Dependency
    {
        return NULL;
    }
}

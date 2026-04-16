<?php

namespace VSHM\Modules\Zoom\Settings;

use VSHM\Bus\SaveSettings;
use VSHM\Modules\Zoom;
use VSHM\Settings\SettingBase;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class GoogleFetchDelay
 *
 * @package VSHM
 */
abstract class ZoomSettingBase extends SettingBase
{
    public const CONTEXT = 'zoom';

    public static function subscribe(): void
    {
        vshm()->settings->registerObserver(static::ID, static::class, self::CONTEXT);

        add_filter('vshm_export_settings', static function ($settings) {
            $settings[ static::ID ][ self::CONTEXT ] = vshm()->settings->get(static::ID, self::CONTEXT);

            return $settings;
        });
        add_filter('vshm_import_settings', static function ($toSave, $settings, $version) {
            if (isset($settings[ static::ID ][ self::CONTEXT ])) {
                $toSave[ static::ID ] = static::sanitize($settings[ static::ID ]);
            }

            return $toSave;
        }, 10, 3);
        add_action('vshm_dispatching_SaveSettings', static function (SaveSettings $command) {
            $settings = $command->getSettings();

            if (isset($settings[ static::ID ])) {
                $settings[ static::ID ] = static::sanitize($settings[ static::ID ]);
                $command->setSettings($settings);
            }
        });

        add_filter('vshm_backend_settings', static function ($settings) {
            $settings[ static::ID ] = vshm()->settings->get(static::ID, self::CONTEXT);

            return $settings;
        });
    }

    public static function onSave($value): void
    {
        $settings = get_option(Zoom::OPTIONS_TAG, []);

        $settings[ static::ID ] = static::sanitize($value);

        update_option(Zoom::OPTIONS_TAG, $settings);
    }

}
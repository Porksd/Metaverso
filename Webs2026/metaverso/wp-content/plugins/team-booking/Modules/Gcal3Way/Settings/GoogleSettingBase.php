<?php

namespace VSHM\Modules\Gcal3Way\Settings;

use VSHM\Bus\SaveSettings;
use VSHM\Settings\SettingBase;

defined('ABSPATH') || exit;

/**
 * Class GoogleFetchDelay
 *
 * @package VSHM
 */
abstract class GoogleSettingBase extends SettingBase
{
    public const CONTEXT = 'gcal3way';

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
}
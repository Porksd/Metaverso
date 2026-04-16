<?php

namespace VSHM\Settings;

use VSHF\Config\Dependency;

defined('ABSPATH') || exit;

/**
 * Implements some common setting methods.
 *
 * Defines an interface for a generic customizer setting.
 */
abstract class SettingCustomizer implements \VSHF\Config\ObserverInterface
{
    /**
     * @var string
     */
    public static $sanitizeCallback = '';

    /**
     * @return string
     */
    public static function getLabel(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public static function getSection(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public static function get_option_id(): string
    {
        return vshm()->get_settings_name() . '[style]' . '[' . static::ID . ']';
    }

    /**
     * @return array
     */
    public static function get_control(): array
    {
        return [];
    }

    public static function subscribe(): void
    {

        vshm()->settings->registerObserver(static::ID, static::class, 'style');

        add_action('vshm_settings_customizer', [static::class, 'customize_register'], 10, 2);
        add_filter('vshm_export_settings', static function ($settings) {
            $settings['style'][ static::ID ] = vshm()->settings->get(static::ID, 'style');

            return $settings;
        });
        add_filter('vshm_import_settings', static function ($toSave, $settings, $version) {
            if (isset($settings['style'][ static::ID ])) {
                $toSave['style'][ static::ID ] = static::sanitize($settings['style'][ static::ID ]);
            }

            return $toSave;
        }, 10, 3);
    }

    /**
     * @param        $wp_customize
     * @param string $plugin_slug
     */
    public static function customize_register($wp_customize, string $plugin_slug): void
    {
        if ($plugin_slug === vshm()->plugin['SLUG']) {

            $wp_customize->add_setting(static::get_option_id(), [
                'type'              => 'option',
                'capability'        => 'manage_options',
                'default'           => static::default(),
                'transport'         => 'postMessage',
                'sanitize_callback' => static::$sanitizeCallback
            ]);
            $wp_customize->selective_refresh->add_partial(static::get_option_id(), [
                'selector'            => '.tbk-frontend',
                'container_inclusive' => FALSE,
                'render_callback'     => function () {
                    echo "<div class='tbk-inner-content'></div>";
                },
            ]);
            if (!empty(static::get_control())) {
                $wp_customize->add_control(static::get_option_id(), static::get_control());
            }

        }
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

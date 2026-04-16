<?php

namespace VSHM\Settings;

defined('ABSPATH') || exit;

/**
 *  Class SettingSoldoutSlotColor
 */
class SettingSoldoutSlotColor extends SettingCustomizer
{
    public const ID = 'soldoutSlotColor';

    public static function customize_register($wp_customize, string $plugin_slug): void
    {
        parent::customize_register($wp_customize, $plugin_slug);

        if ($plugin_slug === vshm()->plugin['SLUG']) {
            $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, static::get_option_id(), [
                'label'   => static::getLabel(),
                'section' => static::getSection()
            ]));
        }
    }

    public static function getLabel(): string
    {
        return __('Booked slot color', 'team-booking');
    }

    /**
     * @return string
     */
    public static function getSection(): string
    {
        return vshm()->plugin['SLUG'] . '-style';
    }

    /**
     * @var string
     */
    public static $sanitizeCallback = 'sanitize_hex_color';

    public static function sanitize($value): string
    {
        return sanitize_hex_color($value) ?: static::default();
    }

    public static function default(): string
    {
        return '#d95c5c';
    }
}

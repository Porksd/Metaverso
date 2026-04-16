<?php

namespace VSHM\Settings;

use VSHM\UI\Customizer\Range_Slider_Control;

defined('ABSPATH') || exit;

/**
 *  Class SettingMapsZoom
 */
class SettingMapsZoom extends SettingCustomizer
{
    public const ID = 'googleMapsZoom';

    public static function customize_register($wp_customize, string $plugin_slug): void
    {
        parent::customize_register($wp_customize, $plugin_slug);

        if ($plugin_slug === vshm()->plugin['SLUG']) {
            $wp_customize->add_control(new Range_Slider_Control($wp_customize, static::get_option_id(), [
                'label'       => static::getLabel(),
                'section'     => static::getSection(),
                'priority'    => 1,
                'description' => static::getDescription(),
                'input_attrs' => [
                    'min'  => 0,
                    'max'  => 19,
                    'step' => 1
                ]
            ]));
        }
    }

    public static function getLabel(): string
    {
        return __('Map zoom level', 'team-booking');
    }

    /**
     * @return string
     */
    public static function getSection(): string
    {
        return vshm()->plugin['SLUG'] . '-maps';
    }

    /**
     * @var string
     */
    public static $sanitizeCallback = '';

    public static function sanitize($value): int
    {
        return (int)$value;
    }

    public static function default(): int
    {
        return 14;
    }
}

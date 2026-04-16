<?php

namespace VSHM\Settings;

use VSHM\UI\Customizer\Range_Slider_Control;

defined('ABSPATH') || exit;

/**
 *  Class SettingBorderWidth
 */
class SettingBorderWidth extends SettingCustomizer
{
    public const ID = 'widgetBorderWidth';

    public static function customize_register($wp_customize, string $plugin_slug): void
    {
        parent::customize_register($wp_customize, $plugin_slug);

        if ($plugin_slug === vshm()->plugin['SLUG']) {
            $wp_customize->add_control(new Range_Slider_Control($wp_customize, static::get_option_id(), [
                'label'       => static::getLabel(),
                'section'     => static::getSection(),
                'priority'    => 100,
                'input_attrs' => [
                    'min'    => 0,
                    'max'    => 50,
                    'step'   => 1,
                    'suffix' => 'px'
                ]
            ]));
        }
    }

    public static function getLabel(): string
    {
        return __('Border size', 'team-booking');
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
    public static $sanitizeCallback = '';

    public static function sanitize($value): int
    {
        return (int)$value;
    }

    public static function default(): int
    {
        return 0;
    }
}

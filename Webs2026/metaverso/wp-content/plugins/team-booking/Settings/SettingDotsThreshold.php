<?php

namespace VSHM\Settings;

use VSHM\UI\Customizer\Range_Slider_Control;

defined('ABSPATH') || exit;

/**
 *  Class SettingDotsThreshold
 */
class SettingDotsThreshold extends SettingCustomizer
{
    public const ID = 'dotsThreshold';

    public static function customize_register($wp_customize, string $plugin_slug): void
    {
        parent::customize_register($wp_customize, $plugin_slug);

        if ($plugin_slug === vshm()->plugin['SLUG']) {
            $wp_customize->add_control(new Range_Slider_Control($wp_customize, static::get_option_id(), [
                'label'       => static::getLabel(),
                'section'     => static::getSection(),
                'priority'    => 100,
                'description' => static::getDescription(),
                'input_attrs' => [
                    'min'  => 0,
                    'max'  => 200,
                    'step' => 1
                ]
            ]));
        }
    }

    public static function getLabel(): string
    {
        return __('Numbered dots threshold', 'team-booking');
    }

    public static function getDescription(): string
    {
        return __('Numbers inside the dots are not displayed when their value is below this threshold.', 'team-booking');
    }

    /**
     * @return string
     */
    public static function getSection(): string
    {
        return vshm()->plugin['SLUG'] . '-behaviour';
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

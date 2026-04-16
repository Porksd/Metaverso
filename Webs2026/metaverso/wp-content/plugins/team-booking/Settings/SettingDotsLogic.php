<?php

namespace VSHM\Settings;

defined('ABSPATH') || exit;

/**
 *  Class SettingDotsLogic
 */
class SettingDotsLogic extends SettingCustomizer
{
    public const ID = 'dotsLogic';

    public static function getOptions(): array
    {
        return [
            'slots'           => __('Total number of available slots', 'team-booking'),
            'tickets'         => __('Total number of available tickets', 'team-booking'),
            'service'         => __('Service name', 'team-booking'),
            'slots_service'   => __('Service name + total number of available slots', 'team-booking'),
            'tickets_service' => __('Service name + total number of available tickets', 'team-booking'),
            'hide'            => __('Hide the dots', 'team-booking'),
        ];
    }

    public static function get_control(): array
    {
        return [
            'type'        => 'select',
            'section'     => static::getSection(),
            'description' => static::getDescription(),
            'label'       => static::getLabel(),
            'choices'     => static::getOptions()
        ];
    }

    public static function getLabel(): string
    {
        return __('Numbered dots meaning', 'team-booking');
    }

    public static function getDescription(): string
    {
        return __('Select what the number inside the dots represents.', 'team-booking');
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

    public static function sanitize($value): string
    {
        return array_key_exists((string)$value, static::getOptions()) ? (string)$value : static::default();
    }

    public static function default(): string
    {
        return 'slots';
    }
}

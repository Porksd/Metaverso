<?php

namespace VSHM\Settings\Service;

use VSHM\Providers\Objects\Service;
use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Class Description
 *
 * @package VSHM
 */
class Description extends ServiceSettingBase
{
    public const ID = 'description';

    public static function subscribe(): void
    {
        return;
    }

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Editor::get(__('Description', 'team-booking'), self::ID);
        $element->setDescription(__('It will be displayed at the top of the time slots list or reservation form for unscheduled services.', 'team-booking'));
        $element->setAlert(Alert::warning(
            __('If you use HTML content, please consider the fact that it will observe the stylesheet of your theme and other plugins, so the result may differ from what you see in the editor.', 'team-booking')
        ));

        return $element;
    }

    public static function getProcessedDefault(Service $service)
    {
        return $service->description ?? self::getDefault();
    }

    public static function getDefault(): string
    {
        return '';
    }
}
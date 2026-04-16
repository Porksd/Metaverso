<?php

namespace VSHM\Settings\Service;

use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class RedirectUrl
 *
 * @package VSHM
 */
class RedirectUrl extends ServiceSettingBase
{

    public const ID = 'redirectUrl';

    public static function getBackendElement(): Element_Setting
    {
        $element = \VSHM\UI\Admin\Settings_Input::get(__('Redirect URL', 'team-booking'), self::ID);
        $element->setPlaceholder('https://');

        $element->addDependency(Settings_Dependency::TRUTHY(Redirect::ID));

        return $element;
    }

    public static function getDefault(): string
    {
        return '';
    }
}
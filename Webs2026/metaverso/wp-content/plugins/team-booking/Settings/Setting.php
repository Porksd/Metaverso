<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Element_Setting;

defined('ABSPATH') || exit;

/**
 * Interface Setting
 *
 * Defines an interface for a generic backend setting.
 */
interface Setting
{

    /**
     * Returns the backend UI element for this setting.
     *
     * @return Element_Setting
     */
    public static function getBackendElement(): ?Element_Setting;

    /**
     * Subscribes the setting (actions & filters).
     *
     * @return void
     */
    public static function subscribe(): void;
}

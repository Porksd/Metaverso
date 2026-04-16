<?php

namespace VSHM\Settings;

use VSHM\Bus\SaveSettings;
use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class AllowedServiceProviderWpRoles
 */
class AllowedServiceProviderWpRoles extends SettingBase
{

    public const ID = 'allowedServiceProviderWpRoles';

    public const ROLE = 'tb_can_sync_calendar';

    public static function subscribe(): void
    {
        parent::subscribe();
        add_action('vshm_dispatching_SaveSettings', [self::class, 'save_settings']);
    }

    public static function getBackendElement(): Element_Setting
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles_all = get_editable_roles();

        $element = \VSHM\UI\Admin\Settings_Checkboxes::get(__('WP Roles allowed to be Service Providers', 'team-booking'), self::ID);
        $element->setDescription(__('Select WP User roles that can provide services.', 'team-booking'));

        foreach ($roles_all as $name => $role) {
            if ($name === 'administrator') {
                continue;
            }
            $option = Settings_Option::get($role['name'], $name);
            $element->addOption($option);
        }

        $element->setAlert(Alert::info(__('Administrators are always allowed', 'team-booking')));

        return $element;
    }

    /**
     * @param SaveSettings $command
     */
    public static function save_settings(SaveSettings $command): void
    {
        $settings = $command->getSettings();
        if (isset($settings[ static::ID ]) && is_array($settings[ static::ID ])) {
            if (!function_exists('get_editable_roles')) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
            }
            $roles_all = get_editable_roles();
            foreach ($roles_all as $name => $role) {
                if ($name === 'administrator' || isset($role['capabilities']['manage_options'])) {
                    continue;
                }
                $obj = get_role($name);
                if (!$obj) {
                    continue;
                }
                if (in_array($name, $settings[ static::ID ], TRUE)) {
                    $obj->add_cap(static::ROLE);
                } else {
                    $obj->remove_cap(static::ROLE);
                }
            }
        }
    }

    /**
     * This method retrieve all the roles with tb_can_sync_calendar capability
     *
     * @return array List of roles allowed to link a calendar
     *
     * TODO: change the capability name for future versions.
     */
    public static function getRolesWithSyncCap(): array
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles = get_editable_roles();
        $list  = [];
        foreach ($roles as $name => $role) {
            if (isset($role['capabilities'][ self::ROLE ])) {
                $list[] = $name;
            }
        }

        return $list;
    }

    public static function default(): array
    {
        return self::getRolesWithSyncCap();
    }
}
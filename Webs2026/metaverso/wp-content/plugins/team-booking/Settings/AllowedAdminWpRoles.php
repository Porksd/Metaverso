<?php

namespace VSHM\Settings;

use VSHM\Bus\SaveSettings;
use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class AllowedAdminWpRoles
 */
class AllowedAdminWpRoles extends SettingBase
{

    public const ID = 'allowedAdminWpRoles';

    public const ROLE = 'tbk_can_admin';

    public static function subscribe(): void
    {
        parent::subscribe();
        add_action('vshm_dispatching_SaveSettings', [self::class, 'save_settings']);
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

    public static function getBackendElement(): Element_Setting
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles_all = get_editable_roles();

        $element = \VSHM\UI\Admin\Settings_Checkboxes::get(__('WP Roles allowed to manage the plugin', 'team-booking'), self::ID);
        $element->setDescription(__('Select WP User roles that can manage the plugin and providers.', 'team-booking'));

        foreach ($roles_all as $name => $role) {
            if ($name === 'administrator' || isset($role['capabilities']['manage_options'])) {
                continue;
            }
            $option = Settings_Option::get($role['name'], $name);
            $element->addOption($option);
        }

        $element->setAlert(Alert::info(__('Administrators are always allowed', 'team-booking')));

        return $element;
    }

    /**
     * This method retrieve all the roles with tbk_can_admin capability
     *
     * @return array List of roles allowed to admin
     *
     * @NEXT change the capability name for future versions.
     */
    public static function getRolesWithAdminCap(): array
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles = get_editable_roles();
        $list  = [];
        foreach ($roles as $name => $role) {
            if (isset($role['capabilities'][ self::ROLE ]) || isset($role['capabilities']['manage_options'])) {
                $list[] = $name;
            }
        }

        return $list;
    }

    public static function default(): array
    {
        return self::getRolesWithAdminCap();
    }
}
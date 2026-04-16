<?php

namespace VSHM\Providers;

use VSHM\Settings\AllowedServiceProviderWpRoles;

defined('ABSPATH') || exit;

/**
 * Class ServiceProviders
 *
 * @package VSHM\Providers
 */
class ServiceProviders extends ProviderBase
{
    public static function getMetaKeys()
    {
        return apply_filters('tbk_provider_meta_keys', []);
    }

    public static function convert_user($user): array
    {
        $provider = [
            'id'      => $user->ID,
            'name'    => $user->display_name,
            'email'   => $user->user_email,
            'url'     => $user->user_url,
            'wpRoles' => array_values((array)get_userdata($user->ID)->roles),
            'avatar'  => get_avatar_url($user->ID)
        ];
        foreach (self::getMetaKeys() as $metaKey) {
            $provider[ $metaKey ] = apply_filters('tbk_provider_meta_value', get_user_meta($user->ID, $metaKey, TRUE), $metaKey);
        }

        return $provider;
    }

    /**
     * @param bool $noCache
     *
     * @return array
     */
    public static function provide(bool $noCache = FALSE): array
    {
        $all_users = get_users();
        $items     = [];
        foreach ($all_users as $user) {
            if ($user->has_cap(AllowedServiceProviderWpRoles::ROLE) || user_can($user->ID, 'manage_options')) {
                $items[] = self::convert_user($user);
            }
        }

        return $items;
    }

    public static function store($data): void
    {
        $id = $data['id'];
        foreach ($data as $key => $value) {
            if (in_array($key, self::getMetaKeys(), TRUE)) {
                update_user_meta($id, $key, $value);
            }
        }
    }

    public static function update($data): void
    {
        self::store($data);
    }

    public static function remove($data): void
    {
        $id = $data['id'];
        foreach (self::getMetaKeys() as $key) {
            delete_user_meta($id, $key);
        }
        $user = new \WP_User($id);
        $user->remove_cap(AllowedServiceProviderWpRoles::ROLE);
    }

    public static function provideBy(array $conditions, bool $single = FALSE)
    {
        $items     = [];
        $all_users = [];
        if (isset($conditions['id'])) {
            $all_users = get_users(['include' => [(int)$conditions['id']]]);
        }

        foreach ($all_users as $user) {
            if ($user->has_cap(AllowedServiceProviderWpRoles::ROLE) || user_can($user->ID, 'manage_options')) {
                $items[] = self::convert_user($user);
            }
        }

        if ($single) {
            return $items[0] ?? NULL;
        }

        return $items;

    }
}
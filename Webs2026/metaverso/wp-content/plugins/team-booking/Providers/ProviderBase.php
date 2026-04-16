<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class ProviderBase
 */
abstract class ProviderBase implements Provider
{
    public const TABLE_NAME      = '';
    public const TABLE_STRUCTURE = [];

    public static function subscribe()
    {
    }

    public static function maybe_create_table(): void
    {
        DB::create_table(static::TABLE_NAME, static::TABLE_STRUCTURE);
    }

    /**
     * @param $record
     *
     * @return mixed
     */
    public static function convert_from_db($record)
    {
        return [];
    }

    /**
     * @param bool $noCache
     *
     * @return array
     */
    public static function provide(bool $noCache = FALSE): array
    {
        $items = self::cache_get('provide', static::TABLE_NAME);

        if ($items !== NULL && !$noCache) {
            return $items;
        }

        $items   = [];
        $results = DB::select(static::TABLE_NAME);

        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $items;
        }

        foreach ($results as $result) {
            $items[] = static::convert_from_db($result);
        }

        self::cache_set('provide', $items, static::TABLE_NAME);

        return apply_filters('vshm_provided_data', $items, static::class);
    }

    public static function cache_get($key, $group, $singleValue = FALSE): ?array
    {

        if (!vshm()->settings->get(\VSHM\Settings\UseCache::ID)) {
            return NULL;
        }

        if ($singleValue) {
            return NULL;
        }

        $items = wp_cache_get($key, $group, FALSE, $found);
        if ($found && is_array($items)) {
            return $items;
        }

        return NULL;
    }

    public static function cache_set($key, $data, $group): void
    {
        if (vshm()->settings->get(\VSHM\Settings\UseCache::ID)) {
            wp_cache_set($key, $data, $group);
        }
    }

    public static function flush_cache(): void
    {
        if (!vshm()->settings->get(\VSHM\Settings\UseCache::ID)) {
            return;
        }

        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(static::TABLE_NAME);
        } else {
            wp_cache_flush();
        }
    }

    public static function store($data): void
    {
        static::flush_cache();
    }

    public static function remove($data): void
    {
        static::flush_cache();
    }

    public static function update($data): void
    {
        static::flush_cache();
    }

    public static function adapt_conditions(array $conditions): array
    {
        return [];
    }

    public static function provideBy(array $conditions, bool $single = FALSE)
    {
        $hash  = md5(json_encode($conditions));
        $items = self::cache_get('provideBy' . $hash, static::TABLE_NAME, $single);

        if (NULL !== $items) {
            return $items;
        }

        $items = [];

        $results = DB::select(static::TABLE_NAME, '*', DB::where(static::adapt_conditions($conditions)));

        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $single ? NULL : $items;
        }

        foreach ($results as $result) {
            $items[] = static::convert_from_db($result);
        }

        if ($single) {
            //wp_cache_set('provideBy' . $hash, $items[0] ?? NULL, static::TABLE_NAME);

            return $items[0] ?? NULL;
        }
        self::cache_set('provideBy' . $hash, $items, static::TABLE_NAME);

        return apply_filters('vshm_provided_data', $items, static::class);
    }

    public static function removeBy(array $conditions): void
    {
        static::flush_cache();
        // TODO: Implement removeBy() method.
    }
}
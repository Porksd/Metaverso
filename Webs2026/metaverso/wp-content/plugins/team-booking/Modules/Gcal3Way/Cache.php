<?php

namespace VSHM\Modules\Gcal3Way;

use VSHM\DB;
use VSHM\Modules\Gcal3Way\Settings\GoogleFetchDelay;
use VSHM\Modules\Gcal3Way\Settings\GoogleSettingBase;

defined('ABSPATH') || exit;

/**
 * Class Cache
 */
class Cache
{
    public const TABLE_NAME      = 'tbk_gcal_cache';
    public const TABLE_STRUCTURE = [
        'data'  => ['type' => 'text', 'null' => TRUE],
        'hash'  => ['type' => 'text', 'null' => TRUE],
        'start' => 'int',
        'end'   => 'int',
    ];

    public static function maybe_create_table()
    {
        DB::create_table(static::TABLE_NAME, static::TABLE_STRUCTURE);
    }

    public static function store($data): void
    {
        DB::insert(self::TABLE_NAME, [
            'data'    => json_encode($data['data']),
            'hash'    => $data['hash'] ?? NULL,
            'start'   => $data['start'],
            'end'     => $data['end'],
            'created' => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function clean(): void
    {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . self::TABLE_NAME);

        $age = vshm()->settings->get(GoogleFetchDelay::ID, GoogleSettingBase::CONTEXT);

        $now = new \DateTime('-' . $age . ' seconds');
        $now = gmdate('Y-m-d H:i:s', $now->getTimestamp());

        $query = "DELETE FROM $table_name WHERE created < '$now'";

        $wpdb->get_results($query);

    }

    public static function flush(): void
    {
        DB::truncate_table(self::TABLE_NAME);
    }

    public static function provide($min, $max)
    {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . self::TABLE_NAME);

        $age = vshm()->settings->get(GoogleFetchDelay::ID, GoogleSettingBase::CONTEXT);

        $now = new \DateTime('-' . $age . ' seconds');
        $now = gmdate('Y-m-d H:i:s', $now->getTimestamp());

        $query = "SELECT * FROM $table_name WHERE created >= '$now' AND start <= {$min} AND end >= {$max} ORDER BY created DESC LIMIT 1";

        $results = $wpdb->get_results($query, 'ARRAY_A');

        if (empty($results)) {
            return NULL;
        }

        foreach ($results as $result) {
            return $result['data']
                ? json_decode($result['data'], TRUE)
                : NULL;
        }

        return NULL;
    }

}
<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class ServicesData
 *
 * @package VSHM\Providers
 */
class ServicesData extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_services_data';
    public const TABLE_STRUCTURE = [
        'service_id' => 'text',
        'data_key'   => 'text',
        'data_value' => ['type' => 'text', 'null' => TRUE],
    ];

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        return [
            'service_id' => $record['service_id'],
            'key'        => $record['data_key'],
            'value'      => $record['data_value'],
            'id'         => $record['id'],
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'service_id' => $data['service_id'],
            'data_key'   => $data['key'],
            'data_value' => $data['value'],
            'created'    => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function storeMany($data): void
    {
        static::flush_cache();
        $toInsert = [];
        foreach ($data as $datum) {
            $toInsert[] = [
                'service_id' => $datum['service_id'],
                'data_key'   => $datum['key'],
                'data_value' => $datum['value'],
                'created'    => gmdate('Y-m-d H:i:s')
            ];
        }
        DB::insertMany(self::TABLE_NAME,
            $toInsert,
            [
                'service_id',
                'data_key',
                'data_value',
                'created'
            ]
        );
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'data_value' => $data['value'],
        ], [
            'service_id' => $data['service_id'],
            'data_key'   => $data['key']
        ]);
    }

    public static function remove($data): void
    {
        parent::remove($data);
        DB::delete(self::TABLE_NAME, [
            'id' => $data['id']
        ]);
    }

    public static function adapt_conditions(array $conditions): array
    {
        $adapted = [];

        foreach ($conditions as $key => $condition) {
            switch ($key) {
                case 'key':
                    $adapted['data_key'] = $condition;
                    break;
                case 'value':
                    $adapted['data_value'] = $condition;
                    break;
                case 'service_id':
                    $adapted['service_id'] = $condition;
                    break;
            }
        }

        return $adapted;
    }

    /**
     * @param array $conditions
     * @param bool  $single
     * @param bool  $cached
     *
     * @return array|mixed
     */
    public static function provideBy(array $conditions, bool $single = FALSE, bool $cached = TRUE)
    {
        $hash  = md5(json_encode($conditions));
        $items = self::cache_get('provideBy' . $hash, static::TABLE_NAME, $single);

        if ($cached && NULL !== $items) {
            return $items;
        }

        $items = [];

        $results = DB::select(self::TABLE_NAME, '*', DB::where(self::adapt_conditions($conditions)));

        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $single ? NULL : $items;
        }

        foreach ($results as $result) {
            $items[] = self::convert_from_db($result);
        }

        if ($single) {
            $toReturn = isset($items[0]) ? $items[0]['value'] : NULL;

            //wp_cache_set('provideBy' . $hash, $toReturn, static::TABLE_NAME);

            return $toReturn;
        }

        self::cache_set('provideBy' . $hash, $items, static::TABLE_NAME);

        return $items;
    }

    public static function removeBy(array $conditions): void
    {
        DB::delete(self::TABLE_NAME, self::adapt_conditions($conditions));
    }
}
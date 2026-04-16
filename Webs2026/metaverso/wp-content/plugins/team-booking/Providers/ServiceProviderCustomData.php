<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class ServiceProviderCustomData
 *
 * @package VSHM\Providers
 */
class ServiceProviderCustomData extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_provider_custom_data';
    public const TABLE_STRUCTURE = [
        'service_id'  => 'text',
        'provider_id' => 'int',
        'data_key'    => 'text',
        'data_value'  => ['type' => 'text', 'null' => TRUE],
    ];

    public const JSON_DATA_KEYS = [
    ];

    private static function maybe_json_decode($value, $key)
    {
        return in_array($key, self::JSON_DATA_KEYS, TRUE) ? json_decode($value, TRUE) : $value;
    }

    private static function maybe_json_encode($value, $key)
    {
        return in_array($key, self::JSON_DATA_KEYS, TRUE) ? json_encode($value) : $value;
    }

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        return [
            'service_id'  => $record['service_id'],
            'provider_id' => $record['provider_id'],
            'key'         => $record['data_key'],
            'value'       => self::maybe_json_decode($record['data_value'], $record['data_key']),
            'id'          => $record['id'],
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'service_id'  => $data['service_id'],
            'provider_id' => $data['provider_id'],
            'data_key'    => $data['key'],
            'data_value'  => self::maybe_json_encode($data['value'], $data['key']),
            'created'     => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function storeMany($data): void
    {
        static::flush_cache();
        $toInsert = [];
        foreach ($data as $datum) {
            $toInsert[] = [
                'service_id'  => $datum['service_id'],
                'provider_id' => $datum['provider_id'],
                'data_key'    => $datum['key'],
                'data_value'  => self::maybe_json_encode($datum['value'], $datum['key']),
                'created'     => gmdate('Y-m-d H:i:s')
            ];
        }
        if (!empty($toInsert)) {
            DB::insertMany(self::TABLE_NAME,
                $toInsert,
                [
                    'service_id',
                    'provider_id',
                    'data_key',
                    'data_value',
                    'created'
                ]
            );
        }
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'data_value' => self::maybe_json_encode($data['value'], $data['key']),
        ], [
            'service_id'  => $data['service_id'],
            'provider_id' => $data['provider_id'],
            'data_key'    => $data['key']
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
                case 'provider_id':
                    $adapted['provider_id'] = $condition;
                    break;
                case 'service_id':
                    $adapted['service_id'] = $condition;
                    break;
            }
        }

        return $adapted;
    }

    public static function provideByWithDefault(array $conditions, $service)
    {
        if (!isset($conditions['key'])) {
            trigger_error('Missing "key" in conditions.');
        }

        return apply_filters('vshm_ensure_service_personal_setting', self::provideBy($conditions, TRUE), $conditions['key'], $service);
    }

    /**
     * @param array $conditions
     * @param bool  $single
     *
     * @return array|mixed
     */
    public static function provideBy(array $conditions, bool $single = FALSE)
    {
        $hash  = md5(json_encode($conditions));
        $items = self::cache_get('provideBy' . $hash, static::TABLE_NAME, $single);

        if (NULL !== $items) {
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

            return $toReturn;
        }
        self::cache_set('provideBy' . $hash, $items, static::TABLE_NAME);

        return apply_filters('vshm_provided_data', $items, static::class);
    }

    public static function removeBy(array $conditions): void
    {
        DB::delete(self::TABLE_NAME, self::adapt_conditions($conditions));
    }
}
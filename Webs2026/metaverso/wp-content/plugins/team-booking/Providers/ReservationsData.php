<?php

namespace VSHM\Providers;

use VSHM\DB;
use VSHM\Providers\Objects\Reservation;
use VSHM\Settings\Reservation\Discount;
use VSHM\Settings\Reservation\Refund;

defined('ABSPATH') || exit;

/**
 * Class ReservationsData
 *
 * @package VSHM\Providers
 */
class ReservationsData extends ProviderBase
{

    public const TABLE_NAME      = 'tbk_reservations_data';
    public const TABLE_STRUCTURE = [
        'reservation_id' => 'text',
        'data_key'       => ['type' => 'text', 'unique' => FALSE],
        'data_value'     => ['type' => 'text', 'null' => TRUE],
    ];

    public const JSON_DATA_KEYS = [
        'paymentDetails',
        'files',
        'discount', //legacy
        Discount::ID,
        Refund::ID,
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
            'reservation_id' => $record['reservation_id'],
            'key'            => $record['data_key'],
            'value'          => self::maybe_json_decode($record['data_value'], $record['data_key'])
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'reservation_id' => $data['reservation_id'],
            'data_key'       => $data['key'],
            'data_value'     => self::maybe_json_encode($data['value'], $data['key']),
            'created'        => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function storeMany($data): void
    {
        static::flush_cache();
        $toInsert = [];
        foreach ($data as $datum) {
            $toInsert[] = [
                'reservation_id' => $datum['reservation_id'],
                'data_key'       => $datum['key'],
                'data_value'     => self::maybe_json_encode($datum['value'], $datum['key']),
                'created'        => gmdate('Y-m-d H:i:s')
            ];
        }
        DB::insertMany(self::TABLE_NAME,
            $toInsert,
            [
                'reservation_id',
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
            'data_value' => self::maybe_json_encode($data['value'], $data['key']),
        ], [
            'reservation_id' => $data['reservation_id'],
            'data_key'       => $data['key']
        ]);
    }

    public static function removeAll(): void
    {
        static::flush_cache();
        DB::truncate_table(self::TABLE_NAME);
    }

    /**
     * @param array $conditions
     * @param bool  $single
     *
     * @return Reservation[]
     */
    public static function provideByWith(array $conditions = [], bool $single = FALSE): ?array
    {
        $hash  = md5(json_encode($conditions));
        $items = self::cache_get('provideByWith' . $hash, static::TABLE_NAME, $single);

        if (NULL !== $items) {
            return $items;
        }

        $items = [];

        $results = DB::selectJoin(
            self::TABLE_NAME,
            Reservations::TABLE_NAME,
            'reservation_id',
            'uid',
            '*',
            self::adapt_conditions($conditions)
        );

        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $items;
        }

        foreach ($results as $result) {

            if (!isset($items[ $result['uid'] ])) {
                $items[ $result['uid'] ] = Reservations::convert_from_db($result);
            }

            $dataResult = self::convert_from_db($result);

            $items[ $result['uid'] ]->data[ $dataResult['key'] ] = $dataResult['value'];
        }

        $items = array_values($items);

        if ($single) {

            return $items[0] ?? NULL;
        }

        self::cache_set('provideByWith' . $hash, $items, static::TABLE_NAME);

        return $items;
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
                case 'reservation_id':
                    $adapted['reservation_id'] = $condition;
                    break;
            }
        }

        return $adapted;
    }

    /**
     * @param array $conditions
     * @param bool  $single
     * @param bool  $valuesOnly
     * @param bool  $cached
     *
     * @return array|mixed
     */
    public static function provideBy(array $conditions, bool $single = FALSE, bool $valuesOnly = FALSE, bool $cached = TRUE)
    {
        $hash  = md5(json_encode($conditions) . $valuesOnly);
        $items = self::cache_get('provideBy' . $hash, static::TABLE_NAME, $single);

        if ($cached && NULL !== $items) {
            return $items;
        }

        $items = [];

        $results = DB::select(self::TABLE_NAME, ['data_value', 'data_key', 'reservation_id'], DB::where(self::adapt_conditions($conditions)));

        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $items;
        }

        foreach ($results as $result) {
            $item = self::convert_from_db($result);
            if ($valuesOnly) {
                $items[] = $item['value'];
            } else {
                $items[] = $item;
            }
        }

        if ($single) {

            if ($valuesOnly) {
                $toReturn = $items[0] ?? NULL;
            } else {
                $toReturn = isset($items[0])
                    ? $items[0]['value']
                    : NULL;

            }

            //wp_cache_set('provideBy' . $hash, $toReturn, static::TABLE_NAME);

            return $toReturn;
        }

        self::cache_set('provideBy' . $hash, $items, static::TABLE_NAME);

        return $items;
    }

    public static function removeBy(array $conditions): void
    {
        static::flush_cache();
        DB::delete(self::TABLE_NAME, self::adapt_conditions($conditions));
    }

    public static function clean_entries_for_non_existent_reservations()
    {
        global $wpdb;

        $table_name              = $wpdb->prefix . self::TABLE_NAME;
        $table_name_reservations = $wpdb->prefix . Reservations::TABLE_NAME;
        $query                   = "DELETE t1 from {$table_name} t1 LEFT JOIN {$table_name_reservations} t2 ON t2.uid = t1.reservation_id WHERE t2.uid IS NULL";

        return $wpdb->query($query);
    }
}
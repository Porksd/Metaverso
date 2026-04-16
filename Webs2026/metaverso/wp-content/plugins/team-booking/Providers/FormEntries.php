<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class FormEntries
 *
 * @package VSHM\Providers
 */
class FormEntries extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_form_entries';
    public const TABLE_STRUCTURE = [
        'field_id'       => 'text',
        'reservation_id' => 'text',
        'value'          => ['type' => 'text', 'null' => TRUE],
        'data'           => ['type' => 'text', 'null' => TRUE], // additional data in JSON
    ];

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        return [
            'id'            => $record['field_id'],
            'reservationId' => $record['reservation_id'],
            'value'         => $record['value'],
            'data'          => json_decode($record['data'], TRUE)
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'field_id'       => $data['id'],
            'reservation_id' => $data['reservationId'],
            'value'          => $data['value'],
            'data'           => json_encode($data['data']),
            'created'        => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function storeMany($data): void
    {
        static::flush_cache();
        $toInsert = [];
        foreach ($data as $datum) {
            $toInsert[] = [
                'field_id'       => $datum['id'],
                'reservation_id' => $datum['reservationId'],
                'value'          => $datum['value'],
                'data'           => json_encode($datum['data']),
                'created'        => gmdate('Y-m-d H:i:s')
            ];
        }
        DB::insertMany(self::TABLE_NAME,
            $toInsert,
            [
                'field_id',
                'reservation_id',
                'value',
                'data',
                'created'
            ]
        );
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'value' => $data['value'],
            'data'  => json_encode($data['data'])
        ], [
            'field_id'       => $data['id'],
            'reservation_id' => $data['reservationId'],
        ]);
    }

    public static function remove($data): void
    {
        parent::remove($data);
        DB::delete(self::TABLE_NAME, [
            'field_id' => $data['id']
        ]);
    }

    public static function provideByMultiple($field, $values = []): array
    {
        $items   = [];
        $results = DB::select(self::TABLE_NAME, '*', DB::whereIn([
            $field => $values
        ]));
        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $items;
        }
        foreach ($results as $result) {
            $items[] = self::convert_from_db($result);
        }

        return $items;
    }

    public static function provideByMultipleReservations($reservationsIds = []): array
    {
        $items = [];
        if (empty($reservationsIds)) {
            return $items;
        }
        $results = DB::select(self::TABLE_NAME, '*', DB::whereIn([
            'reservation_id' => $reservationsIds
        ]));
        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $items;
        }
        foreach ($results as $result) {
            $items[ $result['reservation_id'] ][] = self::convert_from_db($result);
        }

        return $items;
    }

    public static function adapt_conditions(array $conditions): array
    {
        $adapted = [];

        foreach ($conditions as $key => $condition) {
            switch ($key) {
                case 'reservationId':
                    $adapted['reservation_id'] = $condition;
                    break;
                case 'value':
                    $adapted['value'] = $condition;
                    break;
                case 'id':
                    $adapted['field_id'] = $condition;
                    break;
            }
        }

        return $adapted;
    }

    public static function removeBy(array $conditions): void
    {
        static::flush_cache();
        $where = [];
        if (isset($conditions['reservation_id'])) {
            $where['reservation_id'] = $conditions['reservation_id'];
        }
        if (isset($conditions['field_id'])) {
            $where['field_id'] = $conditions['field_id'];
        }
        DB::delete(self::TABLE_NAME, $where);
    }

    public static function removeAll($reset): void
    {
        static::flush_cache();
        if ($reset) {
            DB::truncate_table(self::TABLE_NAME);
        } else {
            DB::delete(self::TABLE_NAME, []);
        }
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
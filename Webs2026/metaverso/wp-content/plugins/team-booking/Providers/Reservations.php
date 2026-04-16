<?php

namespace VSHM\Providers;

use VSHM\DB;
use VSHM\Providers\Objects\Reservation;

defined('ABSPATH') || exit;

/**
 * Class Reservations
 *
 * @package VSHM\Providers
 */
class Reservations extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_reservations';
    public const TABLE_STRUCTURE = [
        'uid'            => 'text',
        'service_id'     => 'text',
        'provider_id'    => 'int',
        'customer_id'    => 'text',
        'date_start_utc' => ['type' => 'datetime', 'null' => TRUE],
        'date_end_utc'   => ['type' => 'datetime', 'null' => TRUE],
        'status'         => 'text'
    ];

    /**
     * @param $record
     *
     * @return Reservation
     */
    public static function convert_from_db($record): Reservation
    {
        $start = $record['date_start_utc'];
        $end   = $record['date_end_utc'];
        $tz    = new \DateTimeZone('UTC');

        $reservation             = new Reservation();
        $reservation->id         = $record['uid'];
        $reservation->db_id      = $record['id'];
        $reservation->serviceId  = $record['service_id'];
        $reservation->providerId = $record['provider_id'];
        $reservation->customerId = $record['customer_id'];
        $reservation->start      = $start ? \DateTime::createFromFormat('Y-m-d H:i:s', $start, $tz)->getTimestamp() : NULL;
        $reservation->end        = $end ? \DateTime::createFromFormat('Y-m-d H:i:s', $end, $tz)->getTimestamp() : NULL;
        $reservation->status     = $record['status'];
        $reservation->created    = $record['created'] ? \DateTime::createFromFormat('Y-m-d H:i:s', $record['created'], $tz)->getTimestamp() : NULL;

        return $reservation;
    }

    /**
     * @param Reservation $data
     *
     * @return void
     */
    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'uid'            => $data->id,
            'service_id'     => $data->serviceId,
            'provider_id'    => $data->providerId,
            'customer_id'    => $data->customerId,
            'date_start_utc' => NULL === $data->start ? NULL : gmdate('Y-m-d H:i:s', $data->start),
            'date_end_utc'   => NULL === $data->end ? NULL : gmdate('Y-m-d H:i:s', $data->end),
            'status'         => $data->status,
            'created'        => gmdate('Y-m-d H:i:s', $data->created ?? time())
        ]);
    }

    /**
     * @param Reservation[] $data
     *
     * @return void
     */
    public static function storeMany(array $data): void
    {
        static::flush_cache();
        $toInsert = [];
        foreach ($data as $datum) {
            $toInsert[] = [
                'uid'            => $datum->id,
                'service_id'     => $datum->serviceId,
                'provider_id'    => $datum->providerId,
                'customer_id'    => $datum->customerId,
                'date_start_utc' => NULL === $datum->start ? NULL : gmdate('Y-m-d H:i:s', $datum->start),
                'date_end_utc'   => NULL === $datum->end ? NULL : gmdate('Y-m-d H:i:s', $datum->end),
                'status'         => $datum->status,
                'created'        => gmdate('Y-m-d H:i:s', $datum->created ?? time())
            ];
        }
        DB::insertMany(self::TABLE_NAME,
            $toInsert,
            [
                'uid',
                'service_id',
                'provider_id',
                'customer_id',
                'date_start_utc',
                'date_end_utc',
                'status',
                'created'
            ]
        );
    }

    /**
     * @param Reservation $data
     *
     * @return void
     */
    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'service_id'     => $data->serviceId,
            'provider_id'    => $data->providerId,
            'customer_id'    => $data->customerId,
            'date_start_utc' => NULL === $data->start ? NULL : gmdate('Y-m-d H:i:s', $data->start),
            'date_end_utc'   => NULL === $data->end ? NULL : gmdate('Y-m-d H:i:s', $data->end),
            'status'         => $data->status,
        ], ['uid' => $data->id]);
    }

    /**
     * @param Reservation $data
     *
     * @return void
     */
    public static function remove($data): void
    {
        parent::remove($data);
        DB::delete(self::TABLE_NAME, [
            'uid' => $data->id
        ]);
    }

    /**
     * @param array    $conditions
     * @param bool     $single
     * @param int|null $start
     * @param int|null $end
     * @param bool     $unscheduled
     *
     * @return Reservation[]|Reservation|null
     */
    public static function provideByWithData(
        array $conditions = [],
        bool  $single = FALSE,
        int   $start = NULL,
        int   $end = NULL,
        bool  $unscheduled = FALSE
    )
    {
        $hash  = md5(json_encode($conditions));
        $items = self::cache_get('provideByWithData' . $hash, static::TABLE_NAME, $single);

        global $wpdb;

        $data_table_name = $wpdb->prefix . ReservationsData::TABLE_NAME;

        if (NULL !== $items) {
            return $items;
        }

        $items = [];

        if ($start) {
            $results = self::provideBetween($start, $end, $conditions, $unscheduled);
        } else {
            $results = self::provideBy($conditions);
        }

        /**
         * Reservations Data
         */
        $dataResults = [];
        if (!empty($results)) {
            $wpdb->query("SET @@group_concat_max_len = 10000;");
            $dataQuery = "SELECT GROUP_CONCAT(DISTINCT CONCAT('MAX(IF( data_key = ''', data_key,''', data_value, NULL)) AS ', data_key )) FROM {$data_table_name};";
            $group     = $wpdb->get_var($dataQuery);
            $dataQuery = "SELECT reservation_id, {$group} FROM {$data_table_name} GROUP BY reservation_id;";

            $dataResults = array_column($wpdb->get_results($dataQuery, 'ARRAY_A'), NULL, 'reservation_id');
        }

        /**
         * Form Entries
         */
        $entries = DB::selectJoin(
            FormEntries::TABLE_NAME,
            FormFields::TABLE_NAME,
            'field_id',
            'field_id',
            [
                't1.reservation_id',
                't1.field_id',
                't1.value',
                't1.data',
                't2.label',
                't2.type',
                't2.data as field_data',
            ]
        );

        foreach ($results as $result) {
            $result->data = $dataResults[ $result->id ] ?? [];
            foreach ($result->data as $key => $datum) {
                if (in_array($key, ReservationsData::JSON_DATA_KEYS, TRUE)) {
                    $result->data[ $key ] = json_decode($datum, TRUE);
                }
                $result->data[ $key ] = apply_filters('tbk_reservation_data_processing_frontend', $result->data[ $key ], $key);
            }
            unset($result->data['reservation_id']);
            $items[ $result->id ] = $result;
        }

        foreach ($entries as $entry) {
            if (isset($items[ $entry['reservation_id'] ])) {
                if (!isset($items[ $entry['reservation_id'] ]->data['formFields'])) {
                    $items[ $entry['reservation_id'] ]->data['formFields'] = [];
                }

                $field_data = json_decode($entry['field_data'], TRUE);

                $entry_value = $entry['value'];
                if (($entry['type'] === 'select' || $entry['type'] === 'radio')
                    && isset($field_data['options'])
                    && is_array($field_data['options'])) {
                    $entry_value = $field_data['options'][ $entry['value'] ]['value'] ?? $entry['value'];
                }

                $items[ $entry['reservation_id'] ]->data['formFields'][] = [
                    'id'            => $entry['field_id'],
                    'reservationId' => $entry['reservation_id'],
                    'value'         => $entry_value,
                    'data'          => json_decode($entry['data'], TRUE),
                    'type'          => $entry['type'],
                    'label'         => $entry['label'],
                ];
            }
        }

        $items = array_values($items);

        if ($single) {

            return $items[0] ?? NULL;
        }

        self::cache_set('provideByWithData' . $hash, $items, static::TABLE_NAME);

        return $items;
    }

    /**
     * @param $start_timestamp
     * @param $end_timestamp
     * @param $where
     * @param $unscheduled
     *
     * @return Reservation[]
     */
    public static function provideBetween($start_timestamp, $end_timestamp = NULL, $where = [], $unscheduled = FALSE): array
    {
        $hash  = md5(json_encode($where) . $start_timestamp . $end_timestamp);
        $items = self::cache_get('provideBetween' . $hash, static::TABLE_NAME);

        if (NULL !== $items) {
            return $items;
        }

        global $wpdb;
        $items      = [];
        $table_name = esc_sql($wpdb->prefix . self::TABLE_NAME);
        $start      = gmdate('Y-m-d H:i:s', $start_timestamp);

        $query = "SELECT * FROM $table_name WHERE date_end_utc >= '$start'";

        if (NULL !== $end_timestamp) {
            $end   = gmdate('Y-m-d H:i:s', $end_timestamp);
            $query .= " AND date_start_utc <= '$end'";
        }

        // TODO: more solid
        if ($unscheduled) {
            $query .= " OR date_start_utc = date_end_utc";
        }

        if (!empty($where)) {
            foreach ($where as $column => $condition) {
                if (!is_array($condition)) {
                    $condition = [
                        'operator' => '=',
                        'value'    => $condition
                    ];
                }
                $column   = DB::esc_sql_name($column);
                $value_s  = esc_sql($condition['value']);
                $operator = DB::esc_sql_name($condition['operator']);
                $query    .= " AND {$column} {$operator} '{$value_s}'";
            }
        }

        $results = $wpdb->get_results($query, 'ARRAY_A');

        if ($results instanceof \WP_Error) {
            // TODO: handle it

            return $items;
        }

        foreach ($results as $result) {
            $items[] = self::convert_from_db($result);
        }

        self::cache_set('provideBetween' . $hash, $items, static::TABLE_NAME);

        return $items;
    }

    public static function adapt_conditions(array $conditions): array
    {
        $adapted = [];
        foreach ($conditions as $key => $condition) {
            switch ($key) {
                case 'id':
                    $adapted['uid'] = $condition;
                    break;
                case 'serviceId':
                    $adapted['service_id'] = $condition;
                    break;
                case 'customerId':
                    $adapted['customer_id'] = $condition;
                    break;
                case 'provider_id':
                    $adapted['provider_id'] = $condition;
                    break;
                case 'status':
                    $adapted['status'] = $condition;
                    break;
                case 'start':
                    $adapted['date_start_utc'] = [
                        'value'    => gmdate('Y-m-d H:i:s', $condition['value']),
                        'operator' => $condition['operator']
                    ];
                    break;
                case 'end':
                    $adapted['date_end_utc'] = [
                        'value'    => gmdate('Y-m-d H:i:s', $condition['value']),
                        'operator' => $condition['operator']
                    ];
                    break;
            }
        }

        return $adapted;
    }

    /**
     * @param array $conditions
     *
     * @return int
     */
    public static function count(array $conditions = []): int
    {
        return DB::count(self::TABLE_NAME, self::adapt_conditions($conditions));
    }

    /**
     * @param $conditions
     * @param $page
     * @param $itemsPerPage
     *
     * @return Reservation[]
     */
    public static function provideByPaginate($conditions, $page = 1, $itemsPerPage = 50): array
    {
        $items = [];

        $results = DB::select(self::TABLE_NAME, '*',
            DB::where(self::adapt_conditions($conditions)),
            DB::pagination('created', 'ASC', $itemsPerPage, $page)
        );

        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $items;
        }

        foreach ($results as $result) {
            $items[] = self::convert_from_db($result);
        }

        return $items;
    }

    /**
     * @param array $conditions
     * @param bool  $single
     *
     * @return Reservation[]|mixed|Reservation|null
     */
    public static function provideBy(array $conditions, bool $single = FALSE)
    {
        $hash  = md5(json_encode($conditions));
        $items = self::cache_get('provideBy' . $hash, static::TABLE_NAME, $single);

        if (NULL !== $items) {
            return $items;
        }

        $items = [];

        $where     = self::adapt_conditions($conditions);
        $whereData = [];

        foreach ($conditions as $key => $condition) {
            if ($key === 'data' && is_array($condition)) {
                $whereData = $condition;
            }
        }

        if (!empty($whereData)) {
            $results = DB::selectMultiTableConditions(
                self::TABLE_NAME,
                ReservationsData::TABLE_NAME,
                'uid',
                'reservation_id',
                'data_key',
                'data_value',
                $whereData,
                '*',
                $where
            );
        } else {
            $results = DB::select(self::TABLE_NAME, '*', DB::where($where));
        }

        if ($results instanceof \WP_Error) {
            // TODO: handle it
            return $items;
        }

        foreach ($results as $result) {
            $items[] = self::convert_from_db($result);
        }

        if ($single) {
            //wp_cache_set('provideBy' . $hash, $items[0] ?? NULL, static::TABLE_NAME);

            return $items[0] ?? NULL;
        }
        self::cache_set('provideBy' . $hash, $items, static::TABLE_NAME);

        return $items;
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

    public static function clean_reservations_for_non_existent_services()
    {
        global $wpdb;

        $table_name          = $wpdb->prefix . self::TABLE_NAME;
        $table_name_services = $wpdb->prefix . Services::TABLE_NAME;
        $query               = "DELETE from {$table_name} t1 WHERE NOT EXISTS (select NULL from {$table_name_services} t2 WHERE t2.uid = t1.service_id)";

        return $wpdb->query($query);
    }
}
<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class Customers
 *
 * @package VSHM\Providers
 */
class Customers extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_customers';
    public const TABLE_STRUCTURE = [
        'customer_name' => ['type' => 'text', 'null' => TRUE],
        'uid'           => 'varchar',
        'email'         => 'varchar',
        'phone'         => ['type' => 'varchar', 'null' => TRUE],
        'wp_user'       => 'int',
        'access_token'  => 'varchar',
        'status'        => 'int'
    ];

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        return [
            'id'           => $record['uid'],
            'name'         => $record['customer_name'],
            'email'        => $record['email'],
            'phone'        => $record['phone'],
            'wp_user'      => (int)$record['wp_user'],
            'access_token' => $record['access_token'],
            'status'       => (int)$record['status'],
            'avatar'       => get_avatar_url((int)$record['wp_user']),
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'uid'           => $data['id'],
            'customer_name' => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'wp_user'       => $data['wp_user'],
            'access_token'  => $data['access_token'],
            'status'        => $data['status'],
            'created'       => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function storeMany($data): void
    {
        static::flush_cache();
        $toInsert = [];
        foreach ($data as $datum) {
            $toInsert[] = [
                'uid'           => $datum['id'],
                'customer_name' => $datum['name'],
                'email'         => $datum['email'],
                'phone'         => $datum['phone'],
                'wp_user'       => $datum['wp_user'],
                'access_token'  => $datum['access_token'],
                'status'        => $datum['status'],
                'created'       => gmdate('Y-m-d H:i:s')
            ];
        }
        DB::insertMany(self::TABLE_NAME,
            $toInsert,
            [
                'uid',
                'customer_name',
                'email',
                'phone',
                'wp_user',
                'access_token',
                'status',
                'created'
            ]
        );
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'customer_name' => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'wp_user'       => $data['wp_user'],
            'access_token'  => $data['access_token'],
            'status'        => $data['status'],
        ], ['uid' => $data['id']]);
    }

    public static function remove($data): void
    {
        parent::remove($data);
        DB::delete(self::TABLE_NAME, [
            'uid' => $data['id']
        ]);
    }

    public static function adapt_conditions(array $conditions): array
    {
        $adapted = [];

        foreach ($conditions as $key => $condition) {
            switch ($key) {
                case 'name':
                    $adapted['customer_name'] = $condition;
                    break;
                case 'id':
                    $adapted['uid'] = $condition;
                    break;
                case 'email':
                    $adapted['email'] = $condition;
                    break;
                case 'wp_user':
                    $adapted['wp_user'] = $condition;
                    break;
                case 'status':
                    $adapted['status'] = $condition;
                    break;
                case 'access_token':
                    $adapted['access_token'] = $condition;
                    break;
            }
        }

        return $adapted;
    }

    public static function clean_entries_for_non_existent_reservations()
    {
        global $wpdb;

        $table_name              = $wpdb->prefix . self::TABLE_NAME;
        $table_name_reservations = $wpdb->prefix . Reservations::TABLE_NAME;
        $query                   = "DELETE t1 from {$table_name} t1 LEFT JOIN {$table_name_reservations} t2 ON t2.customer_id = t1.uid WHERE t2.customer_id IS NULL";

        return $wpdb->query($query);
    }
}
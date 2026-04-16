<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class Locations
 *
 * @package VSHM\Providers
 */
class Locations extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_locations';
    public const TABLE_STRUCTURE = [
        'name'     => ['type' => 'text', 'null' => TRUE],
        'address'  => ['type' => 'text', 'null' => TRUE],
        'lat_'     => ['type' => 'text', 'null' => TRUE],
        'long_'    => ['type' => 'text', 'null' => TRUE],
        'uid'      => 'text',
        'status'   => 'int',
        'capacity' => 'int'
    ];

    public static function convert_from_db($record): array
    {
        return [
            'name'     => $record['name'],
            'address'  => $record['address'],
            'lat'      => $record['lat_'],
            'long'     => $record['long_'],
            'id'       => $record['uid'],
            'capacity' => (int)$record['capacity'],
            'status'   => (int)$record['status']
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'name'     => $data['name'],
            'address'  => $data['address'],
            'lat_'     => $data['lat'],
            'long_'    => $data['long'],
            'uid'      => $data['id'],
            'capacity' => (int)$data['capacity'],
            'status'   => (int)$data['status'],
            'created'  => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function remove($data): void
    {
        parent::remove($data);
        DB::delete(self::TABLE_NAME, [
            'uid' => $data['id']
        ]);
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'name'     => $data['name'],
            'address'  => $data['address'],
            'lat_'     => $data['lat'],
            'long_'    => $data['long'],
            'status'   => (int)$data['status'],
            'capacity' => (int)$data['capacity'],
        ], ['uid' => $data['id']]);
    }

    public static function adapt_conditions(array $conditions): array
    {
        $adapted = [];
        foreach ($conditions as $key => $condition) {
            switch ($key) {
                case 'name':
                    $adapted['name'] = $condition;
                    break;
                case 'status':
                    $adapted['status'] = $condition;
                    break;
                case 'id':
                    $adapted['uid'] = $condition;
                    break;
            }
        }

        return $adapted;
    }
}
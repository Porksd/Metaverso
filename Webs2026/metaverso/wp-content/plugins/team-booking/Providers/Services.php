<?php

namespace VSHM\Providers;

use VSHM\DB;
use VSHM\Providers\Objects\Service;

defined('ABSPATH') || exit;

/**
 * Class Services
 *
 * @package VSHM\Providers
 */
class Services extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_services';
    public const TABLE_STRUCTURE = [
        'name'        => ['type' => 'text', 'null' => TRUE],
        'class'       => 'text',
        'uid'         => 'text',
        'description' => ['type' => 'text', 'null' => TRUE],
        'color'       => ['type' => 'varchar', 'null' => TRUE],
        'status'      => 'int'
    ];

    /**
     * @param $record
     *
     * @return Service
     */
    public static function convert_from_db($record): Service
    {
        $service              = new Service();
        $service->name        = $record['name'];
        $service->id          = $record['uid'];
        $service->description = $record['description'];
        $service->status      = (int)$record['status'];
        $service->class       = $record['class'];
        $service->color       = $record['color'];
        $service->created     = $record['created'];

        return $service;
    }

    /**
     * @param Service $data
     *
     * @return void
     */
    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'name'        => $data->name,
            'description' => $data->description,
            'uid'         => $data->id,
            'color'       => $data->color,
            'status'      => $data->status,
            'class'       => $data->class,
            'created'     => gmdate('Y-m-d H:i:s')
        ]);
    }

    /**
     * @param Service[] $services
     *
     * @return void
     */
    public static function storeMany(array $services): void
    {
        static::flush_cache();
        $toInsert = [];
        foreach ($services as $service) {
            $toInsert[] = [
                'name'        => $service->name,
                'description' => $service->description,
                'uid'         => $service->id,
                'color'       => $service->color,
                'status'      => $service->status,
                'class'       => $service->class,
                'created'     => gmdate('Y-m-d H:i:s')
            ];
        }
        DB::insertMany(self::TABLE_NAME,
            $toInsert,
            [
                'name',
                'description',
                'uid',
                'color',
                'status',
                'class',
                'created'
            ]
        );
    }

    /**
     * @return Service[]
     */
    public static function provide(bool $noCache = FALSE): array
    {
        return parent::provide($noCache);
    }

    /**
     * @param array $conditions
     * @param bool  $single
     *
     * @return Service[]|Service|null
     */
    public static function provideBy(array $conditions, bool $single = FALSE)
    {
        return parent::provideBy($conditions, $single);
    }

    /**
     * @param Service $data
     *
     * @return void
     */
    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'name'        => $data->name,
            'description' => $data->description,
            'color'       => $data->color,
            'status'      => $data->status,
            'class'       => $data->class,
        ], ['uid' => $data->id]);
    }

    /**
     * @param Service $data
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

    public static function removeBy(array $conditions): void
    {
        parent::removeBy($conditions);
        DB::delete(self::TABLE_NAME, self::adapt_conditions($conditions));
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
                case 'class':
                    $adapted['class'] = $condition;
                    break;
            }
        }

        return $adapted;
    }
}
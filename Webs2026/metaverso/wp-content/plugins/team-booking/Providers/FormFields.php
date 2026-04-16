<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class FormFields
 *
 * @package VSHM\Providers
 */
class FormFields extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_form_fields';
    public const TABLE_STRUCTURE = [
        'field_id'    => 'text',
        'type'        => 'text',
        'hook'        => ['type' => 'text', 'null' => TRUE],
        'data'        => ['type' => 'text', 'null' => TRUE], // specific type properties in JSON
        'label'       => ['type' => 'text', 'null' => TRUE],
        'description' => ['type' => 'text', 'null' => TRUE]
    ];

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        return [
            'id'          => $record['field_id'],
            'type'        => $record['type'],
            'hook'        => $record['hook'],
            'data'        => json_decode($record['data'], TRUE),
            'label'       => $record['label'],
            'description' => $record['description'],
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'field_id'    => $data['id'],
            'type'        => $data['type'],
            'hook'        => $data['hook'],
            'data'        => json_encode($data['data']),
            'label'       => $data['label'],
            'description' => $data['description'],
            'created'     => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function storeMany($data): void
    {
        static::flush_cache();
        $toInsert = [];
        foreach ($data as $datum) {
            $toInsert[] = [
                'field_id'    => $datum['id'],
                'type'        => $datum['type'],
                'hook'        => $datum['hook'],
                'data'        => json_encode($datum['data']),
                'label'       => $datum['label'],
                'description' => $datum['description'],
                'created'     => gmdate('Y-m-d H:i:s')
            ];
        }
        DB::insertMany(self::TABLE_NAME,
            $toInsert,
            [
                'field_id',
                'type',
                'hook',
                'data',
                'label',
                'description',
                'created'
            ]
        );
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'type'        => $data['type'],
            'hook'        => $data['hook'],
            'data'        => json_encode($data['data']),
            'label'       => $data['label'],
            'description' => $data['description'],
        ], ['field_id' => $data['id']]);
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
        $hash  = md5(json_encode($values) . $field);
        $items = self::cache_get('provideByMultiple' . $hash, static::TABLE_NAME);

        if (NULL !== $items) {
            return $items;
        }

        $items = [];
        if ($field === 'id') {
            $field = 'field_id';
        }
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

        self::cache_set('provideByMultiple' . $hash, $items, static::TABLE_NAME);

        return $items;
    }

    public static function adapt_conditions(array $conditions): array
    {
        $adapted = [];
        foreach ($conditions as $key => $condition) {
            switch ($key) {
                case 'id':
                    $adapted['field_id'] = $condition;
                    break;
                case 'hook':
                    $adapted['hook'] = $condition;
                    break;
                case 'type':
                    $adapted['type'] = $condition;
                    break;
                case 'label':
                    $adapted['label'] = $condition;
                    break;
            }
        }

        return $adapted;
    }
}
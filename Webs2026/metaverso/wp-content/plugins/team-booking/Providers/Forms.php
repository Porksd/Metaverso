<?php

namespace VSHM\Providers;

use VSHM\DB;
use VSHM\TbkSettings;

defined('ABSPATH') || exit;

/**
 * Class Forms
 *
 * @package VSHM\Providers
 */
class Forms extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_forms';
    public const TABLE_STRUCTURE = [
        'form_id'   => 'text',
        'fields'    => ['type' => 'text', 'null' => TRUE],
        'required'  => ['type' => 'text', 'null' => TRUE],
        'is_active' => ['type' => 'text', 'null' => TRUE],
        'logic'     => ['type' => 'text', 'null' => TRUE],
    ];

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        return [
            'id'       => $record['form_id'],
            'fields'   => json_decode($record['fields'], TRUE),
            'required' => json_decode($record['required'], TRUE),
            'active'   => json_decode($record['is_active'], TRUE),
            'logic'    => json_decode($record['logic'], TRUE),
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'form_id'   => $data['id'],
            'fields'    => json_encode($data['fields']),
            'required'  => json_encode($data['required']),
            'is_active' => json_encode($data['active']),
            'logic'     => json_encode($data['logic']),
            'created'   => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'fields'    => json_encode($data['fields']),
            'required'  => json_encode($data['required']),
            'is_active' => json_encode($data['active']),
            'logic'     => json_encode($data['logic']),
        ], ['form_id' => $data['id']]);
    }

    public static function remove($data): void
    {
        parent::remove($data);
        DB::delete(self::TABLE_NAME, [
            'form_id' => $data['id']
        ]);
    }

    public static function adapt_conditions(array $conditions): array
    {
        $adapted = [];
        foreach ($conditions as $key => $condition) {
            switch ($key) {
                case 'id':
                    $adapted['form_id'] = $condition;
                    break;
            }
        }

        return $adapted;
    }
}
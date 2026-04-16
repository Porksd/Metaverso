<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class ApiTokens
 *
 * @package VSHM\Providers
 */
class ApiTokens extends ProviderBase
{
    public const TABLE_NAME      = 'tbk_api_tokens';
    public const TABLE_STRUCTURE = [
        'name'     => ['type' => 'text', 'null' => TRUE],
        'usages'   => 'int',
        'token'    => 'varchar',
        'readonly' => 'int'
    ];

    public static function convert_from_db($record): array
    {
        return [
            'name'     => $record['name'],
            'token'    => $record['token'],
            'usages'   => (int)$record['usages'],
            'readonly' => (bool)$record['readonly']
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'name'     => $data['name'],
            'token'    => $data['token'],
            'usages'   => $data['usages'],
            'readonly' => $data['readonly'],
            'created'  => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function remove($data): void
    {
        parent::remove($data);
        DB::delete(self::TABLE_NAME, [
            'token' => $data['token']
        ]);
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'readonly' => $data['readonly'],
            'name'     => $data['name'],
            'usages'   => $data['usages'],
        ], ['token' => $data['token']]);
    }

    public static function adapt_conditions(array $conditions): array
    {
        $adapted = [];

        foreach ($conditions as $key => $condition) {
            switch ($key) {
                case 'name':
                    $adapted['name'] = $condition;
                    break;
                case 'token':
                    $adapted['token'] = $condition;
                    break;
                case 'readonly':
                    $adapted['readonly'] = $condition;
                    break;
            }
        }

        return $adapted;
    }
}
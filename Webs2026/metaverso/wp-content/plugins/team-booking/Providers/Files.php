<?php

namespace VSHM\Providers;

use VSHM\DB;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Files
 *
 * @package VSHM\Providers
 */
class Files extends ProviderBase
{
    public const TABLE_NAME = 'tbk_uploaded_files';

    public const TABLE_STRUCTURE = [
        'hash' => 'varchar',
        'path' => 'text',
        'url'  => 'text',
        'mime' => 'varchar'
    ];

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        return [
            'hash'    => $record['hash'],
            'file'    => $record['path'],
            'url'     => $record['url'],
            'type'    => $record['mime'],
            'created' => $record['created'],
        ];
    }

    /**
     * @param string $reservation_id
     *
     * @return array
     */
    public static function provideByReservationId(string $reservation_id): array
    {
        $filesHashes = ReservationsData::provideBy(['reservation_id' => $reservation_id, 'key' => 'files'], TRUE);
        $return      = [];

        if ($filesHashes && is_array($filesHashes)) {
            $fileRecords = DB::select(self::TABLE_NAME, '*', DB::whereIn([
                'hash' => array_values($filesHashes)
            ]));

            if ($fileRecords && is_array($fileRecords)) {
                $return = array_column($fileRecords, NULL, 'hash');
            }
        }

        foreach ($return as $hash => $item) {
            $return[ $hash ] = self::convert_from_db($item);
        }

        return $return;
    }


    public static function store($data): void
    {
        parent::store($data);

        DB::insert(self::TABLE_NAME, [
            'hash'    => Tools::file_hash($data),
            'path'    => $data['file'],
            'url'     => $data['url'],
            'mime'    => $data['type'],
            'created' => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function remove($data): void
    {
        parent::remove($data);
        DB::delete(self::TABLE_NAME, [
            'hash' => $data['hash']
        ]);
    }
}
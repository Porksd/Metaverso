<?php

namespace VSHM\Providers;

use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * Class Promotions
 *
 * @package VSHM\Providers
 */
class Promotions extends ProviderBase
{

    /**
     * TODO
     */
    public const TABLE_NAME      = 'tbk_promotions';
    public const TABLE_STRUCTURE = [
        'uid'            => 'text',
        'promotion_name' => 'text',
        'start_utc'      => 'datetime',
        'end_utc'        => 'datetime',
        'status'         => 'int',
        'data'           => 'text',
        'promotion_type' => 'text',
        'discount_type'  => 'text',
        'discount_value' => 'text'
    ];

    /**
     * @param $record
     *
     * @return array
     */
    public static function convert_from_db($record): array
    {
        $start = $record['start_utc'];
        $end   = $record['end_utc'];
        $tz    = new \DateTimeZone('UTC');

        return [
            'id'                    => $record['uid'],
            'db_id'                 => $record['id'],
            'promotionPeriod_start' => \DateTime::createFromFormat('Y-m-d H:i:s', $start, $tz)->getTimestamp(),
            'promotionPeriod_end'   => \DateTime::createFromFormat('Y-m-d H:i:s', $end, $tz)->getTimestamp(),
            'promotionName'         => $record['promotion_name'],
            'promotionType'         => $record['promotion_type'],
            'discountType'          => $record['discount_type'],
            'promotionValue'        => $record['discount_value'],
            'status'                => (int)$record['status'],
            'data'                  => json_decode($record['data'], TRUE)
        ];
    }

    public static function store($data): void
    {
        parent::store($data);
        DB::insert(self::TABLE_NAME, [
            'uid'            => $data['id'],
            'start_utc'      => gmdate('Y-m-d H:i:s', $data['promotionPeriod_start']),
            'end_utc'        => gmdate('Y-m-d H:i:s', $data['promotionPeriod_end']),
            'promotion_name' => $data['promotionName'],
            'promotion_type' => $data['promotionType'],
            'discount_type'  => $data['discountType'],
            'status'         => (int)$data['status'],
            'discount_value' => $data['promotionValue'],
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
                'uid'            => $datum['id'],
                'start_utc'      => gmdate('Y-m-d H:i:s', $datum['promotionPeriod_start']),
                'end_utc'        => gmdate('Y-m-d H:i:s', $datum['promotionPeriod_end']),
                'promotion_name' => $datum['promotionName'],
                'promotion_type' => $datum['promotionType'],
                'discount_type'  => $datum['discountType'],
                'status'         => (int)$datum['status'],
                'discount_value' => $datum['promotionValue'],
                'data'           => json_encode($datum['data']),
                'created'        => gmdate('Y-m-d H:i:s')
            ];
        }
        DB::insertMany(self::TABLE_NAME,
            $toInsert,
            [
                'uid',
                'start_utc',
                'end_utc',
                'promotion_name',
                'promotion_type',
                'discount_type',
                'status',
                'discount_value',
                'data',
                'created'
            ]
        );
    }

    public static function update($data): void
    {
        parent::update($data);
        DB::update(self::TABLE_NAME, [
            'start_utc'      => gmdate('Y-m-d H:i:s', $data['promotionPeriod_start']),
            'end_utc'        => gmdate('Y-m-d H:i:s', $data['promotionPeriod_end']),
            'promotion_name' => $data['promotionName'],
            'promotion_type' => $data['promotionType'],
            'discount_type'  => $data['discountType'],
            'status'         => (int)$data['status'],
            'discount_value' => $data['promotionValue'],
            'data'           => json_encode($data['data']),
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
                case 'promotionType':
                    $adapted['promotion_type'] = $condition;
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
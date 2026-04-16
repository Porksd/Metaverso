<?php

namespace VSHM\Routes;

use VSHM\Providers\ReservationsData;
use VSHM\REST_Controller;
use VSHM\Settings\Reservation\Discount;

defined('ABSPATH') || exit;

/**
 * Class PromotionsRoute
 *
 * @package VSHM\Routes
 */
final class PromotionsRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/promotions/';

    public static function prepare_coupons_for_frontend($promotionId): array
    {
        $promotion      = \VSHM\Providers\Promotions::provideBy(['id' => $promotionId], TRUE);
        $coupons_filled = [];
        if ($promotion) {
            if ($promotion['promotionType'] === 'coupon' && isset($promotion['data']['coupons'])) {
                $coupons = is_array($promotion['data']['coupons']) ? $promotion['data']['coupons'] : [];

                $discountedReservations = ReservationsData::provideByWith(['key' => Discount::ID]);

                foreach ($coupons as $coupon) {
                    $to_fill = [
                        'name'        => $coupon,
                        'used'        => FALSE,
                        'reservation' => NULL
                    ];

                    foreach ($discountedReservations as $discountedReservation) {
                        foreach ($discountedReservation->data[ Discount::ID ] as $reservationDiscount) {
                            if ($reservationDiscount['id'] !== $promotionId || !isset($reservationDiscount['coupon'])) {
                                continue;
                            }
                            if ($discountedReservation->status === 'confirmed'
                                || (
                                    $discountedReservation->status === 'pending'
                                    && !apply_filters('tbk_is_reservation_expired', FALSE, $discountedReservation)
                                )) {
                                $to_fill['used']              = TRUE;
                                $to_fill['reservation']       = $discountedReservation->id;
                                $to_fill['reservation_db_id'] = $discountedReservation->db_id;
                            }
                        }
                    }
                    $coupons_filled[] = $to_fill;
                }
            }
        }

        return $coupons_filled;
    }

    public static function prepare_for_frontend(): array
    {
        $promotions    = \VSHM\Providers\Promotions::provide();
        $promotionUses = [];

        $discountedReservations = ReservationsData::provideByWith(['key' => Discount::ID]);

        foreach ($discountedReservations as $discountedReservation) {
            foreach ($discountedReservation->data[ Discount::ID ] as $reservationDiscount) {
                if ($discountedReservation->status === 'confirmed'
                    || (
                        $discountedReservation->status === 'pending'
                        && !apply_filters('tbk_is_reservation_expired', FALSE, $discountedReservation)
                    )) {
                    if (!isset($promotionUses[ $reservationDiscount['id'] ])) {
                        $promotionUses[ $reservationDiscount['id'] ] = [];
                    }
                    $promotionUses[ $reservationDiscount['id'] ][] = $discountedReservation->id;
                }
            }
        }

        foreach ($promotions as $key => $promotion) {
            $promotions[ $key ]['__uses'] = $promotionUses[ $promotion['id'] ] ?? [];
        }

        return $promotions;
    }

    public static function register(): void
    {
        REST_Controller::register_routes([
            \VSHM\Routes\Promotions\Get::getPath()           => \VSHM\Routes\Promotions\Get::get(),
            \VSHM\Routes\Promotions\Remove::getPath()        => \VSHM\Routes\Promotions\Remove::get(),
            \VSHM\Routes\Promotions\RemoveMulti::getPath()   => \VSHM\Routes\Promotions\RemoveMulti::get(),
            \VSHM\Routes\Promotions\Save::getPath()          => \VSHM\Routes\Promotions\Save::get(),
            \VSHM\Routes\Promotions\Add::getPath()           => \VSHM\Routes\Promotions\Add::get(),
            \VSHM\Routes\Promotions\CouponsGet::getPath()    => \VSHM\Routes\Promotions\CouponsGet::get(),
            \VSHM\Routes\Promotions\CouponsAdd::getPath()    => \VSHM\Routes\Promotions\CouponsAdd::get(),
            \VSHM\Routes\Promotions\CouponsRemove::getPath() => \VSHM\Routes\Promotions\CouponsRemove::get(),
            \VSHM\Routes\Promotions\CouponsVerify::getPath() => \VSHM\Routes\Promotions\CouponsVerify::get(),
        ]);
    }

    public static function getPath()
    {
        return self::$path;
    }
}
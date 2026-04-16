<?php

namespace VSHM\Routes\Promotions;

use VSHM\Bus\EditPromotion;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\PromotionsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class CouponsRemove
 *
 * @package VSHM\Routes
 */
class CouponsRemove implements SingleRoute
{
    public static function getPath(): string
    {
        return PromotionsRoute::getPath() . 'coupons/remove/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $promotionId = $request->get_param('promotionId');
                $coupon      = $request->get_param('coupon');
                $promotion   = \VSHM\Providers\Promotions::provideBy(['id' => $promotionId], TRUE);
                if ($promotion) {
                    if ($promotion['promotionType'] === 'coupon' && isset($promotion['data']['coupons'])) {
                        $coupons                      = is_array($promotion['data']['coupons']) ? $promotion['data']['coupons'] : [];
                        $promotion['data']['coupons'] = array_diff($coupons, [$coupon]);
                        vshm()->bus->dispatch(new EditPromotion(
                            $promotion['id'],
                            $promotion['promotionName'],
                            $promotion['promotionType'],
                            $promotion['promotionPeriod_start'],
                            $promotion['promotionPeriod_end'],
                            $promotion['promotionValue'],
                            $promotion['discountType'],
                            $promotion['status'],
                            $promotion['data']
                        ));
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'coupons'    => PromotionsRoute::prepare_coupons_for_frontend($request->get_param('promotionId')),
                    'promotions' => PromotionsRoute::prepare_for_frontend()
                ]);
            },
            'args'                => [
                'promotionId' => [
                    'required' => TRUE
                ],
                'coupon'      => [
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
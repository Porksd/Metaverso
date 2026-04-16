<?php

namespace VSHM\Routes\Promotions;

use VSHM\Bus\EditPromotion;
use VSHM\Functions;
use VSHM\Providers\Promotions;
use VSHM\REST_Controller;
use VSHM\Routes\PromotionsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Promotion\CouponMode;

defined('ABSPATH') || exit;

/**
 * Class Save
 *
 * @package VSHM\Routes
 */
class Save implements SingleRoute
{
    public static function getPath(): string
    {
        return PromotionsRoute::getPath() . 'save/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $settings = $request->get_param('data');
                if (is_array($settings)) {
                    foreach ($settings as $setting) {
                        // settings are always for the same promotion...
                        $promotion = Promotions::provideBy(['id' => $setting['promotion_id']], TRUE);
                        if (array_key_exists($setting['key'], $promotion)) {
                            if (($setting['key'] === 'promotionType') && $promotion[ $setting['key'] ] !== $setting['value']) {
                                if ($setting['value'] === 'coupon') {
                                    // Promotion just switched to coupon type, we need to set a coupon mode as well
                                    $promotion['data'][ CouponMode::ID ] = CouponMode::default($setting['promotion_id']);
                                }
                                if ($setting['value'] === 'campaign') {
                                    // Promotion just switched to campaign type, remove the coupon data
                                    // TODO
                                    unset($promotion['data']['coupons']);
                                }
                            }
                            $promotion[ $setting['key'] ] = $setting['value'];

                        } elseif (isset($promotion['data']) && array_key_exists($setting['key'], $promotion['data'])) {
                            $promotion['data'][ $setting['key'] ] = $setting['value'];
                        }
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
                    'promotions' => PromotionsRoute::prepare_for_frontend(),
                    'message'    => __('Settings have been saved!', 'team-booking')
                ]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
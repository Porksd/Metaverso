<?php

namespace VSHM\Routes\Promotions;

use VSHM\Functions;
use VSHM\Providers\Promotions;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\PromotionsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\CurrencyCode;
use VSHM\Settings\Promotion\DiscountType;
use VSHM\Settings\Promotion\PromotionType;
use VSHM\Settings\Service\Price;

defined('ABSPATH') || exit;

/**
 * Class CouponsVerify
 */
class CouponsVerify implements SingleRoute
{
    public static function getPath(): string
    {
        return PromotionsRoute::getPath() . 'coupons/verify/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => function (\WP_REST_Request $request) {

                $slots            = is_string($request->get_param('slots'))
                    ? json_decode($request->get_param('slots'), TRUE)
                    : $request->get_param('slots');
                $slotsTickets     = (int)$request->get_param('slotsTickets');
                $coupon           = $request->get_param('coupon');
                $valid            = FALSE;
                $return_promotion = [];

                $formFields = is_string($request->get_param('formFields')) ? json_decode($request->get_param('formFields'), TRUE) : $request->get_param('formFields');
                $formValues = is_string($request->get_param('formValues')) ? json_decode($request->get_param('formValues'), TRUE) : $request->get_param('formValues');

                foreach (Promotions::provideBy([PromotionType::ID => PromotionType::COUPON, 'status' => 1]) as $promotion) {
                    if (apply_filters('tbk_is_coupon_valid', FALSE, $promotion, $coupon)) {
                        $new_prices      = [];
                        $otherPromotions = Promotions::provideBy([PromotionType::ID => PromotionType::CAMPAIGN, 'status' => 1]);
                        $applied_to      = [];
                        foreach ($slots as $slot) {
                            $servicePrice = ServicesData::provideBy(['service_id' => $slot['serviceId'], 'key' => Price::ID], TRUE);
                            if (!$servicePrice) {
                                return REST_Controller::get_error_response(self::getPath(), ['message' => __('Service price not found', 'team-booking')], 404);
                            }
                            if (isset($slot['overrides']['price'])) {
                                $servicePrice = $slot['overrides']['price'];
                            }
                            $currency = vshm()->settings->get(CurrencyCode::ID);
                            $price    = \Whitecube\Price\Price::parse($servicePrice, $currency);
                            $price->setUnits($slotsTickets ?? 1);

                            $price = Functions::apply_extras_to_price($price, $formFields ?? [], $formValues ?? []);

                            if (apply_filters('tbk_is_promotion_applicable', FALSE, $promotion, $slot['start'], $slot['end'], $slot['serviceId'])) {
                                $price        = Functions::apply_discount_modifier($price, $promotion['id'], $promotion['discountType'], $promotion['promotionValue']);
                                $applied_to[] = $slot['event_id'] ?? NULL;
                            }

                            foreach ($otherPromotions as $otherPromotion) {
                                if (apply_filters('tbk_is_promotion_applicable', FALSE, $otherPromotion, $slot['start'], $slot['end'], $slot['serviceId'])) {
                                    $price = Functions::apply_discount_modifier($price, $otherPromotion['id'], $otherPromotion['discountType'], $otherPromotion['promotionValue']);
                                }
                            }

                            $new_prices[ $slot['event_id'] ?? NULL ] = $price->inclusive()->getAmount()->toFloat();
                        }
                        $valid            = TRUE;
                        $return_promotion = [
                            'type'      => $promotion[ DiscountType::ID ],
                            'value'     => $promotion['promotionValue'],
                            'appliedTo' => $applied_to
                        ];
                        break;
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'valid'     => $valid,
                    'promotion' => $return_promotion,
                    'prices'    => $new_prices ?? []
                ]);
            },
            'args'     => [
                'slots'        => [
                    'required' => TRUE
                ],
                'slotsTickets' => [
                    'required' => TRUE
                ],
                'formFields'   => [
                    'required' => TRUE
                ],
                'formValues'   => [
                    'required' => TRUE
                ],
                'coupon'       => [
                    'required' => TRUE
                ]
            ],
        ];
    }
}
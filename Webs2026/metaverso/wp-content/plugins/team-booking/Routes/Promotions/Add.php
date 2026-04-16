<?php

namespace VSHM\Routes\Promotions;

use VSHM\Bus\CreatePromotion;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\PromotionsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Promotion\PromotionType;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Add
 *
 * @package VSHM\Routes
 */
class Add implements SingleRoute
{
    public static function getPath(): string
    {
        return PromotionsRoute::getPath() . 'add/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $settings       = $request->get_param('data');
                $whitelistTypes = PromotionType::TYPES;
                if (!in_array($settings['type'], $whitelistTypes, TRUE)) {
                    return REST_Controller::get_error_response(self::getPath(), [
                        'promotions' => PromotionsRoute::prepare_for_frontend(),
                        'message'    => 'Type not allowed',
                    ], 401);
                }

                $newId = apply_filters('tbk_promotion_token_gen', Tools::generate_token('alnum', 32, 'p_'));

                vshm()->bus->dispatch(new CreatePromotion(
                    $settings['name'],
                    $settings['type'],
                    $settings['discountType'],
                    $settings['discountValue'],
                    $newId,
                    (int)$settings['start'],
                    (int)$settings['end']
                ));

                return REST_Controller::get_ok_response(self::getPath(), ['promotions' => PromotionsRoute::prepare_for_frontend()]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
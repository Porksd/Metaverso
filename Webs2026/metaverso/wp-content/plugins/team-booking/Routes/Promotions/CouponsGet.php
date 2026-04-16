<?php

namespace VSHM\Routes\Promotions;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\PromotionsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class CouponsGet
 *
 * @package VSHM\Routes
 */
class CouponsGet implements SingleRoute
{
    public static function getPath(): string
    {
        return PromotionsRoute::getPath() . 'coupons/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                return REST_Controller::get_ok_response(self::getPath(), [
                    'coupons' => PromotionsRoute::prepare_coupons_for_frontend($request->get_param('promotionId'))
                ]);
            },
            'args'                => [
                'promotionId' => [
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin();
            }
        ];
    }
}
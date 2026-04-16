<?php

namespace VSHM\Routes\Services;

use VSHM\Bus\CreateService;
use VSHM\Bus\UpdateOrCreateServiceProperty;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Service\ShortDescription;
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
        return ServicesRoute::getPath() . 'add/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $item = $request->get_param('data');

                // TODO: validation

                $serviceId = apply_filters('tbk_service_token_gen', Tools::generate_token('alnum', 32, 's_'));

                vshm()->bus->dispatch(new CreateService(
                    $serviceId,
                    $item['name'],
                    '',
                    $item['class'],
                    $item['color'] ?? apply_filters('tbk_default_service_color', sprintf('#%06X', mt_rand(0, 0xFFFFFF)))
                ));

                if (isset($item['description']) && $item['description']) {
                    vshm()->bus->dispatch(new UpdateOrCreateServiceProperty($serviceId, ShortDescription::ID, $item['description']));
                }

                return REST_Controller::get_ok_response(self::getPath(), ['data' => ServicesRoute::prepare_for_backend()]);
            },
            'args'                => [
                'data' => [
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
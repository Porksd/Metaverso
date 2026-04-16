<?php

namespace VSHM\Routes\API\Services;

use VSHM\Bus\CreateService;
use VSHM\Bus\UseApiToken;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Add
 *
 * @package VSHM\Routes\API\Services
 */
class Add implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'services/add/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $record = json_decode($request->get_body(), TRUE);

                if (!is_array($record)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Body is not a valid JSON']);
                }

                if (!isset($record['name'], $record['class'])) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Data is incomplete']);
                }

                if (!in_array($record['class'], ['appointment', 'unscheduled'], TRUE)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Wrong service class']);

                }

                $service_id = apply_filters('tbk_service_token_gen', Tools::generate_token('alnum', 32, 's_'));

                vshm()->bus->dispatch(new CreateService(
                    $service_id,
                    (string)$record['name'],
                    isset($record['description']) ? (string)$record['description'] : '',
                    $record['class'],
                    isset($record['color']) ? (string)$record['color'] : NULL
                ), vshm()->bus::AGENT_APP, $token['token']);

                return REST_Controller::get_ok_response(self::getPath(), ['id' => $service_id]);
            },
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
<?php

namespace VSHM\Routes;

use VSHM\Bus\CreateApiToken;
use VSHM\Bus\DeleteApiToken;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class ApiTokensRoute
 *
 * @package VSHM\Routes
 */
final class ApiTokensRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/api/tokens/';

    public static function register(): void
    {
        REST_Controller::register_routes([
            self::$path . 'get/'    => [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    return REST_Controller::get_ok_response(self::$path . 'get/', ['data' => \VSHM\Providers\ApiTokens::provide()]);
                },
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin();
                }
            ],
            self::$path . 'remove/' => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    $token = $request->get_param('token');

                    vshm()->bus->dispatch(new DeleteApiToken($token));

                    return REST_Controller::get_ok_response(self::$path . 'remove/', ['data' => \VSHM\Providers\ApiTokens::provide()]);
                },
                'args'                => [
                    'token' => [
                        'type'     => 'string',
                        'required' => TRUE
                    ]
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
            self::$path . 'add/'    => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => function (\WP_REST_Request $request) {

                    $token = Tools::generate_token();
                    vshm()->bus->dispatch(new CreateApiToken($token, $request->get_param('name'), (bool)$request->get_param('readonly')));

                    return REST_Controller::get_ok_response(self::$path . 'add/', ['data' => \VSHM\Providers\ApiTokens::provide()]);
                },
                'args'                => [
                    'name'     => [
                        'type'     => 'string',
                        'required' => TRUE
                    ],
                    'readonly' => [
                        'type'     => 'boolean',
                        'required' => TRUE
                    ]
                ],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin(TRUE);
                }
            ],
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
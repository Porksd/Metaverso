<?php

namespace VSHM\Routes\API\Customers;

use VSHM\Bus\CreateCustomer;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\Customers;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Add
 *
 * @package VSHM\Routes\API\Customers
 */
class Add implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'customers/add/';
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

                if (!isset($record['email'], $record['name'])) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Data is incomplete']);
                }

                if (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Email address is not valid']);
                }

                $customer = Customers::provideBy(['email' => $record['email']], TRUE);

                if ($customer) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Email already in use']);
                }

                $wpId = 0;

                if (isset($record['wpUserId'])) {
                    $user = get_user_by('id', (int)$record['wpUserId']);
                    if (!$user) {
                        return REST_Controller::get_error_response(self::getPath(), ['message' => 'WP User not found'], 404);
                    }
                } else {
                    if (filter_var($request->get_param('createUser'), FILTER_VALIDATE_BOOLEAN)) {

                        $newUserId = wp_insert_user([
                            'user_pass'    => wp_generate_password(),
                            'display_name' => $record['name'],
                            'user_email'   => $record['email'],
                            'user_login'   => explode('@', $record['email'])[0] . '_' . Tools::generate_token('numeric', 2)
                        ]);

                        if ($newUserId instanceof \WP_Error) {

                            return REST_Controller::get_error_response(self::getPath(), ['message' => $newUserId->get_error_message()]);
                        }
                        $wpId = $newUserId;
                    }
                }

                $customer_id = apply_filters('tbk_customer_token_gen', Tools::generate_token('alnum', 32, 'c_'));

                vshm()->bus->dispatch(new CreateCustomer(
                    $customer_id,
                    (string)$record['name'],
                    $record['email'],
                    $record['phone'] ?: '',
                    $wpId,
                    Tools::generate_token()
                ), vshm()->bus::AGENT_APP, $token['token']);


                return REST_Controller::get_ok_response(self::getPath(), ['id' => $customer_id], 201);
            },
            'args'                => [
                'createUser' => [
                    'type' => 'bool'
                ]
            ],
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
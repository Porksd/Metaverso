<?php

namespace VSHM\Routes\API\Customers;

use VSHM\Bus\EditCustomer;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\Customers;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Edit
 *
 * @package VSHM\Routes\API\Customers
 */
class Edit implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'customers/edit/';
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

                if (!isset($record['id'])) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Data is incomplete']);
                }

                $customer = Customers::provideBy(['id' => $record['id']], TRUE);

                if (!$customer) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Customer not found'], 404);
                }

                if (isset($record['email']) && $record['email'] !== $customer['email']) {

                    if (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
                        return REST_Controller::get_error_response(self::getPath(), ['message' => 'Email address is not valid']);
                    }

                    $otherCustomer = Customers::provideBy(['email' => $record['email']], TRUE);
                    if ($otherCustomer) {
                        return REST_Controller::get_error_response(self::getPath(), ['message' => 'Email address already in use']);
                    }

                }

                if (isset($record['wpUserId']) && (int)$record['wpUserId'] !== $customer['wp_user']) {

                    $user = get_user_by('id', (int)$record['wpUserId']);
                    if (!$user) {
                        return REST_Controller::get_error_response(self::getPath(), ['message' => 'WP User not found'], 404);
                    }
                    $otherCustomer = Customers::provideBy(['wp_user' => (int)$record['wpUserId']], TRUE);
                    if ($otherCustomer) {
                        return REST_Controller::get_error_response(self::getPath(), ['message' => 'WP User already in use']);
                    }

                }

                vshm()->bus->dispatch(new EditCustomer(
                    $customer['id'],
                    $record['name'] ? (string)$record['name'] : $customer['name'],
                    $record['email'] ? (string)$record['email'] : $customer['email'],
                    $record['phone'] ?: $customer['phone'],
                    (int)$record['wpUserId'] ?: (int)$customer['wp_user'],
                    $customer['access_token'],
                    $customer['status']
                ), vshm()->bus::AGENT_APP, $token['token']);


                return REST_Controller::get_ok_response(self::getPath());
            },
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
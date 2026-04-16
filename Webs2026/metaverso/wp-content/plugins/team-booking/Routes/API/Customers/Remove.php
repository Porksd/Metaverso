<?php

namespace VSHM\Routes\API\Customers;

use VSHM\Bus\DeleteCustomer;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Remove
 *
 * @package VSHM\Routes\API\Customers
 */
class Remove implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'customers/remove/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $token = ApiRoute::getStoredToken($request);
                vshm()->bus->dispatch(new UseApiToken($token));

                $customer = Customers::provideBy(['id' => $request->get_param('id')], TRUE);

                if (!$customer) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Customer not found.'], 404);
                }

                $reservations = Reservations::provideBy(['customerId' => $customer['id']]);
                if (!empty($reservations)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => "Customer can't be removed. There are reservations for this customer."], 403);
                }

                vshm()->bus->dispatch(new DeleteCustomer($customer['id']), vshm()->bus::AGENT_APP, $token['token']);

                return REST_Controller::get_ok_response(self::getPath());
            },
            'args'                => [
                'id' => [
                    'type'     => 'string',
                    'required' => TRUE
                ]
            ],
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
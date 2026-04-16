<?php

namespace VSHM\Routes\Customers;

use VSHM\Bus\CreateCustomer;
use VSHM\Functions;
use VSHM\Providers\Customers;
use VSHM\REST_Controller;
use VSHM\Routes\CustomersRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Get
 *
 * @package VSHM\Routes
 */
class Add implements SingleRoute
{
    public static function getPath(): string
    {
        return CustomersRoute::getPath() . 'add/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $item = $request->get_param('data'); // email, name, phone, wp_user

                // TODO: validation

                $wpId = $item['wp_user'];

                if ($wpId < 1) {
                    if (email_exists($item['email'])) {

                        return REST_Controller::get_error_response(self::getPath(), ['message' => __('This email address is already registered for a WordPress user.', 'team-booking')]);
                    }

                    $customers_email_check = Customers::provideBy(['email' => $item['email']]);
                    if (count($customers_email_check)) {

                        return REST_Controller::get_error_response(self::getPath(), ['message' => __('This email address is already registered for another customer.', 'team-booking')]);
                    }
                }

                if ($wpId < 0) {
                    /**
                     * if -1 we should create a new WordPress user
                     */
                    $newUserId = wp_insert_user([
                        'user_pass'    => wp_generate_password(),
                        'display_name' => $item['name'],
                        'user_email'   => $item['email'],
                        'user_login'   => explode('@', $item['email'])[0] . '_' . Tools::generate_token('numeric', 2)
                    ]);

                    if ($newUserId instanceof \WP_Error) {

                        return REST_Controller::get_error_response(self::getPath(), ['message' => $newUserId->get_error_message()]);
                    }
                    $wpId = $newUserId;
                }

                if ($wpId > 0) {
                    $wp_user = get_user_by('ID', $wpId);

                    if (!$wp_user) {
                        return REST_Controller::get_error_response(self::getPath(), ['message' => __('This WordPress User does not exist.', 'team-booking')]);
                    }

                    $item['email'] = $wp_user->user_email;
                    $item['name']  = $wp_user->display_name;
                }

                $customer_id = apply_filters('tbk_customer_token_gen', Tools::generate_token('alnum', 32, 'c_'));

                vshm()->bus->dispatch(new CreateCustomer($customer_id, $item['name'], $item['email'], $item['phone'], $wpId));

                return REST_Controller::get_ok_response(self::getPath(), ['data' => \VSHM\Providers\Customers::provide()]);

            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
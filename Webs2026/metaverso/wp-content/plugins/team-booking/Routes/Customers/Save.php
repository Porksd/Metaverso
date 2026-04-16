<?php

namespace VSHM\Routes\Customers;

use VSHM\Bus\EditCustomer;
use VSHM\Functions;
use VSHM\Providers\Customers;
use VSHM\REST_Controller;
use VSHM\Routes\CustomersRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Customer\Email;

defined('ABSPATH') || exit;

/**
 * Class Save
 *
 * @package VSHM\Routes
 */
class Save implements SingleRoute
{
    public static function getPath(): string
    {
        return CustomersRoute::getPath() . 'save/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $settings = $request->get_param('data');
                if (is_array($settings)) {
                    foreach ($settings as $setting) {
                        $customer = Customers::provideBy(['id' => $setting['customer_id']], TRUE);

                        if (!$customer) {
                            continue;
                        }

                        if (isset($customer[ $setting['key'] ])) {
                            if ($setting['key'] === Email::ID) {

                                if (!filter_var($setting['value'], FILTER_VALIDATE_EMAIL)) {
                                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Please provide a valid email address.', 'team-booking')]);
                                }

                                $email_exists = email_exists($setting['value']);

                                if ($email_exists && $email_exists !== $customer['wp_user']) {

                                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('This email address is already registered for a WordPress user.', 'team-booking')]);
                                }

                                $customers_email_check = Customers::provideBy(['email' => $setting['value']]);
                                if (count($customers_email_check)) {
                                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('This email address is already registered for another customer.', 'team-booking')]);
                                }
                            }
                            if ($setting['key'] === 'wp_user' && $setting['value']) {
                                $customers_user_check = Customers::provideBy(['wp_user' => $setting['value']]);
                                if (count($customers_user_check)) {

                                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('This WordPress User is already associated with another customer.', 'team-booking')]);
                                }

                                $wp_user = get_user_by('ID', $setting['value']);

                                if (!$wp_user) {
                                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('This WordPress User does not exist.', 'team-booking')]);
                                }

                                $customers_email_check = Customers::provideBy(['email' => $wp_user->user_email]);
                                if (count($customers_email_check)) {
                                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Another customer is already registered with this email address.', 'team-booking')]);
                                }

                                $customer['email'] = $wp_user->user_email;
                                $customer['name']  = $wp_user->display_name;
                            }
                            $customer[ $setting['key'] ] = $setting['value'];
                            vshm()->bus->dispatch(new EditCustomer(
                                $customer['id'],
                                $customer['name'],
                                $customer['email'],
                                $customer['phone'],
                                $customer['wp_user'],
                                $customer['access_token'],
                                $customer['status']
                            ));
                        }

                    }
                }

                return REST_Controller::get_ok_response(
                    self::getPath(),
                    ['message' => __('Settings have been saved!', 'team-booking')]
                );
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
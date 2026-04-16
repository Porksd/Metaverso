<?php

namespace VSHM\Routes\Customers;

use VSHM\Bus\DeleteCustomer;
use VSHM\Functions;
use VSHM\Providers\Reservations;
use VSHM\REST_Controller;
use VSHM\Routes\CustomersRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class RemoveMulti
 *
 * @package VSHM\Routes
 */
class RemoveMulti implements SingleRoute
{
    public static function getPath(): string
    {
        return CustomersRoute::getPath() . 'remove/multi/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $customers = $request->get_param('data');

                $limited = FALSE;

                if (isset($customers['ids']) && is_array($customers['ids'])) {

                    $reservations = array_column(Reservations::provide(), 'id', 'customerId');

                    foreach ($customers['ids'] as $id) {
                        if (!isset($reservations[ $id ])) {
                            vshm()->bus->dispatch(new DeleteCustomer($id));
                        } else {
                            $limited = TRUE;
                        }
                    }
                }

                $args = ['data' => \VSHM\Providers\Customers::provide()];

                if ($limited) {
                    $args['message'] = __('Certain customers could not be removed due to existing reservations associated with them.', 'team-booking');
                }

                return REST_Controller::get_ok_response(self::getPath(), $args);

            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
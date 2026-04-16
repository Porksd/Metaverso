<?php

namespace VSHM\Routes\Forms;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\FormsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Service\ReservationFormId;

defined('ABSPATH') || exit;

/**
 * Class Get
 *
 * @package VSHM\Routes
 */
class Get implements SingleRoute
{
    public static function getPath(): string
    {
        return FormsRoute::getPath() . 'get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $id = $request->get_param('id');

                if (!$id && $request->get_param('serviceId')) {
                    $serviceForm = \VSHM\Providers\ServicesData::provideBy(['service_id' => $request->get_param('serviceId'), 'key' => ReservationFormId::ID]);
                    if (is_array($serviceForm) && count($serviceForm) === 1) {
                        $id = $serviceForm[0]['value'];
                    } else {
                        return REST_Controller::get_error_response(self::getPath(), ['message' => __('Service form ID not found.', 'team-booking')], 404);
                    }
                }

                $forms = $id ? \VSHM\Providers\Forms::provideBy(['id' => $id]) : \VSHM\Providers\Forms::provide();

                foreach ($forms as $key => $form) {
                    $forms[ $key ] = apply_filters('tbk_populating_form', $form);
                }

                return REST_Controller::get_ok_response(self::getPath(), ['data' => $forms]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin();
            }
        ];
    }
}
<?php

namespace VSHM\Routes\Forms;

use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\FormsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Service\ReservationFormId;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class GetFrontend
 *
 * @package VSHM\Routes
 */
class GetFrontend implements SingleRoute
{
    public static function getPath(): string
    {
        return FormsRoute::getPath() . 'get/frontend/';
    }

    public static function get(): array
    {
        return [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => function (\WP_REST_Request $request) {

                $service = \VSHM\Providers\ServicesData::provideBy(['service_id' => $request->get_param('serviceId'), 'key' => ReservationFormId::ID]);

                // @NEXT: other conditions may vary the form output, such as the slot and the provider.

                if (is_array($service) && count($service) === 1) {
                    $id = $service[0]['value'];
                } else {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Service not found', 'team-booking')], 404);
                }

                $forms = \VSHM\Providers\Forms::provideBy(['id' => $id]);

                if (empty($forms)) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => __('Booking form not found', 'team-booking')], 404);
                }

                // Fully populating fields
                foreach ($forms as $key => $form) {
                    $forms[ $key ] = apply_filters('tbk_populating_form_frontend', $form);
                }

                return REST_Controller::get_ok_response(self::getPath(), ['data' => $forms[0]]);
            }
        ];
    }
}
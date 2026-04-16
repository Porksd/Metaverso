<?php

namespace VSHM\Routes\API\Reservations;

use VSHM\Bus\CreateReservation;
use VSHM\Bus\UseApiToken;
use VSHM\Providers\Customers;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\ApiRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Reservation\AvailabilityId;
use VSHM\Settings\Service\Approval;
use VSHM\Settings\Service\PaymentRequirement;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Add
 *
 * @package VSHM\Routes\API\Reservations
 */
class Add implements SingleRoute
{
    public static function getPath(): string
    {
        return ApiRoute::getPath() . 'reservations/add/';
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

                if (!isset($record['serviceId'], $record['customerId'], $record['providerId'], $record['availabilityId'])) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Data is incomplete']);
                }

                $service = Services::provideBy(['id' => (string)$record['serviceId']], TRUE);
                if (!$service) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service not found'], 404);
                }
                if ($service->class !== 'unscheduled' && !(isset($record['start'], $record['end']))) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Data is incomplete']);
                }

                $provider = ServiceProviders::provideBy(['id' => (string)$record['providerId']], TRUE);
                if (!$provider) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service provider not found'], 404);
                }
                $customer = Customers::provideBy(['id' => (string)$record['customerId']], TRUE);
                if (!$customer) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Customer not found'], 404);
                }

                $reservation_id = apply_filters('tbk_reservation_token_gen', Tools::generate_token('alnum', 32, 'r_'));

                /**
                 * Determining the status
                 */
                $status           = 'confirmed';
                $approval_setting = ServicesData::provideBy(['key' => Approval::ID, 'service_id' => $service->id], TRUE);
                $payment_setting  = ServicesData::provideBy(['key' => PaymentRequirement::ID, 'service_id' => $service->id], TRUE);

                if ($payment_setting === PaymentRequirement::IMMEDIATE) {
                    $status = 'pending';
                }
                if ($approval_setting !== 'none') {
                    // TODO: evaluate the possibility to auto-approve it
                    $status = 'pending';
                }

                $data = [
                    [
                        'key'   => AvailabilityId::ID,
                        'value' => $record['availabilityId']
                    ]
                ];

                if (isset($record['data']) && is_array($record['data'])) {
                    foreach ($record['data'] as $key => $datum) {
                        $data[] = [
                            'key'   => $key,
                            'value' => $datum
                        ];
                    }
                }

                vshm()->bus->dispatch(new CreateReservation(
                    $reservation_id,
                    $service->id,
                    $customer['id'],
                    $provider['id'],
                    $record['start'] ?? time(),
                    $record['end'] ?? time(),
                    $data,
                    $status
                ), vshm()->bus::AGENT_APP, $token['token']);

                return REST_Controller::get_ok_response(self::getPath(), ['id' => $reservation_id]);
            },
            'permission_callback' => function (\WP_REST_Request $request) {
                return ApiRoute::validate_write_request($request);
            }
        ];
    }
}
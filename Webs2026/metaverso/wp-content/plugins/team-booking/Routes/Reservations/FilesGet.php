<?php

namespace VSHM\Routes\Reservations;

use VSHM\Functions;
use VSHM\Providers\Files;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\ReservationsRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Service\ReservationFormId;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class FilesGet
 *
 * @package VSHM\Routes\Reservations
 */
class FilesGet implements SingleRoute
{
    public static function getPath(): string
    {
        return ReservationsRoute::getPath() . '(?P<id>[\w]+)/files/get/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $reservation_id = $request->get_url_params()['id'];
                $files          = [];
                $reservation    = Reservations::provideBy(['id' => $reservation_id], TRUE);

                if (!$reservation) {
                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Reservation not found.'], 404);
                }

                $serviceForm = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => ReservationFormId::ID], TRUE);
                if (!$serviceForm) {

                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service form not found.'], 404);
                }

                $formsStuff = Forms::provideBy(['id' => $serviceForm], TRUE);

                if (!$formsStuff || !isset($formsStuff['fields'])) {

                    return REST_Controller::get_error_response(self::getPath(), ['message' => 'Service form fields not found.'], 404);
                }

                $serviceFormFields = FormFields::provideByMultiple('field_id', $formsStuff['fields']);

                $filesHashes = ReservationsData::provideBy(['reservation_id' => $reservation_id, 'key' => 'files'], TRUE);

                $fileRecords = Files::provideByReservationId($reservation_id);

                foreach ($serviceFormFields as $serviceFormField) {
                    if ($serviceFormField['type'] === 'file_upload') {
                        $files[ $serviceFormField['id'] ] = [
                            'label'       => $serviceFormField['label'],
                            'description' => $serviceFormField['description'],
                            'fileUrl'     => isset($filesHashes[ $serviceFormField['id'] ]) ? $fileRecords[ $filesHashes[ $serviceFormField['id'] ] ]['url'] : NULL,
                            'fileName'    => isset($filesHashes[ $serviceFormField['id'] ]) ? basename($fileRecords[ $filesHashes[ $serviceFormField['id'] ] ]['url']) : NULL,
                            'mime'        => isset($filesHashes[ $serviceFormField['id'] ]) ? $fileRecords[ $filesHashes[ $serviceFormField['id'] ] ]['type'] : NULL,
                            'ext'         => isset($filesHashes[ $serviceFormField['id'] ]) ? Tools::mime_to_ext($fileRecords[ $filesHashes[ $serviceFormField['id'] ] ]['type']) : NULL,
                            'hash'        => $filesHashes[ $serviceFormField['id'] ] ?? NULL,
                        ];
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), ['files' => $files]);
            },
            'permission_callback' => static function () {
                return Functions::current_user_is_provider();
            }
        ];
    }
}
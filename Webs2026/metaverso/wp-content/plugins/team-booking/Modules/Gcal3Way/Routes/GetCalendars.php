<?php

namespace VSHM\Modules\Gcal3Way\Routes;

use VSHM\Functions;
use VSHM\Modules\Gcal2Ways;
use VSHM\Providers\ServiceProviders;
use VSHM\REST_Controller;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Provider\GoogleApiToken;

defined('ABSPATH') || exit;

/**
 * Class GetCalendars
 *
 * @package VSHM\Modules\Gcal3Way\Routes
 */
class GetCalendars implements SingleRoute
{
    public static function getPath(): string
    {
        return Gcal2Ways::$route_path . 'get/calendars/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => static function (\WP_REST_Request $request) {
                $client = Gcal2Ways::_client();

                $provider = ServiceProviders::provideBy(['id' => get_current_user_id()], TRUE);

                $calendarOptions = [];
                if (isset($provider[ GoogleApiToken::ID ]) && !empty($provider[ GoogleApiToken::ID ])) {
                    $client->setAccessToken($provider[ GoogleApiToken::ID ]);
                    $service = new \Google\Service\Calendar($client);
                    try {
                        $calendars = $service->calendarList->listCalendarList();
                    } catch (\Exception|\Google\Service\Exception $e) {
                        $response = [
                            'message' => $e->getMessage()
                        ];
                        if ($e instanceof \Google\Service\Exception) {
                            $errors = $e->getErrors();
                            if (isset($errors[0]['reason']) && $errors[0]['reason'] === 'insufficientPermissions') {
                                $response = [
                                    'message' => __('Insufficient permissions, please disconnect, then request the authorization again and ensure to select ALL the checkboxes when asked for permissions!', 'team-booking')
                                ];
                            }
                        }

                        return REST_Controller::get_error_response(self::getPath(), $response, 403);
                    }


                    $colors = $service->colors->get()->getCalendar();

                    foreach ($calendars->getItems() as $item) {
                        $calendarOptions[] = [
                            'name'        => $item->getSummary(),
                            'value'       => $item->getId(),
                            'avatarColor' => $colors[ $item->getColorId() ]->getBackground(),
                            'description' => $item->getAccessRole()
                        ];
                    }
                }

                try {
                    $response = [
                        'calendars' => $calendarOptions,
                        'g_account' => Gcal2Ways::getTokenEmailAccount()
                    ];
                } catch (\Exception $e) {
                    $response = [
                        'message' => $e->getMessage()
                    ];

                    return REST_Controller::get_error_response(self::getPath(), $response);
                }

                return REST_Controller::get_ok_response(self::getPath(), $response);
            },
            'permission_callback' => static function () {
                return Functions::current_user_is_provider(TRUE);
            }
        ];
    }
}
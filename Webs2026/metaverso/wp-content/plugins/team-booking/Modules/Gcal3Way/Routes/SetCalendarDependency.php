<?php

namespace VSHM\Modules\Gcal3Way\Routes;

use VSHM\Bus\UpdateProviderProperty;
use VSHM\Functions;
use VSHM\Modules\Gcal2Ways;
use VSHM\Providers\ServiceProviders;
use VSHM\REST_Controller;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Provider\GoogleCalendars;

defined('ABSPATH') || exit;

/**
 * Class SetCalendarDependency
 *
 * @package VSHM\Modules\Gcal3Way\Routes
 */
class SetCalendarDependency implements SingleRoute
{
    public static function getPath(): string
    {
        return Gcal2Ways::$route_path . 'set/calendar/dependency/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => static function (\WP_REST_Request $request) {
                $calendar_id = $request->get_param('calendarId');
                $independent = filter_var($request->get_param('independent'), FILTER_VALIDATE_BOOLEAN);

                $provider = ServiceProviders::provideBy(['id' => get_current_user_id()]);
                if (isset($provider[0])) {
                    $calendars = $provider[0][ GoogleCalendars::ID ];
                    if (isset($calendars[ $calendar_id ])) {
                        $calendars[ $calendar_id ][ Gcal2Ways::CALENDAR_INDEPENDENT ] = $independent;
                    }
                    vshm()->bus->dispatch(new UpdateProviderProperty(get_current_user_id(), GoogleCalendars::ID, $calendars));
                }

                return REST_Controller::get_ok_response(self::getPath(), [
                    'providers' => ServiceProviders::provide()
                ]);
            },
            'args'                => [
                'calendarId'  => [
                    'required' => TRUE
                ],
                'independent' => [
                    'required' => TRUE
                ],
            ],
            'permission_callback' => static function () {
                return Functions::current_user_is_provider(TRUE);
            }
        ];
    }
}
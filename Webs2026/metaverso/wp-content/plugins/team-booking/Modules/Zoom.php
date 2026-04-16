<?php

namespace VSHM\Modules;

use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Functions;
use VSHM\Modules\Zoom\S2SoAuth;
use VSHM\Modules\Zoom\Settings\ZoomAccessToken;
use VSHM\Modules\Zoom\Settings\ZoomAccountId;
use VSHM\Modules\Zoom\Settings\ZoomJWTApiKey;
use VSHM\Modules\Zoom\Settings\ZoomJWTApiSecret;
use VSHM\Modules\Zoom\Settings\ZoomSettingBase;
use VSHM\Modules\Zoom\ZoomAPIWrapper;
use VSHM\Modules\Zoom\ZoomBusMiddleware;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Settings\Customer\CustomerSettingBase;
use VSHM\Settings\Customer\Name;
use VSHM\Settings\Reservation\ZoomMeetingId;
use VSHM\Settings\Service\ConfirmationEmailToAdmin;
use VSHM\Settings\Service\CreateZoomMeeting;
use VSHM\Settings\Service\Personal_ConfirmationEmailToProvider;
use VSHM\Modules\Zoom\Settings\ZoomApiKey;
use VSHM\Modules\Zoom\Settings\ZoomApiSecret;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class Zoom
 */
class Zoom
{
    /**
     * @var string
     */
    public static $route_path = '/zoom';

    public const OPTIONS_TAG = 'tbk_zoom';

    public static function bootstrap(): void
    {
        vshm()->settings->hydrate(get_option(self::OPTIONS_TAG, []), ZoomSettingBase::CONTEXT);

        vshm()->bus->addMiddleware(ZoomBusMiddleware::class);

        Tools::subscribe_classes_in_dir(
            __DIR__ . DIRECTORY_SEPARATOR . 'Zoom' . DIRECTORY_SEPARATOR . 'Settings' . DIRECTORY_SEPARATOR,
            '\\VSHM\\Modules\\Zoom\\Settings\\',
            'subscribe',
            [
                'ZoomSettingBase.php'
            ]
        );

        REST_Controller::register_routes([
            self::$route_path . '/reservation/getmeetinglink'    => [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => [self::class, 'getMeetingLinkCallback'],
                'args'     => [
                    'hash'          => [
                        'type' => 'string'
                    ],
                    'reservationId' => [
                        'type'     => 'string',
                        'required' => TRUE
                    ],
                ]
            ],
            self::$route_path . '/reservation/startmeetinglink/' => [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [self::class, 'startMeetingLinkCallback'],
                'args'                => [
                    'reservationId' => [
                        'type'     => 'string',
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => function () {
                    return Functions::current_user_is_provider();
                }
            ],
            self::$route_path . '/reservation/getmeetingdata/'   => [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [self::class, 'getMeetingDataCallback'],
                'args'                => [
                    'meetingId' => [
                        'type'     => 'string',
                        'required' => TRUE
                    ],
                ],
                'permission_callback' => function () {
                    return Functions::current_user_is_provider();
                }
            ]
        ]);
        add_filter('tbk_backend_settings_page_items', [self::class, 'add_settings_page_items']);

        if (self::isConfigured()) {
            add_filter('tbk_preparing_reservation_for_frontend', [self::class, 'inject_meeting_id']);
            add_filter('tbk_template_hooks_editor', [self::class, 'template_hooks_editor'], 10, 3);
            add_filter('tbk_notification_templates', [self::class, 'template_hooks'], 10, 3);
        }
    }

    public static function isS2SoAuthConfigured(): bool
    {
        return vshm()->settings->get(ZoomApiSecret::ID, ZoomSettingBase::CONTEXT)
            && vshm()->settings->get(ZoomApiKey::ID, ZoomSettingBase::CONTEXT)
            && vshm()->settings->get(ZoomAccountId::ID, ZoomSettingBase::CONTEXT);
    }

    //TODO: remove after September 2023
    public static function isJWTConfigured(): bool
    {
        return vshm()->settings->get(ZoomJWTApiSecret::ID, ZoomSettingBase::CONTEXT)
            && vshm()->settings->get(ZoomJWTApiKey::ID, ZoomSettingBase::CONTEXT);
    }

    public static function isConfigured(): bool
    {
        // TODO: change after September 2023
        return self::isJWTConfigured() || self::isS2SoAuthConfigured();
    }

    /**
     * @param array       $values
     * @param string      $reservationId
     * @param string|null $notificationType
     *
     * @return array
     */
    public static function template_hooks(array $values, string $reservationId, ?string $notificationType = ''): array
    {
        $meetingId = ReservationsData::provideBy(['reservation_id' => $reservationId, 'key' => ZoomMeetingId::ID], TRUE);
        if ($meetingId) {
            $meeting              = self::getMeeting($meetingId);
            $values['zoom::join'] = $meeting['join_url'];
            if ($notificationType === ConfirmationEmailToAdmin::ID || $notificationType === Personal_ConfirmationEmailToProvider::ID) {
                $values['zoom::start'] = $meeting['start_url'];
            }
        }

        return $values;
    }

    /**
     * @param array  $hooks
     * @param string $notificationType
     * @param string $serviceId
     *
     * @return array
     */
    public static function template_hooks_editor(array $hooks, string $notificationType, string $serviceId): array
    {
        $hooks[] = [
            'value'        => 'zoom::join',
            'label'        => __('Zoom Join Meeting link', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];
        if ($notificationType === ConfirmationEmailToAdmin::ID || $notificationType === Personal_ConfirmationEmailToProvider::ID) {
            $hooks[] = [
                'value'        => 'zoom::start',
                'label'        => __('Zoom Start Meeting link', 'team-booking'),
                'context'      => 'reservation',
                'contextLabel' => __('Reservation', 'team-booking')
            ];
        }


        return $hooks;
    }

    public static function getMeetingDataCallback(\WP_REST_Request $request): \WP_REST_Response
    {
        $meeting       = self::getMeeting($request->get_param('meetingId'));
        $mappedMeeting = [
            'id' => $request->get_param('meetingId')
        ];
        if (isset($meeting['join_url'])) {
            $mappedMeeting['join_url'] = $meeting['join_url'];
        }
        if (isset($meeting['start_url'])) {
            $mappedMeeting['start_url'] = $meeting['start_url'];
        }
        if (isset($meeting['password'])) {
            $mappedMeeting['password'] = $meeting['password'];
        }
        if (isset($meeting['status'])) {
            $mappedMeeting['status'] = $meeting['status']; //"waiting" or "started"
        }

        return REST_Controller::get_ok_response(REST_Controller::get_root_rest_url() . self::$route_path . '/reservation/getmeetingdata', $mappedMeeting);
    }

    /**
     * @param array $reservationArray
     *
     * @return array
     */
    public static function inject_meeting_id(array $reservationArray): array
    {
        $meetingIds = array_column(ReservationsData::provideBy(['key' => ZoomMeetingId::ID]), 'value', 'reservation_id');
        if (isset($meetingIds[ $reservationArray['id'] ])) {
            $reservationArray[ ZoomMeetingId::ID ] = $meetingIds[ $reservationArray['id'] ];
        }

        return $reservationArray;
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public static function getMeetingLinkCallback(\WP_REST_Request $request): \WP_REST_Response
    {
        $reservation = Reservations::provideBy(['id' => $request->get_param('reservationId')], TRUE);
        $meetingId   = ReservationsData::provideBy(['reservation_id' => $request->get_param('reservationId'), 'key' => ZoomMeetingId::ID], TRUE);
        $response    = [];
        if ($reservation && $meetingId) {
            $customer = Customers::provideBy(['id' => $reservation->customerId], TRUE);

            if ($customer['wp_user'] === get_current_user_id() || md5($reservation->id . $customer['access_token']) === $request->get_param('hash')) {
                $meeting = self::getMeeting($meetingId);
                if (!$meeting) {
                    return REST_Controller::get_error_response(REST_Controller::get_root_rest_url() . self::$route_path . '/reservation/getmeetinglink', [
                        'message' => 'Zoom API error. See the error.log for details.'
                    ]);
                }
                if (isset($meeting['join_url'])) {
                    $response['link'] = $meeting['join_url'];
                }
            }
        }

        return REST_Controller::get_ok_response(REST_Controller::get_root_rest_url() . self::$route_path . '/reservation/getmeetinglink', $response);
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public static function startMeetingLinkCallback(\WP_REST_Request $request): \WP_REST_Response
    {
        $reservation = Reservations::provideBy(['id' => $request->get_param('reservationId')], TRUE);
        $meetingId   = ReservationsData::provideBy(['reservation_id' => $request->get_param('reservationId'), 'key' => ZoomMeetingId::ID], TRUE);
        $response    = [];
        if ($reservation && $meetingId) {
            $meeting = self::getMeeting($meetingId);
            if (!$meeting) {
                return REST_Controller::get_error_response(REST_Controller::get_root_rest_url() . self::$route_path . '/reservation/startmeetinglink', [
                    'message' => 'Zoom API error. See the error.log for details.'
                ]);
            }
            if (isset($meeting['start_url'])) {
                $response['link'] = $meeting['start_url'];
            }
        }

        return REST_Controller::get_ok_response(REST_Controller::get_root_rest_url() . self::$route_path . '/reservation/startmeetinglink', $response);

    }

    public static function getMeeting($id)
    {
        $zoom = self::getZoom();

        $response = $zoom->doRequest('GET', '/meetings/{meetingId}', [], ['meetingId' => $id]);

        if ($response === FALSE) {
            error_log("Errors:" . implode("\n", $zoom->requestErrors()));
        }

        return $response;
    }

    public static function getMeetings()
    {
        $zoom = self::getZoom();

        $response = $zoom->doRequest('GET', '/users/me/meetings');
        if ($response === FALSE) {
            error_log("Errors:" . implode("\n", $zoom->requestErrors()));
        }

        return $response;
    }

    public static function deleteMeeting($id)
    {
        $zoom     = self::getZoom();
        $response = $zoom->doRequest('DELETE', '/meetings/{meetingId}', [], ['meetingId' => $id]);

        if ($response === FALSE) {
            error_log("Errors:" . implode("\n", $zoom->requestErrors()));
        }

        return $response;
    }

    public static function updateMeeting($meetingId, $updated = []): void
    {
        $zoom     = self::getZoom();
        $response = $zoom->doRequest('PATCH', '/meetings/{meetingId}', [], ['meetingId' => $meetingId], json_encode($updated, JSON_FORCE_OBJECT));
        if ($response === FALSE) {
            error_log("Errors:" . implode("\n", $zoom->requestErrors()));
        }
        if ($zoom->responseCode() !== 204) {
            error_log("Errors:" . implode("\n", $response['message']));
        }
    }

    public static function createMeeting($reservation): bool
    {
        $service    = Services::provideBy(['id' => $reservation->serviceId], TRUE);
        $createZoom = filter_var(ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => CreateZoomMeeting::ID], TRUE), FILTER_VALIDATE_BOOLEAN);

        if ($service && $createZoom && $service->class !== 'unscheduled') {

            $startDate = DateTimeTbk::createFromFormatSilently('U', $reservation->start);
            $startDate->setTimezone(new \DateTimeZone('UTC'));
            $endDate = DateTimeTbk::createFromFormatSilently('U', $reservation->end);
            $endDate->setTimezone(new \DateTimeZone('UTC'));

            $zoom = self::getZoom();

            // Gathering password requirements...

            $response = $zoom->doRequest('GET', '/users/me/settings', ['option' => 'meeting_security']);

            if ($zoom->responseCode() !== 200) {
                ob_start();
                echo 'Zoom API error: ';
                var_dump($response);
                error_log(ob_get_clean());

                return FALSE;
            }

            $pwd_req  = $response['meeting_security']['meeting_password_requirement'];
            $pwd_type = 'alnum';
            $pwd_len  = 10;
            if ($pwd_req['length']) {
                $pwd_len = (int)$pwd_req['length'];
            }
            if ($pwd_req['only_allow_numeric']) {
                $pwd_type = 'numeric';
            }
            if ($pwd_req['have_special_character']) {
                $pwd_len--;
            }
            if ($pwd_req['have_upper_and_lower_characters']) {
                $pwd_len -= 2;
            }
            $pwd = Tools::generate_token($pwd_type, $pwd_len);
            if ($pwd_req['have_special_character']) {
                $pwd .= '@';
            }
            if ($pwd_req['have_upper_and_lower_characters']) {
                $pwd .= 'zZ';
            }

            try {
                $customerName = vshm()->settings->getProperty(
                    Name::ID,
                    CustomerSettingBase::CONTEXT,
                    $reservation->customerId
                );
            } catch (\UnexpectedValueException $e) {
                $customerName = '';
            }

            $meeting = [
                'topic'      => $service->name,
                'type'       => 2,
                'start_time' => $startDate->format('Y-m-d\TH:i:s\Z'),
                'duration'   => round(($endDate->getTimestamp() - $startDate->getTimestamp()) / 60),
                'password'   => $pwd,
                'agenda'     => sprintf(
                /* translators: %s: Name of a customer */
                    __('Meeting with %s', 'team-booking'),
                    $customerName
                ),
                'settings'   => [

                ]
            ];

            $response = $zoom->doRequest('POST', '/users/me/meetings', [], [], json_encode($meeting, JSON_FORCE_OBJECT));
            if ($response === FALSE) {
                error_log("Errors:" . implode("\n", $zoom->requestErrors()));
            }

            if ($zoom->responseCode() !== 201) {
                ob_start();
                var_dump($response);
                error_log(ob_get_clean());
            } else if (isset($response['id'])) {
                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($reservation->id, ZoomMeetingId::ID, $response['id']));
            }
        }

        return TRUE;
    }

    /**
     * @return false|string
     */
    public static function getS2SoAuthTokenAndSave()
    {
        $accessToken = S2SoAuth::generateAccessToken(
            vshm()->settings->get(ZoomAccountId::ID, ZoomSettingBase::CONTEXT),
            vshm()->settings->get(ZoomApiKey::ID, ZoomSettingBase::CONTEXT),
            vshm()->settings->get(ZoomApiSecret::ID, ZoomSettingBase::CONTEXT),
        );

        if (is_wp_error($accessToken)) {

            Tools::log_dump($accessToken);

            return FALSE;
        }

        vshm()->settings->save(ZoomAccessToken::ID, $accessToken->access_token, ZoomSettingBase::CONTEXT);

        return $accessToken->access_token;

    }

    /**
     * @return ZoomAPIWrapper
     */
    public static function getZoom(): ZoomAPIWrapper
    {
        if (self::isS2SoAuthConfigured()) {
            $accessToken = vshm()->settings->get(ZoomAccessToken::ID, ZoomSettingBase::CONTEXT);

            if (!$accessToken) {
                $accessToken = self::getS2SoAuthTokenAndSave();

                if ($accessToken === FALSE) {
                    // TODO: error handling
                }
            }

            return new ZoomAPIWrapper(
                vshm()->settings->get(ZoomApiKey::ID, ZoomSettingBase::CONTEXT),
                vshm()->settings->get(ZoomApiSecret::ID, ZoomSettingBase::CONTEXT),
                $accessToken
            );

        }

        // TODO: remove after September 2023
        return new ZoomAPIWrapper(
            vshm()->settings->get(ZoomJWTApiKey::ID, ZoomSettingBase::CONTEXT),
            vshm()->settings->get(ZoomJWTApiSecret::ID, ZoomSettingBase::CONTEXT)
        );
    }

    public static function add_settings_page_items($page)
    {
        $menu_item      = \VSHM\UI\Admin\SidebarItem::option(__('Zoom', 'team-booking'), 'zoom');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();


        $settings_panel->setTitle(__('Zoom', 'team-booking'));
        $settings_panel->setIconUrl(vshm()->plugin['URL'] . '/Assets/zoom_logo.png');
        $settings_panel->setDescription(__('Refer to the plugin documentation to know hot to get your API keys.', 'team-booking'));

        $settings_panel->addItem(ZoomApiKey::getBackendElement());
        $settings_panel->addItem(ZoomApiSecret::getBackendElement());
        $settings_panel->addItem(ZoomAccountId::getBackendElement());

        // TODO: remove after September 2023
        $settings_panel->addItem(ZoomJWTApiKey::getBackendElement());
        $settings_panel->addItem(ZoomJWTApiSecret::getBackendElement());

        $menu_item->setContent($settings_panel);
        $page->addSidebarItem($menu_item);

        return $page;
    }
}
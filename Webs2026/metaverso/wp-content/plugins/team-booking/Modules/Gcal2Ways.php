<?php

namespace VSHM\Modules;

use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;
use VSHM\Bus\UpdateProviderProperty;
use VSHM\Functions;
use VSHM\Modules\Gcal3Way\Cache;
use VSHM\Modules\Gcal3Way\FreeBusy;
use VSHM\Modules\Gcal3Way\GoogleBusMiddleware;
use VSHM\Modules\Gcal3Way\Routes\GetAuthUrl;
use VSHM\Modules\Gcal3Way\Routes\GetCalendars;
use VSHM\Modules\Gcal3Way\Routes\ManuallyCreateEvent;
use VSHM\Modules\Gcal3Way\Routes\OAuth;
use VSHM\Modules\Gcal3Way\Routes\RevokeAuth;
use VSHM\Modules\Gcal3Way\Routes\SetCalendarDependency;
use VSHM\Modules\Gcal3Way\Routes\SetCalendarDestination;
use VSHM\Modules\Gcal3Way\Routes\SetCalendarPersonal;
use VSHM\Modules\Gcal3Way\Routes\SetCalendarServices;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiAutorizedOrigin;
use VSHM\Modules\Gcal3Way\Settings\GoogleSettingBase;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Plugin\ReservationsBlockingFactory;
use VSHM\Plugin\TimeSlotDurationFactory;
use VSHM\Plugin\TimeSlotFactory;
use VSHM\Plugin\TimeSlotReflowLogicFactory;
use VSHM\Providers\Customers;
use VSHM\Providers\Objects\Reservation;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\REST_Controller;
use VSHM\Modules\Gcal3Way\Settings\GoogleAllowSlotCommands;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiApplicationName;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiClientId;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiClientSecret;
use VSHM\Modules\Gcal3Way\Settings\GoogleFetchDelay;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiRedirectURI;
use VSHM\Settings\Provider\AllowedServices;
use VSHM\Settings\Provider\GoogleApiToken;
use VSHM\Settings\Provider\GoogleCalendars;
use VSHM\Settings\Provider\RestrictServices;
use VSHM\Settings\Reservation\AvailabilityId;
use VSHM\Settings\Reservation\GoogleCalendarEventId;
use VSHM\Settings\Reservation\GoogleMeetId;
use VSHM\Settings\Service\BlockAvailabilityAfterOneReservation;
use VSHM\Settings\Service\DiscardedAvailableSlots;
use VSHM\Settings\Service\Personal_BufferRule;
use VSHM\Settings\Service\Personal_BufferTimespan;
use VSHM\Settings\Service\Personal_DiscardOverlappingWithPersonal;
use VSHM\Settings\Service\Personal_DiscardOverlappingWithSame;
use VSHM\Settings\Service\Personal_OverlappingWithSameDropTickets;
use VSHM\Settings\Service\Personal_Participate;
use VSHM\Settings\Service\ShowBookedSlots;
use VSHM\Settings\Service\ShowSlotCustomers;
use VSHM\Settings\Service\TotalSlotTickets;
use VSHM\Tools;
use \Google as Google;
use \GuzzleHttp as GuzzleHttp;
use VSHM\UI\Admin\Page;

defined('ABSPATH') || exit;

/**
 * Class Gcal2Ways
 */
class Gcal2Ways
{
    public const OPTIONS_TAG          = 'tbk_gcal_2ways';
    public const CALENDAR_DESTINATION = 'destination';
    public const CALENDAR_PERSONAL    = 'personal';
    public const CALENDAR_ID          = 'calendar_id';
    public const CALENDAR_INDEPENDENT = 'independent';

    /**
     * @var string
     */
    public static $route_path = '/google/';

    public static function bootstrap(): void
    {
        vshm()->settings->hydrate(get_option(self::OPTIONS_TAG, []), GoogleSettingBase::CONTEXT);

        vshm()->bus->addMiddleware(GoogleBusMiddleware::class, 10);

        Tools::subscribe_classes_in_dir(
            __DIR__ . DIRECTORY_SEPARATOR . 'Gcal3Way' . DIRECTORY_SEPARATOR . 'Settings' . DIRECTORY_SEPARATOR,
            '\\VSHM\\Modules\\Gcal3Way\\Settings\\',
            'subscribe',
            [
                'GoogleSettingBase.php'
            ]
        );

        REST_Controller::register_routes([
            OAuth::getPath()                  => OAuth::get(),
            GetAuthUrl::getPath()             => GetAuthUrl::get(),
            RevokeAuth::getPath()             => RevokeAuth::get(),
            GetCalendars::getPath()           => GetCalendars::get(),
            ManuallyCreateEvent::getPath()    => ManuallyCreateEvent::get(),
            SetCalendarDependency::getPath()  => SetCalendarDependency::get(),
            SetCalendarDestination::getPath() => SetCalendarDestination::get(),
            SetCalendarPersonal::getPath()    => SetCalendarPersonal::get(),
            SetCalendarServices::getPath()    => SetCalendarServices::get(),
        ]);

        Cache::maybe_create_table();
        Cache::clean();

        add_filter('tbk_backend_settings_page_items', [self::class, 'add_settings_page_items']);
        add_action('tbk_availability', [self::class, 'getSlotsFromBusy'], 10, 7);
        add_filter('tbk_overlapping_reservation_from_different_availability_adds_up', [self::class, 'determineIndependencyWhenOverlapping'], 10, 4);
        add_filter('tbk_preparing_reservation_for_frontend', [self::class, 'inject_meeting_id']);
        add_filter('tbk_template_hooks_editor', [self::class, 'template_hooks_editor'], 10, 3);
        add_filter('tbk_notification_templates', [self::class, 'template_hooks'], 10, 3);
        add_filter('tbk_backend_availability_menu_items', [self::class, 'availability_menu']);
        add_filter('tbk_working_hours_blocking_events', [self::class, 'working_hours_blocking_events'], 10, 5);
        add_filter('tbk_provider_meta_value', [self::class, 'provider_meta_value_integrity_check'], 10, 2);
        add_filter('tbk_service_personal_settings_items', [self::class, 'service_personal_settings_items']);

        add_action('wp_ajax_teambooking_oauth_callback', [self::class, 'legacy_oauth_callback']); //legacy
    }

    public static function service_personal_settings_items($items): array
    {
        if (!self::is_configured()) {
            return $items;
        }
        $provider = ServiceProviders::provideBy(['id' => get_current_user_id()], TRUE);

        if (!$provider[ GoogleApiToken::ID ]) {
            return $items;
        }

        foreach ($items as $key => $item) {
            if ($item['id'] === 'behavior') {
                $items[ $key ]['items'][] = \VSHM\Settings\Service\Personal_GcalCreateEvent::getBackendElement()->get_structure();
                $items[ $key ]['items'][] = \VSHM\Settings\Service\Personal_EventTitleBooked::getBackendElement()->get_structure();
                $items[ $key ]['items'][] = \VSHM\Settings\Service\Personal_EventColorBooked::getBackendElement()->get_structure();
                $items[ $key ]['items'][] = \VSHM\Settings\Service\Personal_GcalAddGuests::getBackendElement()->get_structure();
                $items[ $key ]['items'][] = \VSHM\Settings\Service\Personal_GcalCreateMeet::getBackendElement()->get_structure();
                $items[ $key ]['items'][] = \VSHM\Settings\Service\Personal_GcalEventDescriptionContent::getBackendElement()->get_structure();
                $items[ $key ]['items'][] = \VSHM\Settings\Service\Personal_GcalEventDescriptionCustomContent::getBackendElement()->get_structure();
                $items[ $key ]['items'][] = \VSHM\Settings\Service\Personal_GcalReminder::getBackendElement()->get_structure();
            }
        }

        return $items;
    }

    public static function legacy_oauth_callback(): void
    {
        if (isset($_GET['code'], $_GET['state']) && Functions::current_user_is_provider(TRUE)) {

            $base_url = REST_Controller::get_root_rest_url() . '/google/oauth';
            $url      = add_query_arg([
                'code'  => $_GET['code'],
                'state' => $_GET['state'],
            ], $base_url);
            wp_redirect($url);
            exit;
        }
    }

    public static function provider_meta_value_integrity_check($value, $metaKey)
    {

        if ($metaKey === GoogleCalendars::ID) {
            if (!is_array($value)) {
                $value = [];
            }
            $services = array_column(Services::provide(), 'id', 'id');
            foreach ($value as $calendarId => $calendarItem) {

                if (!isset($calendarItem['services']) || !is_array($calendarItem['services'])) {
                    unset($value[ $calendarId ]);
                    continue;
                }
                foreach ($calendarItem['services'] as $key => $service) {
                    if (!isset($services[ $service ])) {
                        unset($value[ $calendarId ]['services'][ $key ]);
                    }
                }

                if (empty($value[ $calendarId ]['services'])) {
                    unset($value[ $calendarId ]);
                }

            }
        }

        return $value;
    }

    public static function working_hours_blocking_events(array $events, $min_timestamp, $max_timestamp = NULL, array $services = NULL, array $providers = NULL): array
    {
        return FreeBusy::getForWorkingHours($min_timestamp, $max_timestamp, $services, $providers) + $events;
    }

    /**
     * @param Page $page
     *
     * @return Page
     */
    public static function availability_menu(Page $page): Page
    {
        $menu_item      = \VSHM\UI\Admin\SidebarItem::option(__('Google', 'team-booking'), 'google');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $settings_panel->addItem(\VSHM\UI\Admin\Plugin\GoogleAvailabilityPanel::get('', 'google_panel'));
        $menu_item->setContent($settings_panel);

        $page->addSidebarItem($menu_item, 2);

        return $page;
    }

    /**
     * @param array       $values
     * @param string      $reservationId
     * @param string|null $notificationType
     *
     * @return array
     */
    public static function template_hooks(array $values, string $reservationId, ?string $notificationType): array
    {
        $meetingId = ReservationsData::provideBy(['reservation_id' => $reservationId, 'key' => GoogleMeetId::ID], TRUE);
        if ($meetingId) {
            $values['google::meet'] = $meetingId;
        }

        return $values;
    }

    /**
     * @param array $hooks
     * @param       $notificationType
     * @param       $serviceId
     *
     * @return array
     */
    public static function template_hooks_editor(array $hooks, $notificationType, $serviceId): array
    {
        $hooks[] = [
            'value'        => 'google::meet',
            'label'        => __('Google Meet link', 'team-booking'),
            'context'      => 'reservation',
            'contextLabel' => __('Reservation', 'team-booking')
        ];

        return $hooks;
    }

    /**
     * @param array $reservationArray
     *
     * @return array
     */
    public static function inject_meeting_id(array $reservationArray): array
    {
        $meetingIds = array_column(ReservationsData::provideBy(['key' => GoogleMeetId::ID]), 'value', 'reservation_id');
        if (isset($meetingIds[ $reservationArray['id'] ])) {
            $reservationArray[ GoogleMeetId::ID ] = $meetingIds[ $reservationArray['id'] ];
        }

        return $reservationArray;
    }

    /**
     * @param bool   $addsUp
     * @param string $overlappingAvailabilityId
     * @param string $reservationAvailabilityId
     * @param int    $provider_id
     *
     * @return bool
     */
    public static function determineIndependencyWhenOverlapping(bool $addsUp, string $overlappingAvailabilityId, string $reservationAvailabilityId, int $provider_id): bool
    {
        $provider = ServiceProviders::provideBy(['id' => $provider_id], TRUE);
        if ($provider) {
            $calendars = $provider[ GoogleCalendars::ID ];
            if (isset($calendars[ $overlappingAvailabilityId ], $calendars[ $reservationAvailabilityId ])) {
                // Overlapping reservation must count only if both calendars are not independent
                return !$calendars[ $overlappingAvailabilityId ][ self::CALENDAR_INDEPENDENT ] && !$calendars[ $reservationAvailabilityId ][ self::CALENDAR_INDEPENDENT ];
            }
        }

        return $addsUp;
    }

    public static function get_destination_from_availability_id(?string $availabilityId, ?array $provider): ?string
    {
        if (!$availabilityId || !is_array($provider)) {
            return NULL;
        }

        if (Tools::starts_with($availabilityId, $provider['id'] . '__')) {
            // This is from working hours...
            $availability = $provider[ WorkingHours::ID ] ?? [];

            $availabilityId = explode('__', $availabilityId)[1];

            foreach ($availability as $item) {
                if ($item['id'] === $availabilityId) {
                    $destination = $item['destination'];
                }
            }
        } else {
            $destination = $provider[ GoogleCalendars::ID ][ $availabilityId ][ self::CALENDAR_DESTINATION ] ?? NULL;
        }

        return $destination ?? NULL;
    }

    /**
     *
     * Determines if there are other POTENTIAL reservations for a given GCAL event.
     * Reservations must share availability ID, provider, service and start/end times in order to be
     * considered for the SAME gcal event.
     *
     * @param string $reservation_id
     *
     * @return array|false
     */
    public static function _are_there_other_reservations(string $reservation_id)
    {
        $res               = Reservations::provideBy(['id' => $reservation_id], TRUE);
        $availId           = ReservationsData::provideBy(['reservation_id' => $reservation_id, 'key' => AvailabilityId::ID], TRUE);
        $otherReservations = Reservations::provideBy([
            'id'          => [
                'value'    => $reservation_id,
                'operator' => '!='
            ],
            'serviceId'   => $res->serviceId,
            'provider_id' => $res->providerId,
            'start'       => [
                'value'    => $res->start,
                'operator' => '=',
            ],
            'end'         => [
                'value'    => $res->end,
                'operator' => '=',
            ],
            'data'        => [
                AvailabilityId::ID => $availId
            ]
        ]);
        $otherReservations = array_column($otherReservations, 'id');

        return count($otherReservations) ? $otherReservations : FALSE;
    }

    /**
     *
     * Determines if there are other  reservations for a given GCAL event ID.
     *
     * @param string      $reservation_id
     * @param string|null $gcal_event_id
     *
     * @return array|false
     */
    public static function _are_there_other_reservations_by_event_id(string $reservation_id, string $gcal_event_id = NULL)
    {
        $otherReservations = [];

        $gcal_event_id = $gcal_event_id ?? (string)ReservationsData::provideBy(['reservation_id' => $reservation_id, 'key' => GoogleCalendarEventId::ID], TRUE);

        if ($gcal_event_id) {
            $otherReservations = array_column(ReservationsData::provideBy(['key' => GoogleCalendarEventId::ID, 'value' => $gcal_event_id]), 'reservation_id');

            if (($key = array_search($reservation_id, $otherReservations, TRUE)) !== FALSE) {
                unset($otherReservations[ $key ]);
            }
        }

        return count($otherReservations) ? $otherReservations : FALSE;
    }

    public static function parseDynamicHooks(string $string, string $reservationId): string
    {
        $preparedValues = Notifications::prepare_placeholders($reservationId, '');

        $blacklist = [
            'service::description',
            'status_link',
            'cancellation_link',
            'pay_link',
            'ics_link',
            'decline_link',
            'approve_link',
            'manage_link',
        ];

        foreach ($preparedValues as $key => $preparedValue) {
            if (in_array($key, $blacklist, TRUE)) {
                unset($preparedValues[ $key ]);
            }
        }

        return Notifications::find_and_replace_hooks($string, $preparedValues);
    }

    /**
     * @param $calls
     * @param $partial_results
     * @param $client Google\Client Already authenticated client!
     */
    public static function batchCall($calls, &$partial_results, $client): void
    {
        $batches = array_chunk($calls, 50, TRUE);
        foreach ($batches as $requests) {
            $batch = new Google\Http\Batch($client, FALSE, NULL, '/batch/calendar/v3');
            foreach ($requests as $helper => $request) {
                $batch->add($request, $helper);
            }
            try {
                foreach ((array)$batch->execute() as $id => $value) {
                    $partial_results[ $id ] = $value;
                }
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    trigger_error("Google connection error code {$e->getCode()}: {$e->getMessage()}");
                }
                continue;
            }
        }
    }

    public static function add_settings_page_items($page)
    {
        $menu_item      = \VSHM\UI\Admin\SidebarItem::option(__('Google Calendar', 'team-booking'), 'gcal');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $settings_panel->setTitle(__('Google Calendar', 'team-booking'));
        $settings_panel->setDescription(__('In order to use Google Calendar with the plugin, you have to activate and configure a Google Project.', 'team-booking'));
        $settings_panel->setIconUrl(vshm()->plugin['URL'] . '/Assets/calendar_logo.png');

        $settings_panel->addItem(GoogleApiClientId::getBackendElement());
        $settings_panel->addItem(GoogleApiClientSecret::getBackendElement());
        $settings_panel->addItem(GoogleApiApplicationName::getBackendElement());
        $settings_panel->addItem(GoogleAllowSlotCommands::getBackendElement());
        $settings_panel->addItem(GoogleFetchDelay::getBackendElement());
        $settings_panel->addItem(GoogleApiRedirectURI::getBackendElement());
        $settings_panel->addItem(GoogleApiAutorizedOrigin::getBackendElement());

        $menu_item->setContent($settings_panel);
        $page->addSidebarItem($menu_item);

        return $page;
    }

    public static function is_configured(): bool
    {
        return vshm()->settings->get(GoogleApiClientId::ID, GoogleSettingBase::CONTEXT)
            && vshm()->settings->get(GoogleApiClientSecret::ID, GoogleSettingBase::CONTEXT)
            && vshm()->settings->get(GoogleApiApplicationName::ID, GoogleSettingBase::CONTEXT);
    }

    /**
     * @return Google\Client
     */
    public static function _client(): Google\Client
    {
        $client = new Google\Client();
        $client->setClientId(vshm()->settings->get(GoogleApiClientId::ID, GoogleSettingBase::CONTEXT));
        $client->setClientSecret(vshm()->settings->get(GoogleApiClientSecret::ID, GoogleSettingBase::CONTEXT));
        $client->setApplicationName(vshm()->settings->get(GoogleApiApplicationName::ID, GoogleSettingBase::CONTEXT));
        $client->setRedirectUri(REST_Controller::get_root_rest_url() . '/google/oauth');
        $client->addScope([
            Google\Service\Calendar::CALENDAR,
            'email'
        ]);
        $client->setAccessType('offline');

        $guzzle           = $client->getHttpClient();
        $config           = $guzzle->getConfig();
        $config['verify'] = vshm()->plugin['PATH'] . 'cacerts.pem';
        $client->setHttpClient(new GuzzleHttp\Client($config));

        return $client;
    }

    /**
     * Returns the connected gmail account of the current user.
     *
     * @throws \Exception
     */
    public static function getTokenEmailAccount($user_id = NULL)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $provider = ServiceProviders::provideBy(['id' => $user_id], TRUE);
        if ($provider && isset($provider[ GoogleApiToken::ID ])) {
            $client = self::_client();
            $client->setAccessToken($provider[ GoogleApiToken::ID ]);

            /**
             * Allowing some server time skew
             */
            $jwt          = new \Firebase\JWT\JWT;
            $jwt::$leeway = 10;

            try {
                $token_info = $client->verifyIdToken();

                if (!$token_info) {
                    throw new \Exception('There are issues with the oAuth ID token verification process.');
                }

                return $token_info['email'];
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'cURL error 60') !== FALSE) {
                    // Certificate problem
                    throw new \Exception('cURL certificate issues.');
                }

                /**
                 * If the id_token is expired, let's try to refresh it.
                 */
                try {
                    $client->refreshToken($client->getRefreshToken());

                    try {
                        $token_info = $client->verifyIdToken();

                        if (!$token_info) {
                            throw new \Exception('There are issues with the oAuth ID token verification process.');
                        }

                        vshm()->bus->dispatch(new UpdateProviderProperty($user_id, GoogleApiToken::ID, $client->getAccessToken()));

                        return $token_info['email'];
                    } catch (\Exception $e) {
                        throw new \Exception($e->getMessage());
                    }

                } catch (\Exception $ex) {
                    $original_error = Tools::looking_for_json($ex->getMessage());
                    if (is_array($original_error) && NULL !== json_decode($original_error[0])) {
                        $array_message = json_decode($original_error[0], TRUE);
                        if (isset($array_message['error_description']) && $array_message['error_description'] === 'Token has been revoked.') {
                            throw new \Exception(__('Your authorization was revoked upstream, please disconnect and reconnect!', 'team-booking'));
                        }
                        if (isset($array_message['error']) && $array_message['error'] === 'invalid_grant') {
                            throw new \Exception(__('Something changed in your Google account (a password reset or similar), please disconnect and reconnect!', 'team-booking'));
                        }
                    }
                    throw new \Exception($ex->getMessage());
                }
            }
        }

        return '';
    }

    /**
     * @param Google\Service\Calendar\Event $item
     *
     * @return array
     */
    public static function extractPropertiesFromItem($item): array
    {
        $return = [];

        $pieces = explode('__', $item->getSummary());
        if (isset($pieces[1])) {
            $commands = array_map('\VSHM\Tools::mb_strtolower', array_map('trim', explode(',', $pieces[1])));
            foreach ($commands as $command) {
                $tmp = explode('=', $command);

                $return[ trim($tmp[0]) ] = isset($tmp[1]) ? trim($tmp[1]) : NULL;
            }
        }

        if ($item->getLocation()) {
            $return['location'] = $item->getLocation();
        }

        return $return;
    }

    /**
     * @param             $client
     * @param array       $helper_array
     * @param bool|string $page_token
     * @param bool        $sync_token
     *
     * @return \Exception|false|Google\Service\Calendar\Events|Google\Service\Exception
     */
    private static function getSingleRequest($client, array $helper_array, $page_token = FALSE, $sync_token = FALSE)
    {
        $provider = ServiceProviders::provideBy(['id' => $helper_array['provider_id']], TRUE);

        if (!isset($provider[ GoogleApiToken::ID ])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                trigger_error("Provider {$helper_array['provider_id']} access token is NULL, skipping Google fetching");
            }

            return FALSE;
        }
        $client->setAccessToken($provider[ GoogleApiToken::ID ]);
        // Set the query params
        $event_list_params = [
            'singleEvents' => FALSE,
            'maxResults'   => 2500,
        ];
        if ($page_token) {
            $event_list_params['pageToken'] = $page_token;
        }
        if ($sync_token) {
            $event_list_params['syncToken'] = $sync_token;
        }
        try {
            $service = new Google\Service\Calendar($client);

            return $service->events->listEvents($helper_array['calendar_id'], $event_list_params);
        } catch (Google\Service\Exception $e) {
            if ($e->getCode() === 404) {
                // Calendar ID not found
                $googleCalendars = $provider[ GoogleCalendars::ID ];
                if (is_array($googleCalendars)) {
                    unset($googleCalendars[ $helper_array['calendar_id'] ]);
                    vshm()->bus->dispatch(new UpdateProviderProperty($helper_array['provider_id'], GoogleCalendars::ID, $googleCalendars));
                }
            }

            return $e;
        } catch (\Exception $e) {
            return $e;
        }
    }


    /**
     * @param int   $provider_id
     * @param       $batch_requests
     * @param       $client
     * @param array $optParams
     */
    private static function prepareProviderRequests(int $provider_id, &$batch_requests, $client, array $optParams = []): void
    {
        $helper_array = [
            'calendar_id' => '',
            'provider_id' => '',
        ];
        $provider     = ServiceProviders::provideBy(['id' => $provider_id], TRUE);

        if (!($provider)) {
            return;
        }

        $client->setAccessToken($provider[ GoogleApiToken::ID ]);
        $helper_array['provider_id'] = $provider_id;

        // Set the query params
        $event_list_params = $optParams + [
                'singleEvents' => FALSE,
                'maxResults'   => 2500
            ];

        $googleCalendars = $provider[ GoogleCalendars::ID ];

        if (!is_array($googleCalendars)) {
            return;
        }

        foreach ($googleCalendars as $calendar_data) {
            if (isset($calendar_data['sync_token'])) {
                $event_list_params['syncToken'] = $calendar_data['sync_token'];
                $helper_array['sync_token']     = $calendar_data['sync_token'];
            } else {
                continue;
            }
            $helper_array['calendar_id'] = $calendar_data[ self::CALENDAR_ID ];
            try {
                $service = new Google\Service\Calendar($client);
                $request = $service->events->listEvents($calendar_data[ self::CALENDAR_ID ], $event_list_params);
            } catch (Google\Service\Exception $e) {
                if ($e->getCode() === 404) {
                    // Calendar ID not found
                    unset($googleCalendars[ $calendar_data[ self::CALENDAR_ID ] ]);
                    vshm()->bus->dispatch(new UpdateProviderProperty($provider_id, GoogleCalendars::ID, $googleCalendars));
                }
                continue;
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    trigger_error("Google connection error code {$e->getCode()}: {$e->getMessage()}");
                }
                continue;
            }
            // Adding the request to the batch requests
            $batch_requests[ base64_encode(gzdeflate(json_encode($helper_array))) ] = $request;
        }
    }

    /**
     * @param               $other_slots
     * @param               $min_timestamp
     * @param               $max_timestamp
     * @param Reservation[] $reservations
     * @param array|null    $services
     * @param array|null    $providers
     *
     * @return array
     */
    public static function getSlotsFromBusy($other_slots, $min_timestamp, $max_timestamp = NULL, array $reservations = [], array $services = NULL, array $providers = NULL): array
    {
        $slots     = [];
        $customers = array_column(Customers::provide(), NULL, 'id');

        $intervals = FreeBusy::get($min_timestamp, $max_timestamp);

        $isFrontendRequest = apply_filters('tbk_availability_request_is_frontend', FALSE);

        $providersServiceData = \VSHM\Providers\ServiceProviderCustomData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    Personal_Participate::ID,
                    Personal_BufferTimespan::ID,
                    Personal_BufferRule::ID,
                    Personal_DiscardOverlappingWithPersonal::ID,
                    Personal_DiscardOverlappingWithSame::ID,
                    Personal_OverlappingWithSameDropTickets::ID,
                ]
            ]
        ]);
        $providersServiceData = Functions::organize_service_custom_data($providersServiceData);

        $servicesData = \VSHM\Providers\ServicesData::provideBy([
            'key' => [
                'operator' => 'IN',
                'value'    => [
                    DiscardedAvailableSlots::ID,
                    TotalSlotTickets::ID,
                    BlockAvailabilityAfterOneReservation::ID,
                    ShowSlotCustomers::ID,
                    ShowBookedSlots::ID,
                ]
            ]
        ]);
        $servicesData = Functions::organize_service_data($servicesData);

        foreach ($intervals as $provider_id => $results) {

            $freebusy  = $results['freebusy'];
            $overrides = $results['overrides'] ?? [];
            $personals = $results['personals'] ?? [];

            if (!empty($providers) && !in_array($provider_id, array_map('intval', $providers), TRUE)) {
                continue;
            }

            $provider = ServiceProviders::provideBy(['id' => $provider_id], TRUE);
            if (!$provider) {
                continue;
            }

            $googleCalendars = $provider[ GoogleCalendars::ID ];

            $reservationsBlockingFactory = new ReservationsBlockingFactory($reservations, $provider_id);

            $personalSets = [];
            foreach ($personals as $personal_cal_id => $p_ints) {
                $collection = [];
                foreach ($p_ints as $p_int) {

                    $collection[] = Period::make(
                        DateTimeTbk::createFromFormatSilently(DATE_RFC3339, $p_int['start']),
                        DateTimeTbk::createFromFormatSilently(DATE_RFC3339, $p_int['end']),
                        Precision::SECOND,
                        Boundaries::EXCLUDE_ALL
                    );
                }
                $personalSets[ $personal_cal_id ] = PeriodCollection::make(...$collection);
            }

            $freebusySets = [];
            foreach ($freebusy as $cal_id => $ints) {
                $collection = [];
                foreach ($ints as $int) {

                    $start_ = DateTimeTbk::createFromFormatSilently(DATE_RFC3339, $int['start']);
                    $end_   = DateTimeTbk::createFromFormatSilently(DATE_RFC3339, $int['end']);

                    if ($end_->getTimestamp() <= $min_timestamp
                        || ($max_timestamp && $start_->getTimestamp() >= $max_timestamp)) {
                        continue;
                    }

                    $collection[] = Period::make(
                        $start_,
                        $end_,
                        Precision::SECOND,
                        Boundaries::EXCLUDE_ALL
                    );
                }
                $freebusySets[ $cal_id ] = PeriodCollection::make(...$collection);
            }

            foreach ($googleCalendars as $cal_id => $cal) {

                if (!isset($freebusySets[ $cal_id ])) {
                    continue;
                }

                foreach (($cal['services'] ?? []) as $serviceId) {

                    $providerServiceData = $providersServiceData[ $provider_id ][ $serviceId ] ?? [];
                    $serviceData         = $servicesData[ $serviceId ] ?? [];

                    $buffer              = (int)($providerServiceData[ Personal_BufferTimespan::ID ] ?? Personal_BufferTimespan::getDefault());
                    $bufferMode          = $providerServiceData[ Personal_BufferRule::ID ] ?? Personal_BufferRule::getDefault();
                    $overlappingPersonal = $providerServiceData[ Personal_DiscardOverlappingWithPersonal::ID ] ?? Personal_DiscardOverlappingWithPersonal::getDefault();

                    if (!filter_var(
                        ($providerServiceData[ Personal_Participate::ID ] ?? Personal_Participate::getDefault()),
                        FILTER_VALIDATE_BOOLEAN)) {
                        continue;
                    }

                    if ($provider[ RestrictServices::ID ] && !in_array($serviceId, $provider[ AllowedServices::ID ], TRUE)) {
                        continue;
                    }

                    if (!empty($services) && !in_array($serviceId, $services, TRUE)) {
                        continue;
                    }

                    $service = Services::provideBy(['id' => $serviceId, 'status' => 1], TRUE);

                    if (!$service) {
                        continue;
                    }

                    $durationFactory = new TimeSlotDurationFactory($serviceId, $provider_id);

                    $discardedSlotsTreatAs = $serviceData[ DiscardedAvailableSlots::ID ] ?? DiscardedAvailableSlots::getDefault();
                    $maxServiceSlotTickets = $serviceData[ TotalSlotTickets::ID ] ?? TotalSlotTickets::getDefault();
                    $blockAfterOne         = $serviceData[ BlockAvailabilityAfterOneReservation::ID ] ?? BlockAvailabilityAfterOneReservation::getDefault();
                    $showCustomers         = $serviceData[ ShowSlotCustomers::ID ] ?? ShowSlotCustomers::getDefault();
                    $showBookedSlots       = $serviceData[ ShowBookedSlots::ID ] ?? ShowBookedSlots::getDefault();

                    $p_dropTickets = filter_var($providerServiceData[ Personal_OverlappingWithSameDropTickets::ID ]
                            ?? Personal_OverlappingWithSameDropTickets::getDefault(), FILTER_VALIDATE_BOOLEAN)
                        && filter_var($providerServiceData[ Personal_DiscardOverlappingWithSame::ID ]
                            ?? Personal_DiscardOverlappingWithSame::getDefault());

                    $reflowLogicFactory = new TimeSlotReflowLogicFactory($serviceId, $provider_id);

                    $intervals = $freebusySets[ $cal_id ];

                    $observePersonalEvents = isset($cal['personal'], $personalSets[ $cal['personal'] ])
                        && filter_var($overlappingPersonal, FILTER_VALIDATE_BOOLEAN);

                    $reservationsOtherServicesPeriod = $reservationsBlockingFactory->getAsPeriodSet(static function ($reservation) use ($serviceId, $cal_id) {
                        return $reservation->serviceId !== $serviceId
                            && $reservation->data[ AvailabilityId::ID ] === $cal_id;
                    });

                    $reservationsSameServicePeriod = $reservationsBlockingFactory->getAsPeriodSet(static function ($reservation) use ($serviceId, $cal_id, $p_dropTickets, $cal) {
                        return $reservation->serviceId === $serviceId
                            && ($reservation->data[ AvailabilityId::ID ] === $cal_id
                                || ($p_dropTickets && !$cal[ self::CALENDAR_INDEPENDENT ]));
                    });

                    /**
                     * Setting up intervals
                     */
                    if ($reflowLogicFactory->mustReflow()) {
                        if ($observePersonalEvents) {
                            $intervals = Tools::periodSubtract($intervals, $personalSets[ $cal['personal'] ]);
                        }
                        // TODO: should this be conditional??
                        $intervals = Tools::periodSubtract($intervals, $reservationsOtherServicesPeriod);
                    }
                    $periodsBeforeReservationsSameService = $intervals;
                    $intervals                            = Tools::periodSubtract($intervals, $reservationsSameServicePeriod);

                    $timeslotFactory = new TimeSlotFactory($serviceId, $provider_id);
                    $timeslotFactory->setAvailabilityId($cal_id);
                    if ($bufferMode === Personal_BufferRule::ALWAYS) {
                        $timeslotFactory->setBuffer($buffer);
                    }

                    if (isset($overrides[ $cal_id ])) {
                        $timeslotFactory->setGcalOverrides($overrides[ $cal_id ]);
                    }

                    foreach ($intervals as $int) {

                        $start = $int->getStart();
                        $end   = $int->getEnd();

                        $wholeInterval = $end->getTimestamp() - $start->getTimestamp();

                        $duration = $durationFactory->get($wholeInterval);

                        $spoolStart = $start->getTimestamp();
                        $spoolEnd   = $spoolStart + $duration;

                        $safe = 100000;

                        while ($wholeInterval >= $duration && $safe > 0) {

                            $timeslotFactory->setBoundaries($spoolStart, $spoolEnd);

                            $reservationCount = 0;

                            if ($timeslotFactory->areOpenCloseConditionsMet()) {

                                $reservationsIds = [];
                                $slotCustomers   = [];
                                $dropSlot        = FALSE;

                                $maxTicketsForThisSlot = (int)$maxServiceSlotTickets;

                                $timeslotFactory->update_gcal_overrides();

                                if (!$reflowLogicFactory->mustReflow()) {

                                    //Personal
                                    if ($observePersonalEvents) {
                                        foreach ($personalSets[ $cal['personal'] ] as $personalSet) {
                                            if ($timeslotFactory->getPeriod()->overlapsWith($personalSet)) {
                                                if ($discardedSlotsTreatAs === DiscardedAvailableSlots::BOOKED) {
                                                    $reservationCount = $maxTicketsForThisSlot;
                                                } else {
                                                    $dropSlot = TRUE;
                                                }
                                                break;
                                            }
                                        }
                                    }

                                    // Reservations
                                    if (!$dropSlot) {
                                        foreach ($reservationsBlockingFactory->get() as $reservation) {
                                            $independenceCondition = !$cal[ self::CALENDAR_INDEPENDENT ] || $reservation->data[ AvailabilityId::ID ] === $cal_id;

                                            $isTheSameSlot = ($reservation->data[ AvailabilityId::ID ] === $cal_id || $p_dropTickets)
                                                && $reservation->serviceId === $serviceId;

                                            if ($independenceCondition
                                                && !$isTheSameSlot
                                                && $timeslotFactory->isOverlappingConditionSatisfied($reservation)
                                                && $timeslotFactory->overlapsWithReservation($reservation)
                                            ) {
                                                if ($discardedSlotsTreatAs === DiscardedAvailableSlots::BOOKED) {
                                                    $reservationCount = $maxTicketsForThisSlot;
                                                } else {
                                                    $dropSlot = TRUE;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }

                                if (!$dropSlot) {
                                    $slots[] = $timeslotFactory->getSlot($reservationCount, $reservationsIds, $slotCustomers);
                                }

                            }

                            if ($buffer > 0) {
                                if ($bufferMode === Personal_BufferRule::ALWAYS || ($reservationCount > 0)) {
                                    $wholeInterval -= $buffer;
                                    $spoolStart    += $buffer;
                                }
                            }

                            $wholeInterval -= $duration;
                            $spoolStart    += $duration;
                            $spoolEnd      = $spoolStart + $duration;
                            --$safe;
                        }


                    }

                    // Reservations are turned into booked slots
                    foreach ($reservationsSameServicePeriod as $reservationPeriod) {

                        /**
                         * If reservation periods don't overlap with the original availability period, skip them
                         */
                        if ($periodsBeforeReservationsSameService->overlapSingle(PeriodCollection::make($reservationPeriod))->isEmpty()) {
                            continue;
                        }

                        $wholeInterval = $reservationPeriod->getEnd()->getTimestamp() - $reservationPeriod->getStart()->getTimestamp();

                        // In this loop, we need to subtract any eventual buffer, as buffer can't be converted into available time.
                        if ($buffer > 0) {
                            $wholeInterval -= $buffer;
                        }

                        $duration      = $durationFactory->get($wholeInterval);

                        $spoolStart = $reservationPeriod->getStart()->getTimestamp();
                        $spoolEnd   = $spoolStart + $duration;
                        $safe       = 100000;

                        while ($wholeInterval >= $duration && $safe > 0) {
                            $timeslotFactory->setBoundaries($spoolStart, $spoolEnd);
                            if ($timeslotFactory->areOpenCloseConditionsMet()) {
                                $reservationCount = 0;
                                $reservationsIds  = [];
                                $slotCustomers    = [];
                                $dropSlot         = FALSE;

                                $maxTicketsForThisSlot = (int)$maxServiceSlotTickets;

                                $timeslotFactory->update_gcal_overrides();

                                foreach ($reservationsBlockingFactory->get() as $reservation) {

                                    $isSameSlot = ($reservation->data[ AvailabilityId::ID ] === $cal_id
                                            || ($p_dropTickets && !$cal[ self::CALENDAR_INDEPENDENT ]))
                                        && $reservation->serviceId === $serviceId;

                                    if ($isSameSlot
                                        && $timeslotFactory->overlapsWithReservation($reservation)
                                    ) {

                                        if (!$isFrontendRequest) {
                                            $reservationsIds[] = $reservation->id;
                                            $slotCustomers[]   = $customers[ $reservation->customerId ];

                                        } else {
                                            switch ($showCustomers) {
                                                case ShowSlotCustomers::NAME:
                                                    $slotCustomers[] = [
                                                        'name' => $customers[ $reservation->customerId ]['name']
                                                    ];
                                                    break;
                                                case ShowSlotCustomers::EMAIL:
                                                    $slotCustomers[] = [
                                                        'email' => $customers[ $reservation->customerId ]['email']
                                                    ];
                                                    break;
                                                case ShowSlotCustomers::NAME_EMAIL:
                                                    $slotCustomers[] = [
                                                        'email' => $customers[ $reservation->customerId ]['email'],
                                                        'name'  => $customers[ $reservation->customerId ]['name']
                                                    ];
                                                    break;
                                            }
                                        }

                                        if ($blockAfterOne) {
                                            $reservationCount = $maxTicketsForThisSlot;
                                            break;
                                        }

                                        $reservationCount += ($reservation->data[ \VSHM\Settings\Reservation\Tickets::ID ] ?: 1);
                                    }
                                }

                                if ($reservationCount === $maxTicketsForThisSlot && !$showBookedSlots && $isFrontendRequest) {
                                    $dropSlot = TRUE;
                                }

                                if (!$dropSlot) {
                                    $slots[] = $timeslotFactory->getSlot($reservationCount, $reservationsIds, $slotCustomers);
                                }


                            }

                            $wholeInterval -= $duration;
                            $spoolStart    += $duration;
                            $spoolEnd      = $spoolStart + $duration;
                            --$safe;

                        }

                    }
                }

            }

        }

        return array_merge($slots, $other_slots);
    }

}
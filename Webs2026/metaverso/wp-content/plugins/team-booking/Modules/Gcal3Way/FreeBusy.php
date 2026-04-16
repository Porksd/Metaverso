<?php

namespace VSHM\Modules\Gcal3Way;

use VSHM\Functions;
use VSHM\Modules\Gcal2Ways;
use VSHM\Modules\Gcal3Way\Settings\GoogleSettingBase;
use VSHM\Modules\WorkingHours;
use VSHM\Plugin\DateTimeTbk;
use VSHM\Providers\ServiceProviders;
use VSHM\Modules\Gcal3Way\Settings\GoogleAllowSlotCommands;
use VSHM\Modules\Gcal3Way\Settings\GoogleFetchDelay;
use VSHM\Settings\Provider\GoogleApiToken;
use VSHM\Settings\Provider\GoogleCalendars;
use VSHM\Tools;

defined('ABSPATH') || exit;

class FreeBusy
{

    /**
     * @param int        $timestamp_min
     * @param int|null   $timestamp_max
     * @param array|null $services
     * @param array|null $providers
     *
     * @return array
     */
    public static function getForWorkingHours(int $timestamp_min, int $timestamp_max = NULL, array $services = NULL, array $providers = NULL): array
    {
        // Extending the timeout limit just in case we are fetching a lot of data
        @set_time_limit(120);

        $min = new DateTimeTbk('@' . $timestamp_min);
        $max = NULL !== $timestamp_max ? new DateTimeTbk('@' . $timestamp_max) : $min->add(new \DateInterval('P1M'));

        $batch_requests = [];

        $results = [];

        $serviceProviders = ServiceProviders::provide();

        $calendarsToQuery = [];
        foreach ($serviceProviders as $provider) {

            $provider_id = $provider['id'];

            if (!empty($providers) && !in_array($provider_id, array_map('intval', $providers), TRUE)) {
                continue;
            }

            if (!$provider[ GoogleApiToken::ID ]) {
                continue;
            }

            $availability = $provider[ WorkingHours::ID ] ?? [];

            // To save bandwidth, we use the batch method
            $client = Gcal2Ways::_client();
            $client->setUseBatch(TRUE);
            $client->setAccessToken($provider[ GoogleApiToken::ID ]);

            $freebusy = new \Google\Service\Calendar\FreeBusyRequest();

            $helper_array = [
                'provider_id' => $provider['id'],
                'personals'   => [],
                'type'        => 'freebusy'
            ];

            foreach ($availability as $plan) {

                if (!isset($plan['personal'])) {
                    continue;
                }

                if (is_array($services) && !array_intersect($plan['services'] ?? [], $services)) {
                    continue;
                }

                if (!$plan['personal']) {
                    continue;
                }

                $fbitem = new \Google\Service\Calendar\FreeBusyRequestItem();
                $fbitem->setId($plan['personal']);
                $calendarsToQuery[ $plan['personal'] ] = $fbitem;

            }

            if (empty($calendarsToQuery)) {
                continue;
            }

            $freebusy->setItems(array_values($calendarsToQuery));

            try {
                $service = new \Google\Service\Calendar($client);

                self::splitFreeBusy($min, $max, $freebusy, $service, $helper_array, $batch_requests);

            } catch (\Google\Service\Exception|\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    trigger_error("Google connection error code {$e->getCode()}: {$e->getMessage()}");
                }
                continue;
            }

            while (!empty($batch_requests)) {
                $partial_results = [];
                Gcal2Ways::batchCall($batch_requests, $partial_results, $client);
                $batch_requests = [];
                foreach ($partial_results as $id => $result) {
                    /** @var $result \Google\Service\Calendar\FreeBusyResponse */
                    $helper = json_decode(gzinflate(base64_decode(substr($id, 9))), TRUE); // Get rid of "response-"

                    if ($result instanceof \Google\Service\Calendar\FreeBusyResponse) {
                        $results[ $helper['provider_id'] ] = $results[ $helper['provider_id'] ] ?? [];
                        foreach ($result->getCalendars() as $cal_id => $calendar) {

                            $results[ $helper['provider_id'] ][ $cal_id ] = $results[ $helper['provider_id'] ][ $cal_id ] ?? [];
                            foreach ($calendar->getBusy() as $item) {
                                $results[ $helper['provider_id'] ][ $cal_id ][] = [
                                    'start' => $item->getStart(),
                                    'end'   => $item->getEnd()
                                ];
                            }

                        }
                    } else if ($result instanceof \Google\Service\Exception) {
                        Tools::log_dump('Error with provider ' . $helper['provider_id']);
                        Tools::log_dump($helper);
                        Tools::log_dump($result->getErrors());
                    } else {
                        Tools::log_dump('Error with provider ' . $helper['provider_id']);
                        Tools::log_dump($helper);
                        Tools::log_dump($result);
                    }

                }
            }

        }

        $processed_results = [];
        foreach ($results as $provider_id => $result) {
            foreach ($result as $calendarId => $items) {
                foreach ($serviceProviders as $serviceProvider) {
                    $availability = $serviceProvider[ WorkingHours::ID ] ?? [];
                    foreach ($availability as $plan) {
                        if ($calendarId === $plan['personal']) {
                            foreach ($plan['services'] as $serviceId) {
                                if (isset($processed_results[ $provider_id ][ $serviceId ])) {
                                    $processed_results[ $provider_id ][ $serviceId ] = array_merge($processed_results[ $provider_id ][ $serviceId ], $items);
                                } else {
                                    $processed_results[ $provider_id ][ $serviceId ] = $items;
                                }
                            }
                        }

                    }
                }
            }
        }

        return $processed_results;
    }

    /**
     * @param int      $timestamp_min
     * @param int|null $timestamp_max
     *
     * @return array
     */
    public static function get(int $timestamp_min, int $timestamp_max = NULL): array
    {
        // Extending the timeout limit just in case we are fetching a lot of data
        @set_time_limit(120);

        $min = new DateTimeTbk('@' . $timestamp_min);

        $max = NULL !== $timestamp_max ? new DateTimeTbk('@' . $timestamp_max) : $min->add(new \DateInterval('P1M'));

        if (vshm()->settings->get(GoogleFetchDelay::ID, GoogleSettingBase::CONTEXT)) {
            $cached = Cache::provide($min->getTimestamp(), $max->getTimestamp());
            if ($cached) {
                return $cached;
            }

        }

        $batch_requests = [];

        /**
         * The results array structure will be:
         *
         * [provider_id]
         *      [calendar_id]
         *           [items]      = array of events
         *           [sync_token] = sync token
         */
        $results   = [];
        $providers = ServiceProviders::provide();
        foreach ($providers as $provider) {

            $helper_array = [
                'provider_id' => $provider['id'],
                'personals'   => [],
                'type'        => 'freebusy'
            ];

            if (!$provider[ GoogleApiToken::ID ]) {
                continue;
            }

            // To save bandwidth, we use the batch method
            $client = Gcal2Ways::_client();
            $client->setUseBatch(TRUE);
            $client->setAccessToken($provider[ GoogleApiToken::ID ]);

            $googleCalendars  = $provider[ GoogleCalendars::ID ];
            $calendarsToQuery = [];

            if (!is_array($googleCalendars)) {
                continue;
            }

            try {
                $service = new \Google\Service\Calendar($client);

                $freebusy = new \Google\Service\Calendar\FreeBusyRequest();

                foreach ($googleCalendars as $calendar_data) {

                    $fbitem = new \Google\Service\Calendar\FreeBusyRequestItem();
                    $fbitem->setId($calendar_data[ Gcal2Ways::CALENDAR_ID ]);
                    $calendarsToQuery[ $calendar_data[ Gcal2Ways::CALENDAR_ID ] ] = $fbitem;

                    if (isset($calendar_data[ Gcal2Ways::CALENDAR_PERSONAL ])) {
                        $fbitem = new \Google\Service\Calendar\FreeBusyRequestItem();
                        $fbitem->setId($calendar_data[ Gcal2Ways::CALENDAR_PERSONAL ]);
                        $calendarsToQuery[ $calendar_data[ Gcal2Ways::CALENDAR_PERSONAL ] ] = $fbitem;
                        /**
                         * Tracing personal calendars for later use
                         */
                        $helper_array['personals'][] = $calendar_data[ Gcal2Ways::CALENDAR_PERSONAL ];
                    }
                }

                $freebusy->setItems(array_values($calendarsToQuery));

                self::splitFreeBusy($min, $max, $freebusy, $service, $helper_array, $batch_requests);

                $slotCommandsAllowed = Functions::user_can_admin($provider['id'])
                    || vshm()->settings->get(GoogleAllowSlotCommands::ID, GoogleSettingBase::CONTEXT);
                if ($slotCommandsAllowed) {
                    foreach ($googleCalendars as $calendar_data) {
                        $query                       = $service->events->listEvents($calendar_data[ Gcal2Ways::CALENDAR_ID ], [
                            'q'            => urlencode('__'),
                            'timeMin'      => $min->format(\DateTime::RFC3339),
                            'timeMax'      => $max->format(\DateTime::RFC3339),
                            'singleEvents' => TRUE,
                            'fields'       => urlencode('items(summary,start,end,id,location)')
                        ]);
                        $helper_array['type']        = 'query';
                        $helper_array['calendar_id'] = $calendar_data[ Gcal2Ways::CALENDAR_ID ];

                        $batch_requests[ base64_encode(gzdeflate(json_encode($helper_array))) ] = $query;
                    }
                }

            } catch (\Google\Service\Exception|\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    trigger_error("Google connection error code {$e->getCode()}: {$e->getMessage()}");
                }
                continue;
            }

            while (!empty($batch_requests)) {
                $partial_results = [];
                Gcal2Ways::batchCall($batch_requests, $partial_results, $client);
                $batch_requests = [];
                foreach ($partial_results as $id => $result) {

                    /** @var $result \Google\Service\Calendar\FreeBusyResponse */
                    $helper = json_decode(gzinflate(base64_decode(substr($id, 9))), TRUE); // Get rid of "response-"

                    if ($result instanceof \Google\Service\Calendar\FreeBusyResponse) {
                        $results[ $helper['provider_id'] ]['freebusy']  = $results[ $helper['provider_id'] ]['freebusy'] ?? [];
                        $results[ $helper['provider_id'] ]['personals'] = $results[ $helper['provider_id'] ]['personals'] ?? [];
                        foreach ($result->getCalendars() as $cal_id => $calendar) {

                            $where = in_array($cal_id, $helper['personals'], TRUE) ? 'personals' : 'freebusy';

                            $results[ $helper['provider_id'] ][ $where ][ $cal_id ] = $results[ $helper['provider_id'] ][ $where ][ $cal_id ] ?? [];
                            foreach ($calendar->getBusy() as $item) {
                                $results[ $helper['provider_id'] ][ $where ][ $cal_id ][] = [
                                    'start' => $item->getStart(),
                                    'end'   => $item->getEnd()
                                ];
                            }

                        }
                    } elseif ($result instanceof \Google\Service\Calendar\Events) {
                        foreach ($result->getItems() as $item) {

                            $start = DateTimeTbk::createFromFormatSilently(
                                self::isAllDay($item) ? 'Y-m-d' : \DateTime::RFC3339,
                                self::isAllDay($item) ? $item->getStart()->getDate() : $item->getStart()->getDateTime(),
                                $item->getStart()->getTimeZone()
                            )->getTimestamp();
                            $end   = DateTimeTbk::createFromFormatSilently(
                                self::isAllDay($item) ? 'Y-m-d' : \DateTime::RFC3339,
                                self::isAllDay($item) ? $item->getEnd()->getDate() : $item->getEnd()->getDateTime(),
                                $item->getEnd()->getTimeZone()
                            )->getTimestamp();

                            if ($end <= $timestamp_min) {
                                continue;
                            }

                            $results[ $helper['provider_id'] ]['overrides'][ $helper['calendar_id'] ][] = [
                                'start'      => $start,
                                'end'        => $end,
                                'properties' => Gcal2Ways::extractPropertiesFromItem($item),
                                'id'         => $item->getId()
                            ];
                        }
                    } else if ($result instanceof \Google\Service\Exception) {
                        Tools::log_dump('Error with provider ' . $helper['provider_id']);
                        Tools::log_dump($helper);
                        Tools::log_dump($result->getErrors());
                    } else {
                        Tools::log_dump('Error with provider ' . $helper['provider_id']);
                        Tools::log_dump($helper);
                        Tools::log_dump($result);
                    }

                }
            }
        }

        if (vshm()->settings->get(GoogleFetchDelay::ID, GoogleSettingBase::CONTEXT)) {
            Cache::store([
                'data'  => $results,
                'start' => $min->getTimestamp(),
                'end'   => $max->getTimestamp(),
            ]);
        }

        return $results;
    }

    private static function splitFreeBusy($min, $max, $freebusy, $service, $helper_array, &$batch_requests)
    {
        $control     = 12;
        $timeMinNext = $min;
        do {
            $timeMin                 = $timeMinNext->format(\DateTime::RFC3339);
            $timeMinNext             = $timeMinNext->add(new \DateInterval('P2M'));
            $timeMax                 = $max < $timeMinNext ? $max->format(\DateTime::RFC3339) : $timeMinNext->format(\DateTime::RFC3339);
            $helper_array['timeMin'] = $timeMin;
            $helper_array['timeMax'] = $timeMax;

            $freebusy->setTimeMin($timeMin);
            $freebusy->setTimeMax($timeMax);

            $request = $service->freebusy->query($freebusy);
            // Adding the request to the batch requests
            $batch_requests[ base64_encode(gzdeflate(json_encode($helper_array))) ] = $request;
            $control--;
        } while ($max > $timeMinNext && $control > 0);
    }

    public static function isAllDay($item): bool
    {
        if ($item->getStart()->getDateTime()) {
            return FALSE;
        }

        if ($item->getStart()->getDate()) {
            return TRUE;
        }

        return FALSE;
    }

}
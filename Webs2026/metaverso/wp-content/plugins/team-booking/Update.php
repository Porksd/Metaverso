<?php

namespace VSHM;

use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Modules\Gcal2Ways;
use VSHM\Modules\Gcal3Way\Settings\GoogleAllowSlotCommands;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiApplicationName;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiClientId;
use VSHM\Modules\Gcal3Way\Settings\GoogleApiClientSecret;
use VSHM\Modules\Zoom;
use VSHM\Modules\Zoom\Settings\ZoomJWTApiKey;
use VSHM\Modules\Zoom\Settings\ZoomJWTApiSecret;
use VSHM\Providers\Promotions;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\Settings\Reservation\Discount;

defined('ABSPATH') || exit;

/**
 * Class Update
 */
class Update
{

    public static function get_stored_version()
    {
        return get_option('tbk_version');
    }

    public static function is_migrated_from_v2()
    {
        return get_option('tbk_migrated_from_v2');
    }

    public static function is_v2_data_present(): bool
    {
        return (bool)get_option('team_booking');
    }

    public static function is_awaiting_migration_from_v2(): bool
    {
        return self::is_v2_data_present() && !self::is_migrated_from_v2();
    }

    public static function update_stored_version(): void
    {
        update_option('tbk_version', vshm()->plugin['VERSION']);
    }

    public static function set_migrated_from_v2(bool $migrated = TRUE): void
    {
        update_option('tbk_migrated_from_v2', $migrated);
    }

    public static function remove_v2_data()
    {
        DB::drop_table('teambooking_events');
        DB::drop_table('teambooking_promotions');
        DB::drop_table('teambooking_reservations');
        DB::drop_table('teambooking_sessions');
        wp_clear_scheduled_hook('tb-db-cleaning-routine');
        wp_clear_scheduled_hook('tb_email_reminder_handler');
        $post_args = [
            'post_type' => ['tbk_service', 'tbk_form'],
            'nopaging'  => TRUE
        ];
        $posts     = get_posts($post_args);
        foreach ($posts as $post) {
            $properties = array_keys(get_post_custom($post->ID));
            foreach ($properties as $property) {
                delete_post_meta_by_key($property);
            }
            wp_delete_post($post->ID, TRUE);
        }
        delete_option('tmbkg_zoom');
        delete_option('team_booking');
    }

    public static function maybe_update(): void
    {
        if (vshm()->plugin['VERSION'] !== self::get_stored_version()) {

            if (version_compare(self::get_stored_version(), '3.0.9', '<')) {
                self::to_3_0_9();
            }

            if (version_compare(self::get_stored_version(), '3.0.13', '<')) {
                self::to_3_0_13();
            }

            self::update_stored_version();
        }
    }

    public static function to_3_0_13(): bool
    {
        $zoomSettings = [];
        if (isset(vshm()->settings->getAllByContextRaw()[ ZoomJWTApiKey::ID ])) {
            $zoomSettings[ ZoomJWTApiKey::ID ] = vshm()->settings->getAllByContextRaw()[ ZoomJWTApiKey::ID ];
        }
        if (isset(vshm()->settings->getAllByContextRaw()[ ZoomJWTApiSecret::ID ])) {
            $zoomSettings[ ZoomJWTApiSecret::ID ] = vshm()->settings->getAllByContextRaw()[ ZoomJWTApiSecret::ID ];
        }
        update_option(Zoom::OPTIONS_TAG, $zoomSettings);

        return TRUE;
    }

    public static function to_3_0_9(): bool
    {
        $old_discount_records = ReservationsData::provideBy(['key' => 'discount']);

        $new_records_by_res_id = [];
        foreach ($old_discount_records as $old_discount_record) {
            if (!isset($new_records_by_res_id[ $old_discount_record['reservation_id'] ])) {
                $new_records_by_res_id[ $old_discount_record['reservation_id'] ] = [];
            }
            $new_records_by_res_id[ $old_discount_record['reservation_id'] ][] = $old_discount_record['value'];
        }

        foreach ($new_records_by_res_id as $res_id => $discounts) {
            vshm()->bus->dispatch(new UpdateOrCreateReservationProperty($res_id, Discount::ID, $discounts));
        }

        ReservationsData::removeBy(['key' => 'discount']);

        return TRUE;
    }

    /**
     * @return bool
     */
    public static function to_3(): bool
    {
        /**
         * Plugin settings
         */
        $settings      = TbkSettings::translateSettings();
        $base_settings = [];
        $gcal_settings = [];
        foreach ($settings as $key => $setting) {
            if ($key === GoogleApiClientId::ID) {
                $gcal_settings[ $key ] = $setting;
                continue;
            }
            if ($key === GoogleApiClientSecret::ID) {
                $gcal_settings[ $key ] = $setting;
                continue;
            }
            if ($key === GoogleApiApplicationName::ID) {
                $gcal_settings[ $key ] = $setting;
                continue;
            }
            if ($key === GoogleAllowSlotCommands::ID) {
                $gcal_settings[ $key ] = $setting;
                continue;
            }
            $base_settings[ $key ] = $setting;
        }

        $zoomData = get_option('tmbkg_zoom');
        if (is_array($zoomData)) {
            foreach ($zoomData as $key => $zoomDatum) {
                if ($key === 'zoom_apiKey') {
                    $base_settings[ ZoomJWTApiKey::ID ] = $zoomDatum;
                }
                if ($key === 'zoom_apiSecret') {
                    $base_settings[ ZoomJWTApiSecret::ID ] = $zoomDatum;
                }
            }

        }

        update_option(vshm()->get_settings_name(), $base_settings);
        update_option(Gcal2Ways::OPTIONS_TAG, $gcal_settings);

        /**
         * Services
         */
        $services         = TbkSettings::translateServices();
        $existingServices = array_column(Services::provide(), 'name', 'id');

        $data_to_store = [];
        foreach ($services as $service) {

            if (isset($existingServices[ $service->id ])) {
                continue;
            }

            foreach ($service->data as $key => $value) {
                $data_to_store[] = [
                    'service_id' => $service->id,
                    'key'        => $key,
                    'value'      => $value
                ];
            }
        }

        Services::storeMany($services);
        ServicesData::storeMany($data_to_store);

        /**
         * Forms
         */
        TbkSettings::translateForms();

        /**
         * Custom service settings
         */
        $settings = TbkSettings::translateCustomServiceSettings();

        ServiceProviderCustomData::storeMany($settings);

        // Filling gaps with defaults
        $services  = Services::provide(TRUE);
        $providers = ServiceProviders::provide();

        $to_insert = [];
        foreach ($services as $service) {
            if ($service->class === 'unscheduled') {
                continue;
            }
            $defaults = apply_filters('vshm_default_service_personal_settings', [], vshm()->plugin['SLUG'], $service);
            foreach ($providers as $provider) {
                foreach ($defaults as $key => $default) {
                    $to_insert[] = [
                        'service_id'  => $service->id,
                        'provider_id' => $provider['id'],
                        'key'         => $key,
                        'value'       => $default,
                    ];
                }
            }
        }
        ServiceProviderCustomData::storeMany($to_insert);

        /**
         * Reservations
         */
        TbkSettings::translateReservations();

        /**
         * Promotions
         */
        $promotions = TbkSettings::translatePromotions();
        if (!empty($promotions)) {
            Promotions::storeMany($promotions);
        }

        self::set_migrated_from_v2(TRUE);

        return TRUE;
    }

}
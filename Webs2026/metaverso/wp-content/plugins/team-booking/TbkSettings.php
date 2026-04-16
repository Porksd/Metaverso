<?php

namespace VSHM;

use VSHM\Bus\CreateLocation;
use VSHM\Providers\Customers;
use VSHM\Providers\FormEntries;
use VSHM\Providers\Locations;
use VSHM\Providers\Objects\Reservation;
use VSHM\Providers\Objects\Service;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\Settings\CurrencyCode;
use VSHM\Settings\Promotion\CouponMode;
use VSHM\Settings\Promotion\DiscountType;
use VSHM\Settings\Promotion\MaximumUses;
use VSHM\Settings\Promotion\Name;
use VSHM\Settings\Promotion\PromotionPeriod;
use VSHM\Settings\Promotion\PromotionServices;
use VSHM\Settings\Promotion\PromotionType;
use VSHM\Settings\Promotion\TimeslotMaxEnd;
use VSHM\Settings\Promotion\TimeslotMaxEndActive;
use VSHM\Settings\Promotion\TimeslotMinStart;
use VSHM\Settings\Promotion\TimeslotMinStartActive;
use VSHM\Settings\Promotion\Value;
use VSHM\Settings\Service\ReservationFormId;

defined('ABSPATH') || exit;

/**
 * This class handles the operations to migrate settings from TeamBooking 2.6.x to TheBooking 3.x
 *
 * Class TbkSettings
 */
class TbkSettings
{
    public static function translateSettings(): array
    {
        $settings     = [];
        $old_settings = self::casttoclass(get_option('team_booking'));

        if ($old_settings) {
            $old_settings = json_decode(json_encode($old_settings), TRUE);
        } else {
            return $settings;
        }

        /**
         * Processing Providers data
         */
        if (isset($old_settings['coworkers_data']) && is_array($old_settings['coworkers_data'])) {
            foreach ($old_settings['coworkers_data'] as $provider) {

                $allowedServices = is_array($provider['services_allowed'])
                    ? $provider['services_allowed']
                    : (isset($provider['services_allowed']) ? json_decode($provider['services_allowed'], TRUE) : []);

                $data = [
                    'id'                                   => $provider['coworker_id'],
                    Settings\Provider\GoogleCalendars::ID  => [],
                    Settings\Provider\GoogleApiToken::ID   => isset($provider['access_token'])
                        ? (json_decode($provider['access_token'], TRUE) ?: '')
                        : '',
                    Settings\Provider\ApiToken::ID         => $provider['tb_api_token'],
                    Settings\Provider\AllowedServices::ID  => is_array($allowedServices)
                        ? $allowedServices
                        : [],
                    Settings\Provider\RestrictServices::ID => is_array($allowedServices) && !empty($allowedServices),
                    Settings\Provider\GoogleAccount::ID    => $provider['auth_google_account'] ?: ''
                ];
                ServiceProviders::store($data);
            }
        }

        $settings['style'][ Settings\SettingBackgroundColor::ID ]    = $old_settings['color_background'];
        $settings['style'][ Settings\SettingAvailableSlotColor::ID ] = $old_settings['color_free_slot'];
        $settings['style'][ Settings\SettingSoldoutSlotColor::ID ]   = $old_settings['color_soldout_slot'];
        $settings['style'][ Settings\SettingBorderWidth::ID ]        = $old_settings['border']['size'];
        $settings['style'][ Settings\SettingBorderColor::ID ]        = $old_settings['border']['color'];
        $settings['style'][ Settings\SettingBorderRadius::ID ]       = $old_settings['border']['radius'];
        $settings['style'][ Settings\SettingDotsThreshold::ID ]      = (int)$old_settings['numbered_dots_lower_bound'];
        $settings['style'][ Settings\SettingDotsLogic::ID ]          = $old_settings['numbered_dots_logic'];
        $settings['style'][ Settings\SettingMapsStyle::ID ]          = (int)$old_settings['map_style'] + 1; // +1 because 0 is now the default style
        $settings['style'][ Settings\SettingMapsZoom::ID ]           = (int)$old_settings['gmaps_zoom_level'];

        $settings[ Settings\AllowCart::ID ]          = (bool)$old_settings['allow_cart'];
        $settings[ Settings\AllowIcalDownload::ID ]  = (bool)$old_settings['show_ical'];
        $settings[ Settings\PaymentPendingTime::ID ] = (int)$old_settings['max_pending_time'] ?: Settings\PaymentPendingTime::default();

        switch ($old_settings['autofill_reservation_form']) {
            case 'hide':
            case 1:
                $settings[ Settings\PrepopulateBookingForm::ID ] = TRUE;
                break;
            case 0:
                $settings[ Settings\PrepopulateBookingForm::ID ] = FALSE;
                break;
            default:
                $settings[ Settings\PrepopulateBookingForm::ID ] = Settings\PrepopulateBookingForm::default();
                break;
        }

        $settings[ Modules\Gcal3Way\Settings\GoogleAllowSlotCommands::ID ]  = (bool)$old_settings['allow_slot_commands'];
        $settings[ Modules\Gcal3Way\Settings\GoogleApiApplicationName::ID ] = $old_settings['application_project_name'];
        $settings[ Modules\Gcal3Way\Settings\GoogleApiClientSecret::ID ]    = $old_settings['application_client_secret'];
        $settings[ Modules\Gcal3Way\Settings\GoogleApiClientId::ID ]        = $old_settings['application_cliend_id']; // This is intended
        $settings[ Settings\GoogleMapsApiKey::ID ]                          = $old_settings['gmaps_api_key'];
        $settings[ Settings\LoadCalendarAtClosestSlot::ID ]                 = $old_settings['first_month_with_free_slot_is_shown'];
        $settings[ Settings\LoginUrl::ID ]                                  = $old_settings['login_url'];
        $settings[ Settings\RegistrationUrl::ID ]                           = $old_settings['registration_url'];
        $settings[ Settings\SkipGoogleMapsLib::ID ]                         = (bool)$old_settings['skip_gmaps_library'];
        $settings[ Settings\CurrencyCode::ID ]                              = $old_settings['currency_code'] ?: CurrencyCode::default();

        $settings['apiSecret'] = $old_settings['secret_key'];

        // Requires a dedicated db table
        $settings['apiTokens'] = $old_settings['tokens'];

        // Requires a dedicated db table
        $settings['serviceProvidersProfileUrl'] = $old_settings['coworkers_url_array'];


        return $settings;
    }

    public static function translateCustomServiceSettings(): array
    {
        $settings     = [];
        $old_settings = self::casttoclass(get_option('team_booking'));

        if ($old_settings) {
            $old_settings = json_decode(json_encode($old_settings), TRUE);
        } else {
            return $settings;
        }
        if (isset($old_settings['coworkers_data']) && is_array($old_settings['coworkers_data'])) {
            foreach ($old_settings['coworkers_data'] as $data) {
                if (isset($data['custom_event_settings']) && is_array($data['custom_event_settings'])) {
                    foreach ($data['custom_event_settings'] as $service_id => $custom_event_setting) {
                        foreach ($custom_event_setting as $key => $value) {
                            switch ($key) {
                                case 'email':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_ConfirmationEmailToProvider::ID . '_Subject',
                                        'value'       => html_entity_decode($value['email_text']['subject'], ENT_QUOTES, 'UTF-8')
                                    ];
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_ConfirmationEmailToProvider::ID . '_Body',
                                        'value'       => html_entity_decode($value['email_text']['body'], ENT_QUOTES, 'UTF-8')
                                    ];
                                    break;
                                case 'get_details_by_email':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_ConfirmationEmailToProvider::ID . '_Send',
                                        'value'       => $value
                                    ];
                                    break;
                                case 'include_uploaded_files_as_attachment':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_ConfirmationEmailToProvider::ID . '_SendAttachments',
                                        'value'       => $value
                                    ];
                                    break;
                                case 'additional_event_title_data':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => 'addCustomerNameToEventTitle',
                                        'value'       => isset($value['customer']['full_name']) && $value['customer']['full_name']
                                    ];
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => 'addCustomerEmailToEventTitle',
                                        'value'       => isset($value['customer']['email']) && $value['customer']['email']
                                    ];
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => 'addCustomerPhoneToEventTitle',
                                        'value'       => isset($value['customer']['phone']) && $value['customer']['phone']
                                    ];
                                    break;
                                case 'after_booked_title':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_EventTitleBooked::ID,
                                        'value'       => $value
                                    ];
                                    break;
                                case 'open_time':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_WhenToOpen::ID,
                                        'value'       => !$value ? 'PT0H' : $value
                                    ];
                                    break;
                                case 'min_time':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_WhenToClose::ID,
                                        'value'       => !$value ? 'PT0M' : $value
                                    ];
                                    break;
                                case 'min_time_reference':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_WhenToCloseReference::ID,
                                        'value'       => $value
                                    ];
                                    break;
                                case 'duration_rule':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_SlotDurationRule::ID,
                                        'value'       => $value
                                    ];
                                    break;
                                case 'fixed_duration':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_SlotDuration::ID,
                                        'value'       => (int)$value
                                    ];
                                    break;
                                case 'buffer_duration':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_BufferTimespan::ID,
                                        'value'       => $value
                                    ];
                                    break;
                                case 'buffer_duration_rule':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_BufferRule::ID,
                                        'value'       => $value
                                    ];
                                    break;
                                case 'reminder':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_GcalReminder::ID,
                                        'value'       => $value
                                    ];
                                    break;
                                case 'add_customer_as_guest':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_GcalAddGuests::ID,
                                        'value'       => (bool)$value
                                    ];
                                    break;
                                case 'deal_with_unrelated_events':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_DiscardOverlappingWithPersonal::ID,
                                        'value'       => (bool)$value
                                    ];
                                    break;
                                case 'deal_with_same_service_booked_slots':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_DiscardOverlappingWithSame::ID,
                                        'value'       => (bool)$value
                                    ];
                                    break;
                                case 'deal_with_other_service_booked_slots':
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_DiscardOverlappingWithOther::ID,
                                        'value'       => (bool)$value
                                    ];
                                    break;
                                case 'event_description_content':
                                    switch ($value) {
                                        case 2:
                                        case 0:
                                            $value = 'empty';
                                            break;
                                        case 1:
                                            $value = 'customer_data';
                                            break;
                                        default:
                                            $value = Settings\Service\Personal_GcalEventDescriptionContent::getDefault();
                                            break;
                                    }
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => Settings\Service\Personal_GcalEventDescriptionContent::ID,
                                        'value'       => $value
                                    ];
                                    break;
                                default:
                                    $settings[] = [
                                        'provider_id' => $data['coworker_id'],
                                        'service_id'  => $service_id,
                                        'key'         => $key,
                                        'value'       => $value
                                    ];
                                    break;
                            }
                        }
                    }

                }
            }
        }

        return $settings;
    }

    public static function translateForms(): void
    {
        $post_args = [
            'post_type' => 'tbk_form',
            'nopaging'  => TRUE
        ];
        $posts     = get_posts($post_args);
        foreach ($posts as $post) {
            $formId       = $post->ID;
            $fields       = get_post_custom($post->ID);
            $fields_order = isset($fields['_tbk_order']) ? maybe_unserialize($fields['_tbk_order'][0]) : [];
            if (!$fields_order) {
                $fields_order = [];
            }

            $form = [
                'id'       => $formId,
                'fields'   => [],
                'required' => [],
                'active'   => [],
                'logic'    => []
            ];

            $translated_fields = [];

            $new_order_up   = [];
            $new_order_down = [];

            foreach ($fields as $key => $field) {
                if ($key === '_tbk_order') {
                    continue;
                }
                if (0 !== strpos($key, 'tbk_')) {
                    continue;
                }
                if (isset($field[0])) {
                    $arrayed             = maybe_unserialize($field[0]);
                    $position            = array_search($arrayed['hook'], $fields_order);
                    $fieldId             = Tools::generate_token();
                    $translated_fields[] = [
                        'id'          => $fieldId,
                        'type'        => $arrayed['type'],
                        'hook'        => $arrayed['hook'],
                        'label'       => html_entity_decode($arrayed['title'], ENT_QUOTES, 'UTF-8'),
                        'description' => html_entity_decode($arrayed['description'], ENT_QUOTES, 'UTF-8'),
                        'data'        => self::extractFieldData($arrayed)
                    ];
                    if ($position !== FALSE) {
                        $new_order_down[ $position ] = $fieldId;
                    } else {
                        $new_order_up[] = $fieldId;
                    }

                    if ($arrayed['required']) {
                        $form['required'][] = $fieldId;
                    }

                    if ($arrayed['visible'] || $arrayed['hook'] === 'email') {
                        $form['active'][] = $fieldId;
                    }
                }
            }

            $form['fields'] = array_merge(array_values($new_order_up), array_values($new_order_down));

            if (empty(Providers\Forms::provideBy(['id' => $formId]))) {
                Providers\Forms::store($form);
                Providers\FormFields::storeMany($translated_fields);
            }
        }
    }

    public static function extractFieldData($field)
    {
        switch ($field['type']) {
            case 'text_field':
                $return = [
                    'meta_key'   => isset($field['data']['prefill']) ? $field['data']['prefill'] : NULL,
                    'default'    => isset($field['data']['value']) ? $field['data']['value'] : NULL,
                    'validation' => FALSE
                ];
                if (isset($field['data']['value_confirmation'])) {
                    $return['confirm'] = filter_var($field['data']['value_confirmation'], FILTER_VALIDATE_BOOLEAN);
                }
                if (isset($field['data']['validation'])) {
                    if (isset($field['data']['validation']['validate']) && $field['data']['validation']['validate']) {
                        $validation_key = $field['data']['validation']['validate'];
                        if (isset($field['data']['validation']['validation_regex'][ $validation_key ])) {
                            $return['validation'] = $field['data']['validation']['validation_regex'][ $validation_key ];
                        }
                    }
                }

                return $return;
            case 'text_area':
                return [
                    'meta_key' => isset($field['data']['prefill']) ? $field['data']['prefill'] : NULL,
                    'default'  => isset($field['data']['value']) ? $field['data']['value'] : NULL,
                ];
            case 'select':
            case 'radio':
                $return = [
                    'default'  => NULL,
                    'meta_key' => isset($field['data']['prefill']) ? $field['data']['prefill'] : NULL,
                    'options'  => []
                ];

                if (isset($field['data']['options']) && is_array($field['data']['options'])) {
                    foreach ($field['data']['options'] as $option) {
                        $return['options'][] = [
                            'label'           => $option['text'] ?: '',
                            'price_increment' => isset($option['price_increment']) ? $option['price_increment'] : "0",
                        ];
                    }
                }

                return $return;
            case 'file_upload':
                return [
                    'max_size'   => $field['data']['max_size'] ?? NULL,
                    'file_types' => isset($field['data']['file_extensions']) ? (string)$field['data']['file_extensions'] : NULL,
                ];
            case 'checkbox':
                $return = [
                    'default'         => isset($field['data']['checked']) ? filter_var($field['data']['checked'], FILTER_VALIDATE_BOOLEAN) : FALSE,
                    'price_increment' => isset($field['data']['price_increment']) ? (string)$field['data']['price_increment'] : "0",
                ];

                return $return;
            default:
                return $field['data'] ?: [];
        }
    }

    /**
     * @return Service[]
     */
    public static function translateServices(): array
    {
        $post_args = [
            'post_type' => 'tbk_service',
            'nopaging'  => TRUE
        ];
        $posts     = get_posts($post_args);
        $services  = [];
        foreach ($posts as $post) {
            $properties = get_post_custom($post->ID);

            $service              = new Service();
            $service->id          = $properties['tbk_id'][0];
            $service->name        = html_entity_decode($properties['tbk_name'][0], ENT_QUOTES, 'UTF-8');
            $service->description = html_entity_decode($properties['tbk_description'][0], ENT_QUOTES, 'UTF-8');
            $service->status      = (int)$properties['tbk_active'][0];
            $service->color       = $properties['tbk_color'][0] ?? NULL;
            $service->class       = $properties['tbk_class'][0] === 'event' ? 'appointment' : $properties['tbk_class'][0];

            /**
             * Service data
             */

            $service->data[ Settings\Service\CreateZoomMeeting::ID ] =
                isset($properties['tmbkg_create_zoom_meeting'][0])
                    ? (bool)$properties['tmbkg_create_zoom_meeting'][0]
                    : Settings\Service\CreateZoomMeeting::getDefault();

            $email = unserialize($properties['tbk_email_notification_admin'][0]);
            if ($email) {
                $service->data['emailAdminConfirmation_Send']            = (bool)$email['send'];
                $service->data['emailAdminConfirmation_Subject']         = html_entity_decode($email['subject'], ENT_QUOTES, 'UTF-8');
                $service->data['emailAdminConfirmation_Body']            = html_entity_decode($email['body'], ENT_QUOTES, 'UTF-8');
                $service->data['emailAdminConfirmation_SendAttachments'] = (bool)$email['attachments'];

                $service->data['emailAdminConfirmation_SendTo'] = $email['to']; // TODO Transform to comma-separated addresses
                $service->data['emailAdminCancellation_SendTo'] = $email['to']; // TODO Transform to comma-separated addresses
            }

            $email = unserialize($properties['tbk_email_notification_customer'][0]);
            if ($email) {
                $service->data['emailCustomerConfirmation_Send']     = (bool)$email['send'];
                $service->data['emailCustomerConfirmation_Subject']  = html_entity_decode($email['subject'], ENT_QUOTES, 'UTF-8');
                $service->data['emailCustomerConfirmation_Body']     = html_entity_decode($email['body'], ENT_QUOTES, 'UTF-8');
                $service->data['emailCustomerConfirmation_SendFrom'] = $email['from'] ?? 'admin';
            }

            $email = unserialize($properties['tbk_email_cancellation_admin'][0]);
            if ($email) {
                $service->data['emailAdminCancellation_Send']    = (bool)$email['send'];
                $service->data['emailAdminCancellation_Subject'] = html_entity_decode($email['subject'], ENT_QUOTES, 'UTF-8');
                $service->data['emailAdminCancellation_Body']    = html_entity_decode($email['body'], ENT_QUOTES, 'UTF-8');
            }

            $email = unserialize($properties['tbk_email_cancellation_customer'][0]);
            if ($email) {
                $service->data['emailCustomerCancellation_Send']     = (bool)$email['send'];
                $service->data['emailCustomerCancellation_Subject']  = html_entity_decode($email['subject'], ENT_QUOTES, 'UTF-8');
                $service->data['emailCustomerCancellation_Body']     = html_entity_decode($email['body'], ENT_QUOTES, 'UTF-8');
                $service->data['emailCustomerCancellation_SendFrom'] = $email['from'] ?? 'admin';
            }

            if (isset($properties['tbk_email_reminder_customer'])) {
                $email = unserialize($properties['tbk_email_reminder_customer'][0]);
                if ($email) {
                    $service->data['emailCustomerReminder_Send']       = (bool)$email['send'];
                    $service->data['emailCustomerReminder_Subject']    = html_entity_decode($email['subject'], ENT_QUOTES, 'UTF-8');
                    $service->data['emailCustomerReminder_Body']       = html_entity_decode($email['body'], ENT_QUOTES, 'UTF-8');
                    $service->data['emailCustomerReminder_SendFrom']   = $email['from'] ?? 'admin';
                    $service->data['emailCustomerReminder_DaysBefore'] = $email['days_before'];
                }
            }

            // Fixed location
            if (isset($properties['tbk_location'][0]) && $properties['tbk_location'][0]) {

                $locationString = $properties['tbk_location'][0];

                $location = Locations::provideBy(['name' => $locationString], TRUE);

                if ($location) {
                    $service->data[ Settings\Service\LocationAssigned::ID ] = $location['id'];

                } else {
                    $locationId                                             = Tools::generate_token();
                    $service->data[ Settings\Service\LocationAssigned::ID ] = $locationId;
                    vshm()->bus->dispatch(new CreateLocation(
                        $locationString,
                        $locationId,
                        $locationString,
                        1,
                        NULL,
                        NULL,
                        0
                    ));
                }
            }

            $service->data[ Settings\Service\SlotDurationRule::ID ]        = $properties['_tbk_slot_duration'][0] ?? Settings\Service\SlotDurationRule::getDefault();
            $service->data[ Settings\Service\SlotDuration::ID ]            = $properties['tbk_slot_duration'][0] ?? Settings\Service\SlotDuration::getDefault();
            $service->data[ Settings\Service\Location::ID ]                = $properties['_tbk_location'][0] ?? Settings\Service\Location::getDefault();
            $service->data[ Settings\Service\LocationVisibility::ID ]      = $properties['_tbk_location_visibility'][0] ?? Settings\Service\LocationVisibility::getDefault();
            $service->data[ Settings\Service\ShowMap::ID ]                 = $properties['_tbk_show_map'][0] ?? Settings\Service\ShowMap::getDefault();
            $service->data[ Settings\Service\ShowBookedSlots::ID ]         = $properties['_tbk_show_soldout'][0] ?? Settings\Service\ShowBookedSlots::getDefault();
            $service->data[ Settings\Service\ShowSlotCustomers::ID ]       = $properties['_tbk_show_attendees'][0] ?? Settings\Service\ShowSlotCustomers::getDefault();
            $service->data[ Settings\Service\DiscardedAvailableSlots::ID ] = $properties['_tbk_treat_discarded_free_slots'][0] ?? Settings\Service\DiscardedAvailableSlots::getDefault();
            $service->data[ Settings\Service\ShowTimes::ID ]               = $properties['_tbk_show_times'][0] ?? Settings\Service\ShowTimes::getDefault();
            $service->data[ Settings\Service\ShowProvider::ID ]            = $properties['_tbk_show_coworker'][0] ?? Settings\Service\ShowProvider::getDefault();
            $service->data[ Settings\Service\ShowProviderUrl::ID ]         = $properties['_tbk_show_coworker_url'][0] ?? Settings\Service\ShowProviderUrl::getDefault();
            $service->data[ Settings\Service\Approval::ID ]                = $properties['_tbk_approval_rule'][0] ?? Settings\Service\Approval::getDefault();
            $service->data[ Settings\Service\UntilApproval::ID ]           = isset($properties['_tbk_free_until_approval'][0]) ? filter_var($properties['_tbk_free_until_approval'][0], FILTER_VALIDATE_BOOLEAN) : Settings\Service\UntilApproval::getDefault();
            $service->data[ Settings\Service\AllowCancellation::ID ]       = isset($properties['_tbk_customer_cancellation'][0]) ? filter_var($properties['_tbk_customer_cancellation'][0], FILTER_VALIDATE_BOOLEAN) : Settings\Service\AllowCancellation::getDefault();
            $service->data[ Settings\Service\CancellationReason::ID ]      = isset($properties['_tbk_cancellation_reason_allowed'][0]) ? filter_var($properties['_tbk_cancellation_reason_allowed'][0], FILTER_VALIDATE_BOOLEAN) : Settings\Service\CancellationReason::getDefault();
            $service->data[ Settings\Service\CancellationTimespan::ID ]    = isset($properties['_tbk_cancellation_allowed_until'][0]) ? (int)$properties['_tbk_cancellation_allowed_until'][0] : Settings\Service\CancellationTimespan::getDefault();
            $service->data[ Settings\Service\Price::ID ]                   = isset($properties['tbk_price'][0]) ? (int)$properties['tbk_price'][0] : Settings\Service\Price::getDefault();
            $service->data[ Settings\Service\PaymentRequirement::ID ]      = $properties['_tbk_payment'][0] ?? Settings\Service\PaymentRequirement::getDefault();
            $service->data[ Settings\Service\Access::ID ]                  = $properties['_tbk_bookable'][0] ?? Settings\Service\Access::getDefault();
            $service->data[ Settings\Service\RedirectUrl::ID ]             = $properties['tbk_redirect_url'][0] ?? Settings\Service\RedirectUrl::getDefault();
            $service->data[ Settings\Service\Redirect::ID ]                = isset($properties['_tbk_redirect'][0]) ? filter_var($properties['_tbk_redirect'][0], FILTER_VALIDATE_BOOLEAN) : Settings\Service\Redirect::getDefault();
            $service->data[ Settings\Service\ReservationFormId::ID ]       = $properties['tbk_form'][0] ?? NULL;

            /**
             * Event specific
             */
            $service->data[ Settings\Service\TotalSlotTickets::ID ]     = isset($properties['tbk_slot_max_tickets'][0]) ? (int)$properties['tbk_slot_max_tickets'][0] : Settings\Service\TotalSlotTickets::getDefault();
            $service->data[ Settings\Service\TotalUserSlotTickets::ID ] = isset($properties['tbk_slot_max_user_tickets'][0]) ? (int)$properties['tbk_slot_max_user_tickets'][0] : Settings\Service\TotalUserSlotTickets::getDefault();

            /**
             * Unscheduled specific
             */
            $service->data[ Settings\Service\MaxUserReservations::ID ] = isset($properties['tbk_max_reservations_per_user'][0]) ? (int)$properties['tbk_max_reservations_per_user'][0] : Settings\Service\MaxUserReservations::getDefault();
            $service->data[ Settings\Service\AssignmentRule::ID ]      = isset($properties['_tbk_assignment_rule'][0]) ? $properties['_tbk_assignment_rule'][0] : Settings\Service\AssignmentRule::getDefault();
            $service->data[ Settings\Service\DirectProvider::ID ]      = isset($properties['tbk_direct_coworker_id'][0]) ? (int)$properties['tbk_direct_coworker_id'][0] : Settings\Service\DirectProvider::getDefault();

            $services[] = $service;
        }

        return $services;
    }

    public static function translateFilesAsFields($files): array
    {
        $fields = [];
        foreach ($files as $hook => $file) {
            $fields[] = [
                'name'  => $hook,
                'value' => $file['url'],
                'mime'  => $file['type']
            ];
        }

        return $fields;
    }

    public static function translateReservations(): void
    {
        $new_reservations         = [];
        $customersToStore         = [];
        $customersToStoreMap      = [];
        $customersToStoreMapEmail = [];
        $formEntriesToStore       = [];
        $datas                    = [];
        $services                 = array_column(Services::provide(TRUE), 'name', 'id');

        $results = DB::select('teambooking_reservations');
        if ($results instanceof \WP_Error) {
            return;
        }

        foreach ($results as $result) {

            if (!isset($services[ $result['service_id'] ])) {
                // Skipping reservations for non-existent services
                continue;
            }

            /**
             * Step 1. Form Fields Records
             */
            $formFields = self::recursiveCast(self::decode_object($result['form_fields']));
            foreach (self::translateFilesAsFields(self::decode_object($result['files'])) as $fileAsField) {
                $formFields[] = $fileAsField;
            }
            $formEntries = self::translateFormFieldRecords($formFields, $result['service_id'], $result['token']);
            foreach ($formEntries as $formEntry) {
                $formEntriesToStore[] = $formEntry;
            }

            /**
             * Step 2. Customers
             */
            $customerOldId = (int)$result['customer_id'];
            $customerNewId = NULL;
            $customerEmail = NULL;
            $customerPhone = NULL;
            foreach ($formFields as $formField) {
                if (isset($formField['name']) && $formField['name'] === 'email') {
                    $customerEmail = $formField['value'];
                }
                if (isset($formField['name']) && $formField['name'] === 'phone') {
                    $customerPhone = $formField['value'];
                }
            }
            if ($customerOldId) {
                // WordPress user
                if (isset($customersToStoreMap[ $customerOldId ])) {
                    $customerNewId = $customersToStoreMap[ $customerOldId ];
                } else {
                    $user = get_userdata($customerOldId);
                    if ($user) {
                        $customerNewId                         = apply_filters('tbk_customer_token_gen', Tools::generate_token('alnum', 32, 'c_'));
                        $customersToStoreMap[ $customerOldId ] = $customerNewId;
                        if ($user->data->user_email) {
                            $customersToStoreMapEmail[ $user->data->user_email ] = $customerNewId;
                        }
                        $customersToStore[] = [
                            'id'           => $customerNewId,
                            'name'         => $user->data->display_name,
                            'email'        => $user->data->user_email,
                            'phone'        => $customerPhone,
                            'wp_user'      => $customerOldId,
                            'access_token' => Tools::generate_token(),
                            'status'       => 1,
                        ];
                    }
                }
            } else {
                // Guest
                if ($customerEmail) {
                    if (isset($customersToStoreMapEmail[ $customerEmail ])) {
                        $customerNewId = $customersToStoreMapEmail[ $customerEmail ];
                    } else {
                        $user          = get_user_by('email', $customerEmail);
                        $customerNewId = apply_filters('tbk_customer_token_gen', Tools::generate_token('alnum', 32, 'c_'));
                        if ($user) {
                            $customersToStoreMapEmail[ $customerEmail ] = $customerNewId;
                            $customersToStoreMap[ $user->ID ]           = $customerNewId;
                            $customersToStore[]                         = [
                                'id'           => $customerNewId,
                                'name'         => $user->data->display_name,
                                'email'        => $user->data->user_email,
                                'phone'        => $customerPhone,
                                'wp_user'      => $user->ID,
                                'access_token' => Tools::generate_token(),
                                'status'       => 1,
                            ];
                        } else {
                            $customersToStoreMapEmail[ $customerEmail ] = $customerNewId;
                            $customersToStore[]                         = [
                                'id'           => $customerNewId,
                                'name'         => $result['customer_nicename'],
                                'email'        => $customerEmail,
                                'phone'        => $customerPhone,
                                'wp_user'      => 0,
                                'access_token' => Tools::generate_token(),
                                'status'       => 1,
                            ];
                        }
                    }
                }
            }

            if (!$customerNewId) {
                // No way, what if there is no email?
                // Skipping the reservation
                error_log('Reservation ' . $result['token'] . ' skipped, a customer cannot be determined.');
                continue;
            }

            /**
             * Step 3. Save reservations
             */
            $new_reservations[] = new Reservation(
                [
                    'id'         => $result['token'],
                    'serviceId'  => $result['service_id'],
                    'providerId' => (int)$result['coworker_id'],
                    'customerId' => $customerNewId,
                    'status'     => $result['status'] === 'waiting_approval' ? 'pending' : $result['status'],
                    'start'      => NULL === $result['start'] ? NULL : (int)$result['start'],
                    'end'        => NULL === $result['end'] ? NULL : (int)$result['end'],
                    'created'    => (int)$result['created_utc']
                ]
            );

            /**
             * Step 3. Save reservations data
             */

            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\GoogleCalendarId::ID,
                'value'          => self::enforceEmptyValue($result['calendar_id']),
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\AvailabilityId::ID,
                'value'          => self::enforceEmptyValue($result['calendar_id']),
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\SlotId::ID,
                'value'          => self::enforceEmptyValue(
                    apply_filters('tbk_determine_slot_id', '', (int)$result['coworker_id'], $result['calendar_id'], $result['service_id'], (int)$result['start'], (int)$result['end'])
                ),
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\GoogleCalendarEventId::ID,
                'value'          => self::enforceEmptyValue($result['event_id']),
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\CustomerTimezone::ID,
                'value'          => self::enforceEmptyValue($result['customer_timezone'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'enum_for_limit',
                'value'          => filter_var($result['enum_for_limit'], FILTER_VALIDATE_BOOLEAN),
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\GoogleCalendarEventParentId::ID,
                'value'          => self::enforceEmptyValue($result['event_parent_id'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'hangoutUrl',
                'value'          => self::enforceEmptyValue($result['hangout_url'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'eventUrl',
                'value'          => self::enforceEmptyValue($result['event_url'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\Location::ID,
                'value'          => self::enforceEmptyValue($result['service_location'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\Tickets::ID,
                'value'          => (int)$result['tickets']
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\Price::ID,
                'value'          => $result['price']
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'price_discounted',
                'value'          => $result['price_discounted']
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'pending_reason',
                'value'          => self::enforceEmptyValue($result['pending_reason'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'cancellation_reason',
                'value'          => self::enforceEmptyValue($result['canc_reason'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'cancellation_who',
                'value'          => (int)$result['canc_who']
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'confirm_who',
                'value'          => (int)$result['confirm_who']
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'reminderSent',
                'value'          => filter_var($result['email_reminder_sent'], FILTER_VALIDATE_BOOLEAN)
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\Paid::ID,
                'value'          => filter_var($result['paid'], FILTER_VALIDATE_BOOLEAN)
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'paymentGateway',
                'value'          => self::enforceEmptyValue($result['payment_gateway'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\CurrencyCode::ID,
                'value'          => self::enforceEmptyValue($result['currency_code'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'postId',
                'value'          => self::enforceEmptyValue($result['post_id'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'postTitle',
                'value'          => self::enforceEmptyValue($result['post_title'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => 'wantsPayment',
                'value'          => self::enforceEmptyValue($result['wants_payment'])
            ];
            $datas[] = [
                'reservation_id' => $result['token'],
                'key'            => Settings\Reservation\FrontendLang::ID,
                'value'          => self::enforceEmptyValue($result['frontend_lang'])
            ];

            $discounts = self::decode_object($result['discounts']);
            if (is_array($discounts)) {
                $new_discounts = [];
                foreach ($discounts as $discount) {
                    $new_discounts[] = $discount;
                }
                $datas[] = [
                    'reservation_id' => $result['token'],
                    'key'            => Settings\Reservation\Discount::ID,
                    'value'          => $new_discounts
                ];
            }
            $payment = self::decode_object($result['payment_details']);
            if (is_array($payment)) {
                $datas[] = [
                    'reservation_id' => $result['token'],
                    'key'            => Settings\Reservation\Payment::ID,
                    'value'          => $payment
                ];
            }
        }

        if (!empty($formEntriesToStore)) {
            FormEntries::storeMany($formEntriesToStore);
        }

        if (!empty($customersToStore)) {
            Customers::storeMany($customersToStore);
        }

        if (!empty($new_reservations)) {
            Reservations::storeMany($new_reservations);
        }

        if (!empty($datas)) {
            ReservationsData::storeMany($datas);
        }
    }

    public static function translateFormFieldRecords($records, $serviceId, $reservationId): array
    {
        $fields   = [];
        $services = Services::provide();
        foreach ($services as $service) {
            if ($service->id === $serviceId) {

                $serviceData = ServicesData::provideBy(['service_id' => $serviceId, 'key' => ReservationFormId::ID]);

                $serviceForm       = $serviceData[0]['value'];
                $serviceFormFields = [];

                $formsStuff = Providers\Forms::provideBy(['id' => $serviceForm]);
                if (isset($formsStuff[0])) {
                    $serviceFormFields = Providers\FormFields::provideByMultiple('field_id', $formsStuff[0]['fields']);
                }

                foreach ($records as $record) {
                    $id    = 'not_found';
                    $type  = NULL;
                    $label = NULL;

                    foreach ($serviceFormFields as $field) {

                        if ($field['hook'] === $record['name']) {
                            $id    = $field['id'];
                            $type  = $field['type'];
                            $label = $field['label'];
                        }

                    }

                    $data = NULL;
                    if (isset($record['mime'])) {
                        $data['mime'] = $record['mime'];
                    }
                    if (isset($record['price_increment'])) {
                        $data['priceIncrement'] = (string)$record['price_increment'];
                    }

                    $fields[] = [
                        'id'            => $id,
                        'reservationId' => $reservationId,
                        'type'          => $type,
                        'label'         => $label,
                        'value'         => $record['value'],
                        'data'          => $data
                    ];
                }
            }
        }

        return $fields;
    }

    public static function translatePromotions(): array
    {
        $promotions = [];

        $results = DB::select('teambooking_promotions');
        if ($results instanceof \WP_Error) {
            return $promotions;
        }

        $tz = new \DateTimeZone('UTC');

        foreach ($results as $result) {
            $promotion_obj = self::casttoclass(unserialize($result['data_object']));
            $promotionData = [];
            if (property_exists($promotion_obj, 'start_bound')) {
                $promotionData[ TimeslotMinStart::ID ]       = $promotion_obj->start_bound;
                $promotionData[ TimeslotMinStartActive::ID ] = NULL !== $promotion_obj->start_bound;
            }
            if (property_exists($promotion_obj, 'end_bound')) {
                $promotionData[ TimeslotMaxEnd::ID ]       = $promotion_obj->end_bound;
                $promotionData[ TimeslotMaxEndActive::ID ] = NULL !== $promotion_obj->end_bound;
            }
            if (property_exists($promotion_obj, 'limit')) {
                $promotionData[ MaximumUses::ID ] = (int)$promotion_obj->limit;
            }
            if (property_exists($promotion_obj, 'list')) {
                $promotionData['coupons']        = $promotion_obj->list;
                $promotionData[ CouponMode::ID ] = is_array($promotion_obj->list) && !empty($promotion_obj->list) ? 'list' : 'fixed';
            }
            if (property_exists($promotion_obj, 'services')) {
                $promotionData[ PromotionServices::ID ] = $promotion_obj->services;
            }
            $promotions[] = [
                PromotionType::ID              => $result['class'],
                PromotionPeriod::ID . '_start' => \DateTime::createFromFormat('Y-m-d H:i:s', $result['start_time'], $tz)->getTimestamp(),
                PromotionPeriod::ID . '_end'   => \DateTime::createFromFormat('Y-m-d H:i:s', $result['end_time'], $tz)->getTimestamp(),
                Name::ID                       => $promotion_obj->name,
                Value::ID                      => $promotion_obj->discount,
                DiscountType::ID               => $promotion_obj->discount_type, // percentage, direct
                'status'                       => (bool)$promotion_obj->status,
                'data'                         => $promotionData,
                'id'                           => $result['id']
            ];
        }

        return $promotions;
    }

    public static function casttoclass($object, $class = 'stdClass')
    {
        $ser_data = serialize($object);
        # preg_match_all('/O:\d+:"([^"]++)"/', $ser_data, $matches); // find all classes

        /*
         * make private and protected properties public
         *   privates  is stored as "s:14:\0class_name\0property_name")
         *   protected is stored as "s:14:\0*\0property_name")
         */
        $ser_data = preg_replace_callback('/s:\d+:"\0([^\0]+)\0([^"]+)"/',
            static function ($prop_match) {
                [$old, $classname, $propname] = $prop_match;

                return 's:' . strlen($propname) . ':"' . $propname . '"';
            }, $ser_data);

        // replace object-names
        $ser_data = preg_replace('/O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', $ser_data);

        return unserialize($ser_data);
    }

    public static function recursiveCast($array_of_objects)
    {
        foreach ($array_of_objects as $key => $object) {
            if (!is_array($object) && is_object($object)) {
                $array_of_objects[ $key ] = (array)self::casttoclass($object);
            }
        }

        return $array_of_objects;
    }

    /**
     * @param $obj
     *
     * @return mixed
     */
    public static function decode_object($obj)
    {
        $obj_base = base64_decode($obj, TRUE);
        if (!$obj_base) {
            $obj = unserialize($obj);
        } else {
            $obj = unserialize(gzinflate($obj_base));
        }

        return $obj;
    }

    public static function enforceEmptyValue($value): string
    {
        return $value ?? '';
    }
}
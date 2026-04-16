<?php

namespace VSHM\Modules;

use VSHM\Bus\CreateReservation;
use VSHM\Bus\CreateService;
use VSHM\Bus\DeleteService;
use VSHM\Bus\UpdateForm;
use VSHM\Bus\UpdateOrCreateReservationProperty;
use VSHM\Bus\UpdateOrCreateServiceProperty;
use VSHM\Functions;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Settings\Reservation\FrontendLang;
use VSHM\Settings\Service\CancellationEmailToCustomer;
use VSHM\Settings\Service\ConfirmationEmailToCustomer;
use VSHM\Settings\Service\Description;
use VSHM\Settings\Service\Name;
use VSHM\Settings\Service\ReminderEmailToCustomer;
use VSHM\Settings\Service\ReservationFormId;
use VSHM\Settings\Service\ShortDescription;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class WPML
 *
 * @author VonStroheim
 */
class WPML
{
    /**
     * @var string
     */
    public static $route_path = '/wpml/';

    public static function bootstrap(): void
    {

        if (!self::is_str_tr_available()) {
            return;
        }

        add_filter('tbk_service_notifications_items', static function ($items) {

            $image_url = plugins_url() . '/sitepress-multilingual-cms/res/img/icon-16-black.png';

            foreach ($items as $key => $item) {
                if ($item['id'] === 'customer') {
                    foreach ($item['items'] as $inner_key => $inner_item) {
                        $items[ $key ]['items'][ $inner_key ]['customModalContent'] = '
                                <span>
                                ' . (file_exists($image_url) ? '<img style="vertical-align:middle;margin-right: 2px;" src="' . $image_url . '" alt="WPML">' : '') . '
                                <a class="itemInfo" style="vertical-align:middle;" href="'
                            . REST_Controller::get_root_rest_url() . self::$route_path . 'get/link/">'
                            . esc_html__('Translations', 'team-booking')
                            . '</a></span>
                            ';
                    }
                }
            }

            return $items;

        }, 99);

        add_filter('tbk_service_settings_items', static function ($items) {
            $settings = [
                Name::ID,
                Description::ID,
                ShortDescription::ID
            ];

            $image_url = plugins_url() . '/sitepress-multilingual-cms/res/img/icon-16-black.png';

            foreach ($items as $key => $item) {
                if ($item['id'] === 'general') {
                    foreach ($item['items'] as $inner_key => $inner_item) {
                        if (in_array($inner_item['id'], $settings, TRUE)) {
                            $items[ $key ]['items'][ $inner_key ]['customContent'] = '
                                <span>
                                ' . (file_exists($image_url) ? '<img style="vertical-align:middle;margin-right: 2px;" src="' . $image_url . '" alt="WPML">' : '') . '
                                <a class="itemInfo" style="vertical-align:middle;" href="'
                                . REST_Controller::get_root_rest_url() . self::$route_path . 'get/link/">'
                                . esc_html__('Translations', 'team-booking')
                                . '</a></span>
                            ';
                        }
                    }
                }
            }

            return $items;

        });

        add_filter('tbk_populating_form_single_field', static function ($field) {
            $image_url              = plugins_url() . '/sitepress-multilingual-cms/res/img/icon-16-black.png';
            $field['customContent'] = '
                <span>
                ' . (file_exists($image_url) ? '<img style="vertical-align:middle;margin-right: 2px;" src="' . $image_url . '" alt="WPML">' : '') . '
                <a class="itemInfo"  style="vertical-align:middle;" href="'
                . REST_Controller::get_root_rest_url() . self::$route_path . 'get/link/">'
                . esc_html__('Translations', 'team-booking')
                . '</a></span>
            ';

            return $field;
        });

        REST_Controller::register_routes([
            self::$route_path . 'regenerate/' => [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'update'],
                'permission_callback' => static function () {
                    return Functions::current_user_can_admin();
                }
            ],
            self::$route_path . 'get/link/'   => [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_translations_link']
            ],
        ]);

        add_action('vshm_dispatched_DeleteService', [self::class, 'delete_service'], 10, 3);
        add_action('vshm_dispatched_CreateService', [self::class, 'create_service'], 10, 3);
        add_action('vshm_dispatched_UpdateOrCreateServiceProperty', [self::class, 'create_form'], 10, 3);
        add_action('vshm_dispatching_UpdateForm', [self::class, 'update_form'], 10, 3);
        add_action('vshm_dispatched_CreateReservation', [self::class, 'store_frontend_language'], 10, 3);
        add_filter('tbk_filtered_service_name', [self::class, 'translate_service_name'], 10, 2);
        add_filter('tbk_filtered_service_name_from_reservation', [self::class, 'translate_reservation_service_name'], 10, 2);
        add_filter('tbk_filtered_service_description', [self::class, 'translate_service_description'], 10, 2);
        add_filter('tbk_filtered_service_short_description', [self::class, 'translate_service_short_description'], 10, 2);
        add_filter('tbk_filtered_form_field_label', [self::class, 'translate_reservation_form_field_label'], 10, 3);
        add_filter('tbk_filtered_form_field_options', [self::class, 'translate_reservation_form_field_options'], 10, 3);
        add_filter('tbk_filtered_form_field_value', [self::class, 'translate_reservation_form_field_value'], 10, 3); //TODO?
        add_filter('tbk_settings_core_general_items', [self::class, 'settings_core_general_item']);
        add_filter('tbk_send_email_pre_content', [self::class, 'send_email'], 10, 3);
        add_action('tbk_frontend_env_vars', static function ($handle) {
            wp_add_inline_script($handle, 'const VSHM_LANG="' . apply_filters('wpml_current_language', NULL) . '"', 'before');
        }, 10, 2);
    }

    /**
     * @param string $content
     * @param string $contentKey
     * @param string $reservationId
     *
     * @return string
     */
    public static function send_email(string $content, string $contentKey, string $reservationId): string
    {
        return self::get_string_service_email_translation($content, $reservationId, $contentKey);
    }

    /**
     * @param string $serviceId
     * @param bool   $force
     *
     * @return void
     */
    public static function add_string_service_translations(string $serviceId, bool $force = FALSE): void
    {
        self::register_string_service_translation($serviceId, $force);
        self::register_string_service_email_translation($serviceId, $force);

        $formId = ServicesData::provideBy(['service_id' => $serviceId, 'key' => ReservationFormId::ID], TRUE);

        if (!$formId) {
            return;
        }

        $form = Forms::provideBy(['id' => $formId], TRUE);

        if (!$form) {
            return;
        }

        $fields = $form['fields'];
        foreach ($fields as $fieldId) {
            self::register_string_form_translation($fieldId, $serviceId, $force);
        }
    }

    public static function update_form(UpdateForm $command, string $agent_type, $agent_user): void
    {
        $form = \VSHM\Providers\Forms::provideBy([
            'id' => $command->getId()
        ], TRUE);

        if (!$form) {
            return;
        }

        $servicesForms = array_column(ServicesData::provideBy(['key' => ReservationFormId::ID]), 'value', 'service_id');
        foreach ($servicesForms as $serviceId => $formId) {
            if ($formId === $command->getId()) {
                foreach ($form['fields'] as $fieldId) {
                    if (!in_array($fieldId, $command->getFields(), TRUE)) {
                        self::remove_string_form_translation($fieldId, $serviceId);
                    }
                }
                foreach ($command->getFields() as $fieldId) {
                    self::register_string_form_translation($fieldId, $serviceId, FALSE);
                }
                break;
            }
        }
    }

    public static function create_form(UpdateOrCreateServiceProperty $command, string $agent_type, $agent_user): void
    {
        if ($command->getKey() === ReservationFormId::ID) {
            self::add_string_service_translations($command->getServiceId());
        }
    }

    public static function create_service(CreateService $command, string $agent_type, $agent_user): void
    {
        self::add_string_service_translations($command->getId());
    }

    public static function delete_service(DeleteService $command, string $agent_type, $agent_user): void
    {
        self::remove_string_service_translations($command->getId());
        self::remove_string_form_translations($command->getId());
        self::remove_string_email_translations($command->getId());
    }

    public static function settings_core_general_item($panel)
    {
        /** @var $panel \VSHM\UI\Admin\SettingsPanel */

        $update = \VSHM\UI\Admin\Settings_Informative::get(__('Update WPML translations', 'team-booking'));
        $update->addContent(\VSHM\UI\Admin\Settings_Content::CustomType('apiCallButtonPost', [
            'route'      => '/wpml/regenerate/',
            'buttonText' => __("Update", 'team-booking')
        ]));
        $update->setDescription(__("Regenerate existing strings data for WPML", 'team-booking'));
        $panel->addItem($update);

        return $panel;
    }

    public static function update(\WP_REST_Request $request): void
    {
        $services = Services::provide();
        foreach ($services as $service) {
            self::add_string_service_translations($service->id);
        }
    }

    /**
     * @param string $value
     * @param string $service_id
     * @param string $what
     *
     * @return string
     */
    public static function get_string_service_translation(string $value, string $service_id, string $what): string
    {
        return apply_filters('wpml_translate_string', $value, $service_id . '(' . $what . ')', [
            'kind' => 'TheBooking Service',
            'name' => $service_id
        ]);
    }

    /**
     * @param string $value
     * @param string $reservationId
     * @param string $what
     *
     * @return string
     */
    public static function get_string_service_email_translation(string $value, string $reservationId, string $what): string
    {
        $accepted_strings = [
            ReminderEmailToCustomer::ID_SUBJECT,
            ReminderEmailToCustomer::ID_BODY,
            CancellationEmailToCustomer::ID_SUBJECT,
            CancellationEmailToCustomer::ID_BODY,
            ConfirmationEmailToCustomer::ID_SUBJECT,
            ConfirmationEmailToCustomer::ID_BODY,
        ];
        if (!in_array($what, $accepted_strings, TRUE)) {
            return $value;
        }

        $reservation = Reservations::provideBy(['id' => $reservationId], TRUE);

        if (!$reservation) {
            return $value;
        }

        $frontendLang = ReservationsData::provideBy(['reservation_id' => $reservationId, 'key' => FrontendLang::ID], TRUE);

        if ($frontendLang) {
            $context = 'thebooking-e-mail-' . $reservation->serviceId;
            $name    = $reservation->serviceId . '(' . $what . ')';

            return self::force_string_translation($value, $context, $name, $frontendLang);
        }

        return apply_filters('wpml_translate_string', $value, $reservation->serviceId . '(' . $what . ')', [
            'kind' => 'TheBooking E-mail',
            'name' => $reservation->serviceId
        ]);
    }


    /**
     * @param string $value
     * @param string $fieldId
     * @param string $service_id
     * @param string $what
     *
     * @return string
     */
    public static function get_string_form_translation(string $value, string $fieldId, string $service_id, string $what): string
    {
        return apply_filters('wpml_translate_string', $value, $fieldId . '(' . $what . ')', [
            'kind' => 'TheBooking Form',
            'name' => $service_id
        ]);
    }

    /**
     * @param string $fieldId
     * @param string $service_id
     * @param bool   $force
     */
    public static function register_string_form_translation(string $fieldId, string $service_id, bool $force = FALSE): void
    {
        if (!$force && !self::is_str_tr_available()) {
            return;
        }

        $field = FormFields::provideBy(['id' => $fieldId], TRUE);

        if (!$field) {
            return;
        }

        $service = Services::provideBy(['id' => $service_id], TRUE);

        if (!$service) {
            return;
        }

        $package = [
            'kind'  => 'TheBooking Form',
            'name'  => $service_id,
            'title' => $service->name,
        ];
        do_action('wpml_register_string',
            $field['label'],
            $fieldId . '(label)',
            $package,
            $field['label'] . '(label)',
            'LINE'
        );
        if ($field['type'] === 'paragraph') {
            do_action('wpml_register_string',
                /* value */
                $field['description'],
                /* name */
                $fieldId . '(content)',
                /* package */
                $package,
                /* title */
                $field['label'] . '(content)',
                /* type */
                'VISUAL'
            );
        } else {
            if ($field['description'] !== '') {
                do_action('wpml_register_string',
                    $field['description'],
                    $fieldId . '(description)',
                    $package,
                    $field['label'] . '(description)',
                    'VISUAL'
                );
            } else {
                if (function_exists('icl_unregister_string')) {
                    icl_unregister_string('thebooking-form-' . $service_id, $fieldId . '(description)');
                }
            }
        }
        if (($field['type'] === 'text_field') || ($field['type'] === 'text_area')) {
            $default_text = $field['data']['value'] ?? '';
            if ($default_text !== '') {
                do_action('wpml_register_string',
                    $default_text,
                    $fieldId . '(default_text)',
                    $package,
                    $field['label'] . '(default_text)',
                    'LINE'
                );
            } else {
                if (function_exists('icl_unregister_string')) {
                    icl_unregister_string('thebooking-form-' . $service_id, $fieldId . '(default_text)');
                }
            }
        }
        if (($field['type'] === 'select') || ($field['type'] === 'radio')) {
            // deleting all the options
            self::icl_unregister_string_wildcard('thebooking-form-' . $service_id, $fieldId . '(option');
            $i = 1;
            foreach ($field['data']['options'] as $option) {
                do_action('wpml_register_string',
                    $option['label'],
                    $fieldId . '(option_' . $i . ')',
                    $package,
                    $option['label'],
                    'LINE'
                );
                $i++;
            }
        }

    }

    /**
     * @param string $serviceId
     * @param bool   $force
     */
    public static function register_string_service_translation(string $serviceId, bool $force = FALSE): void
    {
        if (!$force && !self::is_str_tr_available()) {
            return;
        }

        $service = Services::provideBy(['id' => $serviceId], TRUE);

        if (!$service) {
            return;
        }

        $package = [
            'kind'  => 'TheBooking Service',
            'name'  => $serviceId,
            'title' => $service->name,
        ];
        do_action('wpml_register_string',
            $service->name,
            $serviceId . '(name)',
            $package,
            $service->name . '(name)',
            'LINE'
        );
        if ($service->description !== '') {
            do_action('wpml_register_string',
                $service->description,
                $serviceId . '(description)',
                $package,
                $service->name . '(description)',
                'VISUAL'
            );
        } else {
            if (function_exists('icl_unregister_string')) {
                icl_unregister_string('thebooking-service-' . $serviceId, $serviceId . '(description)');
            }
        }

        $shortDescription = ServicesData::provideBy(['service_id' => $serviceId, 'key' => ShortDescription::ID], TRUE);

        if ($shortDescription !== '') {
            do_action('wpml_register_string',
                $shortDescription,
                $serviceId . '(short_description)',
                $package,
                $service->name . '(short_description)',
                'LINE'
            );
        } else {
            if (function_exists('icl_unregister_string')) {
                icl_unregister_string('thebooking-service-' . $serviceId, $serviceId . '(short_description)');
            }
        }
    }


    /**
     * @param string $serviceId
     * @param bool   $force
     */
    public static function register_string_service_email_translation(string $serviceId, bool $force = FALSE): void
    {
        if (!$force && !self::is_str_tr_available()) {
            return;
        }

        $service = Services::provideBy(['id' => $serviceId], TRUE);

        if (!$service) {
            return;
        }

        $package = [
            'kind'  => 'TheBooking E-mail',
            'name'  => $serviceId,
            'title' => $service->name,
        ];

        // Confirmation e-mail
        $confEmailSubject = ServicesData::provideBy(['service_id' => $serviceId, 'key' => ConfirmationEmailToCustomer::ID_SUBJECT], TRUE);
        if ($confEmailSubject) {
            do_action('wpml_register_string',
                $confEmailSubject,
                $serviceId . '(' . ConfirmationEmailToCustomer::ID_SUBJECT . ')',
                $package,
                $service->name . '(' . ConfirmationEmailToCustomer::ID_SUBJECT . ')',
                'LINE'
            );
        } else {
            if (function_exists('icl_unregister_string')) {
                icl_unregister_string('thebooking-e-mail-' . $serviceId, $serviceId . '(' . ConfirmationEmailToCustomer::ID_SUBJECT . ')');
            }
        }
        $confEmailBody = ServicesData::provideBy(['service_id' => $serviceId, 'key' => ConfirmationEmailToCustomer::ID_BODY], TRUE);
        if ($confEmailBody) {
            do_action('wpml_register_string',
                $confEmailBody,
                $serviceId . '(' . ConfirmationEmailToCustomer::ID_BODY . ')',
                $package,
                $service->name . '(' . ConfirmationEmailToCustomer::ID_BODY . ')',
                'VISUAL'
            );
        } else {
            if (function_exists('icl_unregister_string')) {
                icl_unregister_string('thebooking-e-mail-' . $serviceId, $serviceId . '(' . ConfirmationEmailToCustomer::ID_BODY . ')');
            }
        }
        // Cancellation e-mail
        $cancEmailSubject = ServicesData::provideBy(['service_id' => $serviceId, 'key' => CancellationEmailToCustomer::ID_SUBJECT], TRUE);
        if ($cancEmailSubject) {
            do_action('wpml_register_string',
                $cancEmailSubject,
                $serviceId . '(' . CancellationEmailToCustomer::ID_SUBJECT . ')',
                $package,
                $service->name . '(' . CancellationEmailToCustomer::ID_SUBJECT . ')',
                'LINE'
            );
        } else {
            if (function_exists('icl_unregister_string')) {
                icl_unregister_string('thebooking-e-mail-' . $serviceId, $serviceId . '(' . CancellationEmailToCustomer::ID_SUBJECT . ')');
            }
        }
        $cancEmailBody = ServicesData::provideBy(['service_id' => $serviceId, 'key' => CancellationEmailToCustomer::ID_BODY], TRUE);
        if ($cancEmailBody) {
            do_action('wpml_register_string',
                $cancEmailBody,
                $serviceId . '(' . CancellationEmailToCustomer::ID_BODY . ')',
                $package,
                $service->name . '(' . CancellationEmailToCustomer::ID_BODY . ')',
                'VISUAL'
            );
        } else {
            if (function_exists('icl_unregister_string')) {
                icl_unregister_string('thebooking-e-mail-' . $serviceId, $serviceId . '(' . CancellationEmailToCustomer::ID_BODY . ')');
            }
        }
        // Reminder e-mail
        if ($service->class !== 'unscheduled') {
            $reminderEmailSubject = ServicesData::provideBy(['service_id' => $serviceId, 'key' => ReminderEmailToCustomer::ID_SUBJECT], TRUE);
            if ($reminderEmailSubject) {
                do_action('wpml_register_string',
                    $reminderEmailSubject,
                    $serviceId . '(' . ReminderEmailToCustomer::ID_SUBJECT . ')',
                    $package,
                    $service->name . '(' . ReminderEmailToCustomer::ID_SUBJECT . ')',
                    'LINE'
                );
            } else {
                if (function_exists('icl_unregister_string')) {
                    icl_unregister_string('thebooking-e-mail-' . $serviceId, $serviceId . '(' . ReminderEmailToCustomer::ID_SUBJECT . ')');
                }
            }
            $reminderEmailBody = ServicesData::provideBy(['service_id' => $serviceId, 'key' => ReminderEmailToCustomer::ID_BODY], TRUE);

            if ($reminderEmailBody) {
                do_action('wpml_register_string',
                    $reminderEmailBody,
                    $serviceId . '(' . ReminderEmailToCustomer::ID_BODY . ')',
                    $package,
                    $service->name . '(' . ReminderEmailToCustomer::ID_BODY . ')',
                    'VISUAL'
                );
            } else {
                if (function_exists('icl_unregister_string')) {
                    icl_unregister_string('thebooking-e-mail-' . $serviceId, $serviceId . '(' . ReminderEmailToCustomer::ID_BODY . ')');
                }
            }
        }
    }

    /**
     * @param string $service_id
     */
    public static function remove_string_service_translation(string $service_id): void
    {
        if (function_exists('icl_unregister_string')) {
            icl_unregister_string('thebooking-service-' . $service_id, $service_id . '(name)');
            icl_unregister_string('thebooking-service-' . $service_id, $service_id . '(description)');
            icl_unregister_string('thebooking-service-' . $service_id, $service_id . '(short_description)');
        }
    }

    /**
     * @param string $fieldId
     * @param string $service_id
     */
    public static function remove_string_form_translation(string $fieldId, string $service_id): void
    {
        if (function_exists('icl_unregister_string')) {
            icl_unregister_string('thebooking-form-' . $service_id, $fieldId . '(label)');
            icl_unregister_string('thebooking-form-' . $service_id, $fieldId . '(content)');
            icl_unregister_string('thebooking-form-' . $service_id, $fieldId . '(description)');
            icl_unregister_string('thebooking-form-' . $service_id, $fieldId . '(default_text)');
            self::icl_unregister_string_wildcard('thebooking-form-' . $service_id, $fieldId . '(option');
        }
    }

    /**
     * @param string $service_id
     */
    public static function remove_string_form_translations(string $service_id): void
    {
        do_action('wpml_delete_package_action', $service_id, 'TheBooking Form');
    }

    /**
     * @param string $service_id
     */
    public static function remove_string_service_translations(string $service_id): void
    {
        do_action('wpml_delete_package_action', $service_id, 'TheBooking Service');
    }

    /**
     * @param string $service_id
     */
    public static function remove_string_email_translations(string $service_id): void
    {
        do_action('wpml_delete_package_action', $service_id, 'TheBooking E-mail');
    }

    /**
     * @param string $context
     * @param string $partial_name
     */
    public static function icl_unregister_string_wildcard(string $context, string $partial_name): void
    {
        global $wpdb;
        $string_ids = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_strings
                                                WHERE context=%s AND name LIKE '%%%s%%'",
            $context, $partial_name));
        foreach ($string_ids as $string_id) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_strings WHERE id=%d", $string_id->id));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d", $string_id->id));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id=%d", $string_id->id));
            do_action('icl_st_unregister_string', $string_id->id);
        }
    }

    /**
     * @return bool
     */
    public static function is_str_tr_available(): bool
    {
        return class_exists('SitePress')
            && class_exists('WPML_String_Translation')
            && class_exists('WPML_TM_Loader');
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public static function get_translations_link(\WP_REST_Request $request): \WP_REST_Response
    {

        $type    = $request->get_param('itemType');
        $context = 'thebooking-';
        if ($type === 'service') {
            $context .= 'service-' . $request->get_param('itemId');
        } elseif ($type === 'email') {
            $context .= 'e-mail-' . $request->get_param('itemId');
        } elseif ($type === 'form') {
            $context .= 'form-' . $request->get_param('itemId');
        }

        return rest_ensure_response(new \WP_REST_Response(
            NULL,
            302,
            [
                'Location' => add_query_arg(
                    'context',
                    $context,
                    admin_url('admin.php?page=wpml-string-translation/menu/string-translation.php')
                )
            ]
        ));
    }

    /**
     * @param CreateReservation $command
     * @param string            $agent_type
     * @param                   $agent_user
     */
    public static function store_frontend_language(CreateReservation $command, string $agent_type, $agent_user): void
    {
        if ($agent_type === vshm()->bus::AGENT_USER) {

            $headers = Tools::get_request_headers();

            $lang = $headers['Vshm-Lang'] ?? apply_filters('wpml_current_language', NULL);

            if ($lang) {
                vshm()->bus->dispatch(new UpdateOrCreateReservationProperty(
                    $command->getId(),
                    FrontendLang::ID,
                    $lang
                ));
            }


        }
    }

    /**
     * @param string $name
     * @param string $serviceId
     *
     * @return string
     */
    public static function translate_service_name(string $name, string $serviceId): string
    {
        $service_name = self::get_string_service_translation($name, $serviceId, 'name');
        if (!empty($service_name)) {
            return $service_name;
        }

        return $name;
    }

    /**
     * @param string $name
     * @param string $reservationId
     *
     * @return string
     */
    public static function translate_reservation_service_name(string $name, string $reservationId): string
    {
        $reservation = Reservations::provideBy(['id' => $reservationId], TRUE);
        if ($reservation) {
            $service_name = self::translate_service_name($name, $reservation->serviceId);
            if (!empty($service_name)) {
                return $service_name;
            }
        }

        return $name;
    }

    /**
     * @param string $description
     * @param string $serviceId
     *
     * @return string
     */
    public static function translate_service_description(string $description, string $serviceId): string
    {
        $service_description = self::get_string_service_translation($description, $serviceId, 'description');
        if (!empty($service_description)) {
            return $service_description;
        }

        return $description;
    }

    /**
     * @param string $label
     * @param string $fieldId
     * @param string $formId
     *
     * @return string
     */
    public static function translate_reservation_form_field_label(string $label, string $fieldId, string $formId): string
    {
        $forms = ServicesData::provideBy(['key' => ReservationFormId::ID, 'value' => $formId]);

        $headers = Tools::get_request_headers();

        foreach ($forms as $form) {

            if (isset($headers['Vshm-Lang'])) {
                $field_label = self::force_string_translation(
                    $label,
                    'thebooking-form-' . $form['service_id'],
                    $fieldId . '(label)',
                    $headers['Vshm-Lang']
                );
            } else {
                $field_label = self::get_string_form_translation($label, $fieldId, $form['service_id'], 'label');
            }

            if (!empty($field_label)) {
                return $field_label;
            }
            break;
        }

        return $label;
    }

    /**
     * @param array  $options
     * @param string $fieldId
     * @param string $formId
     *
     * @return array
     */
    public static function translate_reservation_form_field_options(array $options, string $fieldId, string $formId): array
    {
        $forms = ServicesData::provideBy(['key' => ReservationFormId::ID, 'value' => $formId]);

        $headers = Tools::get_request_headers();

        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($forms as $form) {
            foreach ($options as $key => $option) {
                if (isset($headers['Vshm-Lang'])) {
                    $options[ $key ]['label'] = self::force_string_translation(
                        $option['label'],
                        'thebooking-form-' . $form['service_id'],
                        $fieldId . '(option_' . ($key + 1) . ')',
                        $headers['Vshm-Lang']
                    );
                } else {
                    $options[ $key ]['label'] = self::get_string_form_translation($option['label'], $fieldId, $form['service_id'], 'option_' . $key);
                }
            }
            break;
        }

        return $options;
    }

    /**
     * @param string $value
     * @param string $fieldId
     * @param string $formId
     *
     * @return string
     */
    public static function translate_reservation_form_field_value(string $value, string $fieldId, string $formId): string
    {
        $field = FormFields::provideBy(['id' => $fieldId], TRUE);

        if (!$field) {
            return $value;
        }

        if (isset($field['data']['options']) && is_array($field['data']['options'])) {
            $i = 1;
            foreach ($field['data']['options'] as $option) {
                if ($option['label'] === $value) {

                    $forms = ServicesData::provideBy(['key' => ReservationFormId::ID, 'value' => $formId]);
                    /** @noinspection LoopWhichDoesNotLoopInspection */
                    foreach ($forms as $form) {
                        return self::get_string_form_translation($value, $fieldId, $form['service_id'], 'option_' . $i);
                    }
                }
                $i++;
            }
        }

        return $value;
    }

    /**
     * @param string $description
     * @param string $serviceId
     *
     * @return string
     */
    public static function translate_service_short_description(string $description, string $serviceId): string
    {
        $service_description = self::get_string_service_translation($description, $serviceId, 'short_description');
        if (!empty($service_description)) {
            return $service_description;
        }

        return $description;
    }

    /**
     * @param string $code
     * @param string $reservationId
     *
     * @return string
     */
    public static function expand_lang_code(string $code, string $reservationId): string
    {
        global $wpdb;
        $language = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT code, english_name, active, tag, name
            FROM {$wpdb->prefix}icl_languages lang
            INNER JOIN {$wpdb->prefix}icl_languages_translations trans
            ON lang.code = trans.language_code
            AND lang.code = %s
            AND trans.display_language_code=%s"
                , $code, $code
            )
        );
        if ($language) {
            return $language->name . ' (' . $language->english_name . ')';
        }

        return $code;
    }

    /**
     * Returns the translation of a string in a specific language if it exists or the original if it does not.
     *
     * @param $string
     * @param $context
     * @param $name
     * @param $lang
     *
     * @return string
     */
    public static function force_string_translation($string, $context, $name, $lang): string
    {
        $output = $string;
        if (!empty($lang) && self::is_str_tr_available()) {
            global $wpdb;
            $table1   = $wpdb->prefix . 'icl_strings';
            $table2   = $wpdb->prefix . 'icl_string_translations';
            $sql      = "SELECT * 
            FROM 
                $table1, $table2
            WHERE 
                $table1.context = %s
            AND
                $table1.name = %s
            AND
                $table1.status = '10'
            AND
                $table1.id = $table2.string_id
            AND
                $table2.language = %s
            ";
            $safe_sql = $wpdb->prepare($sql, $context, $name, $lang);
            $result   = $wpdb->get_row($safe_sql, ARRAY_A);
            if (NULL !== $result) {
                $output = $result['value'];
            }
        }

        return $output;
    }

}
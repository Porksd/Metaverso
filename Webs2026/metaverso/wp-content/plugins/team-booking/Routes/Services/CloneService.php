<?php

namespace VSHM\Routes\Services;

use VSHM\Bus\CreateFormField;
use VSHM\Bus\CreateService;
use VSHM\Bus\DeleteFormField;
use VSHM\Bus\UpdateForm;
use VSHM\Bus\UpdateOrCreateServicePersonalProperty;
use VSHM\Bus\UpdateOrCreateServiceProperty;
use VSHM\Functions;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\ServicesRoute;
use VSHM\Routes\SingleRoute;
use VSHM\Settings\Service\CancellationEmailToAdmin;
use VSHM\Settings\Service\CancellationEmailToCustomer;
use VSHM\Settings\Service\ConfirmationEmailToAdmin;
use VSHM\Settings\Service\ConfirmationEmailToCustomer;
use VSHM\Settings\Service\ReminderEmailToCustomer;
use VSHM\Settings\Service\ReservationFormId;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class CloneService
 *
 * @package VSHM\Routes
 */
class CloneService implements SingleRoute
{
    public static function getPath(): string
    {
        return ServicesRoute::getPath() . 'clone/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $item = $request->get_param('service');

                // TODO: validation
                $clonedServiceId   = apply_filters('tbk_service_token_gen', Tools::generate_token('alnum', 32, 's_'));
                $clonedServiceName = sprintf(
                /* translators: %s: name of the service */
                    __('Copy of %s', 'team-booking'),
                    $item['name']
                );

                $k = 0;
                while (count(Services::provideBy(['name' => $clonedServiceName])) > 0) {
                    $k++;
                    $clonedServiceName .= ' ' . $k;
                    if ($k > 1000) {
                        error_log('Something is wrong');
                        break;
                    }
                }

                vshm()->bus->dispatch(new CreateService(
                    $clonedServiceId,
                    $clonedServiceName,
                    $item['description'],
                    $item['class'],
                    $item['color'] ?? apply_filters('tbk_default_service_color', sprintf('#%06X', mt_rand(0, 0xFFFFFF)))
                ));

                // At this point, a form is already created

                $service = Services::provideBy(['id' => $item['id']], TRUE);

                if (!$service) {
                    return REST_Controller::get_error_response(self::getPath());
                }

                // Clone properties
                $properties             = ServicesData::provideBy(['service_id' => $service->id], FALSE, FALSE);
                $notificationProperties = [
                    ConfirmationEmailToAdmin::ID . '_Send',
                    ConfirmationEmailToAdmin::ID . '_Subject',
                    ConfirmationEmailToAdmin::ID . '_Body',
                    ConfirmationEmailToAdmin::ID . '_SendAttachments',
                    ConfirmationEmailToAdmin::ID . '_SendTo',
                    ConfirmationEmailToAdmin::ID . '_SendFrom',
                    ConfirmationEmailToCustomer::ID . '_Send',
                    ConfirmationEmailToCustomer::ID . '_Subject',
                    ConfirmationEmailToCustomer::ID . '_Body',
                    ConfirmationEmailToCustomer::ID . '_SendAttachments',
                    ConfirmationEmailToCustomer::ID . '_SendTo',
                    ConfirmationEmailToCustomer::ID . '_SendFrom',
                    CancellationEmailToAdmin::ID . '_Send',
                    CancellationEmailToAdmin::ID . '_Subject',
                    CancellationEmailToAdmin::ID . '_Body',
                    CancellationEmailToAdmin::ID . '_SendAttachments',
                    CancellationEmailToAdmin::ID . '_SendTo',
                    CancellationEmailToAdmin::ID . '_SendFrom',
                    CancellationEmailToCustomer::ID . '_Send',
                    CancellationEmailToCustomer::ID . '_Subject',
                    CancellationEmailToCustomer::ID . '_Body',
                    CancellationEmailToCustomer::ID . '_SendAttachments',
                    CancellationEmailToCustomer::ID . '_SendTo',
                    CancellationEmailToCustomer::ID . '_SendFrom',
                    ReminderEmailToCustomer::ID . '_Send',
                    ReminderEmailToCustomer::ID . '_Subject',
                    ReminderEmailToCustomer::ID . '_Body',
                    ReminderEmailToCustomer::ID . '_SendAttachments',
                    ReminderEmailToCustomer::ID . '_SendTo',
                    ReminderEmailToCustomer::ID . '_SendFrom',
                ];
                foreach ($properties as $property) {
                    if (in_array($property['key'], $notificationProperties, TRUE)
                        && !filter_var($request->get_param('cloneNotifications'), FILTER_VALIDATE_BOOLEAN)) {
                        continue;
                    }
                    if ($property['key'] === ReservationFormId::ID) {
                        // We don't want to clone this
                        continue;
                    }
                    vshm()->bus->dispatch(new UpdateOrCreateServiceProperty($clonedServiceId, $property['key'], $property['value']));
                }

                // Conditionals
                if (filter_var($request->get_param('cloneForms'), FILTER_VALIDATE_BOOLEAN)) {
                    $originalFormId = ServicesData::provideBy(['key' => ReservationFormId::ID, 'service_id' => $service->id], TRUE);

                    $builtInHooks = ['email', 'first_name', 'second_name', 'address', 'phone'];

                    if ($originalFormId) {
                        $originalForm = Forms::provideBy(['id' => $originalFormId], TRUE);
                        $formToEditId = ServicesData::provideBy(['key' => ReservationFormId::ID, 'service_id' => $clonedServiceId], TRUE);
                        $formToEdit   = Forms::provideBy(['id' => $formToEditId], TRUE);
                        if ($originalForm) {

                            // Step 1. Get fields to clone
                            $fieldsToClone   = FormFields::provideByMultiple('id', $originalForm['fields']);
                            $clonedFieldsIds = [];

                            $extraCheck = [];

                            $old_to_new_FieldId_Mapping = [];

                            foreach ($fieldsToClone as $field) {

                                if (in_array($field['hook'], $extraCheck, TRUE)) {
                                    // Avoid cloning built-in hooks
                                    continue;
                                }

                                if (in_array($field['hook'], $builtInHooks, TRUE)) {
                                    // Avoid cloning built-in hooks
                                    $extraCheck[] = $field['hook'];
                                }

                                $id                = Tools::generate_token();
                                $clonedFieldsIds[] = $id;

                                $old_to_new_FieldId_Mapping[ $field['id'] ] = $id;

                                vshm()->bus->dispatch(new CreateFormField($id, $field['type'], $field['hook'], $field['label'], $field['data'], $field['description']));
                            }

                            // Step 2. Remove old fields
                            if (is_array($formToEdit)) {
                                foreach ($formToEdit['fields'] as $fieldId) {
                                    vshm()->bus->dispatch(new DeleteFormField($fieldId));
                                }
                            }

                            // Step 3. Adapting IDs
                            $activeFields = [];
                            if (is_array($originalForm['active'])) {
                                foreach ($originalForm['active'] as $old_activeFieldId) {
                                    if (isset($old_to_new_FieldId_Mapping[ $old_activeFieldId ])) {
                                        $activeFields[] = $old_to_new_FieldId_Mapping[ $old_activeFieldId ];
                                    }

                                }
                            }
                            $requiredFields = [];
                            if (is_array($originalForm['required'])) {
                                foreach ($originalForm['required'] as $old_activeFieldId) {

                                    if (isset($old_to_new_FieldId_Mapping[ $old_activeFieldId ])) {
                                        $requiredFields[] = $old_to_new_FieldId_Mapping[ $old_activeFieldId ];
                                    }

                                }

                            }
                            $clonedLogic = [];
                            if (is_array($originalForm['logic'])) {
                                foreach ($originalForm['logic'] as $logic_rule) {
                                    $cloned_rule = [];
                                    foreach ($logic_rule as $key => $rule_part) {
                                        $cloned_rule[ $key ] = $old_to_new_FieldId_Mapping[ $rule_part ] ?? $rule_part;
                                    }
                                    $clonedLogic[] = $cloned_rule;
                                }
                            }

                            // Step 4. Edit form.
                            $clonedFieldsIds = array_unique($clonedFieldsIds);
                            vshm()->bus->dispatch(new UpdateForm($formToEditId, $clonedFieldsIds, $requiredFields, $activeFields, $clonedLogic));
                        }
                    }
                }

                if (filter_var($request->get_param('clonePersonal'), FILTER_VALIDATE_BOOLEAN)) {
                    $providers = ServiceProviders::provide();
                    foreach ($providers as $provider) {
                        $properties = ServiceProviderCustomData::provideBy(['service_id' => $service->id, 'provider_id' => $provider['id']]);
                        foreach ($properties as $property) {

                            // TODO: watch for properties that need the name to be adapted
                            vshm()->bus->dispatch(new UpdateOrCreateServicePersonalProperty($clonedServiceId, $provider['id'], $property['key'], $property['value']));
                        }
                    }
                }

                return REST_Controller::get_ok_response(self::getPath(), ['data' => ServicesRoute::prepare_for_backend()]);
            },
            'args'                => [
                'service'            => [
                    'required' => TRUE
                ],
                'cloneNotifications' => [
                    'type'     => 'boolean',
                    'required' => TRUE
                ],
                'cloneForms'         => [
                    'type'     => 'boolean',
                    'required' => TRUE
                ],
                'clonePersonal'      => [
                    'type'     => 'boolean',
                    'required' => TRUE
                ]
            ],
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
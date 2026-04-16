<?php

namespace BitCode\BitFormPro\Frontend;

use BitCode\BitFormPro\Core\Util\Utility;
use BitCode\BitForm\Admin\Form\AdminFormManager;
use BitCode\BitForm\Core\Database\FormEntryLogModel;
use BitCode\BitForm\Core\Database\FormEntryMetaModel;
use BitCode\BitForm\Core\Database\FormEntryModel;
use BitCode\BitForm\Core\Integration\IntegrationHandler;
use BitCode\BitForm\Core\Util\MailNotifier;
use BitCode\BitForm\Core\WorkFlow\WorkFlow;

class DoubleOptin
{

    protected $formId;

    protected $entryId;

    protected $token;

    protected $idendifer;

    protected $logId;

    public function __construct($idendifer, $token)
    {

        $this->idendifer = $idendifer;

        $this->token = $token;
    }

    public function approvedEntryByEmail()
    {
        $invalid = false;

        $strToArr = explode("_", $this->idendifer);

        if (is_array($strToArr) && count($strToArr) == 3) {

            $this->formId = $strToArr[0];

            $this->entryId = $strToArr[1];

            $this->logId = $strToArr[2];
        } else {
            $invalid = true;
            return;
        }

        $dblOptin = (new IntegrationHandler($this->formId))->getAllIntegration('double-opt-in', 'double-opt-in', 1);
        if (is_wp_error($dblOptin)) {
            wp_redirect(home_url());
            exit();
        }

        $dblConfig = json_decode($dblOptin[0]->integration_details);

        $formManager = new AdminFormManager($this->formId);

        if (!$formManager->isExist()) {
            $invalid = true;
            $this->redirect($dblConfig, 0);
        }

        $formEntryModel = new FormEntryModel();

        $entryMeta = new FormEntryMetaModel();

        $formEntry = $formEntryModel->get(
            "status",
            [
                'form_id' => $this->formId,
                'id'      => $this->entryId,
            ]
        );

        if (is_wp_error($formEntry)) {
            $invalid = true;
            $this->redirect($dblConfig, 0);
        }

        if ($formEntry[0]->status == 3) {
            $this->redirect($dblConfig, 2);
        }

        $formEntryMeta = $entryMeta->get(
            [
                'meta_key',
                'meta_value',
            ],
            [
                'bitforms_form_entry_id' => $this->entryId,
            ]
        );

        $tokenExist = '';

        $entries = [];

        foreach ($formEntryMeta as $key => $value) {

            if ($value->meta_key == 'entry_confirm_activation') {
                $tokenExist = $value->meta_value;
            }

            $entries[$value->meta_key] = $value->meta_value;
        }

        if ($this->token !== $tokenExist) {
            $invalid = true;
            $this->redirect($dblConfig, 0);
        }

        $formContent = $formManager->getFormContent();

        $submitted_fields = $formContent->fields;

        foreach ($submitted_fields as $key => $value) {
            if (isset($entries[$key])) {
                $submitted_fields->{$key}->val = $entries[$key];
                $submitted_fields->{$key}->name = $key;
            }
        }

        $workFlow = new WorkFlow($this->formId);
        $workFlowreturnedOnSubmit = $workFlow->executeOnSubmit(
            'create',
            $submitted_fields,
            $entries,
            $this->entryId,
            $this->logId
        );

        $triggerData = isset($workFlowreturnedOnSubmit['triggerData']) ? $workFlowreturnedOnSubmit['triggerData'] : null;

        if (isset($triggerData['dblOptin'])) {
            unset($triggerData['dblOptin']);
        }

        $triggerData['fields'] = $entries;

        if (!empty($triggerData) && !$invalid) {

            if (isset($triggerData['mail'])) {
                foreach ($triggerData['mail'] as $value) {
                    MailNotifier::notify($value, $triggerData['formID'], $triggerData['fields'], $this->entryId);
                }
            }

            do_action("bitforms_exec_integrations", $triggerData['integrations'], $triggerData['fields'], $triggerData['formID'], $triggerData['entryID'], $triggerData['logID']);

            $formEntryModel->update(
                [
                    "status"     => 3,
                    "updated_at" => current_time("mysql"),
                ],
                [
                    'form_id' => $this->formId,
                    'id'      => $this->entryId,
                ]
            );

            $entryMeta->delete(
                [
                    "meta_key"               => 'entry_confirm_activation',
                    "meta_value"             => $this->token,
                    "bitforms_form_entry_id" => $this->entryId,
                ]
            );
            $entryLog = new FormEntryLogModel();
            if (isset($cronNotOk[2]) && \is_int($cronNotOk[2])) {
                $entryLog->update(
                    [
                        "response_type" => "success",
                        "response_obj"  => json_encode(['status' => 'processed']),
                    ],
                    ["id" => $cronNotOk[2]]
                );
            }
            $this->redirect($dblConfig, 1);
        }
    }

    public function redirect($config, $index)
    {
        $redirectPages = [
            (isset($config->invalid_key_url)) ? $config->invalid_key_url : '',
            (isset($config->succ_url)) ? $config->succ_url : '',
            (isset($config->already_activated_url)) ? $config->already_activated_url : '',
        ];

        $customMessages = [
            (isset($config->invalid_key_msg)) ? $config->invalid_key_msg : 'Sorry! Your URL Is Invalid!',
            (isset($config->acti_succ_msg)) ? $config->acti_succ_msg : 'Thanks for the confirmation',
            (isset($config->already_activated_msg)) ? $config->already_activated_msg : 'Your mail is already confirmed!',
        ];

        if (isset($redirectPages[$index])) {
            if (isset($config->custom_redirect) && (string) $config->custom_redirect === '1') {
                wp_redirect($redirectPages[$index]);
                exit();
            } else {
                Utility::view('views/confirmation/email_confirmation', ['data' => [
                    'title'   => 'Entry Confirmation',
                    'message' => $customMessages[$index],
                ]]);
            }
        }
    }
}

<?php

namespace BitCode\BitFormPro\Admin;

use BitCode\BitFormPro\Core\Database\EntryRelatedInfoModel;
use BitCode\BitFormPro\Core\Database\PaymentInfoModel;
use BitCode\BitFormPro\Core\Database\PostInfoModel;
use BitCode\BitForm\Core\Database\FormEntryMetaModel;
use BitCode\BitForm\Core\Database\ReportsModel;
use BitCode\BitForm\Core\Form\FormHandler;
use BitCode\BitForm\Core\Form\FormManager;
use BitCode\BitForm\Core\Integration\IntegrationHandler;
use BitCode\BitForm\Core\Util\ApiResponse;
use BitCode\BitForm\Core\Util\HttpHelper;
use BitCode\BitForm\Core\Util\IpTool;
use BitCode\BitForm\Core\Util\MailConfig;
use BitCode\BitFormPro\Admin\AppSetting\Pdf;
use BitCode\BitForm\Core\Messages\PdfTemplateHandler;
use BitCode\BitFormPro\Admin\FormSettings\FormAbandonment;
use BitCode\BitFormPro\Admin\FormSettings\StandaloneForm;

class AdminAjax
{
    private $infoModel;
    private $paymentModel;
    private $metModel;

    public function __construct()
    {
        $this->infoModel = new EntryRelatedInfoModel();
        $this->paymentModel = new PaymentInfoModel();
        $this->metModel = new FormEntryMetaModel();
    }
    public function register()
    {
        add_action('wp_ajax_bitforms_form_entry_get_notes', array($this, 'getNotes'));
        add_action('wp_ajax_bitforms_form_entry_create_note', array($this, 'insertNote'));
        add_action('wp_ajax_bitforms_form_entry_update_note', array($this, 'updateNote'));
        add_action('wp_ajax_bitforms_form_entry_delete_note', array($this, 'deleteNote'));
        add_action('wp_ajax_bitforms_payment_insert', array($this, 'insertPayment'));
        add_action('wp_ajax_nopriv_bitforms_payment_insert', array($this, 'insertPayment'));
        add_action('wp_ajax_bitforms_payment_details', array($this, 'paymentDetails'));
        add_action('wp_ajax_bitforms_razorpay_transaction_info', array($this, 'getRazorpayTransactionInfo'));
        add_action('wp_ajax_bitforms_test_email', array($this, 'testEmail'));
        add_action('wp_ajax_bitforms_mail_config', array($this, 'saveEmailConfig'));
        add_action('wp_ajax_bitforms_get_mail_config', array($this, 'getEmailConfig'));
        add_action('wp_ajax_bitforms_save_razorpay_details', array($this, 'saveRazorpayDetails'));
        add_action('wp_ajax_nopriv_bitforms_save_razorpay_details', array($this, 'saveRazorpayDetails'));
        add_action('wp_ajax_bitforms_save_payment_setting', array($this, 'savePaymentSetting'));
        add_action('wp_ajax_bitforms_get_pod_field', array($this, 'getPodsField'));
        add_action('wp_ajax_bitforms_get_pod_type', array($this, 'getPodsType'));
        add_action('wp_ajax_bitforms_get_custom_field', array($this, 'getCustomField'));
        add_action('wp_ajax_bitforms_get_wp_taxonomy', array($this, 'getTaxonomies'));
        add_action('wp_ajax_bitforms_get_wp_posts', array($this, 'getAllPosts'));
        add_action('wp_ajax_bitforms_get_wp_users', array($this, 'getAllUsers'));
        // add_action('wp_ajax_bitforms_get_acf_group_fields', array($this, 'getAcfGroupFields'));
        add_action('wp_ajax_bitforms_get_post_type', array($this, 'postTypebyUser'));
        add_action('wp_ajax_bitforms_get_metabox_fields', array($this, 'getMetaboxFields'));
        add_action('wp_ajax_bitforms_get_wp_roles', array($this, 'getUserRoles'));
        add_action('wp_ajax_bitforms_get_user_customfields', array($this, 'getUserCustomFields'));
        add_action('wp_ajax_bitforms_save_auth_settings', array($this, 'saveAuthSettings'));
        add_action('wp_ajax_bitforms_get_auth_set', array($this, 'getAuthSetting'));
        add_action('wp_ajax_bitforms_clone_integration', array($this, 'cloneIntegration'));
        add_action('wp_ajax_bitforms_api_key', array($this, 'saveApiKey'));
        add_action('wp_ajax_bitforms_save_double_opt_in', array($this, 'saveDoubleOptIn'));
        add_action('wp_ajax_bitforms_get_auth_set', array($this, 'getAuthSetting'));
        add_action('wp_ajax_bitforms_get_double_opt_in', array($this, 'getDblOptin'));
        add_action('wp_ajax_bitforms_clone_integration', array($this, 'cloneIntegration'));
        add_action('wp_ajax_bitforms_save_report', array($this, 'saveReport'));
        add_action('wp_ajax_bitforms_delete_report', array($this, 'deleteReport'));
        // add_action('wp_ajax_bitforms_get_stripe_secret_key', array($this, 'getStripeSecretKey'));

        // pdf
        add_action('wp_ajax_bitforms_save_pdf_setting', array($this, 'savePdfSetting'));
        add_action('wp_ajax_bitforms_get_pdf_setting', array($this, 'getPdfSetting'));
        add_action('wp_ajax_bitforms_delete_pdf_template', array($this, 'deletePdfTemplate'));
        add_action('wp_ajax_bitforms_download_pdf_font', array($this, 'pdfFontDownload'));


        // form abandonment
        add_action('wp_ajax_bitforms_get_form_abandonment_config', array(FormAbandonment::class, 'getFormAbandonmentConfig'));

        // standalone
        add_action('wp_ajax_bitforms_save_standalone_css', array(StandaloneForm::class, 'saveStandaloneCSS'));
    }
    public function testEmail()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $to = wp_unslash($_REQUEST['to']);
            $subject = wp_unslash($_REQUEST['subject']);
            $message = wp_unslash($_REQUEST['message']);
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action']);
            if (!empty($to) && !empty($subject) && !empty($message)) {
                try {
                    (new MailConfig())->sendMail();
                    add_action('wp_mail_failed', function ($error) {
                        $data = [];
                        $data['errors'] = $error->errors['wp_mail_failed'];
                        wp_send_json_error($data, 400);
                    });
                    $result = wp_mail($to, $subject, $message);
                    wp_send_json_success($result, 200);
                } catch (Exception $e) {
                    wp_send_json_error($e->getMessage(), 400);
                }
            } else {
                wp_send_json_error(
                    __(
                        'Some of the test fields are empty or an invalid email supplied',
                        'bitformpro'
                    ),
                    401
                );
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }
    public function saveEmailConfig()
    {
        \ignore_user_abort();
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $ipTool = new IpTool();
            $status = $_REQUEST['status'];
            $user_details = $ipTool->getUserDetail();
            $integrationHandler = new IntegrationHandler(0, $user_details);
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action'], $_REQUEST['status']);
            $integrationDetails = json_encode($_REQUEST);
            $user_details = $ipTool->getUserDetail();
            $integrationName = "smtp";
            $integrationType = "smtp";
            $formIntegrations = $integrationHandler->getAllIntegration('mail', 'smtp');
            if (isset($formIntegrations->errors['result_empty'])) {
                $integrationHandler->saveIntegration($integrationName, $integrationType, $integrationDetails, 'mail', $status);
            } else {
                $integrationHandler->updateIntegration($formIntegrations[0]->id, $integrationName, $integrationType, $integrationDetails, 'mail', $status);
            }
            wp_send_json_success($formIntegrations, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getEmailConfig()
    {
        \ignore_user_abort();

        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action']);
            $ipTool = new IpTool();
            $user_details = $ipTool->getUserDetail();
            $integrationHandler = new IntegrationHandler(0, $user_details);
            $user_details = $ipTool->getUserDetail();
            $formIntegrations = $integrationHandler->getAllIntegration('mail', 'smtp');
            wp_send_json_success($formIntegrations, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getNotes()
    {
        $inputJSON = file_get_contents('php://input');
        $queryParams = json_decode($inputJSON);

        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            if (isset($queryParams->formID) && isset($queryParams->entryID)) {
                $formID = wp_unslash($queryParams->formID);
                $entryID = wp_unslash($queryParams->entryID);
            }
            $allNotes = $this->infoModel->getAllNotes($formID, $entryID);
            if (is_wp_error($allNotes)) {
                wp_send_json_error($allNotes->get_error_message(), 411);
            } else {
                wp_send_json_success($allNotes, 200);
                return $allNotes;
            }
        }
    }

    public function insertNote()
    {
        \ignore_user_abort();
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            if (isset($_REQUEST['formID']) && isset($_REQUEST['entryID'])) {
                $formID = wp_unslash($_REQUEST['formID']);
                $entryID = wp_unslash($_REQUEST['entryID']);
                $details = [];
                $details['title'] = $_REQUEST['title'];
                $details['content'] = $_REQUEST['content'];
                $note_details = json_encode(wp_unslash($details));
            }
            $details = $this->infoModel->insertNote($formID, $entryID, $note_details);
            wp_send_json_success($details, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function updateNote()
    {
        \ignore_user_abort();
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            if (isset($_REQUEST['noteID'])) {
                $noteID = wp_unslash($_REQUEST['noteID']);
                $formID = wp_unslash($_REQUEST['formID']);
                $entryID = wp_unslash($_REQUEST['entryID']);
                $details = [];
                $details['title'] = $_REQUEST['title'];
                $details['content'] = $_REQUEST['content'];
                $note_details = json_encode(wp_unslash($details));
            }
            $details = $this->infoModel->updateNote($noteID, $formID, $entryID, $note_details);
            wp_send_json_success($details, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function deleteNote()
    {
        $inputJSON = file_get_contents('php://input');
        $queryParams = json_decode($inputJSON);
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            if (isset($queryParams->noteID)) {
                $noteID = wp_unslash($queryParams->noteID);
                $formID = wp_unslash($queryParams->formID);
                $entryID = wp_unslash($queryParams->entryID);
            }
            $details = $this->infoModel->deleteNote($noteID, $formID, $entryID);
            wp_send_json_success($details, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function insertPayment()
    {
        \ignore_user_abort();
        $inputJSON = file_get_contents('php://input');
        $queryParams = json_decode($inputJSON);
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), "bitforms_{$queryParams->formID}")) {
            if (isset($queryParams->entry_id)) {
                $this->saveMetaData($queryParams);
            }
            if (isset($queryParams->formID) && isset($queryParams->transactionID)) {
                $trnasientData = get_transient("bitform_trigger_transient_{$queryParams->entry_id}");
                $triggerData = is_string($trnasientData) ? json_decode($trnasientData) : $trnasientData;
                $triggerData['fields'][$queryParams->fieldKey] = $queryParams->transactionID;
                set_transient("bitform_trigger_transient_{$queryParams->entry_id}", $triggerData, HOUR_IN_SECONDS);

                $result = $this->savePaymentLog($queryParams);
                wp_send_json_success($result, 200);
            } else {
                wp_send_json_error('FormId & EntryId is required', 400);
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    private function saveMetaData($data)
    {
        return $this->metModel->insert(
            [
                'bitforms_form_entry_id' => $data->entry_id,
                'meta_key' => $data->fieldKey,
                'meta_value' => $data->transactionID,
            ]
        );
    }

    private function savePaymentLog($data)
    {

        $formID = $data->formID;
        $transactionID = $data->transactionID;
        $paymentType = $data->payment_type;
        $paymentName = $data->payment_name;
        $response = wp_json_encode($data->payment_response);
        return $this->paymentModel->paymentInsert($formID, $transactionID, $paymentName, $paymentType, $response);
    }

    public function paymentDetails()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $queryParams = json_decode($inputJSON);
            if (isset($queryParams->formID) && isset($queryParams->transactionID)) {
                $formID = wp_unslash($queryParams->formID);
                $transactionID = wp_unslash($queryParams->transactionID);
                $paymentDeatail = $this->paymentModel->paymentDetail($formID, $transactionID);
                if (is_wp_error($paymentDeatail)) {
                    wp_send_json_error($paymentDeatail->get_error_message(), 411);
                } else {
                    wp_send_json_success($paymentDeatail, 200);
                    return $paymentDeatail;
                }
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getRazorpayTransactionInfo()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $queryParams = json_decode($inputJSON);
            if (isset($queryParams->formID) && isset($queryParams->transactionID)) {
                $defaultHeader = [];
                $transactionID = wp_unslash($queryParams->transactionID);
                $razorpaySettings = $queryParams->razorpaySettings;

                $token = base64_encode("{$razorpaySettings->apiKey}:{$razorpaySettings->apiSecret}");
                $defaultHeader['Authorization'] = "Basic {$token}";
                $requestEndpoint = "https://api.razorpay.com/v1/payments/{$transactionID}";
                $razorpayResponse = HttpHelper::get($requestEndpoint, null, $defaultHeader);

                if (!is_wp_error($razorpayResponse) && $razorpayResponse->error_code == null && !isset($razorpayResponse->error)) {
                    wp_send_json_success($razorpayResponse, 200);
                } else {
                    wp_send_json_error($razorpayResponse, 411);
                }
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function saveRazorpayDetails()
    {
        $inputJSON = file_get_contents('php://input');
        $queryParams = json_decode($inputJSON);

        if (
            wp_verify_nonce(
                sanitize_text_field($_REQUEST['_ajax_nonce']),
                "bitforms_{$queryParams->formID}"
            )
        ) {
            $formManager = new FormManager($queryParams->formID);
            $integrationHandler = new IntegrationHandler(0);
            $allFields = $formManager->getFormContent();
            $razorpayField = $allFields->fields->{$queryParams->fieldKey};
            $payIntegId = isset($razorpayField->payIntegID) ? $razorpayField->payIntegID : $razorpayField->options->payIntegID;
            $integration = $integrationHandler->getAIntegration($payIntegId, 'app', 'payments');
            $integration_details = json_decode($integration[0]->integration_details);
            $defaultHeader = [];
            $token = base64_encode("{$integration_details->apiKey}:{$integration_details->apiSecret}");
            $defaultHeader['Authorization'] = "Basic {$token}";

            $trnasientData = get_transient("bitform_trigger_transient_{$queryParams->entry_id}");
            $triggerData = is_string($trnasientData) ? json_decode($trnasientData) : $trnasientData;
            $triggerData['fields'][$queryParams->fieldKey] = $queryParams->transactionID;
            set_transient("bitform_trigger_transient_{$queryParams->entry_id}", $triggerData, HOUR_IN_SECONDS);

            $requestEndpoint = "https://api.razorpay.com/v1/payments/{$queryParams->transactionID}";
            $razorpayResponse = HttpHelper::get($requestEndpoint, null, $defaultHeader);
            $captureRequestEndpoint = "https://api.razorpay.com/v1/payments/{$queryParams->transactionID}/capture?amount={$razorpayResponse->amount}&currency={$razorpayResponse->currency}";
            HttpHelper::post($captureRequestEndpoint, null, $defaultHeader);

            // invoice
            // if (isset($razorpayField->options->invoice) && $razorpayField->options->invoice->generate) {
            //     $data = [
            //         'type' => 'invoice',
            //         'currency' => $razorpayResponse->currency,
            //         'customer' => [
            //             'contact' => $razorpayResponse->contact,
            //             'email' => $razorpayResponse->email,
            //             'name' => ''
            //         ],
            //         'line_items'=> [
            //             (object) [
            //                 'name' => !empty($razorpayField->options->invoice->itemName) ? $razorpayField->options->invoice->itemName : 'Due Amount',
            //                 'description' => !empty($razorpayField->options->invoice->description) ?$razorpayField->options->invoice->description : '',
            //                 'amount' => $razorpayResponse->amount,
            //                 'currency' => $razorpayResponse->currency,
            //                 'quantity' => 1
            //             ]
            //         ]
            //     ];

            //     if (!isset($razorpayField->options->invoice->sendSMS)) {
            //         $data['sms_notify'] = 0;
            //     }
            //     if (!isset($razorpayField->options->invoice->sendEmail)) {
            //         $data['email_notify'] = 0;
            //     }
            //     $invoiceEndpoint = 'https://api.razorpay.com/v1/invoices?' . http_build_query($data);
            //     HttpHelper::post($invoiceEndpoint, null, $defaultHeader);
            // }

            $queryParams->payment_name = 'razorpay';
            $queryParams->payment_response = $razorpayResponse;
            if (isset($queryParams->entry_id)) {
                $this->saveMetaData($queryParams);
            }
            $this->savePaymentLog($queryParams);
        }
    }

    public function savePdfSetting()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON);

            $status = Pdf::getInstance()->savePdfSetting($input->pdfSetting);

            if (is_wp_error($status)) {
                wp_send_json_error($status->get_error_message(), 411);
            } else {
                wp_send_json_success($status, 200);
            }

        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }

    }
    public function pdfFontDownload()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON);
            $fonts = $input->fontName;
            $status = Pdf::getInstance()->downloadPdfFont($fonts);

            if (is_wp_error($status)) {
                wp_send_json_error($status->get_error_message(), 411);
            } else {
                wp_send_json_success($status, 200);
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getPdfSetting()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action']);
            $pdfSetting = Pdf::getInstance()->getPdfSetting();
            wp_send_json_success($pdfSetting, 200);

        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function deletePdfTemplate()
    {

        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON);
            $formID = $input->formID;
            $id = $input->id;
            $emailTemplateHandler = new PdfTemplateHandler($formID);
            $delete_status = $emailTemplateHandler->delete($id);
            if (is_wp_error($delete_status)) {
                wp_send_json_error($delete_status->get_error_message(), 411);
            } else {
                wp_send_json_success($delete_status, 200);
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }


    public function savePaymentSetting()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON);
            $formHandler = FormHandler::getInstance();
            $status = $formHandler->admin->savePaymentSetting($_REQUEST, $input);
            if (is_wp_error($status)) {
                wp_send_json_error($status->get_error_message(), 411);
            } else {
                wp_send_json_success($status, 200);
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getPodsField()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON);
            $podsAdminExists = is_plugin_active('pods/init.php');

            $podField = [];
            if ($podsAdminExists) {
                $pods = pods($input->pod_type);
                $i = 0;
                foreach ($pods->fields as $field) {
                    $i++;
                    $podField[$i]['key'] = $field['name'];
                    $podField[$i]['name'] = $field['label'];
                    $podField[$i]['required'] = $field['options']['required'] == 1 ? true : false;
                }
            }

            if (is_wp_error($podField)) {
                wp_send_json_error($podField, 411);
            } else {
                wp_send_json_success($podField, 200);
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getPodsType()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $users = get_users(array('fields' => array('ID', 'display_name')));
            $pods = [];
            $podsAdminExists = is_plugin_active('pods/init.php');
            if ($podsAdminExists) {
                $allPods = pods_api()->load_pods();
                foreach ($allPods as $key => $pod) {
                    $pods[$key]['name'] = $pod['name'];
                    $pods[$key]['label'] = $pod['label'];
                }
            }
            $data = ['users' => $users, 'post_types' => $pods];
            wp_send_json_success($data, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getCustomField()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON);
            $acfFields = [];
            $acfFile = [];

            $filterTypes = [
                "text",
                "textarea",
                "password",
                "wysiwyg",
                "number",
                "radio",
                "color_picker",
                "oembed",
                "email",
                "url",
                "date_picker",
                "true_false",
                "date_time_picker",
                "time_picker",
                "message",
                "checkbox",
                "select",
                "post_object",
                "user",
            ];
            $filterFile = ['file', 'image', 'gallery'];

            $field_groups = get_posts(array('post_type' => 'acf-field-group'));
            if ($field_groups) {
                $groups = acf_get_field_groups(array('post_type' => $input->post_type));

                foreach ($groups as $group) {
                    foreach (acf_get_fields($group['key']) as $acfField) {
                        if (in_array($acfField['type'], $filterTypes)) {
                            array_push($acfFields, [
                                'key' => $acfField['key'],
                                'name' => $acfField['label'],
                                'required' => $acfField['required'],
                            ]);
                        } elseif (in_array($acfField['type'], $filterFile)) {
                            array_push($acfFile, [
                                'key' => $acfField['key'],
                                'name' => $acfField['label'],
                                'required' => $acfField['required'],
                            ]);
                        }
                    }
                }
            }
            wp_send_json_success(['acfFields' => $acfFields, 'acfFile' => $acfFile], 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getMetaboxFields()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON);

            $metaboxFields = [];
            $metaboxFile = [];

            $filterTypes = [
                'file_input',
                'group',
                'tab',
                'osm',
                'heading',
                'key_value',
                'map',
                'custom_html',
                'background',
                'fieldset_text',
                'taxonomy',
                'taxonomy_advanced',
            ];

            $fileTypes = [
                "image",
                "image_upload",
                "file_advanced",
                "file_upload",
                "single_image",
                "file",
                "image_advanced",
                "video",
            ];

            if (function_exists('rwmb_meta')) {
                $fields = rwmb_get_object_fields($input->post_type);
                foreach ($fields as $index => $field) {
                    if (!in_array($field['type'], $fileTypes)) {
                        if (!in_array($field['type'], $filterTypes)) {
                            $metaboxFields[$index]['name'] = $field['name'];
                        }

                        $metaboxFields[$index]['key'] = $field['id'];
                        $metaboxFields[$index]['required'] = $field['required'];
                    } else {
                        $metaboxFile[$index]['name'] = $field['name'];
                        $metaboxFile[$index]['key'] = $field['id'];
                        $metaboxFile[$index]['required'] = $field['required'];
                    }
                }
            }
            wp_send_json_success(
                [
                    'metaboxFields' => array_values($metaboxFields),
                    'metaboxFile' => array_values($metaboxFile),
                ],
                200
            );
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getTaxonomies()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $allCategoreis = get_terms(
                array(
                    'hide_empty' => false,
                )
            );

            $getTaxanomies = get_taxonomies($args = array(), $output = 'label', $operator = 'and');
            $taxonomies = [];

            foreach ($getTaxanomies as $index => $taxanomy) {
                $taxonomies[$index]['label'] = $taxanomy->label;
                $taxonomies[$index]['name'] = $taxanomy->name;
                $taxonomies[$index]['singular_name'] = $taxanomy->labels->singular_name;
                $taxonomies[$index]['object_type'] = $taxanomy->object_type;
                $taxonomies[$index]['hierarchical'] = $taxanomy->hierarchical;
            }

            wp_send_json_success(['taxonomies' => array_values($taxonomies), 'allCategoreis' => $allCategoreis], 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    private function getPostTypes()
    {
        $all_cpt = get_post_types(
            array(
                'public' => true,
                'exclude_from_search' => false,
                '_builtin' => false,
                'capability_type' => 'post',

            ),
            'objects'
        );
        $cpt = [];

        foreach ($all_cpt as $key => $post_type) {
            $cpt[$key]['name'] = $post_type->name;
            $cpt[$key]['label'] = $post_type->label;
        }
        $wp_post_types = get_post_types(
            array(
                'public' => true,
                '_builtin' => true,
            )
        );

        $wp_all_post_types = [];

        foreach ($wp_post_types as $key => $post_type) {
            if ($post_type !== 'attachment') {
                $wp_all_post_types[$key]['name'] = $post_type;
                $wp_all_post_types[$key]['label'] = ucwords($post_type);
            }
        }
        return array_merge($wp_all_post_types, $cpt);
    }

    public function getAllPosts()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $postTypes = $this->getPostTypes();

            $postInfoModel = new PostInfoModel();

            $allPosts = $postInfoModel->getAllPosts();

            wp_send_json_success(['posts' => $allPosts, 'postTypes' => $postTypes]);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getAllUsers()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $users = get_users();
            $usersData = [];

            foreach ($users as $key => $user) {
                $usersData[$key]['ID'] = $user->ID;
                $usersData[$key]['display_name'] = $user->display_name;
                $usersData[$key]['user_login'] = $user->user_login;
                $usersData[$key]['user_email'] = $user->user_email;
                $usersData[$key]['user_nicename'] = $user->user_nicename;
                $usersData[$key]['role'] = $user->roles;
            }
            wp_send_json_success(['users' => $usersData]);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function postTypebyUser()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $users = get_users(
                array(
                    'fields' => array('ID', 'display_name', 'user_login', 'user_email', 'user_nicename'),
                )
            );

            $postTypes = $this->getPostTypes();

            $data = ['post_types' => $postTypes, 'users' => $users];
            wp_send_json_success($data, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getAcfGroupFields()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $acfFields = [];
            $types = ['select', 'checkbox', 'radio'];

            $field_groups = get_posts(array('post_type' => 'acf-field-group'));

            if ($field_groups) {
                $groups = acf_get_field_groups();
                foreach ($groups as $group) {
                    foreach (acf_get_fields($group['key']) as $acfField) {
                        if (in_array($acfField['type'], $types)) {
                            array_push($acfFields, [
                                'key' => $acfField['key'],
                                'name' => $acfField['label'],
                                'choices' => $acfField['choices'],
                                'group_title' => $group['title'],
                                'location' => $group['location'],
                            ]);
                        }
                    }
                }
            }

            wp_send_json_success($acfFields, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getUserRoles()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            global $wp_roles;
            $roles = [];
            $key = 0;
            foreach ($wp_roles->get_names() as $index => $role) {
                $key++;
                $roles[$key]['key'] = $index;
                $roles[$key]['name'] = $role;
            }
            wp_send_json_success($roles, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function saveAuthSettings()
    {
        \ignore_user_abort();

        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action'], $_REQUEST['status']);
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $formId = $requestsParams->formId;
            $ipTool = new IpTool();
            $user_details = $ipTool->getUserDetail();
            $integrationHandler = new IntegrationHandler($formId, $user_details);
            $integrationName = $requestsParams->type;
            $status = $requestsParams->status;
            unset($requestsParams->type);
            unset($requestsParams->formId);
            unset($requestsParams->status);
            $integrationDetails = json_encode($requestsParams->$integrationName);
            $formIntegrations = $integrationHandler->getAllIntegration('wp_user_auth', 'wp_auth');
            if (isset($formIntegrations->errors['result_empty'])) {
                $result = $integrationHandler->saveIntegration($integrationName, 'wp_auth', $integrationDetails, 'wp_user_auth', $status);
            } else {
                $result = $integrationHandler->updateIntegration($formIntegrations[0]->id, $integrationName, 'wp_auth', $integrationDetails, 'wp_user_auth', $status);
            }
            wp_send_json_success($result, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function getAuthSetting()
    {
        \ignore_user_abort();
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action'], $_REQUEST['status']);
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $formId = $requestsParams->formID;
            $ipTool = new IpTool();
            $user_details = $ipTool->getUserDetail();
            $integrationHandler = new IntegrationHandler($formId, $user_details);
            $formIntegrations = $integrationHandler->getAllIntegration('wp_user_auth', 'wp_auth');
            wp_send_json_success($formIntegrations, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitformpro'
                ),
                401
            );
        }
    }

    public function cloneIntegration()
    {
        \ignore_user_abort();
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action'], $_REQUEST['status']);
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $ipTool = new IpTool();
            $user_details = $ipTool->getUserDetail();

            $integrationHandler = new IntegrationHandler($requestsParams->formID, $user_details);
            $existInteg = $integrationHandler->getAllIntegration(null, null, 1, $requestsParams->id);
            if (!is_wp_error($existInteg) && count($existInteg) > 0) {
                $inetegDetails = $existInteg[0];

                $integName = 'Duplicate of' . $inetegDetails->integration_name;
                $save = $integrationHandler->saveIntegration(
                    $integName,
                    $inetegDetails->integration_type,
                    $inetegDetails->integration_details,
                    'form',
                    $inetegDetails->status
                );

                if (!is_wp_error($save)) {
                    // wp_send_json_success($inetegDetails, 200);
                    wp_send_json_success($save, 200);
                } else {
                    wp_send_json_error($save->get_error_message(), 411);
                }
            }
            wp_send_json_error(
                __('No Integration Found', 'bitformpro'),
                404
            );
        }
    }

    public function saveApiKey()
    {
        $api_key = null;
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON);
            if (empty($input->api_key)) {
                $api_key = get_option('bitform_secret_api_key');
            } elseif (!empty($input->api_key)) {
                update_option('bitform_secret_api_key', $input->api_key);
                $api_key = $input->api_key;
            }
            if (!$api_key) {
                $api_key = str_replace('-', '', wp_generate_uuid4());
                update_option('bitform_secret_api_key', $api_key);
            }
            wp_send_json_success($api_key, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitform'
                ),
                401
            );
        }
    }

    public function saveDoubleOptIn()
    {
        \ignore_user_abort();

        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action'], $_REQUEST['status']);
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $formId = $requestsParams->formId;
            $ipTool = new IpTool();
            $user_details = $ipTool->getUserDetail();
            $integrationHandler = new IntegrationHandler($formId, $user_details);
            $status = $requestsParams->status;
            unset($requestsParams->formId);
            unset($requestsParams->status);
            $integrationDetails = json_encode($requestsParams);

            $formIntegrations = $integrationHandler->getAllIntegration('double-opt-in', 'double-opt-in');
            if (isset($formIntegrations->errors['result_empty'])) {
                $result = $integrationHandler->saveIntegration('entry confirmation', 'double-opt-in', $integrationDetails, 'double-opt-in', $status);
            } else {
                $result = $integrationHandler->updateIntegration($formIntegrations[0]->id, 'entry confirmation', 'double-opt-in', $integrationDetails, 'double-opt-in', $status);
            }
            wp_send_json_success($result, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitform'
                ),
                401
            );
        }
    }

    public function getDblOptin()
    {
        \ignore_user_abort();
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action'], $_REQUEST['status']);
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $formId = $requestsParams->formID;
            $ipTool = new IpTool();
            $user_details = $ipTool->getUserDetail();
            $integrationHandler = new IntegrationHandler($formId, $user_details);
            $formIntegrations = $integrationHandler->getAllIntegration('double-opt-in', 'double-opt-in');
            wp_send_json_success($formIntegrations, 200);
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitform'
                ),
                401
            );
        }
    }

    public function saveReport()
    {
        \ignore_user_abort();
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action'], $_REQUEST['status']);
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $reportDetails = $requestsParams->report;
            $formId = $requestsParams->formId;
            $reportId = $requestsParams->reportId;
            $ipTool = new IpTool();
            $user_details = $ipTool->getUserDetail();

            $reportsModel = new ReportsModel();
            $reportData = [
                "type" => 'table',
                "category" => 'form',
                "context" => $formId,
                "details" => is_string($reportDetails) ? $reportDetails : wp_json_encode($reportDetails),
                "isDefault" => 0,
                "user_id" => $user_details['id'],
                "user_ip" => $user_details['ip'],
                "user_device" => $user_details['device'],

            ];

            $existRprtId = $reportsModel->get(
                ['id'],
                [
                    'id' => $reportId,
                    'context' => $formId
                ]
            );

            if (!is_wp_error($existRprtId)) {
                $reportData['updated_at'] = $user_details['time'];
                $updated = $reportsModel->update($reportData, ['id' => $reportId]);

                if ($updated && !is_wp_error($updated)) {
                    wp_send_json_success([
                        'report_id' => $reportId,
                        'message' => 'Report upatated successfully !',
                    ], 200);
                } else {
                    wp_send_json_error([
                        'report_id' => $reportId,
                        'message' => 'Report update failed !',
                    ], 411);
                }
            } else {
                $reportData['created_at'] = $user_details['time'];
                $insertedId = $reportsModel->insert($reportData);

                if ($insertedId && !is_wp_error($insertedId)) {
                    wp_send_json_success([
                        'report_id' => $insertedId,
                        'message' => 'Report save successfully !',
                    ], 200);
                } elseif ($insertedId && is_wp_error($insertedId)) {
                    wp_send_json_error([
                        'message' => $insertedId->get_error_message(),
                    ], 411);
                }
            }
        } else {
            wp_send_json_error(
                [
                    'message' => 'Token expired',
                ],
                411
            );
        }
    }

    public function deleteReport()
    {
        \ignore_user_abort();
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            unset($_REQUEST['_ajax_nonce'], $_REQUEST['action'], $_REQUEST['status']);
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $reportId = $requestsParams->report_id;
            $reportsModel = new ReportsModel();
            $deletedId = $reportsModel->delete(
                array(
                    'id' => $reportId,
                )
            );
            if (!is_wp_error($deletedId)) {
                wp_send_json_success([
                    'message' => 'Report deleted successfully !',
                ], 200);
            } else {
                wp_send_json_error($deletedId->get_error_message(), 411);
            }
        }
    }


    public function instandFileUpload()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $uploadDirInfo = wp_upload_dir();
            $wpUploadbaseDir = $uploadDirInfo['basedir'];
            $tmpDir = $wpUploadbaseDir . DIRECTORY_SEPARATOR . 'bitforms' . DIRECTORY_SEPARATOR . 'temp';
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir);
            }

            $fieldKey = sanitize_text_field($_REQUEST['fieldKey']);
            $formID = sanitize_text_field($_REQUEST['formID']);
            $file_details = $_FILES[$fieldKey];

            $fileHandler = new FileHandler();
            $validation = $fileHandler->validation($fieldKey, $file_details, $formID);

            if (!empty($validation['error_type']) && !empty($validation['message'])) {
                wp_send_json_error(
                    __(
                        $validation['message'],
                        'bit-form'
                    ),
                    411
                );
            }

            $fileName = time() . '-' . sanitize_file_name($file_details['name']);
            $src = $file_details['tmp_name'];
            $destination = $tmpDir . DIRECTORY_SEPARATOR . $fileName;

            $uploaded = \move_uploaded_file($src, $destination);
            if ($uploaded) {
                $data = [
                    'file_name' => $fileName,
                    'path' => $destination,
                ];
                wp_send_json_success($data, 200);
            } else {
                $errorMsg = FileHandler::getFileUploadError($file_details['error']);
                wp_send_json_error(
                    __(
                        $errorMsg
                    ),
                    411
                );
            }
        } else {
            wp_send_json_error(
                __(
                    'Token expired',
                    'bitform'
                ),
                401
            );
        }
    }

    public function fileRemove()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $fileName = $_GET['file_name'];

            $uploadDirInfo = wp_upload_dir();
            $wpUploadbaseDir = $uploadDirInfo['basedir'];

            $tempFile = $wpUploadbaseDir . DIRECTORY_SEPARATOR . 'bitforms' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($tempFile)) {
                if (unlink($tempFile) !== true) {
                    wp_send_json_error(__(" Could not delete file because unknown file locat $tempFile"), 411);
                }
                wp_send_json_success(__("File deleted successfully"), 200);
            } else {
                wp_send_json_error(__('File not found'), 411);
            }
        } else {
            wp_send_json_error(__('Token expired'), 401);
        }
    }
}
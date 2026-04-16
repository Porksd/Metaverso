<?php

namespace BitCode\BitFormPro\API\Controller;

use WP_REST_Controller;
use BitCode\BitForm\Core\Database\FormModel;
use BitCode\BitFormPro\Core\Database\PaymentInfoModel;
use BitCode\BitForm\Core\Database\FormEntryMetaModel;
use BitCode\BitForm\Core\Util\Log;
use WP_REST_Request;

class PaymentController extends WP_REST_Controller
{
    protected $formModel;
    protected $formMetaModel;
    protected $paymentModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->formMetaModel = new FormEntryMetaModel();
        $this->paymentModel = new PaymentInfoModel();
    }

    public function handleTransactionCallback(WP_REST_Request $request)
    {
        $payment_type = $request['payment_type'];
        if ($payment_type == 'paypal') {
            $this->handlePayPalTransaction();
        } else if ($payment_type === 'stripe') {
            $this->handleStripeTransaction();
        } else if ($payment_type === 'mollie') {
            (new MolliePaymentController)->handleMollieTransaction();
        } else {
            Log::debug_log('Payment type not found');
        }
    }

    private function formatData($data)
    {
        $formattedData = [];
        foreach ($data as $value) {
            $value_arr = explode(':', $value);
            $formattedData[$value_arr[0]] = $value_arr[1];
        }
        return $formattedData;
    }

    private function handlePayPalTransaction()
    {
        $paypalDataString = file_get_contents('php://input');
        $paypalData = json_decode($paypalDataString);
        $description = $paypalData->resource->purchase_units[0]->description;
        $description_arr = explode(';', $description);
        $formattedData = $this->formatData($description_arr);
        $formId = $formattedData['form-id'];
        $entryId = $formattedData['entry-id'];
        $fieldKey = $formattedData['field-key'];
        $transactionId = $paypalData->resource->id;

        do_action('bitform_paypal_transaction_success', $formId, $entryId, $fieldKey, $paypalData);

        $existMetaData = $this->formMetaModel->isEntryMetaExist([
            'bitforms_form_entry_id' => $entryId,
            'meta_key' => $fieldKey,
            'meta_value' => $transactionId,
        ]);

        if (!$existMetaData) {
            $this->paymentModel->paymentInsert($formId, $transactionId, "paypal", $paypalData->resource_type, $paypalDataString);

            return $this->formMetaModel->insert([
                'bitforms_form_entry_id' => $entryId,
                'meta_key' => $fieldKey,
                'meta_value' => $transactionId,
            ]);
        }
    }
    private function handleStripeTransaction()
    {
        $stripeDataString = file_get_contents('php://input');
        $stripeData = json_decode($stripeDataString);
        $data = $stripeData->data->object;
        $formId = $data->metadata->formID;
        $entryId = $data->metadata->entryID;
        $fieldKey = $data->metadata->fieldKey;
        $transactionId = $data->id;

        do_action('bitform_stripe_transaction_success', $formId, $entryId, $fieldKey, $stripeData);

        $existMetaData = $this->formMetaModel->isEntryMetaExist([
            'bitforms_form_entry_id' => $entryId,
            'meta_key' => $fieldKey,
            'meta_value' => $transactionId,
        ]);

        if (!$existMetaData) {
            $this->paymentModel->paymentInsert($formId, $transactionId, "stripe", 'order', $stripeDataString);

            return $this->formMetaModel->insert([
                'bitforms_form_entry_id' => $entryId,
                'meta_key' => $fieldKey,
                'meta_value' => $transactionId,
            ]);
        }
    }
}
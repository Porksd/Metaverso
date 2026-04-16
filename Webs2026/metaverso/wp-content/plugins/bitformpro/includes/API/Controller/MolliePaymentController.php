<?php

namespace BitCode\BitFormPro\API\Controller;

use BitCode\BitForm\Core\Database\FormEntryMetaModel;
use BitCode\BitFormPro\Core\Database\PaymentInfoModel;
use BitCode\BitForm\Core\Integration\IntegrationHandler;
use BitCode\BitForm\Core\Util\HttpHelper;
use BitCode\BitForm\Core\Util\FieldValueHandler;
use WP_Error;

class MolliePaymentController
{
  protected $formMetaModel;

  protected $paymentModel;

  private const MOLLIE_API_ENDPOINT = 'https://api.mollie.com/v2/payments';

  public function __construct()
  {
    $this->paymentModel = new PaymentInfoModel();
    $this->formMetaModel = new FormEntryMetaModel();
  }

  private function getIntegrationDetails($paymentIntegId)
  {
    $integrationHandler = new IntegrationHandler(0);
    $integrationDetails = $integrationHandler->getAllIntegration('app', 'payments', '', $paymentIntegId);
    $integrationDetails = json_decode($integrationDetails[0]->integration_details);
    return $integrationDetails;
  }

  public function createPayment($request)
  {
    if (!$request) {
      wp_send_json_error('Request is required', 400);
    }
    $data = is_string($request) ? \json_decode($request) : $request;
    $payIntegID = $data->payIntegID;
    $amount = $data->amount;
    $currency = $data->currency;
    $meta = $data->metadata;
    $method = $data->method;
    $description = $data->description;
    $redirectUrl = $data->redirectUrl;
    $entryID = $meta->entryID;

    $integrationDetails = $this->getIntegrationDetails($payIntegID);
    $apiKey = $integrationDetails->apiKey;
    $redirectUrl = $redirectUrl . '?integID=' . $payIntegID;
    $webhook_url = get_rest_url() . 'bitform/v1/payments/mollie' . '?integID=' . $payIntegID;

    // get file value 
    $submittedValue = $this->getSubmittedValue($entryID);
    $replaceDescription = FieldValueHandler::replaceFieldWithValue($description, $submittedValue);

    $headers = [
      'Authorization' => 'Bearer ' . $apiKey,
      'Content-Type' => 'application/json',
    ];
    $data = [
      'amount' => [
        'currency' => $currency,
        'value' => $amount,
      ],
      'description' => $replaceDescription,
      'redirectUrl' => $redirectUrl,
      'webhookUrl' => $webhook_url,
      'metadata' => (array) $meta,
      'method' => $method,
    ];

    $jsonData = wp_json_encode($data);
    $apiResponse = HttpHelper::post(self::MOLLIE_API_ENDPOINT, $jsonData, $headers);

    if (isset($apiResponse->status) && is_int($apiResponse->status) && $apiResponse->status >= 400) {
      wp_send_json_error($apiResponse, $apiResponse->status);
    } else {
      $transactionId = $apiResponse->id;
      update_option('bf_mollie_transaction_id', $transactionId);
      wp_send_json_success($apiResponse, 200);
    }
  }
  public function handleMollieTransaction()
  {
    $mollieDataString = file_get_contents('php://input');
    $data = json_decode($mollieDataString);
    $transactionId = $data->id;
    $paymentIntegId = sanitize_text_field($_GET['integID']);

    if (!$paymentIntegId) {
      return new WP_Error('no_id', 'Payment Integration ID parameter is missing', array('status' => 400));
    }

    $mollieData = $this->getMolliePaymentInfo($paymentIntegId, $transactionId);
    $this->saveMolliePaymentInfo($mollieData);
  }

  public function saveMolliePaymentInfo($data)
  {
    $transactionId = $data->id;
    $formId = $data->metadata->formID;
    $entryId = $data->metadata->entryID;
    $fieldKey = $data->metadata->fieldKey;
    $mollieDataString = wp_json_encode($data);

    do_action('bitform_mollie_transaction_success', $formId, $entryId, $fieldKey, $mollieDataString);

    $existMetaData = $this->formMetaModel->isEntryMetaExist([
      'bitforms_form_entry_id' => $entryId,
      'meta_key' => $fieldKey,
      'meta_value' => $transactionId,
    ]);

    if (!$existMetaData) {

      $this->formMetaModel->insert([
        'bitforms_form_entry_id' => $entryId,
        'meta_key' => $fieldKey,
        'meta_value' => $transactionId,
      ]);
      return $this->paymentModel->paymentInsert($formId, $transactionId, "mollie", 'order', $mollieDataString);

    }
  }

  public function getMolliePaymentInfo($paymentIntegId, $transactionID)
  {
    $endpoint = self::MOLLIE_API_ENDPOINT . '/' . $transactionID;

    $integrationDetails = $this->getIntegrationDetails($paymentIntegId);

    $apiKey = $integrationDetails->apiKey;

    $headers = [
      'Authorization' => 'Bearer ' . $apiKey,
      'Content-Type' => 'application/json',
    ];

    $apiResponse = HttpHelper::get($endpoint, '', $headers);

    if (isset($apiResponse->status) && is_int($apiResponse->status) && $apiResponse->status >= 400) {
      return new WP_Error('api_error', $apiResponse->detail, array('status' => $apiResponse->status));
    } else {
      return $apiResponse;
    }
  }


  public function getSubmittedValue($entryId)
  {
    $formEntryMeta = $this->formMetaModel->get(
      [
        'meta_key',
        'meta_value',
      ],
      [
        'bitforms_form_entry_id' => $entryId,
      ]
    );
    $entries = [];
    foreach ($formEntryMeta as $key => $value) {
      $entries[$value->meta_key] = $value->meta_value;
    }
    return $entries;
  }

}
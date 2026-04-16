<?php

/**
 * SendFox Record Api
 */
namespace BitCode\BitFormPro\Integration\SendFox;

use BitCode\BitForm\Core\Util\ApiResponse as UtilApiResponse;
use BitCode\BitForm\Core\Util\HttpHelper;

/**
 * Provide functionality for Record insert, upsert
 */
class RecordApiHelper
{
    private $_integrationID;
    private $logID;

    private $_integrationDetails;

    private $_logResponse;

    private $_entryID;

    public function __construct($auth_token, $integId, $logID, $entryID)
    {
        $this->_integrationDetails = $auth_token;
        $this->_integrationID = $integId;
        $this->_logResponse = new UtilApiResponse();
        $this->logID = $logID;
        $this->_entryID = $entryID;
    }

    public function addContact($access_token, $listId, $finalData)
    {
        $apiEndpoints = 'https://api.sendfox.com/contacts';
        $listId = explode(',', $listId);
        $header = [
            'Authorization' => "Bearer {$access_token}",
            'Accept' => 'application/json',
        ];

        $data = [
            'email' => $finalData['email'],
            'first_name' => $finalData['first_name'],
            'last_name' => $finalData['last_name'],
            'lists' => $listId,
        ];

        return HttpHelper::post($apiEndpoints, $data, $header);
    }

    public function createContactList($access_token, $finalData)
    {
        $apiEndpoints = 'https://api.sendfox.com/lists';

        $header = [
            'Authorization' => "Bearer {$access_token}",
            'Accept' => 'application/json',
        ];

        $data = [
            'name' => $finalData['name'],
        ];

        return HttpHelper::post($apiEndpoints, $data, $header);
    }

    public function generateReqDataFromFieldMap($data, $fieldMap)
    {
        $dataFinal = [];

        foreach ($fieldMap as $key => $value) {
            $triggerValue = $value->formField;
            $actionValue = $value->sendFoxFormField;
            if ($triggerValue === 'custom') {
                $dataFinal[$actionValue] = $value->customValue;
            } elseif (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }
        return $dataFinal;
    }

    public function generateListReqDataFromFieldMap($data, $fieldMap)
    {
        $dataFinal = [];

        foreach ($fieldMap as $key => $value) {
            $triggerValue = $value->formField;
            $actionValue = $value->sendFoxListFormField;
            if ($triggerValue === 'custom') {
                $dataFinal[$actionValue] = $value->customValue;
            } elseif (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }
        return $dataFinal;
    }

    public function generateReqUnsubscribeDataFromFieldMap($data, $fieldMap)
    {
        $dataFinal = [];

        foreach ($fieldMap as $key => $value) {
            $triggerValue = $value->formField;
            $actionValue = $value->sendFoxUnsubscribeFormField;
            if ($triggerValue === 'custom') {
                $dataFinal[$actionValue] = $value->customValue;
            } elseif (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }
        return $dataFinal;
    }

    public function unsubscribeContact($access_token, $finalData)
    {
        $apiEndpoints = 'https://api.sendfox.com/unsubscribe';

        $header = [
            'Authorization' => "Bearer {$access_token}",
            'Accept' => 'application/json',
        ];

        $data = [
            'email' => $finalData['email'],
        ];
        return HttpHelper::request($apiEndpoints, 'PATCH', $data, $header);
    }

    public function execute(
        $listId,
        $mainAction,
        $fieldValues,
        $fieldMap,
        $access_token,
        $integrationDetails
    ) {
        $fieldData = [];
        $apiResponse = null;
        if ($integrationDetails->mainAction === '1') {
            $type_name = 'Create List';
            $finalData = $this->generateListReqDataFromFieldMap($fieldValues, $integrationDetails->field_map_list);
            $apiResponse = $this->createContactList($access_token, $finalData);

            if (property_exists($apiResponse, 'id')) {
                $this->_logResponse->apiResponse($this->logID, $this->_integrationID, ['type' => 'record', 'type_name' => $type_name], 'success', $apiResponse);
            } else {
                $this->_logResponse->apiResponse($this->logID, $this->_integrationID, ['type' => 'record', 'type_name' => $type_name], 'error', $apiResponse);
            }
        }
        if ($integrationDetails->mainAction === '2') {
            $type_name = 'Create Contact';
            $finalData = $this->generateReqDataFromFieldMap($fieldValues, $fieldMap);
            $apiResponse = $this->addContact($access_token, $listId, $finalData);
            if (property_exists($apiResponse, 'errors')) {
                $this->_logResponse->apiResponse($this->logID, $this->_integrationID, ['type' => 'record', 'type_name' => $type_name], 'error', $apiResponse);
            } else {
                $this->_logResponse->apiResponse($this->logID, $this->_integrationID, ['type' => 'record', 'type_name' => $type_name], 'success', $apiResponse);
            }
        }

        if ($integrationDetails->mainAction === '3') {
            $type_name = 'Unsubscribe';
            $finalData = $this->generateReqUnsubscribeDataFromFieldMap($fieldValues, $integrationDetails->field_map_unsubscribe);
            $apiResponse = $this->unsubscribeContact($access_token, $finalData);
            if (property_exists($apiResponse, 'id')) {
                $this->_logResponse->apiResponse($this->logID, $this->_integrationID, ['type' => 'record', 'type_name' => $type_name], 'success', $apiResponse);
            } else {
                $this->_logResponse->apiResponse($this->logID, $this->_integrationID, ['type' => 'record', 'type_name' => $type_name], 'error', $apiResponse);
            }
        }

        return $apiResponse;
    }
}
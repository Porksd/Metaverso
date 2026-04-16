<?php
namespace BitCode\BitFormPro\Integration\Acumbamail;

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

    public function addSubscriber($auth_token, $listId, $finalData)
    {
        $apiEndpoints = 'https://acumbamail.com/api/1/addSubscriber/';
        $header = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        $requestParams = [
            'auth_token' => $auth_token,
            'list_id' => $listId,
            'welcome_email' => 1,
            'update_subscriber' => 1,
            'merge_fields[EMAIL]' => $finalData['email'],

        ];
        foreach ($finalData as $key => $value) {
            if ($key != 'email') {
                $requestParams['merge_fields[' . $key . ']'] = $value;
            }
        }
        return HttpHelper::post($apiEndpoints, $requestParams, $header);
    }

    public function deleteSubscriber($auth_token, $listId, $finalData)
    {
        $apiEndpoints = 'https://acumbamail.com/api/1/deleteSubscriber/';

        $header = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        $requestParams = [
            'auth_token' => $auth_token,
            'list_id' => $listId,
            'email' => $finalData['email'],
        ];

        return HttpHelper::post($apiEndpoints, $requestParams, $header);
    }

    public function generateReqDataFromFieldMap($data, $fieldMap)
    {
        $dataFinal = [];

        foreach ($fieldMap as $key => $value) {
            $triggerValue = $value->formField;
            $actionValue = $value->acumbamailFormField;
            if ($triggerValue === 'custom') {
                $dataFinal[$actionValue] = $value->customValue;
            } elseif (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }
        return $dataFinal;
    }

    public function execute(
        $listId,
        $mainAction,
        $defaultDataConf,
        $fieldValues,
        $fieldMap,
        $auth_token
    ) {
        $finalData = $this->generateReqDataFromFieldMap($fieldValues, $fieldMap);
        $apiResponse = null;
        $type = null;
        if ($mainAction === '1') {
            $apiResponse = $this->addSubscriber($auth_token, $listId, $finalData);
            $type = 'add subscriber';
        } elseif ($mainAction === '2') {
            $apiResponse = $this->deleteSubscriber($auth_token, $listId, $finalData);
            $type = 'delete subscriber';
        }

        if (property_exists($apiResponse, 'errors')) {
            $this->_logResponse->apiResponse($this->logID, $this->_integrationID, ['type' => 'record', 'type_name' => $type], 'errors', $apiResponse);
        } else {
            $this->_logResponse->apiResponse($this->logID, $this->_integrationID, ['type' => 'record', 'type_name' => $type], 'success', $apiResponse);
        }
        return $apiResponse;
    }
}
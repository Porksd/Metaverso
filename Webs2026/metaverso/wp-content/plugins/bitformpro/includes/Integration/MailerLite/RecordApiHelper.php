<?php

/**
 * MailerLite    Record Api
 */


namespace BitCode\BitFormPro\Integration\MailerLite;

use BitCode\BitForm\Core\Util\HttpHelper;
use BitCode\BitForm\Core\Util\ApiResponse as UtilApiResponse;
use BitCode\BitForm\Core\Database\FormEntryLogModel;

/**
 * Provide functionality for Record insert, upsert
 */
class RecordApiHelper
{
    private $_integrationID;
    private $baseUrl = 'https://api.mailerlite.com/api/v2/';

    private $_defaultHeader;

    private $_logID;

    private $_logResponse;

    private $_entryID;

    private $_actions;

    public function __construct($auth_token, $integId, $logID, $entryID, $actions)
    {
        $this->_integrationID = $integId;
        $this->_defaultHeader = [
            'X-Mailerlite-Apikey' => $auth_token
        ];
        $this->_logID = $logID;
        $this->_logResponse = new UtilApiResponse();
        $this->_entryID = $entryID;
        $this->_actions=$actions;
    }

    public function existSubscriber($auth_token, $email)
    {
        if (empty($auth_token)) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }

        $apiEndpoints = $this->baseUrl . "subscribers/$email";

        $response = HttpHelper::get($apiEndpoints, null, $this->_defaultHeader);
        if (property_exists($response, 'error')) {
            return false;
        } else {
            return true;
        }
    }

    public function enableDoubleOptIn($auth_token)
    {
        if (empty($auth_token)) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bit-integrations'
                ),
                400
            );
        }

        $apiEndpoints = $this->baseUrl . 'settings/double_optin';
        $requestParams = [
            'enable' => true
        ];

        HttpHelper::post($apiEndpoints, $requestParams, $this->_defaultHeader);
    }

    public function addSubscriber($auth_token, $groupIds, $type, $finalData)
    {
        $apiEndpoints = $this->baseUrl . 'subscribers';
        $splitGroupIds = null;
        if (!empty($groupIds)) {
            $splitGroupIds = explode(',', $groupIds);
        }

        if (empty($finalData['email'])) {
            return ['success'=>false, 'message'=>'Required field Email is empty', 'code'=>400];
        }

        $requestParams = [
            'email' => $finalData['email'],
            'type' => $type ? $type : 'active',
        ];

        foreach ($finalData as $key => $value) {
            if ($key !== 'email') {
                if ($key === 'name') {
                    $requestParams[$key] = $value;
                } else {
                    $requestParams['fields'][$key] = $value;
                }
            }
        }
        $requestParams['fields'] =  !empty($requestParams['fields']) ? (object) $requestParams['fields']: [];
        $email = $finalData['email'];
        $isExist = $this->existSubscriber($auth_token, $email);
        $response = null;
        if ($isExist && !empty($this->_actions->update)) {
            if (!empty($this->_actions->double_opt_in)) {
                $this->enableDoubleOptIn($auth_token);
            }
            if (!empty($groupIds)) {
                for ($i = 0; $i < count($splitGroupIds); $i++) {
                    $apiEndpoints = $this->baseUrl . 'groups/' . $splitGroupIds[$i] . '/subscribers';
                    $response = HttpHelper::post($apiEndpoints, $requestParams, $this->_defaultHeader);
                };
            }
            $response = HttpHelper::post($apiEndpoints, $requestParams, $this->_defaultHeader);
            $response->update=true;
        } elseif ($isExist && empty($this->_actions->update)) {
            return ['success'=>false, 'message'=>'Subscriber already exist', 'code'=>400];
        } else {
            if (!empty($this->_actions->double_opt_in)) {
                $this->enableDoubleOptIn($auth_token);
            }
            if (!empty($groupIds)) {
                for ($i = 0; $i < count($splitGroupIds); $i++) {
                    $apiEndpoints = $this->baseUrl . 'groups/' . $splitGroupIds[$i] . '/subscribers';
                    $response = HttpHelper::post($apiEndpoints, $requestParams, $this->_defaultHeader);
                };
                return $response;
            }

            $response = HttpHelper::post($apiEndpoints, $requestParams, $this->_defaultHeader);
        }
        return $response;
    }

    public function generateReqDataFromFieldMap($data, $fieldMap)
    {
        $dataFinal = [];

        foreach ($fieldMap as $key => $value) {
            $triggerValue = $value->formField;
            $actionValue = $value->mailerLiteFormField;
            if ($triggerValue === 'custom') {
                $dataFinal[$actionValue] = $value->customValue;
            } elseif (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }

        return $dataFinal;
    }

    public function executeRecordApi(
        $groupId,
        $type,
        $fieldValues,
        $fieldMap,
        $auth_token
    ) {
        $fieldData = [];
        $attributes = [];
        $model = new FormEntryLogModel();
        $fieldData['attributes'] = (object) $attributes;

    
        $recordApiResponse = null;
        if ($this->_entryID) {
            $result = $model->entryLogCheck($this->_entryID, $this->_integrationID);
            if (!count($result) || isset($result->errors['result_empty'])) {
                $finalData = $this->generateReqDataFromFieldMap($fieldValues, $fieldMap);
                $recordApiResponse = $this->addSubscriber($auth_token, $groupId, $type, $finalData);
          
                if (empty($recordApiResponse)) {
                    $recordApiResponse = ['success' => true, 'id' => $fieldData['email']];
                }
            }
        }

        if ($recordApiResponse->id) {
            $res=['success'=>true, 'message'=>isset($recordApiResponse->update) ? 'Subscriber has been updated successfully':'Subscriber has been created successfully', 'code'=>200];
            $this->_logResponse->apiResponse($this->_logID, $this->_integrationID, ['type' =>  'record', 'type_name' =>isset($recordApiResponse->update) ? 'update':'insert'], 'success', $res);
        } else {
            $this->_logResponse->apiResponse($this->_logID, $this->_integrationID, ['type' =>  'record', 'type_name' => isset($recordApiResponse->update) ? 'update':'insert'], 'error', $recordApiResponse);
        }
        return $recordApiResponse;
    }
}
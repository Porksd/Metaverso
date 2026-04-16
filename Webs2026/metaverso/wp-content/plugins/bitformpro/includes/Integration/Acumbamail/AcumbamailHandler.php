<?php

namespace BitCode\BitFormPro\Integration\Acumbamail;

use WP_Error;
use BitCode\BitForm\Core\Util\HttpHelper;
use BitCode\BitFormPro\Integration\Acumbamail\RecordApiHelper as AcumbamailRecordApiHelper;
use BitCode\BitForm\Core\Integration\IntegrationHandler;
use BitCode\BitForm\Core\Util\ApiResponse;
use BitCode\BitForm\Core\Util\IpTool;


/**
 * Provide functionality for Acumbamail integration
 */
class AcumbamailHandler
{
    private $formID;
    private $integrationID;
    static $baseUrl = 'https://acumbamail.com/api/1/';

    public function __construct($integrationID, $fromID)
    {
        $this->formID = $fromID;
        $this->integrationID = $integrationID;
    }


    public static function registerAjax()
    {
        add_action('wp_ajax_bitforms_acumbamail_authorization_and_fetch_subscriber_list', array(__CLASS__, 'acumbamailAuthAndFetchSubscriberList'));
        add_action('wp_ajax_bitforms_acumbamail_fetch_all_list', array(__CLASS__, 'fetchAllLists'));
        add_action('wp_ajax_bitforms_acumbamail_refresh_fields', array(__CLASS__, 'acumbamailRefreshFields'));
    }



    public static function fetchAllLists()
    {
        if (!isset($_REQUEST['_ajax_nonce']) && !wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bitforms_save')) {
            wp_send_json_error(__('Token expired', 'bitformpro'), 401);
        }

        $inputJSON = file_get_contents('php://input');
        $requestParams = json_decode($inputJSON);


        if (empty($requestParams->auth_token)) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bitformpro'
                ),
                400
            );
        }

        $apiEndpoints = self::$baseUrl . 'getLists/';
        $requestParams = [
            'auth_token' => $requestParams->auth_token,
        ];

        $response = HttpHelper::post($apiEndpoints, $requestParams);

        if ($response !== 'Unauthorized') {
            wp_send_json_success($response, 200);
        } else {
            wp_send_json_error(
                'The token is invalid',
                400
            );
        }
    }

    public static function acumbamailAuthAndFetchSubscriberList()
    {
        if (!isset($_REQUEST['_ajax_nonce']) && !wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bitforms_save')) {
            wp_send_json_error(__('Token expired', 'bitformpro'), 401);
        }

        $inputJSON = file_get_contents('php://input');
        $requestParams = json_decode($inputJSON);
        if (empty($requestParams->auth_token)) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bitformpro'
                ),
                400
            );
        }
        $apiEndpoints = self::$baseUrl . 'getLists/';

        $queryParams = [
            'auth_token' => $requestParams->auth_token,
        ];

        $response = HttpHelper::post($apiEndpoints, $queryParams, null);

        if ($response !== 'Unauthorized') {
            wp_send_json_success($response, 200);
        } else {
            wp_send_json_error(
                'The token is invalid',
                400
            );
        }
    }

    public static function acumbamailRefreshFields()
    {
        if (!isset($_REQUEST['_ajax_nonce']) && !wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bitforms_save')) {
            wp_send_json_error(__('Token expired', 'bitformpro'), 401);
        }

        $inputJSON = file_get_contents('php://input');
        $refreshFieldsRequestParams = json_decode($inputJSON);

        if (empty($refreshFieldsRequestParams->auth_token) || empty($refreshFieldsRequestParams->list_id)) {
            wp_send_json_error(
                __(
                    'Requested parameter is empty',
                    'bitformpro'
                ),
                400
            );
        }
        $apiEndpoints = self::$baseUrl . 'getFields/';

        $requestParams = [
            'auth_token' => $refreshFieldsRequestParams->auth_token,
            'list_id' => $refreshFieldsRequestParams->list_id,
        ];

        $response = HttpHelper::post($apiEndpoints, $requestParams);

        $formattedResponse = [];
        foreach ($response as $key => $value) {
            if ($key === 'email') {
                $formattedResponse[$key] = [
                    $key => $value,
                    'required' => true,
                ];
            } else {
                $formattedResponse[$key] = [
                    $key => $value,
                    'required' => false,
                ];
            }
        }



        if ($response !== 'Unauthorized') {
            wp_send_json_success($formattedResponse, 200);
        } else {
            wp_send_json_error(
                'The token is invalid',
                400
            );
        }
    }


    public function execute(IntegrationHandler $integrationHandler, $integrationData, $fieldValues, $entryID, $logID)
    {
        $integrationDetails = json_decode($integrationData->integration_details);
        $auth_token = $integrationDetails->auth_token;
        $listId = $integrationDetails->listId;
        $mainAction = $integrationDetails->mainAction;
        $fieldMap = $integrationDetails->field_map;
        $defaultDataConf = $integrationDetails->default;

        if (
            empty($listId)
            || empty($fieldMap)
            || empty($auth_token)
        ) {
            return new WP_Error('REQ_FIELD_EMPTY', __('module, fields are required for Acumbamail api', 'bitformpro'));
        }
        $recordApiHelper = new RecordApiHelper($auth_token, $this->integrationID, $logID, $entryID);
        $acumbamailApiResponse = $recordApiHelper->execute(
            $listId,
            $mainAction,
            $defaultDataConf,
            $fieldValues,
            $fieldMap,
            $auth_token
        );

        if (is_wp_error($acumbamailApiResponse)) {
            return $acumbamailApiResponse;
        }
        return $acumbamailApiResponse;
    }
}

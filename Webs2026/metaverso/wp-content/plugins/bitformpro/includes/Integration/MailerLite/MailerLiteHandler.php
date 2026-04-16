<?php

/**
 * MailerLite Integration
 */

namespace BitCode\BitFormPro\Integration\MailerLite;

use WP_Error;
use BitCode\BitForm\Core\Util\HttpHelper;
use BitCode\BitForm\Core\Integration\IntegrationHandler;
use BitCode\BitFormPro\Integration\MailerLite\RecordApiHelper;

/**
 * Provide functionality for MailerLite integration
 */
class MailerLiteHandler
{
    private $_integrationID;
    private static $_baseUrl = 'https://api.mailerlite.com/api/v2/';
    protected $_defaultHeader;

    private $_formID;
    
    public function __construct($integrationID, $fromID)
    {
        $this->_formID = $fromID;
        $this->_integrationID = $integrationID;
    }
    
    public static function registerAjax()
    {
        add_action('wp_ajax_bitforms_mailerlite_fetch_all_groups', array(__CLASS__, 'fetchAllGroups'));
        add_action('wp_ajax_bitforms_mailerlite_refresh_fields', array(__CLASS__, 'mailerliteRefreshFields'));
    }
    public static function fetchAllGroups()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $requestParams = json_decode($inputJSON);
            if (
              empty($requestParams->auth_token)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bitform'
                    ),
                    400
                );
            }

            $apiEndpoints = self::$_baseUrl . 'groups/';

            $header = [
            'X-Mailerlite-Apikey' => $requestParams->auth_token,
        ];

            $response = HttpHelper::get($apiEndpoints, null, $header);
            $formattedResponse = [];

            foreach ($response as $value) {
                $formattedResponse[] =
            [
                'group_id' => $value->id,
                'name' => $value->name,
            ];
            }
        
            wp_send_json_success($formattedResponse, 200);
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

    public static function mailerliteRefreshFields()
    {
        if (isset($_REQUEST['_ajax_nonce']) && wp_verify_nonce($_REQUEST['_ajax_nonce'], 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $requestParams = json_decode($inputJSON);
      
            if (
              empty($requestParams->auth_token)
            ) {
                wp_send_json_error(
                    __(
                        'Requested parameter is empty',
                        'bitform'
                    ),
                    400
                );
            }
            $apiEndpoints = self::$_baseUrl . 'fields';

            $apiKey = $requestParams->auth_token;
            $header = [
            'X-Mailerlite-Apikey' => $apiKey,
        ];

            $response = HttpHelper::get($apiEndpoints, null, $header);
   
         
            $formattedResponse = [];
            foreach ($response as $value) {
                $formattedResponse[] = [
                'key' => $value->key,
                'label' => $value->title,
                'required' => $value->key === 'email' ? true : false,
            ];
            }
         
            if (isset($response->error->message) && $response->error->message === 'Unauthorized') {
                wp_send_json_error(
                    __(
                        'Invalid API Token',
                        'bitform'
                    ),
                    401
                );
            }
            if (count($response) > 0) {
                wp_send_json_success($formattedResponse, 200);
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
   

    public function execute(IntegrationHandler $integrationHandler, $integrationData, $fieldValues, $entryID, $logID)
    {
        $integrationDetails = is_string($integrationData->integration_details) ? json_decode($integrationData->integration_details) : $integrationData->integration_details;
        $auth_token = $integrationDetails->auth_token;
        $groupIds = $integrationDetails->group_ids;
        $fieldMap = $integrationDetails->field_map;
        $type=$integrationDetails->mailer_lite_type;
        $actions=$integrationDetails->actions;
  
        if (
           empty($fieldMap)
            || empty($auth_token)
        ) {
            return new WP_Error('REQ_FIELD_EMPTY', __('module, fields are required for MailerLite api', 'bit-integrations'));
        }
        $recordApiHelper = new RecordApiHelper($auth_token, $this->_integrationID, $logID, $entryID, $actions);
        $mailerliteApiResponse = $recordApiHelper->executeRecordApi(
            $groupIds,
            $type,
            $fieldValues,
            $fieldMap,
            $auth_token
        );

        if (is_wp_error($mailerliteApiResponse)) {
            return $mailerliteApiResponse;
        }
        return $mailerliteApiResponse;
    }
}
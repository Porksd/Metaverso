<?php
namespace BitCode\BitFormPro\Integration\ElasticEmail;

use BitCode\BitForm\Core\Util\ApiResponse as UtilApiResponse;
use BitCode\BitForm\Core\Util\HttpHelper;

class RecordApiHelper {
    private $_defaultHeader;
    private $_integrationID;
    private $_logResponse;
    private $integrationDetails;
    private $logID;

    public function __construct( $api_key, $integId, $logID, $integrationDetails ) {
        $this->integrationDetails = $integrationDetails;
        $this->_defaultHeader['Content-Type'] = 'application/json';
        $this->_defaultHeader['Authorization'] = "Bearer $api_key";
        $this->_integrationID = $integId;
        $this->_logResponse = new UtilApiResponse();
        $this->logID = $logID;
    }

    public function createContact( $data, $listName, $apiKey ) {
        $tmpData = \is_string( $data ) ? $data : \json_encode( [(object) $data] );
        $header = [
            'X-ElasticEmail-ApiKey' => $apiKey,
            'Content-Type'          => 'application/json',
        ];

        $insertRecordEndpoint = "https://api.elasticemail.com/v4/contacts?$listName";
        return HttpHelper::post( $insertRecordEndpoint, $tmpData, $header );
    }

    public function generateReqDataFromFieldMap( $data, $fieldMap ) {
        $dataFinal = [];

        foreach ( $fieldMap as $key => $value ) {
            $triggerValue = $value->formField;
            $actionValue = $value->elasticEmailField;
            if ( $triggerValue === 'custom' ) {
                $dataFinal[$actionValue] = $value->customValue;
            } elseif ( !is_null( $data[$triggerValue] ) ) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }
        if ( $this->integrationDetails->actions ) {
            if ( property_exists( $this->integrationDetails->actions, 'status' ) && $this->integrationDetails->actions->status ) {
                $dataFinal['Status'] = $this->integrationDetails->status;
            }
        }
        return $dataFinal;
    }

    public function execute( $integId, $fieldValues, $fieldMap, $integrationDetails ) {
        $finalData = $this->generateReqDataFromFieldMap( $fieldValues, $fieldMap );
        $listName = $integrationDetails->list_id;
        $query = '';

        foreach ( $listName as $key => $val ) {
            $query .= 'listnames=' . $val . '&';
        };
        if ( strlen( $query ) ) {
            $query = substr( $query, 0, -1 );
        }
        $api_key = $integrationDetails->api_key;
        $apiResponse = $this->createContact( $finalData, $query, $api_key );

        if ( isset( $apiResponse->Error ) ) {
            $this->_logResponse->apiResponse( $this->logID, $integId, ['type' => 'contact', 'type_name' => 'contact_add'], 'errors', $apiResponse );
        } else {
            $this->_logResponse->apiResponse( $this->logID, $integId, ['type' => 'contact', 'type_name' => 'contact_add'], 'success', $apiResponse );
        }
        return $apiResponse;
    }
}
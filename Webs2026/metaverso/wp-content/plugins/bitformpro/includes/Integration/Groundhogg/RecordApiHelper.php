<?php

/**
 * Groundhogg Record Api
 */

namespace BitCode\BitFormPro\Integration\Groundhogg;

use BitCode\BitForm\Core\Util\ApiResponse as UtilApiResponse;
use BitCode\BitForm\Core\Util\HttpHelper;

/**
 * Provide functionality for Record insert, upsert
 */
class RecordApiHelper
{
    private $integrationID;
    private $logID;
    private $entryID;

    private $_logResponse;

    public function __construct($integId, $logID, $entryID)
    {
        $this->integrationID = $integId;
        $this->_logResponse = new UtilApiResponse();
        $this->logID = $logID;
        $this->entryID = $entryID;
    }

    public function generateReqDataFromFieldMap($data, $fieldMap)
    {
        $dataFinal = [];
        foreach ($fieldMap as $key => $value) {
            $triggerValue = $value->formField;
            $actionValue = $value->GroundhoggMapField;
            if ($triggerValue === 'custom') {
                $dataFinal[$actionValue] = $value->customValue;
            } elseif (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            }
        }
        return $dataFinal;
    }

    public function generateMetaDataFromFieldMap($data, $fieldMap)
    {
        $dataFinal = [];
        foreach ($fieldMap as $key => $value) {
            $triggerValue = $value->formField;
            $actionValue = $value->GroundhoggMetaMapField;

            if ($triggerValue === 'custom') {
                $triggerValue = $value->customMetaFormValue;
            }

            if ($actionValue === 'custom') {
                $actionValue = $value->customMetaGroundValue;
            }

            if (!is_null($data[$triggerValue])) {
                $dataFinal[$actionValue] = $data[$triggerValue];
            } else {
                $dataFinal[$actionValue] = $triggerValue;
            }
        }
        return $dataFinal;
    }

    public static function createContact($finalData, $finalReorganizedTags, $integrationDetails)
    {
        if (empty($integrationDetails->token) || empty($integrationDetails->public_key) || empty($integrationDetails->domainName)) {
            wp_send_json_error(
                __(
                    'Request parameter is empty',
                    'bitformpro'
                ),
                400
            );
        }

        $authorizationHeader = [
            'gh-token' => $integrationDetails->token,
            'gh-public-key' => $integrationDetails->public_key
        ];

        $apiEndpoint = $integrationDetails->domainName . '/wp-json/gh/v3/contacts';
        return HttpHelper::post($apiEndpoint, $finalData, $authorizationHeader);
    }

    public static function createTag($diffTags, $integrationDetails)
    {
        if (empty($integrationDetails->token) || empty($integrationDetails->public_key) || empty($integrationDetails->domainName)) {
            wp_send_json_error(
                __(
                    'Request parameter is empty',
                    'bitformpro'
                ),
                400
            );
        }

        $authorizationHeader = [
            'gh-token' => $integrationDetails->token,
            'gh-public-key' => $integrationDetails->public_key
        ];

        $apiEndpoint = $integrationDetails->domainName . '/wp-json/gh/v3/tags';
        return HttpHelper::post($apiEndpoint, $diffTags, $authorizationHeader);
    }

    public static function checkExitsTagsOrCreate($integrationDetails, $finalReorganizedTags)
    {
        $authorizationParams = [
            'gh-token' => $integrationDetails->token,
            'gh-public-key' => $integrationDetails->public_key
        ];
        $exitsTags = [];

        $apiEndpoint = $integrationDetails->domainName . '/wp-json/gh/v3/tags';
        $apiResponse = HttpHelper::get($apiEndpoint, null, $authorizationParams);
        if ($apiResponse->status === 'success') {
            $tags = $apiResponse->tags;
            foreach ($tags as $tag) {
                array_push($exitsTags, $tag->tag_name);
            }
        } else {
            return null;
        };
        $diffTags['tags'] = array_diff($finalReorganizedTags, $exitsTags);
        if ($diffTags) {
            self::createTag($diffTags, $integrationDetails);
        }
    }

    public static function addTagsToExitsUser($addTagsToUser, $integrationDetails, $addTagToEmail)
    {
        $authorizationParams = [
            'gh-token' => $integrationDetails->token,
            'gh-public-key' => $integrationDetails->public_key
        ];
        $prePraperData = [
            'id_or_email' => $addTagToEmail,
            'tags' => $addTagsToUser,
        ];
        $apiEndpoint = $integrationDetails->domainName . '/wp-json/gh/v3/contacts/apply_tags';
        return HttpHelper::request($apiEndpoint, 'PUT', $prePraperData, $authorizationParams);
    }

    public function execute(
        $mainAction,
        $defaultDataConf,
        $fieldValues,
        $fieldMap,
        $public_key,
        $token,
        $actions,
        $integrationDetails
    ) {
        $mainAction = $integrationDetails->mainAction;
        $fieldData = [];
        $finalData = $this->generateReqDataFromFieldMap($fieldValues, $fieldMap);
        // 1 = create contact with tag
        $apiResponse = null;
        $finalReorganizedTags = null;
        if ($mainAction === '1') {
            if ($integrationDetails->showMeta) {
                $fieldMapMeta = $integrationDetails->field_map_meta;
                $metaData = $this->generateMetaDataFromFieldMap($fieldValues, $fieldMapMeta);
                $finalData['meta'] = $metaData;
            }
            if ($actions->tags) {
                $finalReorganizedTags = [];
                $tags = explode(',', $actions->tags);
                foreach ($tags as $tag) {
                    if (isset($fieldValues[$tag])) {
                        $finalReorganizedTags[] = $fieldValues[$tag];
                    } else {
                        $sanitize = ltrim($tag, 'ground-');
                        $finalReorganizedTags[] = $sanitize;
                    }
                };
                $finalData['tags'] = $finalReorganizedTags;
            }
            $this->checkExitsTagsOrCreate($integrationDetails, $finalReorganizedTags);
            $apiResponse = $this->createContact($finalData, $finalReorganizedTags, $integrationDetails);
        }
        // 2 = add tag to contact
        $apiResponseError = null;
        $apiResponseSuccess = null;
        if ($mainAction === '2') {
            $addTagsToUser = [];
            $addTagToEmails = [];
            $allSelectedEmails = explode(',', $integrationDetails->emailAddress);
            foreach ($allSelectedEmails as $emailAddress) {
                // $addTagToEmails[] = $fieldValues[$emailAddress];
                array_push($addTagToEmails, $fieldValues[$emailAddress]);
            }

            if ($integrationDetails->addTagToUser) {
                $tags = explode(',', $integrationDetails->addTagToUser);
                foreach ($tags as $tag) {
                    if ($fieldValues[$tag]) {
                        $addTagsToUser[] = $fieldValues[$tag];
                    } else {
                        $sanitize = ltrim($tag, 'ground-');
                        $addTagsToUser[] = $sanitize;
                    }
                };
                $finalData['tags'] = $addTagsToUser;
            }

            $this->checkExitsTagsOrCreate($integrationDetails, $addTagsToUser);
            foreach ($addTagToEmails as $addTagToEmail) {
                $apiResponse = $this->addTagsToExitsUser($addTagsToUser, $integrationDetails, $addTagToEmail);
                if (property_exists($apiResponse, 'code')) {
                    $apiResponseError[$addTagToEmail] = $apiResponse;
                } else {
                    $apiResponseSuccess[$addTagToEmail] = $apiResponse;
                }
            }
        }

        if ($mainAction === '1') {
            if (property_exists($apiResponse, 'errors')) {
                $this->_logResponse->apiResponse($this->logID, $this->integrationID, ['type' => 'record', 'type_name' => 'Add Contact'], 'errors', $apiResponse);
            } else {
                $this->_logResponse->apiResponse($this->logID, $this->integrationID, ['type' => 'record', 'type_name' => 'Add Contact'], 'success', $apiResponse);
            }
        }
        if ($mainAction === '2') {
            if (!empty($apiResponseError)) {
                $this->_logResponse->apiResponse($this->logID, $this->integrationID, ['type' => 'record', 'type_name' => 'Add Tags'], 'error', $apiResponse);
            }
            if (!empty($apiResponseSuccess)) {
                $this->_logResponse->apiResponse($this->logID, $this->integrationID, ['type' => 'record', 'type_name' => 'Add Tags'], 'success', $apiResponse);
            }
        }
        return $apiResponse;
    }
}
<?php

namespace BitCode\BitFormPro\Admin\FormSettings;

use BitCode\BitForm\Core\Integration\IntegrationHandler;
use BitCode\BitForm\Frontend\Form\FrontendFormManager;
use BitCode\BitForm\Core\Database\FormEntryModel;
use BitCode\BitForm\Core\Cryptography\Cryptography;
use BitCode\BitForm\Admin\Form\Helpers;

final class FormAbandonment
{
    private $_formId;

    public function __construct($formId)
    {
        $this->_formId = $formId;
    }

    public static function getFormAbandonmentSettings($formId)
    {
        $integrationHandler = new IntegrationHandler($formId);
        $formIntegrations = $integrationHandler->getAllIntegration('formAbandonment', 'formAbandonment');
        if (!is_wp_error($formIntegrations) && !empty($formIntegrations)) {
            $data = $formIntegrations[0];
            $details = json_decode($data->integration_details);
            if(!empty($details->active)) {
                return $details;
            }
        }
        return false;
    }

    public static function getFormAbandonmentConfig()
    {
        if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
            $inputJSON = file_get_contents('php://input');
            $requestsParams = json_decode($inputJSON);
            $formId = $requestsParams->formID;
            $details = self::getFormAbandonmentSettings($formId);
            if (!empty($details)) {
                wp_send_json_success($details, 200);
            } else {
                wp_send_json_error(
                    __(
                        'Nothing found',
                        'bitform'
                    ),
                    401
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

    public static function savePartialFormProgress()
    {
        \ignore_user_abort();
        $formId = str_replace('bitforms_', '', $_POST['bitforms_id']);
        $FrontendFormManager = new FrontendFormManager($formId);
        $FrontendFormManager->setSaveFormAsDraft();
        $FrontendFormManager->fieldNameReplaceOfPost();
        $isEntryRestricted = $FrontendFormManager->checkSubmissionRestriction(false);
        if (!empty($isEntryRestricted)) {
            return;
        }
        $userId = get_current_user_id();
        $formAbandonmentSettings = self::getFormAbandonmentSettings($formId);
        if(!empty($formAbandonmentSettings->onlyLoggedInUsers) && $userId == 0) {
            return;
        }
        $entryId = isset($_REQUEST['entryID']) ? $_REQUEST['entryID'] : '';
        $formModel = new FormEntryModel();
        $formEntry = $formModel->get('id', ['id' => $entryId, 'status' => 9]);
        $isEntryUpdate = false;
        if (empty($entryId)) {
            $submitStatus = $FrontendFormManager->saveFormEntry($_POST);
        } elseif(!is_wp_error($formEntry) && !empty($formEntry)) {
            if(Helpers::validateEntryTokenAndUser(sanitize_text_field($_REQUEST['entryToken']), $entryId)){
                $submitStatus = $FrontendFormManager->updateFormEntry($_POST, $formId, $entryId);
                $isEntryUpdate = true;
            } else {
                wp_send_json_error('Entry Token is not Authorized', 401);
            }
        }
        if (!empty($submitStatus) && is_wp_error($submitStatus)) {
            wp_send_json_error($submitStatus->get_error_message(), 400);
        }

        if($isEntryUpdate) {
            $submitStatus['entry_id'] = $entryId;
            wp_send_json_success($submitStatus, 200);
        }

        if($userId) {
            $url     = wp_get_referer();
            $post_id = url_to_postid($url);
            $abandonmentEntries = get_option('bitform_abandonment_entries', []);
            $newEntry = [
                'post_id' => $post_id,
                'form_id' => (int) $formId,
                'entry_id' => $submitStatus['entry_id'],
                'user_id' =>  $userId,
            ];
            // check if the new entry data already exists
            $isNewEntryExists = array_filter($abandonmentEntries, function ($entry) use ($newEntry) {
                return $entry['post_id'] == $newEntry['post_id'] && $entry['form_id'] == $newEntry['form_id'] && $entry['user_id'] == $newEntry['user_id'] && $entry['entry_id'] == $newEntry['entry_id'];
            });
            if (empty($isNewEntryExists)) {
                $abandonmentEntries[] = $newEntry;
            }
            update_option('bitform_abandonment_entries', $abandonmentEntries);
        }

        if(!$userId && !empty($formAbandonmentSettings->repopulateForm)) {
            $submitStatus['user_id'] = 0;
        }
        if(!defined('AUTH_SALT')){
            define('AUTH_SALT', random_bytes(32));   
        }
        $encriptedEntryId = Cryptography::encrypt($submitStatus['entry_id'], AUTH_SALT);
        $submitStatus['entry_token'] = $encriptedEntryId;
        wp_send_json_success($submitStatus, 200);
    }

    public function checkAbandonedFormEntryId()
    {
        $formAbandonmentSettings = FormAbandonment::getFormAbandonmentSettings($this->_formId);
        $abandonmentEntries = get_option('bitform_abandonment_entries', []);
        if (empty($formAbandonmentSettings) || empty($formAbandonmentSettings->repopulateForm)) {
            foreach ($abandonmentEntries as $key => $entry) {
                if ($entry['form_id'] === $this->_formId) {
                    unset($abandonmentEntries[$key]);
                }
            }
            // re index the array
            $abandonmentEntries = array_values($abandonmentEntries);
            update_option('bitform_abandonment_entries', $abandonmentEntries);
            return false;
        }
        if (empty($abandonmentEntries)) {
            return false;
        }
        global $post;
        if (!is_a($post, 'WP_Post') && !isset($post->ID)) {
            return;
        }
        $postId = $post->ID;
        $userId = get_current_user_id();
        if (!$userId) {
            return;
        }
        $abandonmentEntryId = 0;
        $formModel = new FormEntryModel();
        $entryNotFound = true;
        $len = count($abandonmentEntries);
        for($i = $len - 1; $i >= 0; $i--) {
            $abandonedEntry = $abandonmentEntries[$i];
            if ($abandonedEntry['form_id'] === $this->_formId && $abandonedEntry['post_id'] === $postId && $abandonedEntry['user_id'] === $userId) {
                $formEntry = $formModel->get('id', ['id' => $abandonedEntry['entry_id'], 'status' => 9]);
                if (is_wp_error($formEntry) || empty($formEntry)) {
                    unset($abandonmentEntries[$i]);
                    $entryNotFound = true;
                } else {
                    $abandonmentEntryId = $abandonedEntry['entry_id'];
                    break;
                }
            }
        }
        if ($entryNotFound) {
            // re index the array
            $abandonmentEntries = array_values($abandonmentEntries);
            update_option('bitform_abandonment_entries', $abandonmentEntries);
        }
        return $abandonmentEntryId;
    }
}

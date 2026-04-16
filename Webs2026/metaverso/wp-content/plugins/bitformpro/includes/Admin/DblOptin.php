<?php

namespace BitCode\BitFormPro\Admin;

use BitCode\BitFormPro\Core\Database\LogModel;
use BitCode\BitForm\Core\Database\FormEntryMetaModel;
use BitCode\BitForm\Core\Database\FormEntryModel;
use BitCode\BitForm\Core\Integration\IntegrationHandler;
use BitCode\BitForm\Core\Util\FileHandler;
use BitCode\BitForm\Core\Util\MailConfig;

class DblOptin
{

    public function init()
    {
        add_action('bitform_unconfirmed_entries_deleted', array($this, 'deletedUnconfirmedEntries'));
    }

    public function sendEntryMailConfirmation($detail, $workFlowReturnedData)
    {
        $dblOptin = json_decode($detail->integration_details);

        $fldData = $workFlowReturnedData['fields'];

        $emailFld = $dblOptin->fldkey;

        if (isset($fldData[$emailFld]) && !empty($fldData[$emailFld]) && isset($workFlowReturnedData['entryID'])) {

            $mailSubject = $dblOptin->sub;
            $mailBody = $dblOptin->body;
            $token = wp_generate_uuid4();
            $entryId = $workFlowReturnedData['entryID'];
            $urlParams = $workFlowReturnedData['formID'] . '_' . $workFlowReturnedData['entryID'] . '_' . $workFlowReturnedData['logID'];
            $mailBody = preg_replace("/{entry_confirmation_url}/", home_url() . '?token=' . $token . '&entry_id=' . $urlParams, $mailBody);
            add_filter('wp_mail_content_type', [$this, 'filterMailContentType']);

            (new MailConfig())->sendMail();
            $mailSendSuccss = wp_mail($fldData[$emailFld], $mailSubject, $mailBody);

            if ($mailSendSuccss) {
                $this->insertActivationKey($entryId, $token);
            }

            remove_filter('wp_mail_content_type', [$this, 'filterMailContentType']);

        }
    }

    

    public function updatedEmailBodyMessage($mailBody, $urlParams)
    {
        $token = wp_generate_uuid4();

        $mailBody = preg_replace("/{entry_confirmation_url}/", home_url() . '?token=' . $token . '&entry_id=' . $urlParams, $mailBody);

        return ['token' => $token, 'mailbody' => $mailBody];
    }

    public function insertActivationKey($entryId, $token){
        $entryMeta = new FormEntryMetaModel();

        $entryMeta->insert(
            array(
                'bitforms_form_entry_id' => $entryId,
                'meta_key' => 'entry_confirm_activation',
                'meta_value' => $token)
        );
    }

    public function deletedUnconfirmedEntries()
    {
        $doubleOptins = (new IntegrationHandler(0))->getIntegrationWithoutFormId('double-opt-in', 'double-opt-in');
        global $wpdb;

        if (!is_wp_error($doubleOptins) && count($doubleOptins) > 0) {
            foreach ($doubleOptins as $dblOptin) {

                $details = json_decode($dblOptin->integration_details);

                if (
                    isset($details->auto_unconfirmed_deleted)
                ) {
                    $entryTable = $wpdb->prefix . 'bitforms_form_entries'; // $metaTable = $wpdb->prefix.static::$entry_meta_table;
                    $condition = "entries.form_id=$dblOptin->form_id && DATE_ADD(date(entries.created_at), INTERVAL $details->day DAY) < CURRENT_DATE && entries.status=2";
                    $sql = "SELECT entries.id FROM $entryTable as entries  WHERE $condition";

                    $result = $wpdb->get_results($sql, OBJECT_K);
                    $entries = array_column($result, 'id');

                    $formEntryModel = new FormEntryModel();

                    $formLogModel = new LogModel();

                    if (count($entries) > 0) {

                        $formEntryModel->bulkDelete(
                            array(
                                "`{$wpdb->prefix}bitforms_form_entries`.`id`" => $entries,
                                "`{$wpdb->prefix}bitforms_form_entries`.`form_id`" => $dblOptin->form_id)
                        );

                        $formLogModel->logDelete(array(
                            "`{$wpdb->prefix}bitforms_form_entry_log`.`id`" => $entries,
                            "`{$wpdb->prefix}bitforms_form_entry_log`.`form_id`" => $dblOptin->form_id));

                        if (file_exists(BITFORMS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $dblOptin->form_id)) {
                            $fileHandler = new FileHandler();

                            foreach ($entries as $entryID) {
                                $fileEntries = BITFORMS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $dblOptin->form_id . DIRECTORY_SEPARATOR . $entryID;
                                if (file_exists($fileEntries)) {
                                    $fileHandler->rmrf($fileEntries);
                                }
                            }
                        }

                    }
                }
            }

        }
    }

    public function filterMailContentType()
    {
        return 'text/html; charset=UTF-8';
    }
}

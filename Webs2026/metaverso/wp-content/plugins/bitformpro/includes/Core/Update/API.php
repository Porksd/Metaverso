<?php

namespace BitCode\BitFormPro\Core\Update;

use WP_Error;
use BitCode\BitForm\Core\Util\HttpHelper;
use BitCode\BitForm\Core\Util\DateTimeHelper;

final class API
{
    public static function getAPiEndPoint()
    {
        return 'https://wp-api.bitapps.pro';
    }
    public static function getUpdatedInfo()
    {
        $licenseKey = self::getKey();
        $pluginInfoResponse = HttpHelper::get(self::getAPiEndPoint() . '/update/bitformpro', null, ['licKey' => $licenseKey, 'domain' => site_url()]);
        if (is_wp_error($verifyStatus = self::verifyUpdaterResponse($pluginInfoResponse))) {
            return $verifyStatus;
        }
        $pluginData = $pluginInfoResponse->data;
        $dateTimeHelper = new DateTimeHelper();
        $pluginData->updatedAt = $dateTimeHelper->getFormated($pluginData->updatedAt, 'Y-m-d\TH:i:s.u\Z', DateTimeHelper::wp_timezone(), 'Y-m-d H:i:s', null);
        if (!empty($pluginData->details)) {
            $pluginData->sections['description'] = $pluginData->details;
        } else {
            $pluginData->sections['description'] = '';
        }
        if (!empty($pluginData->changelog)) {
            $pluginData->sections['changelog'] = $pluginData->changelog;
        } else {
            $pluginData->sections['changelog'] = '';
        }
        if ($licenseKey) {
            $pluginData->downloadLink = self::getAPiEndPoint() . '/download/' . $licenseKey;
        } else {
            $pluginData->downloadLink = '';
        }
        return $pluginData;
    }

    public static function activateLicense($licenseKey)
    {
        $data = [];
        $data['licenseKey'] = $licenseKey;
        $data['domain'] =  site_url();
        $data['slug'] = 'bitformpro';
        $activateResponse = HttpHelper::post(self::getAPiEndPoint() . '/activate', json_encode($data), ['content-type' => 'application/json']);
        if (!is_wp_error($activateResponse) && $activateResponse->status === 'success') {
            self::setKeyData($licenseKey, $activateResponse);
            return true;
        }
        return empty($activateResponse->message) ? __('Unknow error occured', 'bitformpro') : $activateResponse->message;
    }

    public static function disconnectLicense()
    {
        $integrateData = get_option('bitformpro_integrate_key_data');
        $data = null;
        if (!empty($integrateData) && is_array($integrateData) && $integrateData['status'] === 'success') {
            $data['licenseKey'] = $integrateData['key'];
            $data['domain'] =  site_url();
            $data['slug'] = 'bitformpro';
            $deactivateResponse = HttpHelper::post(self::getAPiEndPoint() . '/deactivate', json_encode($data), ['content-type' => 'application/json']);
            if (!is_wp_error($deactivateResponse) && $deactivateResponse->status === 'success' || $deactivateResponse->code === 'INVALID_LICENSE') {
                self::removeKeyData();
                return true;
            }
            return empty($deactivateResponse->message) ? __('Unknown error occurred', 'bitformpro') : $deactivateResponse->message;
        }
        return __('License data is missing', 'bitformpro');
    }

    public static function setKeyData($licenseKey, $licData)
    {
        $data['key'] = $licenseKey;
        $data['status'] = $licData->status;
        $data['expireIn'] = $licData->expireIn;
        return update_option('bitformpro_integrate_key_data', $data, 'yes');
    }

    public static function getKey()
    {
        $integrateData = get_option('bitformpro_integrate_key_data');
        $licenseKey = false;
        if (!empty($integrateData) && is_array($integrateData) && $integrateData['status'] === 'success') {
            $licenseKey = $integrateData['key'];
        }
        return $licenseKey;
    }
    public static function removeKeyData()
    {
        return delete_option('bitformpro_integrate_key_data');
    }

    private static function verifyUpdaterResponse($response)
    {
        $verifyStatus = $response;
        if (empty($response->data)) {
            $verifyStatus = new WP_Error('API_ERROR', $response->message);
        }

        if (!empty($response->code) && $response->code == 'INVALID') {
            self::removeKeyData();
        }
        return $verifyStatus;
    }
}

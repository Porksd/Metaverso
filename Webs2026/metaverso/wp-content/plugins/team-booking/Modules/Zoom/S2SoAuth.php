<?php

namespace VSHM\Modules\Zoom;

defined('ABSPATH') || exit;

class S2SoAuth
{

    /**
     * @param $account_id
     * @param $client_id
     * @param $client_secret
     *
     * @return mixed
     */
    public static function generateAccessToken($account_id, $client_id, $client_secret)
    {
        $base64Encoded = base64_encode($client_id . ':' . $client_secret);
        $result        = new \WP_Error(0, 'Something went wrong');

        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => "Basic $base64Encoded",
            ],
            'body'    => [
                'grant_type' => 'account_credentials',
                'account_id' => $account_id,
            ],
        ];

        $request_url      = "https://zoom.us/oauth/token";
        $response         = wp_remote_post($request_url, $args);
        $responseCode     = wp_remote_retrieve_response_code($response);
        $response_message = wp_remote_retrieve_response_message($response);
        if ($responseCode === 200 && strtolower($response_message) === 'ok') {
            $responseBody          = wp_remote_retrieve_body($response);
            $decoded_response_body = json_decode($responseBody, FALSE);
            if (!empty($decoded_response_body->access_token)) {
                $result = $decoded_response_body;
            } elseif (!empty($decoded_response_body->errorCode)) {
                $result = new \WP_Error($decoded_response_body->errorCode, $decoded_response_body->errorMessage);
            }
        } else {
            $result = new \WP_Error($responseCode, $response_message);
        }

        return $result;
    }

}
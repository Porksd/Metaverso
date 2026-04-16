<?php

namespace VSHM\Modules\Zoom;

defined('ABSPATH') || exit;

/*
==============================================================================
MIT License
Copyright (c) 2020 Ben Jefferson
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
==============================================================================
*/

use VSHM\Modules\Zoom;

class ZoomAPIWrapper
{
    private $errors;
    private $apiKey;
    private $apiSecret;
    private $S2SoAuthToken;
    private $baseUrl;
    private $timeout;

    private static $S2SOauthRevalidate = 0;
    /**
     * @var int|mixed
     */
    private $responseCode;

    public function __construct($apiKey, $apiSecret, $S2SoAuthToken = NULL, $options = array())
    {
        $this->apiKey        = $apiKey;
        $this->apiSecret     = $apiSecret;
        $this->S2SoAuthToken = $S2SoAuthToken;

        $this->baseUrl = 'https://api.zoom.us/v2';
        $this->timeout = 30;

        // Store any options if they map to valid properties
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) $this->$key = $value;
        }
    }

    public static function urlsafeB64Encode($string)
    {
        return str_replace('=', '', strtr(base64_encode($string), '+/', '-_'));
    }

    private function generateJWT(): string
    {
        $token  = array(
            'iss' => $this->apiKey,
            'exp' => time() + 60,
        );
        $header = array(
            'typ' => 'JWT',
            'alg' => 'HS256',
        );

        $toSign =
            self::urlsafeB64Encode(json_encode($header))
            . '.' .
            self::urlsafeB64Encode(json_encode($token));

        $signature = hash_hmac('SHA256', $toSign, $this->apiSecret, TRUE);

        return $toSign . '.' . self::urlsafeB64Encode($signature);
    }

    private function headers(): array
    {
        return [
            'Authorization: Bearer ' . ($this->S2SoAuthToken ?? $this->generateJWT()),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    private function pathReplace($path, $requestParams)
    {
        $errors = [];
        $path   = preg_replace_callback('/\\{(.*?)\\}/', static function ($matches) use ($requestParams, &$errors) {
            if (!isset($requestParams[ $matches[1] ])) {
                $errors[] = 'Required path parameter was not specified: ' . $matches[1];

                return '';
            }

            return rawurlencode($requestParams[ $matches[1] ]);
        }, $path);

        if (count($errors)) {
            $this->errors = array_merge($this->errors, $errors);
        }

        return $path;
    }

    public function doRequest($method, $path, $queryParams = array(), $pathParams = array(), $body = '')
    {

        if (is_array($body)) {
            // Treat an empty array in the body data as if no body data was set
            if (!count($body)) {
                $body = '';
            } else {
                $body = json_encode($body);
            }
        }

        $this->errors       = array();
        $this->responseCode = 0;

        $path = $this->pathReplace($path, $pathParams);

        if (count($this->errors)) {
            return FALSE;
        }

        $method = strtoupper($method);
        $url    = $this->baseUrl . $path;

        // Add on any query parameters
        if (count($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init();

        /**
         * Enable this only for LOCAL test
         * TODO: REMOVE IN PROD
         */
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers());
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if (in_array($method, array('DELETE', 'PATCH', 'POST', 'PUT'))) {

            // All except DELETE can have a payload in the body
            if ($method !== 'DELETE' && $body !== '') {
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $result = curl_exec($ch);

        // Check the return value of curl_exec()
        if ($result === FALSE) {
            throw new \RuntimeException(curl_error($ch), curl_errno($ch));
        }

        $contentType        = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        /**
         * 401 is the HTTP error code, 124 is the body error code.
         *
         * We check them both, as we can't be 100% sure about how cURL is configured server-side.
         */
        if (($this->responseCode === 401
                || $this->responseCode === 124)
            && $this->S2SoAuthToken) {

            if (self::$S2SOauthRevalidate <= 2) {
                self::$S2SOauthRevalidate++;
                $refreshedOauth = Zoom::getS2SoAuthTokenAndSave();
                if ($refreshedOauth === FALSE) {
                    // TODO: error handling
                } else {
                    $this->S2SoAuthToken = $refreshedOauth;
                    $this->doRequest($method, $path, $queryParams, $pathParams, $body);
                }
            } else {
                self::$S2SOauthRevalidate = 0;
            }

        }

        curl_close($ch);

        return json_decode($result, TRUE);
    }

    // Returns the errors responseCode returned from the last call to doRequest
    public function requestErrors()
    {
        return $this->errors;
    }

    // Returns the responseCode returned from the last call to doRequest
    public function responseCode()
    {
        return $this->responseCode;
    }
}
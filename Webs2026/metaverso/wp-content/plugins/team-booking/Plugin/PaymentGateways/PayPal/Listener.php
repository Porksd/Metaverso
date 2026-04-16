<?php

namespace VSHM\Plugin\PaymentGateways\PayPal;

/**
 *  PayPal IPN Listener
 *
 *  A class to listen for and handle Instant Payment Notifications (IPN) from
 *  the PayPal server.
 *
 *  https://github.com/Quixotix/PHP-PayPal-IPN
 *
 * @package        PHP-PayPal-IPN
 * @author         Micah Carrick
 * @copyright  (c) 2012 - Micah Carrick
 * @modified       VonStroheim
 * @version        2.1.1-tb
 */
class Listener
{
    /**
     *  If true, explicitly sets cURL to use TLS version 1. Use this if cURL
     *  is compiled with GnuTLS SSL.
     *
     * @var boolean
     */
    public $force_tls_v1 = TRUE;

    /**
     *  If true, cURL will use the CURLOPT_FOLLOWLOCATION to follow any
     *  "Location: ..." headers in the response.
     *
     * @var boolean
     */
    public $follow_location = FALSE;

    /**
     *  If true, the PayPal sandbox URI www.sandbox.paypal.com is used for the
     *  post back. If false, the live URI www.paypal.com is used. Default false.
     *
     * @var boolean
     */
    public $use_sandbox = FALSE;

    /**
     *  The amount of time, in seconds, to wait for the PayPal server to respond
     *  before timing out. Default 30 seconds.
     *
     * @var int
     */
    public  $timeout         = 30;
    private $post_data       = [];
    private $post_uri        = '';
    private $response_status = '';
    private $response        = '';

    public const PAYPAL_HOST  = 'ipnpb.paypal.com';
    public const SANDBOX_HOST = 'ipnpb.sandbox.paypal.com';

    /**
     *  Post Back Using cURL
     *
     *  Sends the post back to PayPal using the cURL library. Called by
     *  the processIpn() method if the use_curl property is true. Throws an
     *  exception if the post fails. Populates the response, response_status,
     *  and post_uri properties on success.
     *
     * @param string $encoded_data The post data as a URL encoded string
     *
     * @throws \Exception
     */
    protected function curlPost($encoded_data)
    {

        $uri            = 'https://' . $this->getPaypalHost() . '/cgi-bin/webscr';
        $this->post_uri = $uri;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'api_cert_chain.crt');
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->follow_location);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);

        if ($this->force_tls_v1) {
            curl_setopt($ch, CURLOPT_SSLVERSION, 6);
            $curl_version = curl_version();
            $ssl_version  = isset($curl_version['ssl_version']) ? $curl_version['ssl_version'] : '';
            if (substr_compare($ssl_version, 'NSS/', 0, strlen('NSS/')) !== 0) {
                curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
            }
        }

        $this->response        = curl_exec($ch);
        $this->response_status = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($this->response === FALSE || $this->response_status === '0') {
            $errno  = curl_errno($ch);
            $errstr = curl_error($ch);
            throw new \RuntimeException("cURL error: [$errno] $errstr");
        }
    }

    /**
     * @return string
     */
    private function getPaypalHost()
    {
        return $this->use_sandbox ? self::SANDBOX_HOST : self::PAYPAL_HOST;
    }

    /**
     *  Get POST URI
     *
     *  Returns the URI that was used to send the post back to PayPal. This can
     *  be useful for troubleshooting connection problems. The default URI
     *  would be "ssl://www.sandbox.paypal.com:443/cgi-bin/webscr"
     *
     * @return string
     */
    public function getPostUri()
    {
        return $this->post_uri;
    }

    /**
     *  Get Response
     *
     *  Returns the entire response from PayPal as a string including all the
     *  HTTP headers.
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     *  Get Response Status
     *
     *  Returns the HTTP response status code from PayPal. This should be "200"
     *  if the post back was successful.
     *
     * @return string
     */
    public function getResponseStatus()
    {
        return $this->response_status;
    }

    /**
     *  Get Text Report
     *
     *  Returns a report of the IPN transaction in plain text format. This is
     *  useful in emails to order processors and system administrators.
     *
     * @return string
     */
    public function getTextReport()
    {
        $r = '';

        // date and POST url
        $r .= str_repeat('-', 80);
        $r .= "\n[" . date('m/d/Y g:i A') . '] - ' . $this->getPostUri();
        $r .= " (curl)\n";

        // HTTP Response
        $r .= str_repeat('-', 80);
        $r .= "\n{$this->getResponse()}\n";

        // POST vars
        $r .= str_repeat('-', 80);
        $r .= "\n";

        if (!empty($this->post_data)) {
            foreach ($this->post_data as $key => $value) {
                $r .= str_pad($key, 25) . "$value\n";
            }
        }
        $r .= "\n\n";

        return $r;
    }

    /**
     *  Process IPN
     *
     *  Handles the IPN post back to PayPal and parsing the response. Call this
     *  method from your IPN listener script. Returns true if the response came
     *  back as "VERIFIED", false if the response came back "INVALID", and
     *  throws an exception if there is an error.
     *
     * @param null $post_data
     *
     * @return bool
     * @throws \Exception
     */
    public function processIpn($post_data = NULL)
    {

        $encoded_data = 'cmd=_notify-validate';

        if ($post_data === NULL) {
            // use raw POST data
            $raw_post_data  = file_get_contents('php://input');
            $raw_post_array = explode('&', $raw_post_data);
            if (empty($raw_post_array)) {
                throw new \Exception('No POST data found.');
            } else {
                foreach ($raw_post_array as $keyval) {
                    $keyval = explode('=', $keyval);
                    if (count($keyval) === 2) {
                        $this->post_data[ $keyval[0] ] = urldecode($keyval[1]);
                    }
                }
            }
        } else {
            // use provided data array
            $this->post_data = $post_data;
        }

        foreach ($this->post_data as $key => $value) {
            $value        = urlencode($value);
            $encoded_data .= "&$key=" . $value;
        }

        $this->curlPost($encoded_data);

        if (strpos($this->response_status, '200') === FALSE) {
            throw new \RuntimeException('Invalid response status: ' . $this->response_status);
        }

        if (strpos($this->response, 'VERIFIED') !== FALSE) {
            return TRUE;
        }

        if (strpos($this->response, 'INVALID') !== FALSE) {
            return FALSE;
        }

        throw new \RuntimeException('Unexpected response from PayPal.');
    }

    /**
     *  Require Post Method
     *
     *  Throws an exception and sets an HTTP 405 response header if the request
     *  method was not POST.
     *
     * @throws \RuntimeException
     */
    public function requirePostMethod()
    {
        // require POST requests
        if ($_SERVER['REQUEST_METHOD'] && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST', TRUE, 405);
            throw new \RuntimeException('Invalid HTTP request method (' . $_SERVER['REQUEST_METHOD'] . ') from ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $_SERVER['HTTP_USER_AGENT'] . ')');
        }
    }

}
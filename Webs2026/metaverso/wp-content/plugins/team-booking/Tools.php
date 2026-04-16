<?php

namespace VSHM;

use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

defined('ABSPATH') || exit;

if (!class_exists(Tools::class)) {

    /**
     * Class Tools
     *
     * @package VSHM
     * @author  VonStroheim
     */
    class Tools
    {
        /**
         * @param PeriodCollection        $mainPeriodCollection
         * @param PeriodCollection|Period $periods
         *
         * @return PeriodCollection
         */
        public static function periodSubtract(PeriodCollection $mainPeriodCollection, $periods): PeriodCollection
        {
            if ($periods instanceof Period) {
                $periods = PeriodCollection::make($periods);
            }

            if ($periods->isEmpty()) {
                return $mainPeriodCollection;
            }

            $collection = [];

            foreach ($mainPeriodCollection as $period) {

                if ($period->getIncludedStart() == $period->getIncludedEnd()) {
                    continue;
                }

                $overlapping_periods = [];
                foreach ($periods as $inner_period) {
                    if ($period->overlapsWith($inner_period)) {
                        $overlapping_periods[] = $inner_period;
                    }
                }

                if (!empty($overlapping_periods)) {

                    $boundPeriod = Period::make(
                        $period->getStart(),
                        $period->getEnd(),
                        Precision::SECOND,
                        Boundaries::EXCLUDE_NONE
                    );

                    foreach ($boundPeriod->diff(...$overlapping_periods) as $item) {

                        if ($item->getIncludedStart() == $item->getIncludedEnd()) {
                            continue;
                        }

                        $collection[] = Period::make(
                            $item->getStart(),
                            $item->getEnd(),
                            Precision::SECOND,
                            Boundaries::EXCLUDE_ALL
                        );
                    }

                } else {
                    $collection[] = $period;
                }

            }


            return PeriodCollection::make(...$collection);
        }

        /**
         * Insert a value or key/value pair after a specific key in an array.  If key doesn't exist, value is appended
         * to the end of the array.
         *
         * @credits https://gist.github.com/wpscholar/0deadce1bbfa4adb4e4c
         *
         * @param array  $array
         * @param string $key
         * @param array  $new
         *
         * @return array
         */
        public static function array_insert_after(array $array, $key, array $new): array
        {
            $keys  = array_keys($array);
            $index = array_search($key, $keys, TRUE);
            $pos   = FALSE === $index ? count($array) : $index + 1;

            return array_merge(array_slice($array, 0, $pos), $new, array_slice($array, $pos));
        }

        /**
         * @param array $file
         *
         * @return string
         */
        public static function file_hash($file): string
        {
            return md5(md5_file($file['file']) . $file['file']);
        }

        public static function starts_with($string, $startString): bool
        {
            return (strpos($string, $startString) === 0);
        }

        /**
         * @param $string
         *
         * @return string
         */
        public static function mb_strtoupper($string): string
        {
            if (function_exists('mb_strtoupper')) {
                return mb_strtoupper($string, 'UTF-8');
            }

            return strtoupper($string);
        }

        /**
         * @param $string
         *
         * @return string
         */
        public static function mb_strtolower($string): string
        {
            if (function_exists('mb_strtolower')) {
                return mb_strtolower($string, 'UTF-8');
            }

            return strtolower($string);
        }

        /**
         * @param $string
         * @param $to
         * @param $charset
         *
         * @return string
         */
        public static function mb_convert_encoding($string, $to, $charset)
        {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($string, $to, $charset);
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                trigger_error('Multibyte Extensions not installed, this may cause issues -- thrown when processing PayPal IPN --', E_USER_NOTICE);
            }

            return $string;
        }

        /**
         * @param array $array1
         * @param array $array2
         *
         * @return array
         */
        public static function array_merge_recursive_distinct(array &$array1, array &$array2): array
        {
            $merged = $array1;
            foreach ($array2 as $key => &$value) {
                if (is_array($value) && isset ($merged [ $key ]) && is_array($merged [ $key ])) {
                    $merged [ $key ] = static::array_merge_recursive_distinct($merged [ $key ], $value);
                } else {
                    $merged [ $key ] = $value;
                }
            }

            return $merged;
        }

        public static function subscribe_classes_in_dir($path, $classRoot, $subscribe_fn = 'bootstrap', $exclude = []): void
        {
            $classes = glob($path . '*.php');
            foreach ($classes as $class) {
                $classname = $classRoot . basename($class, '.php');
                if (is_callable([$classname, $subscribe_fn])
                    && !in_array(basename($class), $exclude, TRUE)) {
                    $classname::$subscribe_fn();
                }
            }
        }

        public static function subscribe_classes_in_dir_recursive($path, $classRoot, $subscribe_fn = 'bootstrap', $exclude = []): void
        {
            $classes = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
            $classes = new \RecursiveIteratorIterator($classes);
            $classes = new \RegexIterator($classes, '/\.php$/i');
            foreach ($classes as $class) {

                $base      = str_replace(__DIR__ . DIRECTORY_SEPARATOR . 'Settings' . DIRECTORY_SEPARATOR, '', $class->getPathname());
                $base      = str_replace('/', '\\', $base);
                $classname = $classRoot . str_replace('.php', '', $base);

                if (is_callable([$classname, $subscribe_fn])
                    && !in_array(basename($class), $exclude, TRUE)
                ) {
                    $classname::$subscribe_fn();
                }
            }
        }

        /**
         * Maps MIME types to relative file extensions.
         *
         * @credits https://gist.github.com/alexcorvi/df8faecb59e86bee93411f6a7967df2c
         *
         * @param string $mime
         *
         * @return bool|mixed
         */
        public static function mime_to_ext($mime)
        {
            $mime_map = [
                'video/3gpp2'                                                               => '3g2',
                'video/3gp'                                                                 => '3gp',
                'video/3gpp'                                                                => '3gp',
                'application/x-compressed'                                                  => '7zip',
                'audio/x-acc'                                                               => 'aac',
                'audio/ac3'                                                                 => 'ac3',
                'application/postscript'                                                    => 'ai',
                'audio/x-aiff'                                                              => 'aif',
                'audio/aiff'                                                                => 'aif',
                'audio/x-au'                                                                => 'au',
                'video/x-msvideo'                                                           => 'avi',
                'video/msvideo'                                                             => 'avi',
                'video/avi'                                                                 => 'avi',
                'application/x-troff-msvideo'                                               => 'avi',
                'application/macbinary'                                                     => 'bin',
                'application/mac-binary'                                                    => 'bin',
                'application/x-binary'                                                      => 'bin',
                'application/x-macbinary'                                                   => 'bin',
                'image/bmp'                                                                 => 'bmp',
                'image/x-bmp'                                                               => 'bmp',
                'image/x-bitmap'                                                            => 'bmp',
                'image/x-xbitmap'                                                           => 'bmp',
                'image/x-win-bitmap'                                                        => 'bmp',
                'image/x-windows-bmp'                                                       => 'bmp',
                'image/ms-bmp'                                                              => 'bmp',
                'image/x-ms-bmp'                                                            => 'bmp',
                'application/bmp'                                                           => 'bmp',
                'application/x-bmp'                                                         => 'bmp',
                'application/x-win-bitmap'                                                  => 'bmp',
                'application/cdr'                                                           => 'cdr',
                'application/coreldraw'                                                     => 'cdr',
                'application/x-cdr'                                                         => 'cdr',
                'application/x-coreldraw'                                                   => 'cdr',
                'image/cdr'                                                                 => 'cdr',
                'image/x-cdr'                                                               => 'cdr',
                'zz-application/zz-winassoc-cdr'                                            => 'cdr',
                'application/mac-compactpro'                                                => 'cpt',
                'application/pkix-crl'                                                      => 'crl',
                'application/pkcs-crl'                                                      => 'crl',
                'application/x-x509-ca-cert'                                                => 'crt',
                'application/pkix-cert'                                                     => 'crt',
                'text/css'                                                                  => 'css',
                'text/x-comma-separated-values'                                             => 'csv',
                'text/comma-separated-values'                                               => 'csv',
                'application/vnd.msexcel'                                                   => 'csv',
                'application/x-director'                                                    => 'dcr',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
                'application/x-dvi'                                                         => 'dvi',
                'message/rfc822'                                                            => 'eml',
                'application/x-msdownload'                                                  => 'exe',
                'video/x-f4v'                                                               => 'f4v',
                'audio/x-flac'                                                              => 'flac',
                'video/x-flv'                                                               => 'flv',
                'image/gif'                                                                 => 'gif',
                'application/gpg-keys'                                                      => 'gpg',
                'application/x-gtar'                                                        => 'gtar',
                'application/x-gzip'                                                        => 'gzip',
                'application/mac-binhex40'                                                  => 'hqx',
                'application/mac-binhex'                                                    => 'hqx',
                'application/x-binhex40'                                                    => 'hqx',
                'application/x-mac-binhex40'                                                => 'hqx',
                'text/html'                                                                 => 'html',
                'image/x-icon'                                                              => 'ico',
                'image/x-ico'                                                               => 'ico',
                'image/vnd.microsoft.icon'                                                  => 'ico',
                'text/calendar'                                                             => 'ics',
                'application/java-archive'                                                  => 'jar',
                'application/x-java-application'                                            => 'jar',
                'application/x-jar'                                                         => 'jar',
                'image/jp2'                                                                 => 'jp2',
                'video/mj2'                                                                 => 'jp2',
                'image/jpx'                                                                 => 'jp2',
                'image/jpm'                                                                 => 'jp2',
                'image/jpeg'                                                                => 'jpeg',
                'image/pjpeg'                                                               => 'jpeg',
                'application/x-javascript'                                                  => 'js',
                'application/json'                                                          => 'json',
                'text/json'                                                                 => 'json',
                'application/vnd.google-earth.kml+xml'                                      => 'kml',
                'application/vnd.google-earth.kmz'                                          => 'kmz',
                'text/x-log'                                                                => 'log',
                'audio/x-m4a'                                                               => 'm4a',
                'application/vnd.mpegurl'                                                   => 'm4u',
                'audio/midi'                                                                => 'mid',
                'application/vnd.mif'                                                       => 'mif',
                'video/quicktime'                                                           => 'mov',
                'video/x-sgi-movie'                                                         => 'movie',
                'audio/mpeg'                                                                => 'mp3',
                'audio/mpg'                                                                 => 'mp3',
                'audio/mpeg3'                                                               => 'mp3',
                'audio/mp3'                                                                 => 'mp3',
                'video/mp4'                                                                 => 'mp4',
                'video/mpeg'                                                                => 'mpeg',
                'application/oda'                                                           => 'oda',
                'audio/ogg'                                                                 => 'ogg',
                'video/ogg'                                                                 => 'ogg',
                'application/ogg'                                                           => 'ogg',
                'application/x-pkcs10'                                                      => 'p10',
                'application/pkcs10'                                                        => 'p10',
                'application/x-pkcs12'                                                      => 'p12',
                'application/x-pkcs7-signature'                                             => 'p7a',
                'application/pkcs7-mime'                                                    => 'p7c',
                'application/x-pkcs7-mime'                                                  => 'p7c',
                'application/x-pkcs7-certreqresp'                                           => 'p7r',
                'application/pkcs7-signature'                                               => 'p7s',
                'application/pdf'                                                           => 'pdf',
                'application/octet-stream'                                                  => 'pdf',
                'application/x-x509-user-cert'                                              => 'pem',
                'application/x-pem-file'                                                    => 'pem',
                'application/pgp'                                                           => 'pgp',
                'application/x-httpd-php'                                                   => 'php',
                'application/php'                                                           => 'php',
                'application/x-php'                                                         => 'php',
                'text/php'                                                                  => 'php',
                'text/x-php'                                                                => 'php',
                'application/x-httpd-php-source'                                            => 'php',
                'image/png'                                                                 => 'png',
                'image/x-png'                                                               => 'png',
                'application/powerpoint'                                                    => 'ppt',
                'application/vnd.ms-powerpoint'                                             => 'ppt',
                'application/vnd.ms-office'                                                 => 'ppt',
                'application/msword'                                                        => 'ppt',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
                'application/x-photoshop'                                                   => 'psd',
                'image/vnd.adobe.photoshop'                                                 => 'psd',
                'audio/x-realaudio'                                                         => 'ra',
                'audio/x-pn-realaudio'                                                      => 'ram',
                'application/x-rar'                                                         => 'rar',
                'application/rar'                                                           => 'rar',
                'application/x-rar-compressed'                                              => 'rar',
                'audio/x-pn-realaudio-plugin'                                               => 'rpm',
                'application/x-pkcs7'                                                       => 'rsa',
                'text/rtf'                                                                  => 'rtf',
                'text/richtext'                                                             => 'rtx',
                'video/vnd.rn-realvideo'                                                    => 'rv',
                'application/x-stuffit'                                                     => 'sit',
                'application/smil'                                                          => 'smil',
                'text/srt'                                                                  => 'srt',
                'image/svg+xml'                                                             => 'svg',
                'application/x-shockwave-flash'                                             => 'swf',
                'application/x-tar'                                                         => 'tar',
                'application/x-gzip-compressed'                                             => 'tgz',
                'image/tiff'                                                                => 'tiff',
                'text/plain'                                                                => 'txt',
                'text/x-vcard'                                                              => 'vcf',
                'application/videolan'                                                      => 'vlc',
                'text/vtt'                                                                  => 'vtt',
                'audio/x-wav'                                                               => 'wav',
                'audio/wave'                                                                => 'wav',
                'audio/wav'                                                                 => 'wav',
                'application/wbxml'                                                         => 'wbxml',
                'video/webm'                                                                => 'webm',
                'audio/x-ms-wma'                                                            => 'wma',
                'application/wmlc'                                                          => 'wmlc',
                'video/x-ms-wmv'                                                            => 'wmv',
                'video/x-ms-asf'                                                            => 'wmv',
                'application/xhtml+xml'                                                     => 'xhtml',
                'application/excel'                                                         => 'xl',
                'application/msexcel'                                                       => 'xls',
                'application/x-msexcel'                                                     => 'xls',
                'application/x-ms-excel'                                                    => 'xls',
                'application/x-excel'                                                       => 'xls',
                'application/x-dos_ms_excel'                                                => 'xls',
                'application/xls'                                                           => 'xls',
                'application/x-xls'                                                         => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
                'application/vnd.ms-excel'                                                  => 'xlsx',
                'application/xml'                                                           => 'xml',
                'text/xml'                                                                  => 'xml',
                'text/xsl'                                                                  => 'xsl',
                'application/xspf+xml'                                                      => 'xspf',
                'application/x-compress'                                                    => 'z',
                'application/x-zip'                                                         => 'zip',
                'application/zip'                                                           => 'zip',
                'application/x-zip-compressed'                                              => 'zip',
                'application/s-compressed'                                                  => 'zip',
                'multipart/x-zip'                                                           => 'zip',
                'text/x-scriptzsh'                                                          => 'zsh',
            ];

            return isset($mime_map[ $mime ]) === TRUE ? $mime_map[ $mime ] : FALSE;
        }

        /**
         * Makes an array ready for HTML attribute insertion.
         *
         * @param array $params
         *
         * @return string
         */
        public static function prepare_attribute_params(array $params)
        {
            return htmlspecialchars(json_encode($params), ENT_QUOTES);
        }

        /**
         * Checks if an array is an associative array.
         *
         * @param array $arr
         *
         * @return bool
         */
        public static function array_is_assoc(array $arr)
        {
            if ([] === $arr) {
                return FALSE;
            }

            return array_keys($arr) !== range(0, count($arr) - 1);
        }

        /**
         * @param $string
         *
         * @return array|string|string[]
         */
        public static function escapeJavaScriptText($string)
        {
            return str_replace(['"', "\n"], ['\"', '\n'], addcslashes(str_replace("\r", '', (string)$string), "\0..\37'\\"));
        }

        /**
         * @param $class
         *
         * @return false|string
         */
        public static function get_short_classname($class)
        {
            return substr(strrchr('\\' . $class, '\\'), 1);
        }

        /**
         * Generates a secure token
         * (source https://gist.github.com/raveren/5555297)
         *
         * @param string $type
         * @param int    $length
         * @param string $prefix
         *
         * @return string
         */
        public static function generate_token($type = 'alnum', $length = 32, $prefix = '')
        {
            switch ($type) {
                case 'alnum':
                    $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'alpha':
                    $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                case 'hexdec':
                    $pool = '0123456789abcdef';
                    break;
                case 'numeric':
                    $pool = '0123456789';
                    break;
                case 'nozero':
                    $pool = '123456789';
                    break;
                case 'distinct':
                    $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
                    break;
                case 'alnum_upper':
                    $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    break;
                default:
                    $pool = (string)$type;
                    break;
            }

            $crypto_rand_secure = static function ($min, $max) {
                $range = $max - $min;
                if ($range < 0) {
                    return $min;
                }
                $log    = log($range, 2);
                $bytes  = (int)($log / 8) + 1; // length in bytes
                $bits   = (int)$log + 1; // length in bits
                $filter = (1 << $bits) - 1; // set all lower bits to 1
                do {
                    $rnd = hexdec(bin2hex(random_bytes($bytes)));
                    $rnd &= $filter; // discard irrelevant bits
                } while ($rnd >= $range);

                return $min + $rnd;
            };

            $token = '';
            $max   = strlen($pool);
            for ($i = 0; $i < $length; $i++) {
                $token .= $pool[ $crypto_rand_secure(0, $max) ];
            }

            return $prefix . $token;
        }

        /**
         * Serialize data, if needed.
         *
         * Unlike WordPress core function, this one
         * prevents double-serialization.
         *
         * @param $data
         *
         * @return string
         */
        public static function maybe_serialize($data)
        {
            if (is_array($data) || is_object($data)) {
                return serialize($data);
            }

            return $data;
        }

        /**
         * Changes a key in the array while keeping its order.
         *
         * @param array  $array
         * @param string $old_k
         * @param string $new_k
         *
         * @return array
         */
        public static function change_array_key($array, $old_k, $new_k)
        {
            if (!array_key_exists($old_k, $array)) {
                return $array;
            }
            $keys = array_keys($array);

            $keys[ array_search($old_k, $keys, TRUE) ] = $new_k;

            return array_combine($keys, $array);
        }

        /**
         * Looks for a JSON string inside a generic string
         * which is supposed to contain one JSON string only.
         *
         * Returns any decoded finding.
         *
         * @param $string
         *
         * @return array|bool|mixed|object
         */
        public static function looking_for_json($string)
        {
            $regex   = "/(\{.*?\})/s";
            $matches = array();
            preg_match($regex, $string, $matches);

            return isset($matches[0]) ? self::json_validate($matches[0]) : FALSE;
        }

        /**
         * Validates a JSON string
         *
         * @param $string
         *
         * @return array|bool|mixed|object
         */
        public static function json_validate($string)
        {
            $result = json_decode($string, FALSE);

            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $error = '';
                    break;
                case JSON_ERROR_DEPTH:
                    $error = 'The maximum stack depth has been exceeded.';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error = 'Invalid or malformed JSON.';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $error = 'Control character error, possibly incorrectly encoded.';
                    break;
                case JSON_ERROR_SYNTAX:
                    $error = 'Syntax error, malformed JSON.';
                    break;
                // PHP >= 5.3.3
                case JSON_ERROR_UTF8:
                    $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                    break;
                // PHP >= 5.5.0
                case JSON_ERROR_RECURSION:
                    $error = 'One or more recursive references in the value to be encoded.';
                    break;
                // PHP >= 5.5.0
                case JSON_ERROR_INF_OR_NAN:
                    $error = 'One or more NAN or INF values in the value to be encoded.';
                    break;
                case JSON_ERROR_UNSUPPORTED_TYPE:
                    $error = 'A value of a type that cannot be encoded was given.';
                    break;
                default:
                    $error = 'Unknown JSON error occurred.';
                    break;
            }

            if (!empty($error)) {
                $result = FALSE;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $error .= ' Original string: ' . $string;
                    trigger_error(esc_html($error));
                }
            }

            return $result;
        }

        /**
         * Useful debug function to stringify a variable dump.
         *
         * @param $var
         *
         * @return string
         */
        public static function stringify_dump($var): string
        {
            ob_start();
            var_dump($var);

            return ob_get_clean();
        }

        /**
         * Useful debug function to log a variable dump.
         *
         * @param $var
         */
        public static function log_dump($var): void
        {
            error_log(self::stringify_dump($var));
        }

        /**
         * Checks what kind of request the script is executing in WP context.
         *
         * @param string $type
         *
         * @return bool
         */
        public static function is_request(string $type): bool
        {
            switch ($type) {
                case 'admin':
                    return is_admin();
                case 'ajax':
                    return defined('DOING_AJAX') && DOING_AJAX;
                case 'rest':
                    if ((defined('REST_REQUEST') && REST_REQUEST)
                        || (isset($_GET['rest_route']) && strpos(sanitize_text_field($_GET['rest_route']), REST_Controller::NAME_SPACE))) {
                        return TRUE;
                    }
                    $is_rest = FALSE;
                    if (!empty($_SERVER['REQUEST_URI'])) {
                        $rest_url     = self::get_rest_url(get_current_blog_id(), '/');
                        $rest_path    = trim(parse_url($rest_url, PHP_URL_PATH), '/');
                        $request_path = trim(sanitize_text_field($_SERVER['REQUEST_URI']), '/');
                        $is_rest      = (strpos($request_path, $rest_path) === 0);
                    }

                    return $is_rest;
                case 'cron':
                    return defined('DOING_CRON') && DOING_CRON;
                case 'frontend':
                    return (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX))
                        && (!defined('DOING_CRON') || !DOING_CRON)
                        && (!defined('REST_REQUEST') || !REST_REQUEST);
            }

            return FALSE;
        }

        /**
         * This function is a bypass of WordPress original function
         * as it calls WP_Rewrite and we need it earlier.
         *
         * Todo: check when WordPress will drop the WP_Rewrite call, as it is not actually necessary.
         *
         * @param null   $blog_id
         * @param string $path
         * @param string $scheme
         *
         * @return mixed|void
         */
        public static function get_rest_url($blog_id = NULL, $path = '/', $scheme = 'rest')
        {
            if (empty($path)) {
                $path = '/';
            }

            $path = '/' . ltrim($path, '/');

            if (get_option('permalink_structure') || (is_multisite() && get_blog_option($blog_id, 'permalink_structure'))) {

                if (preg_match('#^/*index.php#', get_option('permalink_structure'))) {
                    $url = get_home_url($blog_id, 'index.php/' . rest_get_url_prefix(), $scheme);
                } else {
                    $url = get_home_url($blog_id, rest_get_url_prefix(), $scheme);
                }

                $url .= $path;
            } else {
                $url = trailingslashit(get_home_url($blog_id, '', $scheme));
                if ('index.php' !== substr($url, 9)) {
                    $url .= 'index.php';
                }
                $url = add_query_arg('rest_route', $path, $url);
            }

            if (isset($_SERVER['SERVER_NAME']) && is_ssl() && $_SERVER['SERVER_NAME'] === parse_url(get_home_url($blog_id), PHP_URL_HOST)) {
                $url = set_url_scheme($url, 'https');
            }

            if (is_admin() && force_ssl_admin()) {
                $url = set_url_scheme($url, 'https');
            }

            return apply_filters('rest_url', $url, $path, $blog_id, $scheme);
        }

        /**
         * Helper to enqueue styles.
         *
         * @param string $handle
         * @param string $path
         * @param array  $deps
         */
        public static function enqueue_style(string $handle, string $path, array $deps = []): void
        {
            wp_enqueue_style($handle,
                vshm()->plugin['URL'] . $path,
                $deps,
                filemtime(vshm()->plugin['DIR'] . implode(DIRECTORY_SEPARATOR, explode('/', $path)))
            );
        }

        /**
         * Helper to enqueue scripts.
         *
         * @param string $handle
         * @param string $path
         * @param array  $deps
         * @param bool   $footer
         */
        public static function enqueue_script(string $handle, string $path, array $deps = [], bool $footer = FALSE): void
        {
            if ($path[0] . $path[1] !== '//') {
                wp_enqueue_script($handle,
                    vshm()->plugin['URL'] . $path,
                    $deps,
                    filemtime(vshm()->plugin['DIR'] . implode(DIRECTORY_SEPARATOR, explode('/', $path))),
                    $footer
                );
            } else {
                wp_enqueue_script($handle,
                    $path,
                    $deps,
                    NULL,
                    $footer
                );
            }
        }

        /**
         * Returns internationalized weekdays labels.
         *
         * @param string $format
         *
         * @return array
         */
        public static function i18n_weekdays_labels(string $format = 'l'): array
        {
            $return = [];
            for ($i = 0; $i < 7; $i++) {
                $return[ $i ] = wp_date($format, strtotime("Sunday +{$i} days"), new \DateTimeZone('UTC'));
            }

            return $return;
        }

        /**
         * Returns internationalized months labels.
         *
         * @param string $format
         *
         * @return array
         */
        public static function i18n_months_labels($format = 'F')
        {
            $return = [];
            for ($i = 0; $i < 12; $i++) {
                $return[ $i ] = wp_date($format, strtotime("1st January +{$i} months"), new \DateTimeZone('UTC'));
            }

            return $return;
        }

        /**
         * @return string
         */
        public static function get_ip_address()
        {
            foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {
                if (array_key_exists($key, $_SERVER) === TRUE) {
                    foreach (explode(',', $_SERVER[ $key ]) as $ip) {
                        $ip = trim($ip);
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== FALSE) {
                            return $ip;
                        }
                    }
                }
            }

            return NULL;
        }

        /**
         * Checks whether a dark theme may be appropriate.
         *
         * @param $hexColor
         *
         * @return bool
         */
        public static function requiresDarkTheme($hexColor)
        {
            $R1           = hexdec(substr($hexColor, 1, 2));
            $G1           = hexdec(substr($hexColor, 3, 2));
            $B1           = hexdec(substr($hexColor, 5, 2));
            $blackColor   = "#000000";
            $R2BlackColor = hexdec(substr($blackColor, 1, 2));
            $G2BlackColor = hexdec(substr($blackColor, 3, 2));
            $B2BlackColor = hexdec(substr($blackColor, 5, 2));

            $L1 = 0.2126 * (($R1 / 255) ** 2.2) +
                0.7152 * (($G1 / 255) ** 2.2) +
                0.0722 * (($B1 / 255) ** 2.2);

            $L2 = 0.2126 * (($R2BlackColor / 255) ** 2.2) +
                0.7152 * (($G2BlackColor / 255) ** 2.2) +
                0.0722 * (($B2BlackColor / 255) ** 2.2);

            if ($L1 > $L2) {
                $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
            } else {
                $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
            }

            return !($contrastRatio > 5);
        }

        /**
         * Increases or decreases the brightness of a color by a percentage of the current brightness.
         *
         * @param string $hexCode       Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
         * @param float  $adjustPercent A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
         *
         * @return  string
         */
        public static function adjustBrightness($hexCode, $adjustPercent)
        {
            $hexCode = ltrim($hexCode, '#');

            if (strlen($hexCode) === 3) {
                $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
            }

            $hexCode = array_map('hexdec', str_split($hexCode, 2));

            foreach ($hexCode as & $color) {
                $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
                $adjustAmount    = ceil($adjustableLimit * $adjustPercent);

                $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
            }

            return '#' . implode($hexCode);
        }

        /**
         * Returns a list of WordPress timezones.
         *
         * @param string $locale
         *
         * @return string[]
         */
        public static function timezone_list($locale = NULL)
        {
            static $mo_loaded = FALSE, $locale_loaded = NULL;

            if (!$mo_loaded || $locale !== $locale_loaded) {
                if ($locale) {
                    $locale_loaded = $locale;
                } else {
                    $locale_loaded = get_locale();
                }
                $mofile = WP_LANG_DIR . '/continents-cities-' . $locale_loaded . '.mo';
                unload_textdomain('continents-cities');
                load_textdomain('continents-cities', $mofile);
                $mo_loaded = TRUE;
            }

            $zonen = [];

            foreach (timezone_identifiers_list() as $zone) {
                $zone = explode('/', $zone);

                $exists    = [
                    0 => isset($zone[0]) && $zone[0],
                    1 => isset($zone[1]) && $zone[1],
                    2 => isset($zone[2]) && $zone[2],
                ];
                $exists[3] = ($exists[0] && 'Etc' !== $zone[0]);
                $exists[4] = ($exists[1] && $exists[3]);
                $exists[5] = ($exists[2] && $exists[3]);

                $zonen[] = [
                    'continent'   => $exists[0] ? $zone[0] : '',
                    'city'        => $exists[1] ? $zone[1] : '',
                    'subcity'     => $exists[2] ? $zone[2] : '',
                    't_continent' => $exists[3] ? translate(str_replace('_', ' ', $zone[0]), 'continents-cities') : '',
                    't_city'      => $exists[4] ? translate(str_replace('_', ' ', $zone[1]), 'continents-cities') : '',
                    't_subcity'   => $exists[5] ? translate(str_replace('_', ' ', $zone[2]), 'continents-cities') : ''
                ];

                usort($zonen, '_wp_timezone_choice_usort_callback');


            }

            return $zonen;
        }

        /**
         * A list of almost all currencies
         *
         * @return array
         */
        public static function getCurrencies($code = NULL)
        {
            $currencies = [
                'AED' => [
                    'label'  => 'United Arab Emirates Dirham',
                    'locale' => 'ar_AE',
                    'symbol' => '&#x62f;&#x2e;&#x625;',
                ],
                'AFN' => [
                    'label'  => 'Afghan Afghani',
                    'locale' => 'fa_AF',
                    'symbol' => '&#1547;',
                ],
                'ALL' => [
                    'label'  => 'Albanian Lek',
                    'locale' => 'sq_AL',
                    'symbol' => 'Lek',
                ],
                'AMD' => [
                    'label'  => 'Armenian Dram',
                    'locale' => 'hy_AM',
                    'symbol' => '&#1423;',
                ],
                'ANG' => [
                    'label'  => 'Netherlands Antillean Gulden',
                    'locale' => 'nl_SX',
                    'symbol' => '&#402;',
                ],
                'AOA' => [
                    'label'  => 'Angolan Kwanza',
                    'locale' => 'pt_AO',
                    'symbol' => 'Kz;',
                ],
                'ARS' => [
                    'label'  => 'Argentine Peso',
                    'locale' => 'es_AR',
                    'symbol' => '$',
                ],
                'AUD' => [
                    'label'  => 'Australian Dollar',
                    'locale' => 'en_AU',
                    'symbol' => '$',
                ],
                'AWG' => [
                    'label'  => 'Aruban Florin',
                    'locale' => 'nl_AW',
                    'symbol' => '&#402;',
                ],
                'AZN' => [
                    'label'  => 'Azerbaijani Manat',
                    'locale' => 'az_Latn_AZ',
                    'symbol' => '&#8380;',
                ],
                'BAM' => [
                    'label'  => 'Bosnia & Herzegovina Convertible Mark',
                    'locale' => 'hr_BA',
                    'symbol' => 'KM',
                ],
                'BBD' => [
                    'label'  => 'Barbadian Dollar',
                    'locale' => 'en_BB',
                    'symbol' => '$',
                ],
                'BDT' => [
                    'label'  => 'Bangladeshi Taka',
                    'locale' => 'bn_BD',
                    'symbol' => '&#2547;',
                ],
                'BGN' => [
                    'label'  => 'Bulgarian Lev',
                    'locale' => 'bg_BG',
                    'symbol' => 'лв',
                ],
                'BIF' => [
                    'label'  => 'Burundian Franc',
                    'locale' => 'rn_BI',
                    'symbol' => 'FBu',
                ],
                'BMD' => [
                    'label'  => 'Bermudian Dollar',
                    'locale' => 'en_BM',
                    'symbol' => '$',
                ],
                'BND' => [
                    'label'  => 'Brunei Dollar',
                    'locale' => 'ms_Latn_BN',
                    'symbol' => '$',
                ],
                'BOB' => [
                    'label'  => 'Bolivian Boliviano',
                    'locale' => 'es_BO',
                    'symbol' => 'Bs.',
                ],
                'BRL' => [
                    'label'  => 'Brazilian Real',
                    'locale' => 'pt_BR',
                    'symbol' => 'R$',
                ],
                'BSD' => [
                    'label'  => 'Bahamian Dollar',
                    'locale' => 'en_BS',
                    'symbol' => 'B$',
                ],
                'BWP' => [
                    'label'  => 'Botswana Pula',
                    'locale' => 'en_BW',
                    'symbol' => 'P',
                ],
                'BZD' => [
                    'label'  => 'Belize Dollar',
                    'locale' => 'en_BZ',
                    'symbol' => 'BZ$',
                ],
                'CAD' => [
                    'label'  => 'Canadian Dollar',
                    'locale' => 'en_CA',
                    'symbol' => '$',
                ],
                'CDF' => [
                    'label'  => 'Congolese Franc',
                    'locale' => 'fr_CD',
                    'symbol' => 'FC',
                ],
                'CHF' => [
                    'label'  => 'Swiss Franc',
                    'locale' => 'fr_CH',
                    'symbol' => 'Fr',
                ],
                'CLP' => [
                    'label'  => 'Chilean Peso',
                    'locale' => 'es_CL',
                    'symbol' => '$',
                ],
                'CNY' => [
                    'label'  => 'Chinese Renminbi Yuan',
                    'locale' => 'zh_Hans_CN',
                    'symbol' => '&#165;',
                ],
                'COP' => [
                    'label'  => 'Colombian Peso',
                    'locale' => 'es_CO',
                    'symbol' => '$',
                ],
                'CRC' => [
                    'label'  => 'Costa Rican Colón',
                    'locale' => 'es_CR',
                    'symbol' => '&#8353;',
                ],
                'CVE' => [
                    'label'  => 'Cape Verdean Escudo',
                    'locale' => 'pt_CV',
                    'symbol' => '$',
                ],
                'CZK' => [
                    'label'  => 'Czech Koruna',
                    'locale' => 'cs_CZ',
                    'symbol' => 'Kč',
                ],
                'DJF' => [
                    'label'  => 'Djiboutian Franc',
                    'locale' => 'fr_DJ',
                    'symbol' => 'Fdj',
                ],
                'DKK' => [
                    'label'  => 'Danish Krone',
                    'locale' => 'da_DK',
                    'symbol' => 'kr',
                ],
                'DOP' => [
                    'label'  => 'Dominican Peso',
                    'locale' => 'es_DO',
                    'symbol' => '$',
                ],
                'DZD' => [
                    'label'  => 'Algerian Dinar',
                    'locale' => 'fr_DZ',
                    'symbol' => '&#1583;.&#1580;',
                ],
                'EGP' => [
                    'label'  => 'Egyptian Pound',
                    'locale' => 'ar_EG',
                    'symbol' => 'E&pound;',
                ],
                'ETB' => [
                    'label'  => 'Ethiopian Birr',
                    'locale' => 'so_ET',
                    'symbol' => 'Br',
                ],
                'EUR' => [
                    'label'  => 'Euro',
                    'locale' => '',
                    'symbol' => '&euro;',
                ],
                'FJD' => [
                    'label'  => 'Fijian Dollar',
                    'locale' => 'en_FJ',
                    'symbol' => '$',
                ],
                'FKP' => [
                    'label'  => 'Falkland Islands Pound',
                    'locale' => 'en_FK',
                    'symbol' => '&pound;',
                ],
                'GBP' => [
                    'label'  => 'British Pound',
                    'locale' => 'en_UK',
                    'symbol' => '&pound;',
                ],
                'GEL' => [
                    'label'  => 'Georgian Lari',
                    'locale' => 'ka_GE',
                    'symbol' => '&#4314;',
                ],
                'GIP' => [
                    'label'  => 'Gibraltar Pound',
                    'locale' => 'en_GI',
                    'symbol' => '&pound;',
                ],
                'GMD' => [
                    'label'  => 'Gambian Dalasi',
                    'locale' => 'en_GM',
                    'symbol' => 'D',
                ],
                'GNF' => [
                    'label'  => 'Guinean Franc',
                    'locale' => 'fr_GN',
                    'symbol' => 'FG',
                ],
                'GTQ' => [
                    'label'  => 'Guatemalan Quetzal',
                    'locale' => 'es_GT',
                    'symbol' => 'Q',
                ],
                'GYD' => [
                    'label'  => 'Guyanese Dollar',
                    'locale' => 'en_GY',
                    'symbol' => '$',
                ],
                'HKD' => [
                    'label'  => 'Hong Kong Dollar',
                    'locale' => 'en_HK',
                    'symbol' => 'HK$',
                ],
                'HNL' => [
                    'label'  => 'Honduran Lempira',
                    'locale' => 'es_HN',
                    'symbol' => 'L',
                ],
                'HRK' => [
                    'label'  => 'Croatian Kuna',
                    'locale' => 'hr_HR',
                    'symbol' => 'kn',
                ],
                'HTG' => [
                    'label'  => 'Haitian Gourde',
                    'locale' => 'fr_HT',
                    'symbol' => 'G',
                ],
                'HUF' => [
                    'label'  => 'Hungarian Forint',
                    'locale' => 'hu_HU',
                    'symbol' => 'Ft',
                ],
                'IDR' => [
                    'label'  => 'Indonesian Rupiah',
                    'locale' => 'id_ID',
                    'symbol' => 'Rp',
                ],
                'ILS' => [
                    'label'  => 'Israeli New Sheqel',
                    'locale' => 'he_IL',
                    'symbol' => '&#8362;',
                ],
                'INR' => [
                    'label'  => 'Indian Rupee',
                    'locale' => 'en_IN',
                    'symbol' => '&#8377;',
                ],
                'ISK' => [
                    'label'  => 'Icelandic Króna',
                    'locale' => 'is_IS',
                    'symbol' => 'kr',
                ],
                'JMD' => [
                    'label'  => 'Jamaican Dollar',
                    'locale' => 'en_JM',
                    'symbol' => '$',
                ],
                'JPY' => [
                    'label'  => 'Japanese Yen',
                    'locale' => 'ja_JP',
                    'symbol' => '&yen;',
                ],
                'KES' => [
                    'label'  => 'Kenyan Shilling',
                    'locale' => 'en_KE',
                    'symbol' => 'KSh',
                ],
                'KGS' => [
                    'label'  => 'Kyrgyzstani Som',
                    'locale' => 'ru_KG',
                    'symbol' => '&#1083;&#1074;',
                ],
                'KHR' => [
                    'label'  => 'Cambodian Riel',
                    'locale' => 'km_KH',
                    'symbol' => '&#6107;',
                ],
                'KMF' => [
                    'label'  => 'Comorian Franc',
                    'locale' => 'fr_KM',
                    'symbol' => 'CF',
                ],
                'KRW' => [
                    'label'  => 'South Korean Won',
                    'locale' => 'ko_KR',
                    'symbol' => '&#8361;',
                ],
                'KYD' => [
                    'label'  => 'Cayman Islands Dollar',
                    'locale' => 'en_KY',
                    'symbol' => '$',
                ],
                'KZT' => [
                    'label'  => 'Kazakhstani Tenge',
                    'locale' => 'ru_KZ',
                    'symbol' => '&#8376;',
                ],
                'LAK' => [
                    'label'  => 'Lao Kipa',
                    'locale' => 'lo_LA',
                    'symbol' => '&#8365;',
                ],
                'LBP' => [
                    'label'  => 'Lebanese Pound',
                    'locale' => 'ar_LB',
                    'symbol' => '&#1604;.&#1604;',
                ],
                'LKR' => [
                    'label'  => 'Sri Lankan Rupee',
                    'locale' => 'si_LK',
                    'symbol' => '&#588;s',
                ],
                'LRD' => [
                    'label'  => 'Liberian Dollar',
                    'locale' => 'en_LR',
                    'symbol' => '$',
                ],
                'LSL' => [
                    'label'  => 'Lesotho Loti',
                    'locale' => '',
                    'symbol' => 'L',
                ],
                'MAD' => [
                    'label'  => 'Moroccan Dirham',
                    'locale' => 'ar_MA',
                    'symbol' => '&#1583;.&#1605;.',
                ],
                'MDL' => [
                    'label'  => 'Moldovan Leu',
                    'locale' => 'ro_MD',
                    'symbol' => 'L',
                ],
                'MGA' => [
                    'label'  => 'Malagasy Ariary',
                    'locale' => 'en_MG',
                    'symbol' => 'Ar',
                ],
                'MKD' => [
                    'label'  => 'Macedonian Denar',
                    'locale' => 'mk_MK',
                    'symbol' => '&#1076;&#1077;&#1085;',
                ],
                'MNT' => [
                    'label'  => 'Mongolian Tögrög',
                    'locale' => 'mn_Cyrl_MN',
                    'symbol' => '&#8366;',
                ],
                'MOP' => [
                    'label'  => 'Macanese Pataca',
                    'locale' => 'pt_MO',
                    'symbol' => 'MOP$',
                ],
                'MRO' => [
                    'label'  => 'Mauritanian Ouguiya',
                    'locale' => 'ar_MR',
                    'symbol' => 'UM',
                ],
                'MUR' => [
                    'label'  => 'Mauritian Rupee',
                    'locale' => 'en_MU',
                    'symbol' => '&#588;s',
                ],
                'MVR' => [
                    'label'  => 'Maldivian Rufiyaa',
                    'locale' => '',
                    'symbol' => 'Rf',
                ],
                'MWK' => [
                    'label'  => 'Malawian Kwacha',
                    'locale' => 'en_MW',
                    'symbol' => 'MK',
                ],
                'MXN' => [
                    'label'  => 'Mexican Peso',
                    'locale' => 'es_MX',
                    'symbol' => '$',
                ],
                'MYR' => [
                    'label'  => 'Malaysian Ringgit',
                    'locale' => 'ta_MY',
                    'symbol' => 'RM',
                ],
                'MZN' => [
                    'label'  => 'Mozambican Metical',
                    'locale' => 'mgh_MZ',
                    'symbol' => 'MT',
                ],
                'NAD' => [
                    'label'  => 'Namibian Dollar',
                    'locale' => 'naq_NA',
                    'symbol' => '$',
                ],
                'NGN' => [
                    'label'  => 'Nigerian Naira',
                    'locale' => 'en_NG',
                    'symbol' => '&#8358;',
                ],
                'NIO' => [
                    'label'  => 'Nicaraguan Córdoba',
                    'locale' => 'es_NI',
                    'symbol' => 'C$',
                ],
                'NOK' => [
                    'label'  => 'Norwegian Krone',
                    'locale' => 'se_NO',
                    'symbol' => 'kr',
                ],
                'NPR' => [
                    'label'  => 'Nepalese Rupee',
                    'locale' => 'ne_NP',
                    'symbol' => 'N&#588;s',
                ],
                'NZD' => [
                    'label'  => 'New Zealand Dollar',
                    'locale' => 'en_NZ',
                    'symbol' => '$',
                ],
                'PAB' => [
                    'label'  => 'Panamanian Balboa',
                    'locale' => 'es_PA',
                    'symbol' => 'B/.',
                ],
                'PEN' => [
                    'label'  => 'Peruvian Nuevo Sol',
                    'locale' => 'es_PE',
                    'symbol' => 'S/.',
                ],
                'PGK' => [
                    'label'  => 'Papua New Guinean Kina',
                    'locale' => 'en_PG',
                    'symbol' => 'K',
                ],
                'PHP' => [
                    'label'  => 'Philippine Peso',
                    'locale' => 'en_PH',
                    'symbol' => '&#8369;',
                ],
                'PKR' => [
                    'label'  => 'Pakistani Rupee',
                    'locale' => 'en_PK',
                    'symbol' => '&#588;s',
                ],
                'PLN' => [
                    'label'  => 'Polish Złoty',
                    'locale' => 'pl_PL',
                    'symbol' => 'z&#322;',
                ],
                'PYG' => [
                    'label'  => 'Paraguayan Guaraní',
                    'locale' => 'es_PY',
                    'symbol' => '&#8370;',
                ],
                'QAR' => [
                    'label'  => 'Qatari Riyal',
                    'locale' => 'ar_QA',
                    'symbol' => '&#1585;.&#1602;',
                ],
                'RON' => [
                    'label'  => 'Romanian Leu',
                    'locale' => 'ro_RO',
                    'symbol' => 'L',
                ],
                'RSD' => [
                    'label'  => 'Serbian Dinar',
                    'locale' => 'sr_Latn_RS',
                    'symbol' => '&#1044;&#1080;&#1085;.',
                ],
                'RUB' => [
                    'label'  => 'Russian Ruble',
                    'locale' => 'ru_RU',
                    'symbol' => '&#8381;',
                ],
                'RWF' => [
                    'label'  => 'Rwandan Franc',
                    'locale' => 'en_RW',
                    'symbol' => 'RF',
                ],
                'SAR' => [
                    'label'  => 'Saudi Riyal',
                    'locale' => 'ar_SA',
                    'symbol' => '&#1585;.&#1587;',
                ],
                'SBD' => [
                    'label'  => 'Solomon Islands Dollar',
                    'locale' => 'en_SB',
                    'symbol' => '$',
                ],
                'SCR' => [
                    'label'  => 'Seychellois Rupee',
                    'locale' => 'fr_SC',
                    'symbol' => '&#588;s',
                ],
                'SEK' => [
                    'label'  => 'Swedish Krona',
                    'locale' => 'sv_SE',
                    'symbol' => 'kr',
                ],
                'SGD' => [
                    'label'  => 'Singapore Dollar',
                    'locale' => 'en_SG',
                    'symbol' => '$',
                ],
                'SHP' => [
                    'label'  => 'Saint Helenian Pound',
                    'locale' => 'en_SH',
                    'symbol' => '&pound;',
                ],
                'SLL' => [
                    'label'  => 'Sierra Leonean Leone',
                    'locale' => 'en_SL',
                    'symbol' => 'Le',
                ],
                'SOS' => [
                    'label'  => 'Somali Shilling',
                    'locale' => 'so_SO',
                    'symbol' => 'So. Sh.',
                ],
                'SRD' => [
                    'label'  => 'Surinamese Dollar',
                    'locale' => 'nl_SR',
                    'symbol' => '$',
                ],
                'STD' => [
                    'label'  => 'São Tomé and Príncipe Dobra',
                    'locale' => 'pt_ST',
                    'symbol' => 'Db',
                ],
                'SZL' => [
                    'label'  => 'Swazi Lilangeni',
                    'locale' => 'en_SZ',
                    'symbol' => 'L',
                ],
                'THB' => [
                    'label'  => 'Thai Baht',
                    'locale' => 'th_TH',
                    'symbol' => '&#3647;',
                ],
                'TJS' => [
                    'label'  => 'Tajikistani Somoni',
                    'locale' => 'tg_Cyrl_TJ',
                    'symbol' => 'SM',
                ],
                'TOP' => [
                    'label'  => 'Tongan Paʻanga',
                    'locale' => 'en_TO',
                    'symbol' => '$',
                ],
                'TRY' => [
                    'label'  => 'Turkish Lira',
                    'locale' => 'tr_TR',
                    'symbol' => '&#8378;',
                ],
                'TTD' => [
                    'label'  => 'Trinidad and Tobago Dollar',
                    'locale' => 'en_TT',
                    'symbol' => '$',
                ],
                'TWD' => [
                    'label'  => 'New Taiwan Dollar',
                    'locale' => 'zh_Hant_TW',
                    'symbol' => 'NT$',
                ],
                'TZS' => [
                    'label'  => 'Tanzanian Shilling',
                    'locale' => 'en_TZ',
                    'symbol' => 'TSh',
                ],
                'UAH' => [
                    'label'  => 'Ukrainian Hryvnia',
                    'locale' => 'uk_UA',
                    'symbol' => '&#8372;',
                ],
                'UGX' => [
                    'label'  => 'Ugandan Shilling',
                    'locale' => 'en_UG',
                    'symbol' => 'USh',
                ],
                'USD' => [
                    'label'  => 'United States Dollar',
                    'locale' => 'en_US',
                    'symbol' => '$',
                ],
                'UYU' => [
                    'label'  => 'Uruguayan Peso',
                    'locale' => 'es_UY',
                    'symbol' => '$U',
                ],
                'UZS' => [
                    'label'  => 'Uzbekistani Som',
                    'locale' => 'uz_Latn_UZ',
                    'symbol' => '&#1083;&#1074;',
                ],
                'VEF' => [
                    'label'  => 'Venezuelan Bolívar',
                    'locale' => 'es_VE',
                    'symbol' => 'Bs',
                ],
                'VND' => [
                    'label'  => 'Vietnamese Đồng',
                    'locale' => 'vi_VN',
                    'symbol' => '&#8363;',
                ],
                'VUV' => [
                    'label'  => 'Vanuatu Vatu',
                    'locale' => 'en_VU',
                    'symbol' => 'VT',
                ],
                'WST' => [
                    'label'  => 'Samoan Tala',
                    'locale' => 'en_WS',
                    'symbol' => 'WS$',
                ],
                'XAF' => [
                    'label'  => 'Central African Cfa Franc',
                    'locale' => 'fr_CF',
                    'symbol' => 'CFA',
                ],
                'XCD' => [
                    'label'  => 'East Caribbean Dollar',
                    'locale' => 'en_AI',
                    'symbol' => 'EC$',
                ],
                'XOF' => [
                    'label'  => 'West African Cfa Franc',
                    'locale' => 'fr_BF',
                    'symbol' => 'CFA',
                ],
                'XPF' => [
                    'label'  => 'Cfp Franc',
                    'locale' => 'fr_PF',
                    'symbol' => 'F',
                ],
                'YER' => [
                    'label'  => 'Yemeni Rial',
                    'locale' => 'ar_YE',
                    'symbol' => '&#65020;',
                ],
                'ZAR' => [
                    'label'  => 'South African Rand',
                    'locale' => 'en_LS',
                    'symbol' => 'R',
                ],
                'ZMW' => [
                    'label'  => 'Zambian Kwacha',
                    'locale' => 'en_ZM',
                    'symbol' => 'ZMW',
                ],
            ];
            if (NULL === $code) {
                return $currencies;
            }

            return $currencies[ $code ];
        }

        /**
         * @param int $from
         * @param int $to
         *
         * @return string
         */
        public static function human_time_diff(int $from, int $to = 0): string
        {
            if (empty($to)) {
                $to = time();
            }

            $diff = (int)abs($to - $from);

            if ($diff < MINUTE_IN_SECONDS) {
                $secs = $diff;
                if ($secs <= 1) {
                    $secs = 1;
                }
                $since = sprintf(
                /* translators: %d: number of seconds */
                    _n('%d second', '%d seconds', $secs),
                    $secs
                );
            } elseif ($diff < HOUR_IN_SECONDS && $diff >= MINUTE_IN_SECONDS) {
                $mins  = round($diff / MINUTE_IN_SECONDS);
                $since = sprintf(
                /* translators: %d: number of minutes */
                    _n('%d m', '%d m', $mins),
                    $mins
                );
            } elseif ($diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS) {
                $hours = round($diff / HOUR_IN_SECONDS);
                $diff  -= $hours * HOUR_IN_SECONDS;
                $mins  = round($diff / MINUTE_IN_SECONDS);
                $since = sprintf(
                /* translators: %d: number of hours */
                    _n('%d h', '%d h', $hours),
                    $hours
                );
                if ($mins > 0) {
                    $since .= ' ' . sprintf(
                        /* translators: %d: number of minutes */
                            _n('%d m', '%d m', $mins),
                            $mins
                        );
                }
            } elseif ($diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS) {
                $days = round($diff / DAY_IN_SECONDS);
                if ($days <= 1) {
                    $days = 1;
                }
                $since = sprintf(
                /* translators: %d: number of days */
                    _n('%d day', '%d days', $days),
                    $days
                );
            } elseif ($diff < MONTH_IN_SECONDS && $diff >= WEEK_IN_SECONDS) {
                $weeks = round($diff / WEEK_IN_SECONDS);
                if ($weeks <= 1) {
                    $weeks = 1;
                }
                $since = sprintf(
                /* translators: %d: number of weeks */
                    _n('%d week', '%d weeks', $weeks),
                    $weeks
                );
            } elseif ($diff < YEAR_IN_SECONDS && $diff >= MONTH_IN_SECONDS) {
                $months = round($diff / MONTH_IN_SECONDS);
                if ($months <= 1) {
                    $months = 1;
                }
                $since = sprintf(
                /* translators: %d: number of months */
                    _n('%d month', '%d months', $months),
                    $months
                );
            } elseif ($diff >= YEAR_IN_SECONDS) {
                $years = round($diff / YEAR_IN_SECONDS);
                if ($years <= 1) {
                    $years = 1;
                }
                $since = sprintf(
                /* translators: %d: number of years */
                    _n('%d year', '%d years', $years),
                    $years
                );
            }

            return $since;
        }

        public static function get_request_headers()
        {
            if (!function_exists('getallheaders')) {
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (strpos($name, 'HTTP_') === 0) {
                        $headers[ str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))) ] = $value;
                    }
                }

                return $headers;
            }

            return getallheaders();
        }

    }
}
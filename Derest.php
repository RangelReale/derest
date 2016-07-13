<?php
namespace RangelReale\derest;

/**
 * Derest is a REST client for PHP.
 *
 * See http://github.com/RangelReale/derest for details.
 *
 * This code is licensed for use, modification, and distribution
 * under the terms of the MIT License (see http://en.wikipedia.org/wiki/MIT_License)
 */
class Derest
{
    /**
     * @var array Default CURL options
     */
    public $curl_opts = [
        CURLOPT_SSL_VERIFYPEER => false, // stop cURL from verifying the peer's certificate
        CURLOPT_FOLLOWLOCATION => false, // follow redirects, Location: headers
        CURLOPT_MAXREDIRS => 10, // but dont redirect more than 10 times
        CURLOPT_HTTPHEADER => array(),
    ];

    public $instances = [
        'default' => [
            'base_url' => null,
            'data_format' => 'json',
            'response_format' => 'json',
            'curl_opts' => [],
        ],
    ];

    public $throw_exceptions = true;

    public $errorProcessor;
    public $requestLogger;

    public function __construct($base_url)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('CURL module not available! Pest requires CURL. See http://php.net/manual/en/book.curl.php');
        }

        /*
         * Only enable CURLOPT_FOLLOWLOCATION if safe_mode and open_base_dir are
         * not in use
         */
        if (ini_get('open_basedir') == '' && strtolower(ini_get('safe_mode')) == 'off') {
            $this->curl_opts['CURLOPT_FOLLOWLOCATION'] = true;
        }

        $this->instances['default']['base_url'] = $base_url;
    }

    public function createInstance($instance, $base_url, $options = [])
    {
        $defaultOptions = [
            'base_url' => null,
            'data_format' => 'json',
            'response_format' => 'json',
            'curl_opts' => [],
        ];

        $this->instances[$instance] = array_merge($defaultOptions, [ 'base_url' => $base_url ], $options);
        return $this;
    }

    public function req($instance = 'default')
    {
        if (!isset($this->instances[$instance])) {
            throw new Exception('Instance does not exists');
        }

        return new Request($this, $instance);
    }

    /**
     * Setup authentication
     *
     * @param string $user
     * @param string $pass
     * @param string $auth  Can be 'basic' or 'digest'
     */
    public function setupAuth($user, $pass, $auth = 'basic')
    {
        Util::setupAuth($this->curl_opts, $user, $pass, $auth);
    }

    /**
     * Set cookies for this session
     * @param array $cookies
     *
     * @see http://curl.haxx.se/docs/manpage.html
     * @see http://www.nczonline.net/blog/2009/05/05/http-cookies-explained/
     */
    public function setupCookies($cookies)
    {
        Util::setupCookies($this->curl_opts, $cookies);
    }

    /**
     * Setup proxy
     * @param string $host
     * @param int $port
     * @param string $user Optional.
     * @param string $pass Optional.
     */
    public function setupProxy($host, $port, $user = NULL, $pass = NULL)
    {
        Util::setupProxy($this->curl_opts, $host, $port, $user, $pass);
    }
}

class Exception extends \Exception
{}
class ClientError_Exception extends Exception
{}
class ServerError_Exception extends ClientError_Exception
{}
class UnknownResponse_Exception extends ClientError_Exception
{}

class Curl_Exception extends Exception
{}

class Data_Encode_Exception extends ServerError_Exception
{}
class Json_Decode_Exception extends ClientError_Exception
{}
class Json_Encode_Exception extends ClientError_Exception
{}
class Xml_Decode_Exception extends ClientError_Exception
{}

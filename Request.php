<?php

namespace RangelReale\derest;

class Request
{
    public $derest;
    public $instance;
    public $curl_opts = [];
    public $instanceData;
    public $receiver;
    public $logger;
    public $throw_exceptions;

    public function __construct($derest, $instance)
    {
        $this->derest = $derest;
        $this->instance = $instance;
        $this->instanceData = $derest->instances[$instance];
        $this->logger = $derest->requestLogger;
        $this->throw_exceptions = $derest->throw_exceptions;
    }

    public function receiver($receiver)
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function receiverMemory()
    {
        $this->receiver = new MemoryResponseReceiver();
        return $this;
    }

    public function logger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function throwExceptions($throw)
    {
        $this->throw_exceptions = $throw;
        return $this;
    }

    public function curlOpts($curlOpts)
    {
        $this->curl_opts += $curlOpts;
        return $this;
    }

    public function head($url, $headers = null)
    {
        return $this->request('HEAD', $url, null, $headers);
    }

    public function get($url, $headers = null)
    {
        return $this->request('GET', $url, null, $headers);
    }

    public function post($url, $data = null, $headers = null)
    {
        return $this->request('POST', $url, $data, $headers);
    }

    public function put($url, $data = null, $headers = null)
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    public function patch($url, $data = null, $headers = null)
    {
        return $this->request('PATCH', $url, $data, $headers);
    }

    public function delete($url, $headers = null)
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    public function request($method, $url, $data = null, $headers = null)
    {
        $resp = new Response($this, $this->receiver);

        $curl = curl_init();

        $curl_opts = $this->derest->curl_opts;
        if (isset($this->instanceData['curl_opts']) && is_array($this->instanceData['curl_opts'])) {
            $curl_opts += $this->instanceData['curl_opts'];
        }
        $curl_opts[CURLOPT_CUSTOMREQUEST] = $method;
        $curl_opts[CURLOPT_URL] = $this->parseUrl($url);
        $curl_opts[CURLOPT_HEADERFUNCTION] = array($resp, 'receiveHeader');
        $curl_opts[CURLOPT_WRITEFUNCTION] = array($resp, 'receiveData');

        if ($method == 'HEAD') {
            $curl_opts[CURLOPT_NOBODY] = true;
        }

        // headers
        if (!is_array($headers))
            $headers = [];

        if (isset($data)) {
            if (is_object($data) && $data instanceof Data) {
                $curl_opts[CURLOPT_POSTFIELDS] = $data->getData($this);
            } elseif (is_array($data) && isset($this->instanceData['data_format']) && $this->instanceData['data_format'] != '') {
                if ($this->instanceData['data_format'] == 'json') {
                    $headers['Accept'] = 'application/json';
                    $headers['Content-Type'] = 'application/json';

                    $enc = json_encode($data);
                    if ($enc === false) {
                        throw new Json_Encode_Exception(
                            'Encoding error: ' . $resp->getLastJsonErrorMessage(),
                            $resp->getLastJsonErrorCode()
                        );
                    }
                    $curl_opts[CURLOPT_POSTFIELDS] = $enc;
                } else {
                    throw new Data_Encode_Exception('Unknown encoding data format: '.$this->instanceData['data_format']);
                }
            } else {
                $curl_opts[CURLOPT_POSTFIELDS] = $data;
            }
        }

        if (isset($curl_opts[CURLOPT_POSTFIELDS]) && is_string($curl_opts[CURLOPT_POSTFIELDS])) {
            $headers['Content-Length'] = strlen($curl_opts[CURLOPT_POSTFIELDS]);
        }


        $flatHeaders = [];
        foreach ($headers as $hname => $hvalue) {
            $flatHeaders[] = $hname.': '.$hvalue;
        }
        $curl_opts[CURLOPT_HTTPHEADER] = $flatHeaders;

        foreach ($curl_opts as $cname => $cval) {
            curl_setopt($curl, $cname, $cval);
        }

        if (isset($this->logger)) {
            $this->logger->logRequest($this, [
                'headers' => $headers,
                'url' => $curl_opts[CURLOPT_URL],
                'method' => $method,
                'curl_opts' => $curl_opts,
            ]);
        }

        $curl_result = curl_exec($curl);
        if ($curl_result === FALSE) {
            throw new Curl_Exception('Curl error: '.curl_error($curl));
        }

        $resp->meta = curl_getinfo($curl);
        $resp->receiveDataFinished($curl);
        curl_close($curl);

        if (isset($this->logger)) {
            $this->logger->logResponse($this, $resp);
        }

        if ($this->throw_exceptions && isset($resp->meta)) {
            $error_info = $this->processError($resp);
            if ($resp->meta['http_code'] >= 400 && $resp->meta['http_code'] <= 499) {
                throw new ClientError_Exception($error_info->message, $error_info->code, null, $error_info->data);
            } elseif ($resp->meta['http_code'] >= 500 && $resp->meta['http_code'] <= 599) {
                throw new ServerError_Exception($error_info->message, $error_info->code, null, $error_info->data);
            } elseif (!isset($resp->meta['http_code']) || $resp->meta['http_code'] >= 600) {
                throw new UnknownResponse_Exception($error_info->message, $error_info->code, null, $error_info->data);
            }
        }

        return $resp;
    }

    public function headJ($url, $headers = null)
    {
        return $this->request('HEAD', $url, null, $headers)->json();
    }

    public function getJ($url, $headers = null)
    {
        return $this->request('GET', $url, null, $headers)->json();
    }

    public function postJ($url, $data = null, $headers = null)
    {
        return $this->request('POST', $url, $data, $headers)->json();
    }

    public function putJ($url, $data = null, $headers = null)
    {
        return $this->request('PUT', $url, $data, $headers)->json();
    }

    public function patchJ($url, $data = null, $headers = null)
    {
        return $this->request('PATCH', $url, $data, $headers)->json();
    }

    public function deleteJ($url, $headers = null)
    {
        return $this->request('DELETE', $url, null, $headers)->json();
    }

    public function headX($url, $headers = null)
    {
        return $this->request('HEAD', $url, null, $headers)->xml();
    }

    public function getX($url, $headers = null)
    {
        return $this->request('GET', $url, null, $headers)->xml();
    }

    public function postX($url, $data = null, $headers = null)
    {
        return $this->request('POST', $url, $data, $headers)->xml();
    }

    public function putX($url, $data = null, $headers = null)
    {
        return $this->request('PUT', $url, $data, $headers)->xml();
    }

    public function patchX($url, $data = null, $headers = null)
    {
        return $this->request('PATCH', $url, $data, $headers)->xml();
    }

    public function deleteX($url, $headers = null)
    {
        return $this->request('DELETE', $url, null, $headers)->xml();
    }

    public function processError($response)
    {
        if (isset($this->derest->errorProcessor)) {
            return $this->derest->errorProcessor->retrieveErrorInfo($response);
        }
        if (isset($response->body)) {
            return new ErrorInfo($response->body);
        }
        return new ErrorInfo('Unknown error');
    }

    public function parseUrl($url, $include_base_url = true)
    {
        $result = '';
        if ($include_base_url) {
            $result = rtrim($this->instanceData['base_url'], '/');
        }

        if (!is_array($url)) {
            if (!empty($url)) {
                $result .= '/'.ltrim($url, '/');
            }
            return $result;
        }

        $build_url = [];
        $build_query = [];

        foreach ($url as $urlindex => $urlitem) {
            if (is_int($urlindex)) {
                foreach (explode('/', trim($urlitem, '/')) as $urlpath) {
                    $build_url[] = $urlpath;
                }
            } else {
                $build_query[trim($urlindex)] = $urlitem;
            }
        }

        foreach ($build_url as $item) {
            $result .= '/' . rawurldecode($item);
        }
        if (count($build_query) > 0) {
            $result .= '?' . http_build_query($build_query);
        }
        return $result;
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

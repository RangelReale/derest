<?php

namespace RangelReale\derest;

class Response
{
    public $request;
    public $headers = [];
    public $receiver;
    public $body;
    public $data;
    public $meta;
    public $content_type;
    public $content_charset;

    public function __construct($request, $receiver = null)
    {
        $this->request = $request;
        $this->receiver = $receiver;
    }

    public function receiveHeader($curl, $header)
    {
        if(($pos = strpos($header, ':')) !== FALSE){
            $hname = substr($header, 0, $pos);
            $hvalue = trim(substr(strstr($header, ':'), 1));
            $hhname = strtolower($hname);
            $this->headers[$hhname] = $hvalue;

            if ($hhname == 'content-type') {
                list($contentType, $charset) = array_pad(explode(";", $hvalue), 2, '');
                $this->content_type = $contentType;
                $this->content_charset = $charset;
            }

            if (isset($this->receiver)) {
                $this->receiver->receiveHeader($this, $hname, $hvalue);
            }
        }
        return strlen($header);
    }

    public function receiveData($curl, $data)
    {
        $processed = false;
        if (isset($this->content_type)) {
            if (isset($this->request->instanceData['response_format'])) {
                if (($this->request->instanceData['response_format'] == 'json' && $this->content_type == 'application/json') ||
                    ($this->request->instanceData['response_format'] == 'xml' && $this->content_type == 'text/xml')) {

                    if (!isset($this->body)) $this->body = '';
                    $this->body .= $data;

                    $processed = true;
                }
            }
        }

        if (isset($this->receiver)) {
            if (!$processed || $this->receiver->receiveIfProcessed($this)) {
                $this->receiver->receiveData($this, $data);
            }
        } elseif (!$processed) {
            throw new ServerError_Exception('No processor for receiving the data was set');
        }

        return strlen($data);
    }

    public function receiveDataFinished($curl)
    {
        $processed = false;
        if (isset($this->content_type) && isset($this->body)) {
            if ($this->content_type == 'application/json') {
                $ret = json_decode($this->body, true);
                if ($ret === null && $this->hasJsonDecodeFailed()) {
                    throw new Json_Decode_Exception(
                        'Decoding error: ' . $this->getLastJsonErrorMessage(),
                        $this->getLastJsonErrorCode()
                    );
                }
                $this->data = $ret;
                $processed = true;
            }
            elseif ($this->content_type == 'text/xml') {
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($this->body);
                if (!$xml) {
                    $err = "Couldn't parse XML response because:\n";
                    $xml_errors = libxml_get_errors();
                    libxml_clear_errors();
                    if (!empty($xml_errors)) {
                        foreach ($xml_errors as $xml_err)
                            $err .= "\n    - " . $xml_err->message;
                        $err .= "\nThe response was:\n";
                        $err .= $body;
                        throw new Xml_Decode_Exception($err);
                    }
                }
                $this->data = $xml;
                $processed = true;
            }
        }

        if (isset($this->receiver)) {
            if (!$processed || $this->receiver->receiveIfProcessed($this)) {
                $this->receiver->receiveDataFinished($this);
            }
        }
    }

    public function is_json()
    {
        return $this->content_type == 'application/json' && isset($this->data);
    }

    public function json()
    {
        if ($this->is_json()) {
            return $this->data;
        }

        throw new ClientError_Exception('Response is not in JSON format');
    }

    public function is_xml()
    {
        return $this->content_type == 'text/xml' && isset($this->data);
    }

    public function xml()
    {
        if ($this->is_xml()) {
            return $this->data;
        }

        throw new ClientError_Exception('Response is not in XML format');
    }

    /**
     * Get last JSON error message
     *
     * @return string
     */
    public function getLastJsonErrorMessage()
    {
        // For PHP < 5.3, just return "Unknown"
        if (!function_exists('json_last_error')) {
            return "Unknown";
        }

        // Use the newer JSON error message function if it exists
        if (function_exists('json_last_error_msg')) {
            return(json_last_error_msg());
        }

        $lastError = json_last_error();

        // PHP 5.3+ only
        if (defined('JSON_ERROR_UTF8') && $lastError === JSON_ERROR_UTF8) {
            return 'Malformed UTF-8 characters, possibly incorrectly encoded';
        }

        switch ($lastError) {
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
                break;
            default:
                return 'Unknown';
                break;
        }
    }


    /**
     * Get last JSON error code
     * @return int|null
     */
    public function getLastJsonErrorCode()
    {
        // For PHP < 5.3, just return the PEST code for unknown errors
        if (!function_exists('json_last_error')) {
            return self::JSON_ERROR_UNKNOWN;
        }

        return json_last_error();
    }

    /**
     * Check if decoding failed
     * @return bool
     */
    private function hasJsonDecodeFailed()
    {
        // you cannot safely determine decode errors in PHP < 5.3
        if (!function_exists('json_last_error')) {
            return false;
        }

        return json_last_error() !== JSON_ERROR_NONE;
    }

}

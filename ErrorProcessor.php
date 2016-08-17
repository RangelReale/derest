<?php

namespace RangelReale\derest;

class ErrorInfo
{
    public $message;
    public $code = 0;
    public $data;
    
    public function __construct($message = '', $code = 0, $data = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->data = $data;
    }
}

interface ErrorProcessor
{
    /**
     * 
     * @param Response $response
     * @return ErrorInfo Error information
     */
    public function retrieveErrorInfo($response);
}

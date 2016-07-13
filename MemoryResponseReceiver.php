<?php

namespace RangelReale\derest;

class MemoryResponseReceiver implements ResponseReceiver
{
    public $headers = [];
    public $data;

    public function receiveIfProcessed($response)
    {
        return false;
    }

    public function receiveHeader($response, $header, $value)
    {
        $this->headers[$header] = $value;
    }

    public function receiveData($response, $data)
    {
        if (!isset($this->data)) $this->data = '';
        $this->data .= $data;
    }

    public function receiveDataFinished($response)
    {

    }
}

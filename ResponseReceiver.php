<?php

namespace RangelReale\derest;

interface ResponseReceiver
{
    public function receiveIfProcessed($response);
    public function receiveHeader($response, $header, $value);
    public function receiveData($response, $data);
    public function receiveDataFinished($response);
}

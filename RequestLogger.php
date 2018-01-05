<?php

namespace RangelReale\derest;

interface RequestLogger
{
    public function logRequest($request, $data);
    public function logResponse($request, $request_data, $response);
}

<?php

namespace RangelReale\derest;

class DataUrlEncoded implements Data
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData($request)
    {
        return http_build_query($this->data);
    }
}

<?php

namespace RangelReale\derest;

class DataRaw implements Data
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData($request)
    {
        return $this->data;
    }
}

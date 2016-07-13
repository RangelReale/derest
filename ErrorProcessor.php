<?php

namespace RangelReale\derest;

interface ErrorProcessor
{
    public function retrieveErrorMessage($response);
}

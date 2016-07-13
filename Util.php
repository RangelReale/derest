<?php

namespace RangelReale\derest;

class Util
{
    public static function setupAuth(&$curl_opts, $user, $pass, $auth = 'basic')
    {
        $curl_opts[CURLOPT_HTTPAUTH] = constant('CURLAUTH_' . strtoupper($auth));
        $curl_opts[CURLOPT_USERPWD] = $user . ":" . $pass;
    }

    public static function setupCookies(&$curl_opts, $cookies)
    {
        if (empty($cookies)) {
            return;
        }
        $cookie_list = array();
        foreach ($cookies as $cookie_name => $cookie_value)
        {
            $cookie = urlencode($cookie_name);
            if (isset($cookie_value))
            {
                $cookie .= '=';
                $cookie .= urlencode($cookie_value);
            }
            $cookie_list[] = $cookie;
        }
        $curl_opts[CURLOPT_COOKIE] = implode(';', $cookie_list);
    }

    public static function setupProxy(&$curl_opts, $host, $port, $user = NULL, $pass = NULL)
    {
        $curl_opts[CURLOPT_PROXYTYPE] = 'HTTP';
        $curl_opts[CURLOPT_PROXY] = $host;
        $curl_opts[CURLOPT_PROXYPORT] = $port;
        if ($user && $pass) {
            $curl_opts[CURLOPT_PROXYUSERPWD] = $user . ":" . $pass;
        }
    }

}

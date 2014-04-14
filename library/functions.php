<?php

/**
 * @param int $seconds
 * @param bool $assoc
 * @return array
 */
function secondsToArray ($seconds, $assoc = true) {
    $time = [
        'seconds' => sprintf('%02d', $seconds % 60),
    ];

    $minutes = floor($seconds / 60);
    if ($minutes > 0) {
        $time['minutes'] = sprintf('%02d', $minutes % 60);
    }

    $hours = floor($minutes / 60);
    if ($hours > 0) {
        $time['hours'] = sprintf('%02d', $hours % 24);
    }

    $days = floor($hours / 24);
    if ($days > 0) {
        $time['days'] = $days;
    }

    if (!$assoc) {
        $time = array_values($assoc);
    }

    return $time;
}

if (!function_exists('http_parse_cookie')) {
    function http_parse_cookie($string)
    {
        $cookies = [];
        $pairs = explode(';', $string);
        foreach ($pairs as $pair) {
            list ($key, $value) = explode('=', $pair);
            $key = trim($key);
            $cookies[$key] = $value;
        }
        return $cookies;
    }
}
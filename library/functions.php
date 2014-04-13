<?php

/**
 * @param int $seconds
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
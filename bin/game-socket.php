<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
    define('LOG_FILE', __DIR__ . '/../logs/game-socket.log');

echo 'Start' . PHP_EOL;

$log = new My_WebSocket_Log(LOG_FILE);

$server = new My_WebSocket_Server(9900);
$server->addListener($log);

$server->startLoop();

echo 'Finish' . PHP_EOL;

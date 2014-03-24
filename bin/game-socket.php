<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
    define('LOG_FILE', __DIR__ . '/../logs/game-socket.log');

echo 'Start' . PHP_EOL;

$server = new My_WebSocket_Server(9900);

// Hint: Status application should not be removed as it displays usefull server informations:
$server->addLogger(new My_WebSocket_Logger_File(LOG_FILE));
$server->addLogger(new My_WebSocket_Logger_Console());

$server->run();
echo 'Finish' . PHP_EOL;

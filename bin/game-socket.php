<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
    define('LOG_FILE', __DIR__ . '/../logs/game-socket.log');

echo 'Start' . PHP_EOL;

$server = new My_WebSocket_Server(9900);

$server->setLogger(new My_WebSocket_Logger_FileConsole(LOG_FILE));
$server->addListener(new My_WebSocket_Listener_Sudoku(), ['sudoku']);

$server->run();
echo 'Finish' . PHP_EOL;

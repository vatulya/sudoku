<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
define('LOG_FILE', __DIR__ . '/../logs/websocket-server.log');

echo 'Start' . PHP_EOL;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$myServer = new My_WebSocket_Server();
$myServer->addListener(new My_WebSocket_Listener_Sudoku(), 'sudoku');

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $myServer
        )
    ),
    8080
);

$server->run();


echo 'Finish' . PHP_EOL;
